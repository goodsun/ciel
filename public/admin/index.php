<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

// Admin check
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$adminIds = array_filter(explode(',', getenv('ADMIN_GOOGLE_IDS') ?: ''));
if (!in_array($_SESSION['user']['google_id'], $adminIds, true)) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$db = getDb();
$tab = $_GET['tab'] ?? 'users';

// Handle POST actions for endpoints/apikeys
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    // Reconcile manual trigger
    if ($action === 'run_reconcile') {
        $lockFile = sys_get_temp_dir() . '/ciel_reconcile_last';
        $lastRun = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
        if (time() - $lastRun >= 900) {
            file_put_contents($lockFile, (string)time());
            $dates = $db->query(
                "SELECT DISTINCT DATE(CONVERT_TZ(created_at, '+09:00', '+00:00')) AS d
                 FROM jobs WHERE status IN ('done', 'deleted') AND cost_reconciled = 0 ORDER BY d"
            )->fetchAll(PDO::FETCH_COLUMN);
            if (empty($dates)) {
                $dates = [(new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d')];
            }
            $php = '/usr/local/php/8.1/bin/php';
            $script = realpath(__DIR__ . '/../../batch/reconcile_costs.php');
            $output = '';
            foreach ($dates as $date) {
                $output .= shell_exec($php . ' ' . $script . ' ' . escapeshellarg($date) . ' --trigger=admin 2>&1');
            }
            $_SESSION['reconcile_result'] = $output;
        } else {
            $remaining = 900 - (time() - $lastRun);
            $_SESSION['reconcile_result'] = "Cooldown: {$remaining}s remaining";
        }
        header('Location: /admin/?tab=reconcile');
        exit;
    }

    // API Keys actions
    if ($action === 'add_apikey') {
        $label = trim($_POST['label'] ?? '');
        $key = trim($_POST['api_key'] ?? '');
        $provider = trim($_POST['provider'] ?? 'runpod');
        if ($label && $key) {
            storeApiKey($label, $key, $provider);
        }
        header('Location: /admin/?tab=apikeys'); exit;
    }
    if ($action === 'toggle_apikey') {
        $id = (int)$_POST['id'];
        $db->prepare('UPDATE api_keys SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        header('Location: /admin/?tab=apikeys'); exit;
    }
    if ($action === 'delete_apikey') {
        $id = (int)$_POST['id'];
        $refs = $db->prepare('SELECT COUNT(*) FROM endpoints WHERE api_key_id = ?');
        $refs->execute([$id]);
        if ((int)$refs->fetchColumn() === 0) {
            $db->prepare('DELETE FROM api_keys WHERE id = ?')->execute([$id]);
        }
        header('Location: /admin/?tab=apikeys'); exit;
    }

    // Endpoints actions
    $validTypes = ['image', 'video', 'edit'];
    if ($action === 'add_endpoint') {
        if (!in_array($_POST['type'] ?? '', $validTypes, true)) { http_response_code(400); exit; }
        $db->prepare(
            'INSERT INTO endpoints (endpoint_id, api_key_id, type, name, steps, cfg, hint, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            trim($_POST['endpoint_id']),
            (int)$_POST['api_key_id'] ?: null,
            $_POST['type'],
            trim($_POST['name']),
            (int)($_POST['steps'] ?? 25),
            (float)($_POST['cfg'] ?? 7.0),
            trim($_POST['hint'] ?? ''),
            (int)($_POST['sort_order'] ?? 0),
        ]);
        header('Location: /admin/?tab=endpoints'); exit;
    }
    if ($action === 'update_endpoint') {
        if (!in_array($_POST['type'] ?? '', $validTypes, true)) { http_response_code(400); exit; }
        $db->prepare(
            'UPDATE endpoints SET api_key_id = ?, type = ?, name = ?, steps = ?, cfg = ?, hint = ?, sort_order = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            (int)$_POST['api_key_id'] ?: null,
            $_POST['type'],
            trim($_POST['name']),
            (int)($_POST['steps'] ?? 25),
            (float)($_POST['cfg'] ?? 7.0),
            trim($_POST['hint'] ?? ''),
            (int)($_POST['sort_order'] ?? 0),
            (int)$_POST['id'],
        ]);
        header('Location: /admin/?tab=endpoints'); exit;
    }
    if ($action === 'toggle_endpoint') {
        $id = (int)$_POST['id'];
        $db->prepare('UPDATE endpoints SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        header('Location: /admin/?tab=endpoints'); exit;
    }
    if ($action === 'delete_endpoint') {
        $id = (int)$_POST['id'];
        $db->prepare('DELETE FROM endpoints WHERE id = ?')->execute([$id]);
        header('Location: /admin/?tab=endpoints'); exit;
    }
}

$pageTitle = 'Admin';
$pageHeading = 'Admin';
require_once __DIR__ . '/../../templates/head.php';
require_once __DIR__ . '/../../templates/header.php';
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
  <a href="?tab=endpoints" class="<?= $tab === 'endpoints' ? 'active' : '' ?>">Endpoints</a>
  <a href="?tab=apikeys" class="<?= $tab === 'apikeys' ? 'active' : '' ?>">API Keys</a>
  <a href="?tab=reconcile" class="<?= $tab === 'reconcile' ? 'active' : '' ?>">Reconcile</a>
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
    <td><a href="/admin/user.php?id=<?= $r['id'] ?>" style="color:#8bb4ff;text-decoration:none;">#<?= $r['id'] ?></a></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><a href="/admin/user.php?id=<?= $r['id'] ?>" style="color:#ccc;text-decoration:none;"><?= htmlspecialchars($r['name']) ?></a></td>
    <td style="color:<?= $r['balance'] > 0 ? '#6bff9e' : '#888' ?>">$<?= number_format((float)$r['balance'], 4) ?></td>
    <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'jobs'): ?>
