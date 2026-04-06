#!/usr/local/php/8.1/bin/php
<?php
// Reconcile job costs against RunPod Billing API.
// Distributes actual billed cost proportionally across jobs by executionTime.
// Run daily at 02:00 UTC: 0 2 * * * path/to/batch/reconcile_costs.php
//
// Usage:
//   reconcile_costs.php              # reconcile yesterday
//   reconcile_costs.php 2026-04-06   # reconcile specific date

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/db.php';

$db = getDb();
$apiKey = $podApiKey;

if (!$apiKey) {
    error_log('[CIEL reconcile] POD_API_KEY not configured');
    exit(1);
}

$marginRate = (float)(getenv('MARGIN_RATE') ?: 3.5);

// Target date (default: yesterday)
$targetDate = $argv[1] ?? (new DateTime('yesterday', new DateTimeZone('UTC')))->format('Y-m-d');
$startTime  = $targetDate . 'T00:00:00Z';
$endTime    = (new DateTime($targetDate, new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d') . 'T00:00:00Z';

echo "[reconcile] Target: {$targetDate}\n";

// Fetch billing data (hourly buckets)
$url = 'https://rest.runpod.io/v1/billing/endpoints?' . http_build_query([
    'bucketSize' => 'hour',
    'startTime'  => $startTime,
    'endTime'    => $endTime,
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
    error_log("[CIEL reconcile] Billing API returned HTTP {$httpCode}");
    exit(1);
}

$billingData = json_decode($response, true);
if (!is_array($billingData) || empty($billingData)) {
    echo "[reconcile] No billing data for {$targetDate}\n";
    exit(0);
}

$totalAdjustment = 0;
$jobsAdjusted = 0;

foreach ($billingData as $bucket) {
    $endpointId   = $bucket['endpointId'];
    $actualAmount = (float)$bucket['amount'];
    $timeBilledMs = (int)$bucket['timeBilledMs'];
    $bucketTime   = $bucket['time']; // e.g. "2026-04-06 01:00:00" (UTC)

    if ($actualAmount <= 0 || $timeBilledMs <= 0) {
        continue;
    }

    // Hour range for this bucket (convert UTC -> JST for DB query)
    $hourStartUtc = new DateTime($bucketTime, new DateTimeZone('UTC'));
    $hourStart = (clone $hourStartUtc)->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');
    $hourEnd   = (clone $hourStartUtc)->modify('+1 hour')->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');

    // Find done, non-reconciled jobs in this endpoint+hour (DB stores JST)
    $stmt = $db->prepare(
        "SELECT * FROM jobs
         WHERE endpoint_id = ?
         AND status = 'done'
         AND cost_reconciled = 0
         AND created_at >= ?
         AND created_at < ?
         ORDER BY id ASC"
    );
    $stmt->execute([$endpointId, $hourStart, $hourEnd]);
    $jobs = $stmt->fetchAll();

    if (empty($jobs)) {
        continue;
    }

    // Total executionTime for proportional distribution
    $totalExecMs = 0;
    foreach ($jobs as $j) {
        $totalExecMs += (int)($j['execution_time'] ?? 0);
    }
    if ($totalExecMs <= 0) {
        continue;
    }

    echo sprintf(
        "[reconcile] %s endpoint=%s actual=$%.6f billed=%dms jobs=%d totalExec=%dms\n",
        $bucketTime, $endpointId, $actualAmount, $timeBilledMs, count($jobs), $totalExecMs
    );

    // Distribute actual cost proportionally
    foreach ($jobs as $j) {
        $jobExecMs = (int)($j['execution_time'] ?? 0);
        if ($jobExecMs <= 0) continue;

        $share = $jobExecMs / $totalExecMs;
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
            // Update job costs
            $db->prepare(
                'UPDATE jobs SET cost_runpod = ?, cost_user = ?, cost_reconciled = 1, updated_at = NOW() WHERE id = ?'
            )->execute([$newCostRunpod, $newCostUser, $j['id']]);

            // Deduct (or credit) the difference from user balance
            $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
               ->execute([$costUserDiff, $userId]);

            // Get new balance
            $stmtBal = $db->prepare('SELECT balance FROM users WHERE id = ?');
            $stmtBal->execute([$userId]);
            $newBalance = $stmtBal->fetchColumn();

            // Record transaction
            $execSec = $jobExecMs / 1000;
            if ($oldCostUser == 0) {
                // First-time charge (status.php/poll_jobs.php no longer charges)
                $note = sprintf('%s %.1fs $%.6f', $j['type'], $execSec, $newCostUser);
            } else {
                // Adjustment of previously charged amount
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
                "  job_id=%d exec=%dms old=$%.6f new=$%.6f diff=%+.6f\n",
                $j['id'], $jobExecMs, $oldCostUser, $newCostUser, $costUserDiff
            );

        } catch (Exception $e) {
            $db->rollBack();
            error_log("[CIEL reconcile] Error job_id={$j['id']}: " . $e->getMessage());
        }
    }
}

echo sprintf("[reconcile] Done. %d jobs adjusted, total adjustment: $%.6f\n", $jobsAdjusted, $totalAdjustment);
