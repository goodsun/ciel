<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
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

$body = json_decode(file_get_contents('php://input'), true);
$jobId = (int)($body['job_id'] ?? 0);

if (!$jobId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing job_id']);
    exit;
}

$userId = $_SESSION['user']['id'];
$db = getDb();

$stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND user_id = ?');
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch();

if (!$job || !$job['output_path']) {
    http_response_code(404);
    echo json_encode(['error' => 'Job not found']);
    exit;
}

$basePath = __DIR__ . '/../../';
$srcPath = $basePath . $job['output_path'];

if (!file_exists($srcPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Move to trash keeping directory structure
$trashPath = str_replace('storage/', 'trash/', $job['output_path']);
$trashDir = dirname($basePath . $trashPath);
if (!is_dir($trashDir)) {
    mkdir($trashDir, 0755, true);
}

rename($srcPath, $basePath . $trashPath);

// Update job record with trash path
$db->prepare('UPDATE jobs SET status = ?, output_path = ?, updated_at = NOW() WHERE id = ?')
   ->execute(['deleted', $trashPath, $job['id']]);

echo json_encode(['ok' => true]);