<?php
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$where = '';
$params = [];
if ($from) { $where .= ' AND j.created_at >= ?'; $params[] = $from . ':00'; }
if ($to)   { $where .= ' AND j.created_at <= ?'; $params[] = $to . ':59'; }

$stmt = $db->prepare("SELECT j.*, u.email, u.name AS user_name, e.name AS endpoint_name FROM jobs j JOIN users u ON j.user_id = u.id LEFT JOIN endpoints e ON j.endpoint_id = e.endpoint_id WHERE 1=1 {$where} ORDER BY j.id DESC LIMIT 500");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Aggregates
$totalRunpod = 0; $totalUser = 0; $totalTime = 0; $countDone = 0; $countPending = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'done' || $r['status'] === 'deleted') {
        $totalRunpod += (float)($r['cost_runpod'] ?? 0);
        $totalUser += (float)($r['cost_user'] ?? 0);
        $totalTime += (int)$r['execution_time'];
        $countDone++;
        if ($r['cost_user'] === null) $countPending++;
    }
}
$profit = $totalUser - $totalRunpod;
?>

<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;align-items:center;font-size:0.85rem;">
  <input type="hidden" name="tab" value="jobs">
  <label style="color:#888;">From</label>
  <input type="datetime-local" name="from" value="<?= htmlspecialchars($from) ?>" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;">
  <label style="color:#888;">To</label>
  <input type="datetime-local" name="to" value="<?= htmlspecialchars($to) ?>" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;">
  <button type="submit" style="padding:6px 16px;background:#4a6fa5;border:none;border-radius:4px;color:#fff;cursor:pointer;">Filter</button>
<?php if ($from || $to): ?>
  <a href="?tab=jobs" style="color:#888;text-decoration:none;font-size:0.8rem;">Clear</a>
<?php endif; ?>
</form>

<?php if ($countDone > 0): ?>
<div class="admin-stat" style="margin-bottom:16px;">
  <div><div class="num"><?= $countDone ?><?php if ($countPending): ?><span style="font-size:0.7rem;color:#888;"> (<?= $countPending ?> pending)</span><?php endif; ?></div><div class="label">Jobs</div></div>
  <div><div class="num" style="color:#ff6b6b;">$<?= number_format($totalRunpod, 4) ?></div><div class="label">RunPod Cost</div></div>
  <div><div class="num" style="color:#ffb86b;">$<?= number_format($totalUser, 4) ?></div><div class="label">User Charged</div></div>
  <div><div class="num" style="color:#6bff9e;">$<?= number_format($profit, 4) ?></div><div class="label">Profit</div></div>
  <div><div class="num"><?= number_format($totalTime / 1000, 1) ?>s</div><div class="label">Total GPU Time</div></div>
</div>
<?php endif; ?>

<table class="admin-table">
  <tr><th></th><th>ID</th><th>User</th><th>Type</th><th>Endpoint</th><th>Status</th><th>RunPod Cost</th><th>User Cost</th><th>Time(s)</th><th>Created</th></tr>
