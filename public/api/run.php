<?php
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
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
// #2: endpoint_id whitelist validation — reject unknown endpoints immediately
$allPods = array_merge($podImage, $podVideo, $podEdit);
$validEndpointIds = array_column($allPods, 'id');
if (!in_array($endpointId, $validEndpointIds, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint_id']);
    exit;
}

// Validate LoRA parameters
$hasLoras = !empty($body['input']['loras']);
$hasLegacyLora = !empty($body['input']['lora_url']);

if ($hasLoras && $hasLegacyLora) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot specify both loras and lora_url']);
    exit;
}

if ($hasLoras) {
    $loras = $body['input']['loras'];
    if (!is_array($loras)) {
        http_response_code(400);
        echo json_encode(['error' => 'loras must be an array']);
        exit;
    }
    if (count($loras) > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'loras: maximum 10 LoRAs allowed']);
        exit;
    }
    foreach ($loras as $i => $entry) {
        if (!is_array($entry)) {
            http_response_code(400);
            echo json_encode(['error' => "loras[$i] must be an object"]);
            exit;
        }
        if (empty($entry['url']) || !is_string($entry['url'])) {
            http_response_code(400);
            echo json_encode(['error' => "loras[$i].url is required and must be a string"]);
            exit;
        }
        if (!preg_match('#^https?://.+\.safetensors$#i', $entry['url'])) {
            http_response_code(400);
            echo json_encode(['error' => "loras[$i].url must be a valid URL ending with .safetensors"]);
            exit;
        }
        if (isset($entry['strength'])) {
            if (!is_numeric($entry['strength']) || $entry['strength'] < -2.0 || $entry['strength'] > 2.0) {
                http_response_code(400);
                echo json_encode(['error' => "loras[$i].strength must be between -2.0 and 2.0"]);
                exit;
            }
        }
    }
} elseif ($hasLegacyLora) {
    $loraUrl = $body['input']['lora_url'];
    if (!is_string($loraUrl) || !preg_match('#^https?://.+\.safetensors$#i', $loraUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'lora_url must be a valid URL ending with .safetensors']);
        exit;
    }
    if (isset($body['input']['lora_strength'])) {
        $loraStrength = $body['input']['lora_strength'];
        if (!is_numeric($loraStrength) || $loraStrength < -2.0 || $loraStrength > 2.0) {
            http_response_code(400);
            echo json_encode(['error' => 'lora_strength must be between -2.0 and 2.0']);
            exit;
        }
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

// Resolve API key for this endpoint
$apiKey = getApiKeyForEndpoint($endpointId);
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured for endpoint']);
    exit;
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
}

http_response_code($httpCode);
echo json_encode($data);
