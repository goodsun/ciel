<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$adminIds = array_filter(explode(',', getenv('ADMIN_GOOGLE_IDS') ?: ''));
if (!in_array($_SESSION['user']['google_id'], $adminIds, true)) {
    http_response_code(403); echo 'Access denied'; exit;
}

$jobId = (int)($_GET['id'] ?? 0);
if (!$jobId) { header('Location: /admin/?tab=jobs'); exit; }

$db = getDb();
$stmt = $db->prepare('SELECT j.*, u.email, u.name as user_name FROM jobs j JOIN users u ON j.user_id = u.id WHERE j.id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) { header('Location: /admin/?tab=jobs'); exit; }

$params = json_decode($job['params'], true);
$hasFile = $job['output_path'] && file_exists(__DIR__ . '/../../' . $job['output_path']);
$isDeleted = $job['status'] === 'deleted';

// Related transaction
$stmt = $db->prepare('SELECT * FROM transactions WHERE job_id = ? LIMIT 1');
$stmt->execute([$jobId]);
$txn = $stmt->fetch();

// Handle purge file request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purge_file') {
    if ($job['output_path']) {
        $fullPath = __DIR__ . '/../../' . $job['output_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    header('Location: /admin/job.php?id=' . $jobId);
    exit;
}

$pageTitle = 'Job #' . $jobId;
$pageHeading = 'Admin';
require_once __DIR__ . '/../../templates/head.php';
require_once __DIR__ . '/../../templates/header.php';
?>

<style>
.job-detail { background:#16213e; border:1px solid #2a2a4a; border-radius:8px; padding:24px; }
.job-detail h2 { color:#8bb4ff; font-size:1.1rem; margin-bottom:16px; }
.job-row { display:flex; gap:24px; margin-bottom:24px; }
.job-meta { flex:1; }
.job-preview { flex:1; text-align:center; }
.job-preview img, .job-preview video { max-width:100%; max-height:400px; border-radius:8px; }
.meta-table { width:100%; font-size:0.85rem; }
.meta-table td { padding:6px 8px; border-bottom:1px solid #1a1a2e; }
.meta-table td:first-child { color:#888; width:140px; white-space:nowrap; }
.meta-table td:last-child { color:#ccc; word-break:break-all; }
.params-box { background:#0d1b2a; border:1px solid #2a2a4a; border-radius:6px; padding:12px; font-family:'SF Mono',Monaco,monospace; font-size:0.8rem; color:#aaa; white-space:pre-wrap; word-break:break-all; max-height:300px; overflow-y:auto; margin-top:16px; }
.back-link { display:inline-block; margin-bottom:16px; color:#888; text-decoration:none; font-size:0.85rem; }
.back-link:hover { color:#8bb4ff; }
</style>

<a href="/admin/?tab=jobs" class="back-link">&larr; Back to Jobs</a>

<div class="job-detail">
  <h2>Job #<?= $job['id'] ?>
    <span style="font-size:0.85rem;margin-left:8px;color:<?= $job['status'] === 'done' ? '#6bff9e' : ($job['status'] === 'failed' ? '#ff6b6b' : ($isDeleted ? '#555' : '#888')) ?>"><?= $job['status'] ?></span>
  </h2>

  <div class="job-row">
    <div class="job-meta">
      <table class="meta-table">
        <tr><td>User</td><td><?= htmlspecialchars($job['user_name']) ?> (<?= htmlspecialchars($job['email']) ?>)</td></tr>
        <tr><td>User ID</td><td><?= $job['user_id'] ?></td></tr>
        <tr><td>Type</td><td><?= $job['type'] ?></td></tr>
        <tr><td>Endpoint</td><td><?= htmlspecialchars($job['endpoint_id']) ?></td></tr>
        <tr><td>RunPod Job ID</td><td><?= htmlspecialchars($job['runpod_job_id'] ?? '-') ?></td></tr>
        <tr><td>Execution Time</td><td><?= $job['execution_time'] ? number_format($job['execution_time'] / 1000, 1) . 's (' . number_format($job['execution_time']) . 'ms)' : '-' ?></td></tr>
        <tr><td>RunPod Cost</td><td><?= $job['cost_runpod'] !== null ? '$' . number_format((float)$job['cost_runpod'], 6) : '<span style="color:#888;">pending</span>' ?></td></tr>
        <tr><td>User Cost</td><td style="color:<?= $job['cost_user'] !== null ? '#ff6b6b' : '#888' ?>"><?= $job['cost_user'] !== null ? '$' . number_format((float)$job['cost_user'], 6) : 'pending' ?></td></tr>
        <tr><td>Est. RunPod</td><td style="color:#888;"><?= $job['est_cost_runpod'] !== null ? '$' . number_format((float)$job['est_cost_runpod'], 6) : '-' ?></td></tr>
        <tr><td>Est. User</td><td style="color:#888;"><?= $job['est_cost_user'] !== null ? '$' . number_format((float)$job['est_cost_user'], 6) : '-' ?></td></tr>
        <tr><td>Reconciled</td><td><?= $job['cost_reconciled'] ? 'Yes' : '<span style="color:#ffb86b;">Pending</span>' ?></td></tr>
        <tr><td>Output Path</td><td><?= htmlspecialchars($job['output_path'] ?? '-') ?></td></tr>
        <tr><td>Created</td><td><?= $job['created_at'] ?></td></tr>
        <tr><td>Updated</td><td><?= $job['updated_at'] ?></td></tr>
      </table>

<?php if ($txn): ?>
      <h3 style="color:#8bb4ff;font-size:0.9rem;margin-top:20px;margin-bottom:8px;">Transaction</h3>
      <table class="meta-table">
        <tr><td>ID</td><td>#<?= $txn['id'] ?></td></tr>
        <tr><td>Amount</td><td style="color:<?= $txn['amount'] >= 0 ? '#6bff9e' : '#ff6b6b' ?>"><?= $txn['amount'] >= 0 ? '+' : '' ?>$<?= number_format((float)$txn['amount'], 6) ?></td></tr>
        <tr><td>Balance After</td><td>$<?= number_format((float)$txn['balance'], 6) ?></td></tr>
        <tr><td>Note</td><td><?= htmlspecialchars($txn['note'] ?? '') ?></td></tr>
        <tr><td>Date</td><td><?= $txn['created_at'] ?></td></tr>
      </table>
<?php endif; ?>
    </div>

    <div class="job-preview">
<?php if ($hasFile && $job['type'] === 'video'): ?>
      <video controls src="/admin/file.php?job_id=<?= $job['id'] ?>" style="<?= $isDeleted ? 'opacity:0.4;' : '' ?>"></video>
<?php elseif ($hasFile): ?>
      <img src="/admin/file.php?job_id=<?= $job['id'] ?>" style="<?= $isDeleted ? 'opacity:0.4;' : '' ?>">
<?php elseif ($isDeleted): ?>
      <div style="width:200px;height:200px;background:#2a2a4a;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#555;font-size:1rem;margin:0 auto;">Deleted</div>
<?php else: ?>
      <div style="width:200px;height:200px;background:#2a2a4a;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#555;font-size:0.85rem;margin:0 auto;">No output</div>
<?php endif; ?>
<?php if ($isDeleted && $hasFile): ?>
      <div style="color:#555;font-size:0.8rem;margin-top:8px;">In trash</div>
      <form method="POST" style="margin-top:6px;" onsubmit="return confirm('Delete this file permanently?')">
        <input type="hidden" name="action" value="purge_file">
        <button type="submit" style="background:none;border:1px solid #ff6b6b44;color:#ff6b6b;padding:4px 12px;border-radius:4px;cursor:pointer;font-size:0.75rem;">
          &#128465; Purge File
        </button>
      </form>
<?php endif; ?>
    </div>
  </div>

  <h3 style="color:#8bb4ff;font-size:0.9rem;margin-bottom:8px;">Parameters</h3>
  <div class="params-box"><?= htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>

  <div style="margin-top:16px;">
    <button onclick='reuseParams(<?= htmlspecialchars(json_encode($params), ENT_QUOTES) ?>, <?= json_encode($job["type"]) ?>, <?= json_encode($job["endpoint_id"]) ?>)'
            style="background:#2a2a4a;border:1px solid #3a3a5a;color:#8bb4ff;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:0.85rem;">
      &#8634; Reuse Settings
    </button>
  </div>
</div>

<script>
function reuseParams(params, type, endpointId) {
  if (endpointId) params._endpoint_id = endpointId;
  if (type === 'image' || type === 'edit') {
    const key = 'ciel_prompt_' + type;
    localStorage.setItem(key, JSON.stringify({ p: params.prompt || '', n: params.negative_prompt || '' }));
    localStorage.setItem('ciel_reuse_' + type, JSON.stringify(params));
    window.location.href = '/' + type + '.php';
  } else if (type === 'video') {
    localStorage.setItem('ciel_prompt_video_i2v', JSON.stringify({ p: params.prompt || '', n: '' }));
    localStorage.setItem('ciel_reuse_video', JSON.stringify(params));
    window.location.href = '/video.php';
  }
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
