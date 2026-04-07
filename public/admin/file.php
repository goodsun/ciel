<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

if (!isLoggedIn()) { http_response_code(401); exit; }
$adminIds = array_filter(explode(',', getenv('ADMIN_GOOGLE_IDS') ?: ''));
if (!in_array($_SESSION['user']['google_id'], $adminIds, true)) {
    http_response_code(403); exit;
}

$jobId = (int)($_GET['job_id'] ?? 0);
if (!$jobId) { http_response_code(400); exit; }

$db = getDb();
$stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job || !$job['output_path']) { http_response_code(404); exit; }

$filePath = realpath(__DIR__ . '/../../' . $job['output_path']);
$storageBase = realpath(__DIR__ . '/../../storage/');
if (!$filePath || !$storageBase || !str_starts_with($filePath, $storageBase . '/')) {
    http_response_code(403); exit;
}

$ext = pathinfo($filePath, PATHINFO_EXTENSION);
$mimeTypes = ['jpg' => 'image/jpeg', 'mp4' => 'video/mp4'];
header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=86400');
readfile($filePath);
