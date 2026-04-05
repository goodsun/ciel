<?php
// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            putenv(trim($line));
        }
    }
}

session_start();

// #6: CSRF token generation and validation
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo 'CSRF token mismatch';
        exit;
    }
}

// Parse Pod config into structured arrays
function parsePodConfig(string $prefix): array {
    $ids   = array_filter(explode(',', getenv("POD_IDS_{$prefix}") ?: ''));
    $names = explode(',', getenv("POD_IDS_{$prefix}_NAME") ?: '');
    $steps = explode(',', getenv("POD_IDS_{$prefix}_STEPS") ?: '');
    $cfgs  = explode(',', getenv("POD_IDS_{$prefix}_CFG") ?: '');
    $hints = explode(',', getenv("POD_IDS_{$prefix}_HINT") ?: '');
    $costs = explode(',', getenv("POD_IDS_{$prefix}_COST_PER_SEC") ?: '');

    $models = [];
    foreach ($ids as $i => $id) {
        $models[] = [
            'id'           => trim($id),
            'name'         => trim($names[$i] ?? ''),
            'steps'        => (int)  trim($steps[$i] ?? '25'),
            'cfg'          => (float)trim($cfgs[$i]  ?? '7.0'),
            'hint'         => trim($hints[$i] ?? ''),
            'cost_per_sec' => (float)trim($costs[$i] ?? '0'),
        ];
    }
    return $models;
}

$podApiKey = getenv('POD_API_KEY') ?: '';
$podImage  = parsePodConfig('IMAGE');
$podVideo  = parsePodConfig('VIDEO');
$podEdit   = parsePodConfig('EDIT');
