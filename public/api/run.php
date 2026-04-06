<?php
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Check balance > 0
$db = getDb();
$stmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$balance = (float)$stmt->fetchColumn();

if ($balance <= 0) {
    http_response_code(402);
    echo json_encode(['error' => 'Insufficient balance']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['endpoint_id']) || empty($body['input'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint_id or input']);
    exit;
}

$endpointId = $body['endpoint_id'];
$type = $body['type'] ?? 'image';
$apiKey = $podApiKey;

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'POD_API_KEY not configured']);
    exit;
}

// #2: endpoint_id whitelist validation — reject unknown endpoints immediately
$allPods = array_merge($podImage, $podVideo, $podEdit);
$validEndpointIds = array_column($allPods, 'id');
if (!in_array($endpointId, $validEndpointIds, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint_id']);
    exit;
}

// Find cost_per_sec for this endpoint
$costPerSec = 0;
foreach ($allPods as $pod) {
    if ($pod['id'] === $endpointId) {
        $costPerSec = $pod['cost_per_sec'];
        break;
    }
}

// Save original input for DB (before safeguard filtering)
$originalInput = $body['input'];

// Safeguard: filter prohibited words based on user language
$safeguardLangs = array_filter(explode(',', getenv('SAFEGUARD_TARGET_LANG') ?: ''));
global $CURRENT_LANG;
if (in_array($CURRENT_LANG, $safeguardLangs, true)) {
    $positiveWords = array_filter(array_map('trim', explode(',', getenv('SAFEGUARD_POSITIVE') ?: '')));
    $negativeWords = array_filter(array_map('trim', explode(',', getenv('SAFEGUARD_NEGATIVE') ?: '')));

    if (!empty($body['input']['prompt']) && $positiveWords) {
        foreach ($positiveWords as $word) {
            $body['input']['prompt'] = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $body['input']['prompt']);
        }
        $body['input']['prompt'] = preg_replace('/,\s*,/', ',', trim($body['input']['prompt'], " ,\t\n"));
    }
    if (!empty($body['input']['negative_prompt']) && $negativeWords) {
        foreach ($negativeWords as $word) {
            $body['input']['negative_prompt'] = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $body['input']['negative_prompt']);
        }
        $body['input']['negative_prompt'] = preg_replace('/,\s*,/', ',', trim($body['input']['negative_prompt'], " ,\t\n"));
    }

    // Add words to prompt/negative_prompt
    $positiveAdd = array_filter(array_map('trim', explode(',', getenv('SAFEGUARD_POSITIVE_ADD') ?: '')));
    $negativeAdd = array_filter(array_map('trim', explode(',', getenv('SAFEGUARD_NEGATIVE_ADD') ?: '')));

    if ($positiveAdd) {
        $prompt = $body['input']['prompt'] ?? '';
        $body['input']['prompt'] = $prompt . ($prompt ? ', ' : '') . implode(', ', $positiveAdd);
    }
    if ($negativeAdd) {
        $neg = $body['input']['negative_prompt'] ?? '';
        $body['input']['negative_prompt'] = $neg . ($neg ? ', ' : '') . implode(', ', $negativeAdd);
    }
}

// Submit to RunPod
$url = "https://api.runpod.ai/v2/{$endpointId}/run";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode(['input' => $body['input']]),
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// Create job record if RunPod accepted
if (!empty($data['id'])) {
    $db->prepare(
        'INSERT INTO jobs (user_id, runpod_job_id, endpoint_id, type, status, params) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        $data['id'],
        $endpointId,
        $type,
        'pending',
        json_encode($originalInput),
    ]);
    $data['job_db_id'] = $db->lastInsertId();
    $data['cost_per_sec'] = $costPerSec;
}

http_response_code($httpCode);
echo json_encode($data);
