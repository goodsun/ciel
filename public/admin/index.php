<?php
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/../../src/db.php';

// Admin check
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$adminIds = explode(',', getenv('ADMIN_GOOGLE_IDS') ?: '');
if (!in_array($_SESSION['user']['google_id'], $adminIds, true)) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$db = getDb();
$tab = $_GET['tab'] ?? 'users';

$pageTitle = 'Admin';
$pageHeading = 'Admin';
require __DIR__ . '/../../templates/head.php';
require __DIR__ . '/../../templates/header.php';
?>

<style>
.admin-tabs { display:flex; gap:4px; margin-bottom:16px; }
.admin-tabs a { padding:8px 16px; background:#1a1a2e; border:1px solid #2a2a4a; border-radius:6px; color:#888; text-decoration:none; font-size:0.85rem; }
.admin-tabs a.active { background:#16213e; color:#8bb4ff; border-color:#8bb4ff; }
.admin-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.admin-table th { text-align:left; padding:8px 6px; color:#8bb4ff; border-bottom:2px solid #2a2a4a; white-space:nowrap; }
.admin-table td { padding:6px; border-bottom:1px solid #1a1a2e; color:#ccc; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.admin-table tr:hover td { background:#1a1a2e; }
.admin-stat { display:flex; gap:16px; margin-bottom:16px; }
.admin-stat div { flex:1; background:#0d1b2a; border:1px solid #2a2a4a; border-radius:8px; padding:12px; text-align:center; }
.admin-stat .num { font-size:1.3rem; font-weight:600; color:#8bb4ff; }
.admin-stat .label { font-size:0.75rem; color:#888; margin-top:4px; }
</style>

<div class="admin-tabs">
  <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
  <a href="?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">Users</a>
  <a href="?tab=jobs" class="<?= $tab === 'jobs' ? 'active' : '' ?>">Jobs</a>
  <a href="?tab=transactions" class="<?= $tab === 'transactions' ? 'active' : '' ?>">Transactions</a>
  <a href="?tab=purchases" class="<?= $tab === 'purchases' ? 'active' : '' ?>">Purchases</a>
</div>

<?php if ($tab === 'dashboard'): ?>
<?php
$userCount = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$jobCount = $db->query("SELECT COUNT(*) FROM jobs WHERE status = 'done'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'purchase'")->fetchColumn();
$totalCost = $db->query("SELECT COALESCE(SUM(cost_runpod), 0) FROM jobs WHERE status = 'done'")->fetchColumn();
$totalCharged = $db->query("SELECT COALESCE(SUM(cost_user), 0) FROM jobs WHERE status = 'done'")->fetchColumn();
$totalBalance = $db->query('SELECT COALESCE(SUM(balance), 0) FROM users')->fetchColumn();
?>
<div class="admin-stat">
  <div><div class="num"><?= $userCount ?></div><div class="label">Users</div></div>
  <div><div class="num"><?= $jobCount ?></div><div class="label">Jobs (done)</div></div>
  <div><div class="num" style="color:#6bff9e;">$<?= number_format((float)$totalRevenue, 2) ?></div><div class="label">Total Purchases</div></div>
  <div><div class="num" style="color:#ff6b6b;">$<?= number_format((float)$totalCost, 4) ?></div><div class="label">RunPod Cost</div></div>
  <div><div class="num" style="color:#ffb86b;">$<?= number_format((float)$totalCharged, 4) ?></div><div class="label">User Charged</div></div>
  <div><div class="num">$<?= number_format((float)$totalBalance, 4) ?></div><div class="label">Total Balance</div></div>
</div>

<?php elseif ($tab === 'users'): ?>
<?php $rows = $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll(); ?>
<table class="admin-table">
  <tr><th>ID</th><th>Email</th><th>Name</th><th>Balance</th><th>Active</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td style="color:<?= $r['balance'] > 0 ? '#6bff9e' : '#888' ?>">$<?= number_format((float)$r['balance'], 4) ?></td>
    <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'jobs'): ?>
<?php $rows = $db->query('SELECT j.*, u.email FROM jobs j JOIN users u ON j.user_id = u.id ORDER BY j.id DESC LIMIT 100')->fetchAll(); ?>
<table class="admin-table">
  <tr><th></th><th>ID</th><th>User</th><th>Type</th><th>Status</th><th>RunPod Cost</th><th>User Cost</th><th>Time(s)</th><th>Created</th></tr>
<?php foreach ($rows as $r):
    $hasFile = $r['output_path'] && file_exists(__DIR__ . '/../../' . $r['output_path']);
    $isDeleted = $r['status'] === 'deleted';
    $isTrash = $isDeleted && $hasFile;
?>
  <tr>
    <td style="width:40px;">
<?php if ($hasFile && $r['type'] !== 'video'): ?>
      <img src="/admin/file.php?job_id=<?= $r['id'] ?>" style="width:36px;height:36px;object-fit:cover;border-radius:4px;<?= $isTrash ? 'opacity:0.4;' : '' ?>">
<?php elseif ($hasFile): ?>
      <span style="font-size:1.2rem;<?= $isTrash ? 'opacity:0.4;' : '' ?>">&#9654;</span>
<?php elseif ($isDeleted): ?>
      <div style="width:36px;height:36px;background:#2a2a4a;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#555;font-size:0.7rem;">DEL</div>
<?php endif; ?>
    </td>
    <td><a href="/admin/job.php?id=<?= $r['id'] ?>" style="color:#8bb4ff;text-decoration:none;">#<?= $r['id'] ?></a></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= $r['type'] ?></td>
    <td style="color:<?= $r['status'] === 'done' ? '#6bff9e' : ($r['status'] === 'failed' ? '#ff6b6b' : ($r['status'] === 'deleted' ? '#555' : '#888')) ?>"><?= $r['status'] ?></td>
    <td>$<?= number_format((float)$r['cost_runpod'], 6) ?></td>
    <td>$<?= number_format((float)$r['cost_user'], 6) ?></td>
    <td><?= $r['execution_time'] ? number_format($r['execution_time'] / 1000, 1) : '-' ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'transactions'): ?>
<?php $rows = $db->query('SELECT t.*, u.email FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC LIMIT 100')->fetchAll(); ?>
<table class="admin-table">
  <tr><th>ID</th><th>User</th><th>Type</th><th>Amount</th><th>Balance</th><th>Note</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= $r['type'] ?></td>
    <td style="color:<?= $r['amount'] >= 0 ? '#6bff9e' : '#ff6b6b' ?>"><?= $r['amount'] >= 0 ? '+' : '' ?>$<?= number_format((float)$r['amount'], 4) ?></td>
    <td>$<?= number_format((float)$r['balance'], 4) ?></td>
    <td><?= htmlspecialchars($r['note'] ?? '') ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'purchases'): ?>
<?php $rows = $db->query('SELECT p.*, u.email FROM purchases p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC LIMIT 100')->fetchAll(); ?>
<table class="admin-table">
  <tr><th>ID</th><th>User</th><th>Amount</th><th>Status</th><th>Stripe Session</th><th>Payment ID</th><th>Created</th></tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td>$<?= number_format((float)$r['amount'], 2) ?></td>
    <td style="color:<?= $r['status'] === 'completed' ? '#6bff9e' : ($r['status'] === 'failed' ? '#ff6b6b' : '#888') ?>"><?= $r['status'] ?></td>
    <td title="<?= htmlspecialchars($r['stripe_session_id']) ?>"><?= htmlspecialchars(substr($r['stripe_session_id'], 0, 20)) ?>...</td>
    <td><?= htmlspecialchars($r['stripe_payment_id'] ?? '-') ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
