#!/usr/local/php/8.1/bin/php
<?php
// Reconcile job costs against RunPod Billing API.
// Distributes actual billed cost proportionally across jobs by executionTime.
// Also saves all raw billing data (3 groupings) to billing_records table.
// Run daily at 02:00 UTC: 0 2 * * * path/to/batch/reconcile_costs.php
//
// Usage:
//   reconcile_costs.php              # reconcile yesterday
//   reconcile_costs.php 2026-04-06   # reconcile specific date

require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/db.php';

$db = getDb();

// Load all active API keys (each may represent a different RunPod account)
$apiKeyRows = $db->query('SELECT id, label FROM api_keys WHERE is_active = 1 ORDER BY id')->fetchAll();
if (empty($apiKeyRows)) {
    error_log('[CIEL reconcile] No active API key configured');
    exit(1);
}

$marginRate = (float)(getenv('MARGIN_RATE') ?: 3.5);
$reconcileStart = hrtime(true);
$reconcileError = null;
$endpointsUpdated = 0;
$billingApiCalls = 0;

// Determine trigger source: poll_jobs passes --trigger=poll
$triggerSource = 'cron';
foreach ($argv as $a) {
    if (str_starts_with($a, '--trigger=')) {
        $triggerSource = substr($a, 10);
    }
}

