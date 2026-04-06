#!/usr/local/php/8.1/bin/php
<?php
// Cron job: poll pending/processing jobs, then reconcile costs
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

        // Update job (cost stays NULL until reconciliation)
        $db->prepare(
            'UPDATE jobs SET status = ?, execution_time = ?, delay_time = ?, worker_id = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['done', $executionTime, $delayTime ?: null, $workerId, $job['id']]);

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
// Phase 2: Reconcile costs (only if unreconciled jobs exist)
// =========================================================
$unreconciledCount = (int)$db->query(
    "SELECT COUNT(*) FROM jobs WHERE status = 'done' AND cost_reconciled = 0"
)->fetchColumn();

if ($unreconciledCount > 0) {
    // Run as subprocess for today (reconcile_costs.php defaults to yesterday)
    $today = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d');
    $cmd = '/usr/local/php/8.1/bin/php ' . __DIR__ . '/reconcile_costs.php ' . escapeshellarg($today);
    passthru($cmd);
}
