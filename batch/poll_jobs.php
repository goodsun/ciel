#!/usr/local/php/8.1/bin/php
<?php
// Cron job: poll pending/processing jobs, recover stale purchases, reconcile costs
// Run every 1 minute: * * * * * path/to/batch/poll_jobs.php

require __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/db.php';

$db = getDb();

// =========================================================
// Phase 1: Poll pending/processing jobs
// =========================================================
$stmt = $db->query("SELECT * FROM jobs WHERE status IN ('pending', 'processing') ORDER BY created_at ASC LIMIT 50");
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $endpointId = $job['endpoint_id'];
    $runpodJobId = $job['runpod_job_id'];

    if (!$runpodJobId) {
        continue;
    }

    $apiKey = getApiKeyForEndpoint($endpointId);
    if (!$apiKey) {
        error_log("[CIEL batch] No API key for endpoint {$endpointId}");
        continue;
    }

    // Poll RunPod
    $url = "https://api.runpod.ai/v2/{$endpointId}/status/{$runpodJobId}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("[CIEL batch] RunPod returned HTTP {$httpCode} for job_id={$job['id']}");
        continue;
    }

    $data = json_decode($response, true);
    $status = $data['status'] ?? '';

    // Update to processing if IN_PROGRESS
    if ($status === 'IN_PROGRESS' && $job['status'] === 'pending') {
        $wid = $data['workerId'] ?? null;
        $db->prepare('UPDATE jobs SET status = ?, worker_id = COALESCE(?, worker_id), updated_at = NOW() WHERE id = ?')
           ->execute(['processing', $wid, $job['id']]);
        continue;
    }

    // Handle failure
    if ($status === 'FAILED') {
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['failed', $job['id']]);
        error_log("[CIEL batch] Job failed: job_id={$job['id']} runpod_job_id={$runpodJobId}");
        continue;
    }

    // Handle completion
    if ($status !== 'COMPLETED') {
        continue;
    }

    $executionTime = (int)($data['executionTime'] ?? 0);
    $delayTime     = (int)($data['delayTime'] ?? 0);
    $workerId      = $data['workerId'] ?? null;

    if ($executionTime <= 0 || $executionTime > 3600000) {
        error_log("[CIEL batch] Anomalous executionTime={$executionTime} for job_id={$job['id']}");
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['failed', $job['id']]);
        continue;
    }

    // Cost determined later by reconcile_costs.php
    $userId = $job['user_id'];
    $db->beginTransaction();
    try {
        // Lock job row
        $stmtLock = $db->prepare('SELECT * FROM jobs WHERE id = ? FOR UPDATE');
        $stmtLock->execute([$job['id']]);
        $jobLocked = $stmtLock->fetch();

        if (!$jobLocked || $jobLocked['status'] === 'done') {
            $db->rollBack();
            continue;
        }

        // Estimate cost from latest reconciled job or endpoint rate
        $est = estimateCost($endpointId, $executionTime);

        // Update job with estimated cost (confirmed cost stays NULL until reconciliation)
        $db->prepare(
            'UPDATE jobs SET status = ?, execution_time = ?, delay_time = ?, worker_id = ?, est_cost_runpod = ?, est_cost_user = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['done', $executionTime, $delayTime ?: null, $workerId, $est['cost_runpod'] ?? null, $est['cost_user'] ?? null, $job['id']]);

        // Save output file
        $storageBase = __DIR__ . '/../storage/users/' . $userId . '/generates';
        if (!is_dir($storageBase)) {
            mkdir($storageBase, 0755, true);
        }

        $outputPath = null;
        if ($job['type'] === 'video' && !empty($data['output']['video'])) {
            $outputPath = "storage/users/{$userId}/generates/{$job['id']}.mp4";
            file_put_contents($storageBase . '/' . $job['id'] . '.mp4', base64_decode($data['output']['video']));
        } elseif (!empty($data['output']['image'])) {
            $outputPath = "storage/users/{$userId}/generates/{$job['id']}.jpg";
            $imgData = $data['output']['image'];
            if (str_contains($imgData, ',')) {
                $imgData = substr($imgData, strpos($imgData, ',') + 1);
            }
            file_put_contents($storageBase . '/' . $job['id'] . '.jpg', base64_decode($imgData));
        }

        if ($outputPath) {
            $db->prepare('UPDATE jobs SET output_path = ? WHERE id = ?')
               ->execute([$outputPath, $job['id']]);
        }

        $db->commit();
        echo "[OK] job_id={$job['id']} (cost pending reconciliation)\n";

    } catch (Exception $e) {
        $db->rollBack();
        error_log("[CIEL batch] Error processing job_id={$job['id']}: " . $e->getMessage());
    }
}

