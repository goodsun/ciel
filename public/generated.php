<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
requireLogin();

$pageTitle = t('title_generated');
$pageHeading = t('title_generated');
require_once __DIR__ . '/../templates/head.php';
require_once __DIR__ . '/../templates/header.php';

$userId = $_SESSION['user']['id'];
$db = getDb();
$perPage = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$stmtCount = $db->prepare('SELECT COUNT(*) FROM jobs WHERE user_id = ? AND status = ?');
$stmtCount->execute([$userId, 'done']);
$totalJobs = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalJobs / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare('SELECT j.*, e.name AS endpoint_name FROM jobs j LEFT JOIN endpoints e ON j.endpoint_id = e.endpoint_id WHERE j.user_id = ? AND j.status = ? ORDER BY j.created_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$userId, 'done', $perPage, $offset]);
$jobs = $stmt->fetchAll();
?>

<style>
.gen-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.gen-card { background: #16213e; border: 1px solid #2a2a4a; border-radius: 8px; overflow: hidden; position: relative; }
.gen-delete { position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.6); border:none; color:#888; font-size:1rem; width:28px; height:28px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s; z-index:1; }
.gen-card:hover .gen-delete { opacity:1; }
.gen-delete:hover { color:#ff6b6b; background:rgba(0,0,0,0.8); }
.gen-card img, .gen-card video { width: 100%; display: block; cursor: pointer; }
.gen-info { padding: 10px; font-size: 0.8rem; color: #888; }
.lightbox { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; align-items:center; justify-content:center; cursor:pointer; }
.lightbox.show { display:flex; }
.lightbox img, .lightbox video { max-width:95%; max-height:95%; object-fit:contain; border-radius:4px; }
.lightbox .lb-info { position:fixed; bottom:16px; left:0; width:100%; text-align:center; color:#888; font-size:0.8rem; padding:0 16px; }
.lightbox .lb-close { position:fixed; top:16px; right:24px; color:#888; font-size:1.5rem; cursor:pointer; z-index:1001; }
.gen-info .cost { color: #ff6b6b; }
.gen-info .type { color: #8bb4ff; text-transform: uppercase; font-size: 0.7rem; }
.gen-info .time { color: #6bff9e; }
.gen-prompt { padding: 0 10px 10px; font-size: 0.75rem; color: #666; word-break: break-all; max-height: 40px; overflow: hidden; transition: color 0.2s; }
.gen-prompt:hover { color: #8bb4ff; }
</style>

<?php if (empty($jobs)): ?>
  <p style="color:#666;text-align:center;padding:48px 0;"><?= t('no_generated') ?></p>
<?php else: ?>
<?php $hasPending = false; foreach ($jobs as $j) { if (!$j['cost_reconciled']) { $hasPending = true; break; } } ?>
<?php if ($hasPending): ?>
  <div style="background:#1a1a2e;border:1px solid #2a2a4a;border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:0.8rem;color:#888;">
    <?= t('cost_estimate_notice') ?>
  </div>
<?php endif; ?>
  <div class="gen-grid">
<?php foreach ($jobs as $job):
    $params = json_decode($job['params'], true);
    $prompt = $params['prompt'] ?? '';
    $ext = $job['type'] === 'video' ? 'mp4' : 'jpg';
    $filePath = __DIR__ . '/../storage/users/' . $userId . '/generates/' . $job['id'] . '.' . $ext;
    $hasFile = file_exists($filePath);
?>
    <div class="gen-card" id="card-<?= $job['id'] ?>">
      <button class="gen-delete" onclick="deleteJob(<?= $job['id'] ?>, event)" title="Delete">&#128465;</button>
<?php if ($hasFile && $job['type'] === 'video'): ?>
      <video controls preload="metadata" src="/api/file.php?job_id=<?= $job['id'] ?>" onclick="openLightbox(this, 'video', '<?= htmlspecialchars($prompt, ENT_QUOTES) ?>')"></video>
<?php elseif ($hasFile): ?>
      <img src="/api/file.php?job_id=<?= $job['id'] ?>" loading="lazy" onclick="openLightbox(this, 'image', '<?= htmlspecialchars($prompt, ENT_QUOTES) ?>')">
<?php else: ?>
      <div style="padding:40px;text-align:center;color:#555;">File not found</div>
<?php endif; ?>
      <div class="gen-info">
        <span class="type"><?= htmlspecialchars($job['type']) ?></span>
<?php if (!empty($job['model_name'])): ?>
        <span style="color:#c4a7e7;font-size:0.7rem;"><?= htmlspecialchars($job['model_name']) ?></span>
<?php else: ?>
        <span style="color:#aaa;font-size:0.7rem;"><?= htmlspecialchars($job['endpoint_name'] ?? '') ?></span>
<?php endif; ?>
        <span class="time"><?= number_format($job['execution_time'] / 1000, 1) ?>s</span>
<?php $displayCost = $job['cost_user'] ?? $job['est_cost_user']; ?>
<?php if ($job['cost_reconciled']): ?>
        <span style="color:#6bff9e;">$<?= number_format((float)$displayCost, 4) ?></span>
<?php elseif ($displayCost !== null): ?>
        <span style="color:#ffb86b;" title="<?= t('cost_estimate_notice') ?>">(est.) $<?= number_format((float)$displayCost, 4) ?></span>
<?php else: ?>
        <span style="color:#555;">pending</span>
<?php endif; ?>
        <br><?= date('m/d H:i', strtotime($job['created_at'])) ?>
      </div>
      <div class="gen-prompt" style="cursor:pointer;" title="Click to reuse"
           onclick='reuseParams(<?= htmlspecialchars(json_encode($params), ENT_QUOTES) ?>, <?= json_encode($job["type"]) ?>, <?= json_encode($job["endpoint_id"]) ?>)'>
        <?= htmlspecialchars(mb_substr($prompt, 0, 80)) ?>
      </div>
    </div>
<?php endforeach; ?>
  </div>

<?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:24px;font-size:0.85rem;">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" style="color:#8bb4ff;text-decoration:none;">&laquo; Prev</a>
    <?php endif; ?>
    <?php
      $start = max(1, $page - 3);
      $end = min($totalPages, $page + 3);
      if ($start > 1) echo '<a href="?page=1" style="color:#888;text-decoration:none;padding:4px 8px;">1</a>';
      if ($start > 2) echo '<span style="color:#555;">...</span>';
      for ($i = $start; $i <= $end; $i++):
    ?>
      <?php if ($i === $page): ?>
        <span style="color:#fff;background:#2a2a4a;padding:4px 10px;border-radius:4px;"><?= $i ?></span>
      <?php else: ?>
        <a href="?page=<?= $i ?>" style="color:#888;text-decoration:none;padding:4px 8px;"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor;
      if ($end < $totalPages - 1) echo '<span style="color:#555;">...</span>';
      if ($end < $totalPages) echo '<a href="?page=' . $totalPages . '" style="color:#888;text-decoration:none;padding:4px 8px;">' . $totalPages . '</a>';
    ?>
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>" style="color:#8bb4ff;text-decoration:none;">Next &raquo;</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php endif; ?>

<div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
  <span class="lb-close" onclick="closeLightbox(event)">&times;</span>
  <div id="lbContent"></div>
  <div class="lb-info" id="lbInfo"></div>
</div>

<script>
const T = <?= json_encode([
    'delete_confirm'  => t('delete_confirm'),
    'err_delete_failed' => t('err_delete_failed'),
], JSON_UNESCAPED_UNICODE) ?>;

async function deleteJob(jobId, e) {
  e.stopPropagation();
  if (!confirm(T.delete_confirm)) return;
  const res = await fetch('/api/delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ job_id: jobId })
  });
  if (res.ok) {
    const card = document.getElementById('card-' + jobId);
    card.style.transition = 'opacity 0.3s';
    card.style.opacity = '0';
    setTimeout(() => card.remove(), 300);
  } else {
    alert(T.err_delete_failed);
  }
}

function reuseParams(params, type, endpointId) {
  if (endpointId) params._endpoint_id = endpointId;
  if (type === 'image' || type === 'edit') {
    const key = 'ciel_prompt_' + type;
    localStorage.setItem(key, JSON.stringify({ p: params.prompt || '', n: params.negative_prompt || '' }));
    // Save additional params
    localStorage.setItem('ciel_reuse_' + type, JSON.stringify(params));
    window.location.href = '/' + type + '.php';
  } else if (type === 'video') {
    localStorage.setItem('ciel_prompt_video_i2v', JSON.stringify({ p: params.prompt || '', n: '' }));
    localStorage.setItem('ciel_reuse_video', JSON.stringify(params));
    window.location.href = '/video.php';
  }
}

function openLightbox(el, type, prompt) {
  const lb = document.getElementById('lightbox');
  const content = document.getElementById('lbContent');
  const info = document.getElementById('lbInfo');
  if (type === 'video') {
    content.innerHTML = '<video controls autoplay src="' + el.src + '" style="max-width:95%;max-height:90vh;border-radius:4px;"></video>';
  } else {
    content.innerHTML = '<img src="' + el.src + '" style="max-width:95%;max-height:90vh;border-radius:4px;">';
  }
  info.textContent = prompt;
  lb.classList.add('show');
}
function closeLightbox(e) {
  if (e.target.tagName === 'VIDEO' || e.target.tagName === 'IMG') return;
  const lb = document.getElementById('lightbox');
  lb.classList.remove('show');
  const v = lb.querySelector('video');
  if (v) v.pause();
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeLightbox(e);
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