<?php foreach ($rows as $r):
    $hasFile = $r['output_path'] && file_exists(__DIR__ . '/../../' . $r['output_path']);
    $isDeleted = $r['status'] === 'deleted';
    $isTrash = $isDeleted && $hasFile;
?>
  <tr>
    <td style="width:40px;">
<?php if ($hasFile && $r['type'] !== 'video'): ?>
      <a href="/admin/job.php?id=<?= $r['id'] ?>"><img src="/admin/file.php?job_id=<?= $r['id'] ?>" style="width:36px;height:36px;object-fit:cover;border-radius:4px;cursor:pointer;<?= $isTrash ? 'opacity:0.2;' : '' ?>"></a>
<?php elseif ($hasFile): ?>
      <a href="/admin/job.php?id=<?= $r['id'] ?>" style="text-decoration:none;"><span style="font-size:1.2rem;<?= $isTrash ? 'opacity:0.2;' : '' ?>">&#9654;</span></a>
<?php elseif ($isDeleted): ?>
      <div style="width:36px;height:36px;background:#2a2a4a;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#555;font-size:0.7rem;">DEL</div>
<?php endif; ?>
    </td>
    <td><a href="/admin/job.php?id=<?= $r['id'] ?>" style="color:#8bb4ff;text-decoration:none;">#<?= $r['id'] ?></a></td>
    <td title="<?= htmlspecialchars($r['email']) ?>"><a href="/admin/user.php?id=<?= $r['user_id'] ?>" style="color:#ccc;text-decoration:none;"><?= htmlspecialchars($r['user_name'] ?: $r['email']) ?></a></td>
    <td><?= $r['type'] ?></td>
    <td title="<?= htmlspecialchars($r['endpoint_id']) ?>"><?= htmlspecialchars($r['endpoint_name'] ?? $r['endpoint_id']) ?></td>
    <td style="color:<?= $r['status'] === 'done' ? '#6bff9e' : ($r['status'] === 'failed' ? '#ff6b6b' : ($r['status'] === 'deleted' ? '#555' : '#888')) ?>"><?= $r['status'] ?></td>
<?php $dRunpod = $r['cost_runpod'] ?? $r['est_cost_runpod']; $dUser = $r['cost_user'] ?? $r['est_cost_user']; $isEst = $r['cost_runpod'] === null; ?>
    <td style="color:<?= $isEst ? '#ffb86b' : '#ccc' ?>"><?= $dRunpod !== null ? ($isEst ? '~' : '') . '$' . number_format((float)$dRunpod, 6) : '<span style="color:#555;">-</span>' ?></td>
    <td style="color:<?= $isEst ? '#ffb86b' : '#ccc' ?>"><?= $dUser !== null ? ($isEst ? '~' : '') . '$' . number_format((float)$dUser, 6) : '<span style="color:#555;">-</span>' ?></td>
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
<?php elseif ($tab === 'endpoints'): ?>
<?php
$apiKeys = $db->query('SELECT id, label FROM api_keys ORDER BY id')->fetchAll();
$rows = $db->query('SELECT e.*, a.label AS key_label FROM endpoints e LEFT JOIN api_keys a ON e.api_key_id = a.id ORDER BY e.type, e.sort_order')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
?>

<h3 style="color:#8bb4ff;font-size:0.95rem;margin-bottom:12px;">Add Endpoint</h3>
<form method="POST" style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;font-size:0.85rem;">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="action" value="add_endpoint">
  <input name="endpoint_id" placeholder="endpoint_id" required style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:140px;">
  <select name="api_key_id" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;">
    <option value="">-- API Key --</option>
    <?php foreach ($apiKeys as $k): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['label']) ?></option><?php endforeach; ?>
  </select>
  <select name="type" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;">
    <option value="image">image</option><option value="video">video</option><option value="edit">edit</option>
  </select>
  <input name="name" placeholder="Name" required style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:120px;">
  <input name="steps" type="number" value="25" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:60px;">
  <input name="cfg" type="number" step="0.1" value="7.0" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:60px;">
  <input name="hint" placeholder="hint" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:200px;">
  <input name="sort_order" type="number" value="0" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:50px;">
  <button type="submit" style="padding:6px 14px;background:#4a6fa5;border:none;border-radius:4px;color:#fff;cursor:pointer;">Add</button>
</form>

