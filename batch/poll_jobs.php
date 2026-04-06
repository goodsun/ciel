#!/usr/local/php/8.1/bin/php
<?php
// Cron job: poll pending/processing jobs against RunPod API
// Run every 1 minute: * * * * * /home/users/0/bon-soleil/web/ciel/batch/poll_jobs.php

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/db.php';

$db = getDb();
$apiKey = $podApiKey;

if (!$apiKey) {
    error_log('[CIEL batch] POD_API_KEY not configured');
    exit(1);
}

$allPods = array_merge($podImage, $podVideo, $podEdit);
$marginRate = (float)(getenv('MARGIN_RATE') ?: 3.5);

// Fetch unfinished jobs
$stmt = $db->query("SELECT * FROM jobs WHERE status IN ('pending', 'processing') ORDER BY created_at ASC LIMIT 50");
$jobs = $stmt->fetchAll();

if (empty($jobs)) {
    exit(0);
}

foreach ($jobs as $job) {
    $endpointId = $job['endpoint_id'];
    $runpodJobId = $job['runpod_job_id'];

    if (!$runpodJobId) {
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
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['processing', $job['id']]);
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

    if ($executionTime <= 0 || $executionTime > 3600000) {
        error_log("[CIEL batch] Anomalous executionTime={$executionTime} for job_id={$job['id']}");
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['failed', $job['id']]);
        continue;
    }

    $executionSec = $executionTime / 1000;

    // Find cost_per_sec
    $costPerSec = 0;
    foreach ($allPods as $pod) {
        if ($pod['id'] === $endpointId) {
            $costPerSec = $pod['cost_per_sec'];
            break;
        }
    }

    $costRunpod = $executionSec * $costPerSec;
    $costUser = $costRunpod * $marginRate;
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

        // Update job
        $db->prepare(
            'UPDATE jobs SET status = ?, cost_runpod = ?, cost_user = ?, execution_time = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['done', $costRunpod, $costUser, $executionTime, $job['id']]);

        // Deduct balance
        $stmtDeduct = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $stmtDeduct->execute([$costUser, $userId, $costUser]);
        if ($stmtDeduct->rowCount() === 0) {
            error_log("[CIEL batch] Insufficient balance for user_id={$userId} job_id={$job['id']} cost={$costUser}");
            // Still mark as done but log the issue
        }

        // Get new balance
        $stmtBal = $db->prepare('SELECT balance FROM users WHERE id = ?');
        $stmtBal->execute([$userId]);
        $newBalance = $stmtBal->fetchColumn();

        // Record transaction
        $db->prepare(
            'INSERT INTO transactions (user_id, type, amount, balance, job_id, note) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $userId, 'generation', -$costUser, $newBalance, $job['id'],
            sprintf('%s %.1fs $%.6f (batch)', $job['type'], $executionSec, $costUser)
        ]);

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
        echo "[OK] job_id={$job['id']} cost=\${$costUser}\n";

    } catch (Exception $e) {
        $db->rollBack();
        error_log("[CIEL batch] Error processing job_id={$job['id']}: " . $e->getMessage());
    }
}
