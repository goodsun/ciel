<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$adminIds = array_filter(explode(',', getenv('ADMIN_GOOGLE_IDS') ?: ''));
if (!in_array($_SESSION['user']['google_id'], $adminIds, true)) {
    http_response_code(403); echo 'Access denied'; exit;
}

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) { header('Location: /admin/?tab=users'); exit; }

$db = getDb();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: /admin/?tab=users'); exit; }

// Stats
$jobStats = $db->prepare("SELECT
    COUNT(*) AS total,
    SUM(status = 'done') AS done,
    SUM(status = 'failed') AS failed,
    SUM(CASE WHEN status IN ('done','deleted') THEN cost_runpod ELSE 0 END) AS total_runpod,
    SUM(CASE WHEN status IN ('done','deleted') THEN cost_user ELSE 0 END) AS total_user,
    SUM(CASE WHEN status IN ('done','deleted') THEN execution_time ELSE 0 END) AS total_time
    FROM jobs WHERE user_id = ?");
$jobStats->execute([$userId]);
$stats = $jobStats->fetch();

$totalPurchased = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type = 'purchase'");
$totalPurchased->execute([$userId]);
$purchased = (float)$totalPurchased->fetchColumn();

// Recent jobs
$jobStmt = $db->prepare("SELECT j.*, e.name AS endpoint_name FROM jobs j LEFT JOIN endpoints e ON j.endpoint_id = e.endpoint_id WHERE j.user_id = ? ORDER BY j.id DESC LIMIT 50");
$jobStmt->execute([$userId]);
$jobs = $jobStmt->fetchAll();

// Recent transactions
$txnStmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 20');
$txnStmt->execute([$userId]);
$txns = $txnStmt->fetchAll();

$pageTitle = 'User #' . $userId;
$pageHeading = 'Admin';
require_once __DIR__ . '/../../templates/head.php';
require_once __DIR__ . '/../../templates/header.php';
?>

<style>
.user-detail { background:#16213e; border:1px solid #2a2a4a; border-radius:8px; padding:24px; margin-bottom:20px; }
.user-detail h2 { color:#8bb4ff; font-size:1.1rem; margin-bottom:16px; }
.meta-table { width:100%; font-size:0.85rem; }
.meta-table td { padding:6px 8px; border-bottom:1px solid #1a1a2e; }
.meta-table td:first-child { color:#888; width:140px; white-space:nowrap; }
.meta-table td:last-child { color:#ccc; word-break:break-all; }
.admin-stat { display:flex; gap:16px; margin-bottom:16px; }
.admin-stat div { flex:1; background:#0d1b2a; border:1px solid #2a2a4a; border-radius:8px; padding:12px; text-align:center; }
.admin-stat .num { font-size:1.3rem; font-weight:600; color:#8bb4ff; }
.admin-stat .label { font-size:0.75rem; color:#888; margin-top:4px; }
.admin-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.admin-table th { text-align:left; padding:8px 6px; color:#8bb4ff; border-bottom:2px solid #2a2a4a; white-space:nowrap; }
.admin-table td { padding:6px; border-bottom:1px solid #1a1a2e; color:#ccc; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.admin-table tr:hover td { background:#1a1a2e; }
.back-link { display:inline-block; margin-bottom:16px; color:#888; text-decoration:none; font-size:0.85rem; }
.back-link:hover { color:#8bb4ff; }
</style>

<a href="/admin/?tab=users" class="back-link">&larr; Back to Users</a>

<div class="user-detail">
  <h2><?= htmlspecialchars($user['name']) ?></h2>
  <table class="meta-table">
    <tr><td>ID</td><td><?= $user['id'] ?></td></tr>
    <tr><td>Email</td><td><?= htmlspecialchars($user['email']) ?></td></tr>
    <tr><td>Google ID</td><td><?= htmlspecialchars($user['google_id']) ?></td></tr>
    <tr><td>Balance</td><td style="color:<?= $user['balance'] > 0 ? '#6bff9e' : '#ff6b6b' ?>">$<?= number_format((float)$user['balance'], 4) ?></td></tr>
    <tr><td>Active</td><td><?= $user['is_active'] ? 'Yes' : 'No' ?></td></tr>
    <tr><td>Created</td><td><?= $user['created_at'] ?></td></tr>
    <tr><td>Updated</td><td><?= $user['updated_at'] ?></td></tr>
  </table>
</div>

<div class="admin-stat">
  <div><div class="num"><?= (int)$stats['total'] ?></div><div class="label">Total Jobs</div></div>
  <div><div class="num" style="color:#6bff9e;"><?= (int)$stats['done'] ?></div><div class="label">Done</div></div>
  <div><div class="num" style="color:#ff6b6b;"><?= (int)$stats['failed'] ?></div><div class="label">Failed</div></div>
  <div><div class="num" style="color:#6bff9e;">$<?= number_format($purchased, 2) ?></div><div class="label">Purchased</div></div>
  <div><div class="num" style="color:#ff6b6b;">$<?= number_format((float)$stats['total_runpod'], 4) ?></div><div class="label">RunPod Cost</div></div>
  <div><div class="num" style="color:#ffb86b;">$<?= number_format((float)$stats['total_user'], 4) ?></div><div class="label">Charged</div></div>
  <div><div class="num"><?= number_format((float)$stats['total_time'] / 1000, 1) ?>s</div><div class="label">GPU Time</div></div>
</div>

<h3 style="color:#8bb4ff;font-size:0.95rem;margin-bottom:12px;">Recent Jobs</h3>
<table class="admin-table">
  <tr><th></th><th>ID</th><th>Type</th><th>Endpoint</th><th>Status</th><th>Cost</th><th>Time</th><th>Created</th></tr>
<?php foreach ($jobs as $r):
    $hasFile = $r['output_path'] && file_exists(__DIR__ . '/../../' . $r['output_path']);
?>
  <tr>
    <td style="width:36px;">
<?php if ($hasFile && $r['type'] !== 'video'): ?>
      <img src="/admin/file.php?job_id=<?= $r['id'] ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px;">
<?php elseif ($hasFile): ?>
      <span style="font-size:1rem;">&#9654;</span>
<?php endif; ?>
    </td>
    <td><a href="/admin/job.php?id=<?= $r['id'] ?>" style="color:#8bb4ff;text-decoration:none;">#<?= $r['id'] ?></a></td>
    <td><?= $r['type'] ?></td>
    <td><?= htmlspecialchars($r['endpoint_name'] ?? $r['endpoint_id']) ?></td>
    <td style="color:<?= $r['status'] === 'done' ? '#6bff9e' : ($r['status'] === 'failed' ? '#ff6b6b' : '#888') ?>"><?= $r['status'] ?></td>
<?php $uCost = $r['cost_user'] ?? $r['est_cost_user']; $uEst = $r['cost_user'] === null; ?>
    <td><?= $uCost !== null ? ($uEst ? '<span style="color:#888;">~</span>' : '') . '$' . number_format((float)$uCost, 4) : '<span style="color:#555;">-</span>' ?></td>
    <td><?= $r['execution_time'] ? number_format($r['execution_time'] / 1000, 1) . 's' : '-' ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<h3 style="color:#8bb4ff;font-size:0.95rem;margin:20px 0 12px;">Recent Transactions</h3>
<table class="admin-table">
  <tr><th>ID</th><th>Type</th><th>Amount</th><th>Balance</th><th>Note</th><th>Date</th></tr>
<?php foreach ($txns as $t): ?>
  <tr>
    <td><?= $t['id'] ?></td>
    <td><?= $t['type'] ?></td>
    <td style="color:<?= $t['amount'] >= 0 ? '#6bff9e' : '#ff6b6b' ?>"><?= $t['amount'] >= 0 ? '+' : '' ?>$<?= number_format((float)$t['amount'], 4) ?></td>
    <td>$<?= number_format((float)$t['balance'], 4) ?></td>
    <td><?= htmlspecialchars($t['note'] ?? '') ?></td>
    <td><?= $t['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