// =========================================================
// Phase 2: Recover stale pending purchases (cooldown: max once per 10 minutes)
// =========================================================
$lockFilePurchase = sys_get_temp_dir() . '/ciel_recover_purchase_last';
$lastRunPurchase = file_exists($lockFilePurchase) ? (int)file_get_contents($lockFilePurchase) : 0;
if (time() - $lastRunPurchase >= 600) { // 10 minutes
    $stalePurchases = $db->query(
        "SELECT * FROM purchases
         WHERE status = 'pending'
           AND created_at < NOW() - INTERVAL 5 MINUTE
           AND created_at > NOW() - INTERVAL 72 HOUR
         ORDER BY created_at ASC LIMIT 50"
    )->fetchAll();

    if (!empty($stalePurchases)) {
        require_once __DIR__ . '/../src/stripe.php';
        file_put_contents($lockFilePurchase, (string)time());

        foreach ($stalePurchases as $p) {
            $sid = $p['stripe_session_id'];
            $session = retrieveCheckoutSession($sid);
            if (empty($session['id'])) {
                error_log('[CIEL recover] Failed to retrieve Stripe session: ' . $sid);
                continue;
            }

            // Expired session — mark failed
            if (($session['status'] ?? '') === 'expired') {
                $db->prepare("UPDATE purchases SET status = 'failed', updated_at = NOW() WHERE id = ?")
                   ->execute([$p['id']]);
                echo "[recover] session={$sid} -> expired\n";
                continue;
            }

            // Not yet paid — skip
            if (($session['payment_status'] ?? '') !== 'paid') {
                continue;
            }

            // Verify amount
            $stripeAmountCents = (int)($session['amount_total'] ?? 0);
            $dbAmountCents     = (int)round((float)$p['amount'] * 100);
            if ($stripeAmountCents !== $dbAmountCents) {
                error_log(sprintf(
                    '[CIEL recover] Amount mismatch: stripe=%d, db=%d, session=%s',
                    $stripeAmountCents, $dbAmountCents, $sid
                ));
                continue;
            }

            $userId    = (int)$p['user_id'];
            $amount    = (float)$p['amount'];
            $paymentId = $session['payment_intent'] ?? '';

            $db->beginTransaction();
            try {
                $db->prepare('UPDATE purchases SET stripe_payment_id = ?, status = ?, updated_at = NOW() WHERE id = ?')
                   ->execute([$paymentId, 'completed', $p['id']]);
                $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
                   ->execute([$amount, $userId]);
                $stmtBal = $db->prepare('SELECT balance FROM users WHERE id = ?');
                $stmtBal->execute([$userId]);
                $newBalance = $stmtBal->fetchColumn();
                $db->prepare(
                    'INSERT INTO transactions (user_id, type, amount, balance, purchase_id, note) VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                    $userId, 'purchase', $amount, $newBalance, $p['id'],
                    'Stripe purchase $' . number_format($amount, 2) . ' (recovered by batch)'
                ]);
                $db->commit();
                echo "[recover] session={$sid} -> recovered (user={$userId}, \${$amount})\n";
            } catch (Exception $e) {
                $db->rollBack();
                error_log('[CIEL recover] Failed session=' . $sid . ': ' . $e->getMessage());
            }
        }
    } else {
        // No stale purchases — still update lock to avoid querying every minute
        file_put_contents($lockFilePurchase, (string)time());
    }
}

// =========================================================
// Phase 3: Reconcile costs (cooldown: max once per 15 minutes)
// =========================================================
$unreconciledCount = (int)$db->query(
    "SELECT COUNT(*) FROM jobs WHERE status IN ('done', 'deleted') AND cost_reconciled = 0"
)->fetchColumn();

if ($unreconciledCount > 0) {
    $lockFile = sys_get_temp_dir() . '/ciel_reconcile_last';
    $lastRun = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
    if (time() - $lastRun >= 900) { // 15 minutes
        file_put_contents($lockFile, (string)time());
        // Reconcile all dates that have unreconciled jobs
        $dates = $db->query(
            "SELECT DISTINCT DATE(CONVERT_TZ(created_at, '+09:00', '+00:00')) AS d
             FROM jobs WHERE status IN ('done', 'deleted') AND cost_reconciled = 0 ORDER BY d"
        )->fetchAll(PDO::FETCH_COLUMN);
        $php = '/usr/local/php/8.1/bin/php';
        foreach ($dates as $date) {
            $cmd = $php . ' ' . __DIR__ . '/reconcile_costs.php ' . escapeshellarg($date) . ' --trigger=poll';
            passthru($cmd);
        }
    }
}