<table class="admin-table">
  <tr><th>ID</th><th>Endpoint ID</th><th>API Key</th><th>Type</th><th>Name</th><th>Steps</th><th>CFG</th><th>Hint</th><th>Order</th><th>Active</th><th></th></tr>
<?php foreach ($rows as $r): ?>
<?php if ($editId === (int)$r['id']): ?>
  <tr>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="update_endpoint">
      <input type="hidden" name="id" value="<?= $r['id'] ?>">
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['endpoint_id']) ?></td>
      <td><select name="api_key_id" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;font-size:0.8rem;">
        <option value="">--</option>
        <?php foreach ($apiKeys as $k): ?><option value="<?= $k['id'] ?>" <?= $k['id'] == $r['api_key_id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['label']) ?></option><?php endforeach; ?>
      </select></td>
      <td><select name="type" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;font-size:0.8rem;">
        <option value="image" <?= $r['type'] === 'image' ? 'selected' : '' ?>>image</option>
        <option value="video" <?= $r['type'] === 'video' ? 'selected' : '' ?>>video</option>
        <option value="edit" <?= $r['type'] === 'edit' ? 'selected' : '' ?>>edit</option>
      </select></td>
      <td><input name="name" value="<?= htmlspecialchars($r['name']) ?>" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:100px;font-size:0.8rem;"></td>
      <td><input name="steps" type="number" value="<?= $r['steps'] ?>" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:50px;font-size:0.8rem;"></td>
      <td><input name="cfg" type="number" step="0.1" value="<?= $r['cfg'] ?>" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:50px;font-size:0.8rem;"></td>
      <td><input name="hint" value="<?= htmlspecialchars($r['hint']) ?>" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:160px;font-size:0.8rem;"></td>
      <td><input name="sort_order" type="number" value="<?= $r['sort_order'] ?>" style="padding:4px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:40px;font-size:0.8rem;"></td>
      <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
      <td><button type="submit" style="padding:4px 10px;background:#4a6fa5;border:none;border-radius:4px;color:#fff;cursor:pointer;font-size:0.8rem;">Save</button> <a href="?tab=endpoints" style="color:#888;font-size:0.8rem;">Cancel</a></td>
    </form>
  </tr>
<?php else: ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td style="font-family:monospace;font-size:0.75rem;"><?= htmlspecialchars($r['endpoint_id']) ?></td>
    <td><?= htmlspecialchars($r['key_label'] ?? '-') ?></td>
    <td><?= $r['type'] ?></td>
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td><?= $r['steps'] ?></td>
    <td><?= $r['cfg'] ?></td>
    <td style="max-width:200px;" title="<?= htmlspecialchars($r['hint']) ?>"><?= htmlspecialchars($r['hint']) ?></td>
    <td><?= $r['sort_order'] ?></td>
    <td style="color:<?= $r['is_active'] ? '#6bff9e' : '#ff6b6b' ?>"><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
    <td style="white-space:nowrap;">
      <a href="?tab=endpoints&edit=<?= $r['id'] ?>" style="color:#8bb4ff;font-size:0.8rem;">Edit</a>
      <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="toggle_endpoint"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" style="background:none;border:none;color:#ffb86b;cursor:pointer;font-size:0.8rem;"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button></form>
      <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_endpoint"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" style="background:none;border:none;color:#ff6b6b;cursor:pointer;font-size:0.8rem;">Del</button></form>
    </td>
  </tr>
<?php endif; ?>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'apikeys'): ?>
<?php $rows = $db->query('SELECT a.*, (SELECT COUNT(*) FROM endpoints e WHERE e.api_key_id = a.id) AS endpoint_count FROM api_keys a ORDER BY a.id')->fetchAll(); ?>

<h3 style="color:#8bb4ff;font-size:0.95rem;margin-bottom:12px;">Add API Key</h3>
<form method="POST" style="display:flex;gap:6px;margin-bottom:20px;font-size:0.85rem;">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="action" value="add_apikey">
  <input name="label" placeholder="Label (e.g. RunPod Main)" required style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:180px;">
  <input name="api_key" placeholder="API Key" required style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:300px;">
  <input name="provider" value="runpod" placeholder="Provider" style="padding:6px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:4px;color:#e0e0e0;width:100px;">
  <button type="submit" style="padding:6px 14px;background:#4a6fa5;border:none;border-radius:4px;color:#fff;cursor:pointer;">Add</button>
</form>

<table class="admin-table">
  <tr><th>ID</th><th>Label</th><th>Provider</th><th>Key (masked)</th><th>Endpoints</th><th>Active</th><th>Created</th><th></th></tr>
