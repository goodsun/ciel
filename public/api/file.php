<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
requireLogin();

$jobId = (int)($_GET['job_id'] ?? 0);
if (!$jobId) {
    http_response_code(400);
    exit;
}

$userId = $_SESSION['user']['id'];
$db = getDb();
$stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND user_id = ?');
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch();

if (!$job || !$job['output_path']) {
    http_response_code(404);
    exit;
}

$filePath = realpath(__DIR__ . '/../../' . $job['output_path']);
$storageBase = realpath(__DIR__ . '/../../storage/');
if (!$filePath || !$storageBase || !str_starts_with($filePath, $storageBase . '/')) {
    http_response_code(403);
    exit;
}

$ext = pathinfo($filePath, PATHINFO_EXTENSION);
$mimeTypes = ['jpg' => 'image/jpeg', 'mp4' => 'video/mp4'];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=86400');
readfile($filePath);