// Target date (default: yesterday)
$targetDate = $argv[1] ?? (new DateTime('yesterday', new DateTimeZone('UTC')))->format('Y-m-d');
$startTime  = $targetDate . 'T00:00:00Z';
$endTime    = (new DateTime($targetDate, new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d') . 'T00:00:00Z';

echo "[reconcile] Target: {$targetDate}\n";

// ------------------------------------------------------------------
// Helper: fetch one grouping from Billing API
// ------------------------------------------------------------------
function fetchBilling(string $apiKey, string $startTime, string $endTime, string $grouping): ?array
{
    $url = 'https://rest.runpod.io/v1/billing/endpoints?' . http_build_query([
        'bucketSize' => 'hour',
        'startTime'  => $startTime,
        'endTime'    => $endTime,
        'grouping'   => $grouping,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("[CIEL reconcile] Billing API ({$grouping}) returned HTTP {$httpCode}");
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

// ------------------------------------------------------------------
// Helper: save billing rows to billing_records (independent of reconcile)
// groupingKey: the JSON key name ('endpointId', 'gpuTypeId', 'podId')
// ------------------------------------------------------------------
function saveBillingRecords(PDO $db, array $rows, string $groupingKey): void
{
    if (empty($rows)) {
        return;
    }

    $stmt = $db->prepare(
        'INSERT INTO billing_records
            (bucket_time, bucket_size, grouping_type, grouping_value, amount, time_billed_ms, disk_billed_gb)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            amount         = VALUES(amount),
            time_billed_ms = VALUES(time_billed_ms),
            disk_billed_gb = VALUES(disk_billed_gb),
            fetched_at     = CURRENT_TIMESTAMP'
    );

    $saved = 0;
    foreach ($rows as $row) {
        $groupingValue = $row[$groupingKey] ?? null;
        if ($groupingValue === null) {
            continue;
        }
        try {
            $stmt->execute([
                $row['time'],
                'hour',
                $groupingKey,
                $groupingValue,
                $row['amount'],
                (int)$row['timeBilledMs'],
                (int)($row['diskSpaceBilledGB'] ?? 0),
            ]);
            $saved++;
        } catch (Exception $e) {
            error_log("[CIEL reconcile] billing_records insert error ({$groupingKey}): " . $e->getMessage());
        }
    }

    echo "[reconcile] billing_records saved: grouping={$groupingKey} rows={$saved}\n";
}

// ------------------------------------------------------------------
// Helper: append reconcile result to monthly log file (reconcile-YYYY-MM.log)
// ------------------------------------------------------------------
function writeReconcileLog(string $targetDate, string $triggerSource, int $adjusted, int $skipped, float $totalAdj, int $epUpdated, int $apiCalls, int $durationMs, ?string $error = null): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
    $entry = [
        'target_date'       => $targetDate,
        'trigger_source'    => $triggerSource,
        'jobs_adjusted'     => $adjusted,
        'jobs_skipped'      => $skipped,
        'total_adjustment'  => $totalAdj,
        'endpoints_updated' => $epUpdated,
        'billing_api_calls' => $apiCalls,
        'duration_ms'       => $durationMs,
        'error'             => $error,
        'created_at'        => $now->format('Y-m-d H:i:s'),
    ];
    $filename = sprintf('reconcile-%s.log', $now->format('Y-m'));
    file_put_contents($logDir . '/' . $filename, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

// ------------------------------------------------------------------
// 1. Fetch all 3 groupings from ALL API keys and persist to billing_records
// ------------------------------------------------------------------
$groupings = [
    'endpointId' => [],
    'gpuTypeId'  => [],
    'podId'      => [],
];

foreach ($apiKeyRows as $keyRow) {
    $apiKey = getApiKey((int)$keyRow['id']);
    if (!$apiKey) continue;
    echo "[reconcile] Fetching billing for key={$keyRow['label']}\n";

    foreach (array_keys($groupings) as $groupingKey) {
        $billingApiCalls++;
        $data = fetchBilling($apiKey, $startTime, $endTime, $groupingKey);

        if ($data === null) {
            echo "[reconcile] Skipping {$groupingKey} for key={$keyRow['label']} (API error)\n";
        } else {
            saveBillingRecords($db, $data, $groupingKey);
            $groupings[$groupingKey] = array_merge($groupings[$groupingKey], $data);
        }
    }
}

// ------------------------------------------------------------------
// 1b. Update est_cost_per_sec on endpoints from recent billing data
// ------------------------------------------------------------------
$endpointData = $groupings['endpointId'];
if (!empty($endpointData)) {
    $rateByEp = [];
    foreach ($endpointData as $b) {
        $epId = $b['endpointId'] ?? null;
        if (!$epId || (int)$b['timeBilledMs'] <= 0) continue;
        if (!isset($rateByEp[$epId])) $rateByEp[$epId] = ['amount' => 0, 'ms' => 0];
        $rateByEp[$epId]['amount'] += (float)$b['amount'];
        $rateByEp[$epId]['ms']     += (int)$b['timeBilledMs'];
    }
    $stmtRate = $db->prepare('UPDATE endpoints SET est_cost_per_sec = ? WHERE endpoint_id = ?');
    foreach ($rateByEp as $epId => $d) {
        $rate = $d['amount'] / ($d['ms'] / 1000);
        $stmtRate->execute([$rate, $epId]);
    }
    $endpointsUpdated = count($rateByEp);
    echo "[reconcile] Updated est_cost_per_sec for {$endpointsUpdated} endpoint(s)\n";
}

// ------------------------------------------------------------------
// 2. Job cost reconciliation (podId preferred, endpointId fallback)
// ------------------------------------------------------------------
$podBilling      = $groupings['podId'];
$endpointBilling = $groupings['endpointId'];

if (empty($podBilling) && empty($endpointBilling)) {
    $durationMs = (int)((hrtime(true) - $reconcileStart) / 1_000_000);
    echo "[reconcile] No billing data for {$targetDate}, skipping reconcile\n";
    writeReconcileLog($targetDate, $triggerSource, 0, 0, 0, $endpointsUpdated, $billingApiCalls, $durationMs, 'no billing data');
    exit(0);
}

// Index podId billing by (podId, bucketTime) for quick lookup
$podBillingIndex = [];
foreach ($podBilling ?? [] as $b) {
    $key = $b['podId'] . '|' . $b['time'];
    $podBillingIndex[$key] = $b;
}

// Index endpointId billing by (endpointId, bucketTime) for fallback
$endpointBillingIndex = [];
foreach ($endpointBilling ?? [] as $b) {
    $key = $b['endpointId'] . '|' . $b['time'];
    $endpointBillingIndex[$key] = $b;
}

// Fetch all unreconciled done jobs
$unreconciledJobs = $db->query(
    "SELECT * FROM jobs WHERE status IN ('done', 'deleted') AND cost_reconciled = 0 ORDER BY id ASC"
)->fetchAll();

if (empty($unreconciledJobs)) {
    $durationMs = (int)((hrtime(true) - $reconcileStart) / 1_000_000);
    echo "[reconcile] No unreconciled jobs\n";
    writeReconcileLog($targetDate, $triggerSource, 0, 0, 0, $endpointsUpdated, $billingApiCalls, $durationMs);
    exit(0);
}

$totalAdjustment = 0;
$jobsAdjusted = 0;
$jobsSkipped = 0;

foreach ($unreconciledJobs as $j) {
    $jobExecMs = (int)($j['execution_time'] ?? 0);
    if ($jobExecMs <= 0) continue;

    // Determine which billing bucket hour (UTC) this job falls into
    $createdJst = new DateTime($j['created_at'], new DateTimeZone('Asia/Tokyo'));
    $createdUtc = (clone $createdJst)->setTimezone(new DateTimeZone('UTC'));
    $bucketHourUtc = $createdUtc->format('Y-m-d H:00:00');

    // Try podId match first (exact per-worker cost)
    $billingBucket = null;
    $matchType = null;
    if (!empty($j['worker_id'])) {
        $podKey = $j['worker_id'] . '|' . $bucketHourUtc;
        if (isset($podBillingIndex[$podKey])) {
            $billingBucket = $podBillingIndex[$podKey];
            $matchType = 'podId';
        }
    }

    // Fallback to endpointId
    if (!$billingBucket) {
        $epKey = $j['endpoint_id'] . '|' . $bucketHourUtc;
        if (isset($endpointBillingIndex[$epKey])) {
            $billingBucket = $endpointBillingIndex[$epKey];
            $matchType = 'endpointId';
        }
    }

    if (!$billingBucket) {
        $jobsSkipped++;
        continue; // Billing data not yet available
    }

    $actualAmount = (float)$billingBucket['amount'];
    $timeBilledMs = (int)$billingBucket['timeBilledMs'];
    if ($actualAmount <= 0 || $timeBilledMs <= 0) continue;

    // For podId match: find all jobs on the same pod+hour for proportional split
    // For endpointId match: find all jobs on the same endpoint+hour
    $hourStartJst = (new DateTime($bucketHourUtc, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');
    $hourEndJst = (new DateTime($bucketHourUtc, new DateTimeZone('UTC')))
        ->modify('+1 hour')->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');

    if ($matchType === 'podId') {
        $stmtPeers = $db->prepare(
            "SELECT id, execution_time FROM jobs
             WHERE worker_id = ? AND status IN ('done', 'deleted')
             AND created_at >= ? AND created_at < ?
             ORDER BY id ASC"
        );
        $stmtPeers->execute([$j['worker_id'], $hourStartJst, $hourEndJst]);
    } else {
        $stmtPeers = $db->prepare(
            "SELECT id, execution_time FROM jobs
             WHERE endpoint_id = ? AND status IN ('done', 'deleted')
             AND created_at >= ? AND created_at < ?
             ORDER BY id ASC"
        );
        $stmtPeers->execute([$j['endpoint_id'], $hourStartJst, $hourEndJst]);
    }
    $peers = $stmtPeers->fetchAll();

    $totalPeerExecMs = 0;
    foreach ($peers as $p) {
        $totalPeerExecMs += (int)($p['execution_time'] ?? 0);
    }
    if ($totalPeerExecMs <= 0) continue;

    $share = $jobExecMs / $totalPeerExecMs;
    $newCostRunpod = $actualAmount * $share;
    $newCostUser   = $newCostRunpod * $marginRate;

    $oldCostUser = (float)($j['cost_user'] ?? 0);
    $costUserDiff = $newCostUser - $oldCostUser;

    if (abs($costUserDiff) < 0.000001) {
        $db->prepare('UPDATE jobs SET cost_reconciled = 1 WHERE id = ?')->execute([$j['id']]);
        continue;
    }

    $userId = $j['user_id'];

    $db->beginTransaction();
    try {
        $db->prepare(
            'UPDATE jobs SET cost_runpod = ?, cost_user = ?, cost_reconciled = 1, updated_at = NOW() WHERE id = ?'
        )->execute([$newCostRunpod, $newCostUser, $j['id']]);

        $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
           ->execute([$costUserDiff, $userId]);

        $stmtBal = $db->prepare('SELECT balance FROM users WHERE id = ?');
        $stmtBal->execute([$userId]);
        $newBalance = $stmtBal->fetchColumn();

        $execSec = $jobExecMs / 1000;
        if ($oldCostUser == 0) {
            $note = sprintf('%s %.1fs $%.6f', $j['type'], $execSec, $newCostUser);
        } else {
            $note = sprintf('reconcile: job %d adj $%.6f (was $%.6f)', $j['id'], $newCostUser, $oldCostUser);
        }
        $db->prepare(
            'INSERT INTO transactions (user_id, type, amount, balance, job_id, note) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $userId, 'generation', -$costUserDiff, $newBalance, $j['id'], $note
        ]);

        $db->commit();
        $totalAdjustment += $costUserDiff;
        $jobsAdjusted++;

        echo sprintf(
            "  job_id=%d match=%s exec=%dms old=$%.6f new=$%.6f diff=%+.6f\n",
            $j['id'], $matchType, $jobExecMs, $oldCostUser, $newCostUser, $costUserDiff
        );

    } catch (Exception $e) {
        $db->rollBack();
        error_log("[CIEL reconcile] Error job_id={$j['id']}: " . $e->getMessage());
    }
}

$durationMs = (int)((hrtime(true) - $reconcileStart) / 1_000_000);

echo sprintf("[reconcile] Done. %d adjusted, %d skipped (no billing yet), total: $%.6f (%dms)\n",
    $jobsAdjusted, $jobsSkipped, $totalAdjustment, $durationMs);

// Write reconcile log
writeReconcileLog($targetDate, $triggerSource, $jobsAdjusted, $jobsSkipped, $totalAdjustment, $endpointsUpdated, $billingApiCalls, $durationMs, $reconcileError);