<?php foreach ($rows as $r):
    $decrypted = getApiKey((int)$r['id']);
    $masked = $decrypted ? substr($decrypted, 0, 8) . '...' . substr($decrypted, -4) : '(error)';
?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['label']) ?></td>
    <td><?= htmlspecialchars($r['provider']) ?></td>
    <td style="font-family:monospace;font-size:0.75rem;"><?= htmlspecialchars($masked) ?></td>
    <td><?= $r['endpoint_count'] ?></td>
    <td style="color:<?= $r['is_active'] ? '#6bff9e' : '#ff6b6b' ?>"><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
    <td><?= $r['created_at'] ?></td>
    <td style="white-space:nowrap;">
      <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="toggle_apikey"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" style="background:none;border:none;color:#ffb86b;cursor:pointer;font-size:0.8rem;"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button></form>
<?php if ($r['endpoint_count'] == 0): ?>
      <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_apikey"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" style="background:none;border:none;color:#ff6b6b;cursor:pointer;font-size:0.8rem;">Del</button></form>
<?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<?php elseif ($tab === 'reconcile'): ?>
<?php
$lockFile = sys_get_temp_dir() . '/ciel_reconcile_last';
$lastRun = file_exists($lockFile) ? (int)file_get_contents($lockFile) : 0;
$cooldownRemaining = max(0, 900 - (time() - $lastRun));
$canRun = $cooldownRemaining === 0;
$unreconciledCount = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status IN ('done', 'deleted') AND cost_reconciled = 0")->fetchColumn();
?>

<div style="display:flex;gap:16px;align-items:center;margin-bottom:16px;">
  <form method="POST" style="margin:0;">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="run_reconcile">
    <button type="submit" <?= $canRun ? '' : 'disabled' ?> style="padding:8px 20px;background:<?= $canRun ? '#4a6fa5' : '#2a2a4a' ?>;border:none;border-radius:6px;color:<?= $canRun ? '#fff' : '#555' ?>;font-weight:600;cursor:<?= $canRun ? 'pointer' : 'not-allowed' ?>;font-size:0.85rem;">Run Reconcile Now</button>
  </form>
  <span style="font-size:0.8rem;color:#888;">
    <?php if ($canRun): ?>Ready
    <?php else: ?>Cooldown: <?= (int)ceil($cooldownRemaining / 60) ?>min remaining
    <?php endif; ?>
    &middot; <?= $unreconciledCount ?> unreconciled job(s)
  </span>
</div>
<?php if (!empty($_SESSION['reconcile_result'])): ?>
<pre style="background:#0d1b2a;border:1px solid #2a2a4a;border-radius:6px;padding:12px;font-size:0.8rem;color:#aaa;margin-bottom:16px;white-space:pre-wrap;max-height:200px;overflow-y:auto;"><?= htmlspecialchars($_SESSION['reconcile_result']) ?></pre>
<?php unset($_SESSION['reconcile_result']); endif; ?>

<?php $rows = $db->query('SELECT * FROM reconcile_log ORDER BY id DESC LIMIT 100')->fetchAll(); ?>
<table class="admin-table">
  <tr><th>ID</th><th>Date</th><th>Trigger</th><th>Adjusted</th><th>Skipped</th><th>Adjustment</th><th>EP Updated</th><th>API Calls</th><th>Duration</th><th>Error</th><th>Run At</th></tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= $r['target_date'] ?></td>
    <td><?= $r['trigger_source'] ?></td>
    <td style="color:<?= $r['jobs_adjusted'] > 0 ? '#6bff9e' : '#888' ?>"><?= $r['jobs_adjusted'] ?></td>
    <td style="color:<?= $r['jobs_skipped'] > 0 ? '#ffb86b' : '#888' ?>"><?= $r['jobs_skipped'] ?></td>
    <td style="color:<?= $r['total_adjustment'] > 0 ? '#ff6b6b' : '#888' ?>">$<?= number_format((float)$r['total_adjustment'], 6) ?></td>
    <td><?= $r['endpoints_updated'] ?></td>
    <td><?= $r['billing_api_calls'] ?></td>
    <td><?= $r['duration_ms'] ? number_format($r['duration_ms']) . 'ms' : '-' ?></td>
    <td style="color:#ff6b6b;"><?= htmlspecialchars($r['error'] ?? '') ?></td>
    <td><?= $r['created_at'] ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
