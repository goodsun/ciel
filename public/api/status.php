<?php
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

$endpointId = $_GET['endpoint_id'] ?? '';
$jobId      = $_GET['job_id'] ?? '';

if (!$endpointId || !$jobId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint_id or job_id']);
    exit;
}

// #2: endpoint_id whitelist validation
$allPods = array_merge($podImage, $podVideo, $podEdit);
$validEndpointIds = array_column($allPods, 'id');
if (!in_array($endpointId, $validEndpointIds, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint_id']);
    exit;
}

$apiKey = getApiKeyForEndpoint($endpointId);
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured for endpoint']);
    exit;
}

$userId = $_SESSION['user']['id'];
$db = getDb();

// Check if already processed (no lock needed for early-exit read)
$stmt = $db->prepare('SELECT * FROM jobs WHERE runpod_job_id = ? AND user_id = ?');
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch();

if ($job && $job['status'] === 'done') {
    echo json_encode([
        'status'        => 'COMPLETED',
        'executionTime' => $job['execution_time'],
        'cost_user'     => $job['cost_user'],
        'already_done'  => true,
    ]);
    exit;
}

// Poll RunPod
$url = "https://api.runpod.ai/v2/{$endpointId}/status/{$jobId}";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Handle completion — save result, cost determined later by reconcile_costs.php
if (($data['status'] ?? '') === 'COMPLETED' && $job && $job['status'] !== 'done') {
    $executionTime = (int)($data['executionTime'] ?? 0);
    $delayTime     = (int)($data['delayTime'] ?? 0);
    $workerId      = $data['workerId'] ?? null;

    // #8: executionTime anomaly check
    if ($executionTime <= 0 || $executionTime > 3600000) {
        error_log("[CIEL] Anomalous executionTime={$executionTime} for job_id={$job['id']} runpod_job_id={$jobId}");
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['failed', $job['id']]);
        http_response_code(500);
        echo json_encode(['error' => 'Invalid execution time from RunPod']);
        exit;
    }

    $db->beginTransaction();
    try {
        // Re-fetch job with row lock to prevent double-processing
        $stmtLock = $db->prepare('SELECT * FROM jobs WHERE id = ? FOR UPDATE');
        $stmtLock->execute([$job['id']]);
        $jobLocked = $stmtLock->fetch();

        if (!$jobLocked || $jobLocked['status'] === 'done') {
            $db->rollBack();
            echo json_encode([
                'status'       => 'COMPLETED',
                'already_done' => true,
            ]);
            exit;
        }

        // Update job (cost stays NULL until reconciliation)
        $db->prepare(
            'UPDATE jobs SET status = ?, execution_time = ?, delay_time = ?, worker_id = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['done', $executionTime, $delayTime ?: null, $workerId, $job['id']]);

        // Save output file to storage
        $outputPath = null;
        $storageBase = __DIR__ . '/../../storage/users/' . $userId . '/generates';
        if (!is_dir($storageBase)) {
            mkdir($storageBase, 0755, true);
        }

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

        $data['output_path'] = $outputPath;

    } catch (Exception $e) {
        $db->rollBack();
        error_log('[CIEL] Error processing job_id=' . ($job['id'] ?? 'unknown') . ': ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Job processing failed']);
        exit;
    }
} elseif (($data['status'] ?? '') === 'FAILED' && $job && $job['status'] !== 'failed') {
    $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
       ->execute(['failed', $job['id']]);
} elseif ($job && $job['status'] === 'pending' && ($data['status'] ?? '') === 'IN_PROGRESS') {
    $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
       ->execute(['processing', $job['id']]);
}

http_response_code($httpCode);
echo json_encode($data);
