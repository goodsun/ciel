<?php
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/db.php';
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

$apiKey = $podApiKey;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'POD_API_KEY not configured']);
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

// Handle completion — charge user
if (($data['status'] ?? '') === 'COMPLETED' && $job && $job['status'] !== 'done') {
    $executionTime = (int)($data['executionTime'] ?? 0);

    // #8: executionTime anomaly check
    if ($executionTime <= 0 || $executionTime > 3600000) {
        error_log("[CIEL] Anomalous executionTime={$executionTime} for job_id={$job['id']} runpod_job_id={$jobId}");
        $db->prepare('UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?')
           ->execute(['failed', $job['id']]);
        http_response_code(500);
        echo json_encode(['error' => 'Invalid execution time from RunPod']);
        exit;
    }

    $executionSec = $executionTime / 1000;

    // Find cost_per_sec for the validated endpoint
    $costPerSec = 0;
    foreach ($allPods as $pod) {
        if ($pod['id'] === $endpointId) {
            $costPerSec = $pod['cost_per_sec'];
            break;
        }
    }

    $marginRate = (float)(getenv('MARGIN_RATE') ?: 2.0);
    $costRunpod = $executionSec * $costPerSec;
    $costUser   = $costRunpod * $marginRate;

    $db->beginTransaction();
    try {
        // #1: Re-fetch job with row lock to prevent double-charge
        $stmtLock = $db->prepare('SELECT * FROM jobs WHERE id = ? FOR UPDATE');
        $stmtLock->execute([$job['id']]);
        $jobLocked = $stmtLock->fetch();

        if (!$jobLocked || $jobLocked['status'] === 'done') {
            // Already processed by a concurrent request
            $db->rollBack();
            echo json_encode([
                'status'       => 'COMPLETED',
                'already_done' => true,
            ]);
            exit;
        }

        // Update job
        $db->prepare(
            'UPDATE jobs SET status = ?, cost_runpod = ?, cost_user = ?, execution_time = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['done', $costRunpod, $costUser, $executionTime, $job['id']]);

        // #4: Deduct balance with guard against going negative
        $stmtDeduct = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $stmtDeduct->execute([$costUser, $userId, $costUser]);
        if ($stmtDeduct->rowCount() === 0) {
            $db->rollBack();
            http_response_code(402);
            echo json_encode(['error' => 'Insufficient balance']);
            exit;
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
            sprintf('%s %.1fs $%.6f', $job['type'], $executionSec, $costUser)
        ]);

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
            // Strip data URL prefix if present
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
        // #11: Do NOT cache balance in session — always read from DB

        $data['cost_runpod'] = $costRunpod;
        $data['cost_user']   = $costUser;
        $data['output_path'] = $outputPath;
        $data['new_balance'] = $newBalance;

    } catch (Exception $e) {
        // #9: Do not swallow exceptions
        $db->rollBack();
        error_log('[CIEL] Billing error for job_id=' . ($job['id'] ?? 'unknown') . ': ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Billing processing failed']);
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
