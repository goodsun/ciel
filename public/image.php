<?php
require __DIR__ . '/../src/bootstrap.php';
if (empty($podImage)) { http_response_code(404); exit; }
$pageTitle = t('title_image');
$pageHeading = t('title_image');
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstModel = $podImage[0] ?? null;

// Load pending/processing image jobs for resume
$pendingJobs = [];
if (isLoggedIn()) {
    require_once __DIR__ . '/../src/db.php';
    $stmtPending = getDb()->prepare(
        'SELECT j.runpod_job_id, j.endpoint_id, j.created_at, e.name AS endpoint_name FROM jobs j LEFT JOIN endpoints e ON j.endpoint_id = e.endpoint_id WHERE j.user_id = ? AND j.type = ? AND j.status IN (?, ?) ORDER BY j.created_at DESC LIMIT 5'
    );
    $stmtPending->execute([$_SESSION['user']['id'], 'image', 'pending', 'processing']);
    $pendingJobs = $stmtPending->fetchAll();
}
?>

<?php if (!empty($podImage)): ?>
  <div class="model-selector">
<?php foreach ($podImage as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' disabled-ep' : '' ?>" data-index="<?= $i ?>">
      <?= htmlspecialchars($m['name']) ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' <span class="ep-off">OFF</span>' : '' ?>
      <span class="sub">CFG <?= $m['cfg'] ?> / <?= $m['steps'] ?> steps</span>
    </div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="panel" style="display:block;">
    <div class="field">
      <label for="prompt"><?= t('prompt') ?></label>
      <textarea id="prompt" placeholder="1girl, blue hair, cherry blossoms, garden, detailed illustration..."></textarea>
      <div class="hint" id="promptHint"><?= htmlspecialchars($firstModel['hint'] ?? '') ?></div>
    </div>
    <div class="field">
      <label for="negative"><?= t('negative_prompt') ?></label>
      <textarea id="negative" placeholder="lowres, bad anatomy, blurry..."></textarea>
    </div>
    <div class="row">
      <div class="field"><label for="width"><?= t('width') ?></label><input type="text" id="width" value="1024"></div>
      <div class="field"><label for="height"><?= t('height') ?></label><input type="text" id="height" value="1024"></div>
      <div class="field"><label for="steps"><?= t('steps') ?></label><input type="text" id="steps" value="<?= $firstModel['steps'] ?? 25 ?>"></div>
      <div class="field"><label for="seed"><?= t('seed') ?></label><input type="text" id="seed" value="42"></div>
    </div>
    <div class="row">
      <div class="field">
        <label for="cfg"><?= t('cfg') ?>: <span id="cfg-val"><?= $firstModel['cfg'] ?? 7.0 ?></span></label>
        <input type="range" id="cfg" min="1" max="15" step="0.5" value="<?= $firstModel['cfg'] ?? 7.0 ?>">
      </div>
      <div class="field">
        <label for="quality"><?= t('jpeg_quality') ?>: <span id="quality-val">90</span></label>
        <input type="range" id="quality" min="1" max="100" value="90">
      </div>
    </div>
    <details class="lora-section" id="loraSection">
      <summary><?= t('lora_settings') ?></summary>
      <div id="loraRows">
        <div class="lora-row" data-index="0">
          <div class="lora-row-header">
            <span class="lora-row-label">LoRA 1</span>
            <button type="button" class="lora-remove-btn" onclick="removeLoraRow(this)" title="<?= t('lora_remove') ?>">&times;</button>
          </div>
          <div class="field">
            <label><?= t('lora_url') ?></label>
            <input type="text" class="lora-url" placeholder="<?= t('lora_url_placeholder') ?>">
            <div class="hint"><?= t('lora_none') ?></div>
          </div>
          <div class="field">
            <label><?= t('lora_strength') ?>: <span class="lora-strength-val">0.8</span></label>
            <input type="range" class="lora-strength" min="-2.0" max="2.0" step="0.1" value="0.8">
          </div>
        </div>
      </div>
      <button type="button" class="lora-add-btn" id="loraAddBtn" onclick="addLoraRow()"><?= t('lora_add') ?></button>
    </details>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" id="submitBtn"><?= t('generate') ?></button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>"><?= t('login_to_generate') ?></a>
  </div>

  <style>
  .pending-jobs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
  .pending-job { padding: 6px 14px; background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 6px; font-size: 0.75rem; color: #888; cursor: pointer; transition: all 0.2s; }
  .pending-job.active { border-color: #8bb4ff; color: #8bb4ff; }
  .pending-job .pj-status { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #ffb86b; margin-right: 4px; animation: pulse 1.5s infinite; }
  .pending-job.done .pj-status { background: #6bff9e; animation: none; }
  .pending-job.failed .pj-status { background: #ff6b6b; animation: none; }
  @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.3; } }
  </style>

  <div class="log-area" id="logArea">
    <div class="pending-jobs" id="pendingJobs"></div>
    <h3><?= t('log') ?></h3>
    <div class="log" id="log"></div>
  </div>

  <div class="image-modal-overlay" id="imageModal" onclick="closeImageModal(event)">
    <div class="image-modal" onclick="event.stopPropagation()">
      <span class="image-modal-close" onclick="closeImageModal(event)">&times;</span>
      <img id="resultImage" style="max-width:100%;max-height:70vh;border-radius:4px;background:#000;">
      <div style="display:flex;gap:12px;margin-top:12px;justify-content:center;">
        <a class="download-btn" id="downloadBtn" download="output.jpg"><?= t('download') ?></a>
        <a class="download-btn" href="/generated.php" style="background:#2a2a4a;"><?= t('generated') ?></a>
      </div>
      <p style="color:#666;font-size:0.75rem;margin-top:12px;"><?= t('content_notice') ?></p>
    </div>
  </div>
  <style>
  .image-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1000; align-items:center; justify-content:center; cursor:pointer; }
  .image-modal-overlay.show { display:flex; }
  .image-modal { position:relative; max-width:90%; text-align:center; }
  .image-modal-close { position:absolute; top:-30px; right:0; color:#888; font-size:1.5rem; cursor:pointer; z-index:1001; }
  </style>

<script>
const T = <?= json_encode([
    'generate'          => t('generate'),
    'generating'        => t('generating'),
    'log_submitting'    => t('log_submitting'),
    'log_job_id'        => t('log_job_id'),
    'log_waiting'       => t('log_waiting'),
    'log_status'        => t('log_status'),
    'log_complete'      => t('log_complete'),
    'log_failed'        => t('log_failed'),
    'log_request_error' => t('log_request_error'),
    'log_polling_error' => t('log_polling_error'),
    'err_pod_config'    => t('err_pod_config'),
    'err_enter_prompt'  => t('err_enter_prompt'),
    'err_insufficient'  => t('err_insufficient'),
    'err_error'         => t('err_error'),
    '_lora_url'         => t('lora_url'),
    '_lora_url_placeholder' => t('lora_url_placeholder'),
    '_lora_strength'    => t('lora_strength'),
], JSON_UNESCAPED_UNICODE) ?>;
const MODELS = <?= json_encode($podImage, JSON_UNESCAPED_UNICODE) ?>;
const PENDING_JOBS = <?= json_encode($pendingJobs, JSON_UNESCAPED_UNICODE) ?>;
let polling = null;
let currentIndex = 0;
persistPrompts('prompt', 'negative');
persistFields(['width', 'height', 'steps', 'seed', 'cfg', 'quality']);
currentIndex = persistModel(function(idx) {
  currentIndex = idx;
  const m = MODELS[idx];
  document.getElementById('steps').value = m.steps;
  document.getElementById('cfg').value = m.cfg;
  document.getElementById('cfg-val').textContent = m.cfg;
  document.getElementById('promptHint').textContent = m.hint;
});

// Restore reused params from generated page
(function() {
  const saved = localStorage.getItem('ciel_reuse_image');
  if (saved) {
    localStorage.removeItem('ciel_reuse_image');
    const p = JSON.parse(saved);
    // Restore model first (sets defaults), then override with saved values
    if (p._endpoint_id) {
      const idx = MODELS.findIndex(m => m.id === p._endpoint_id);
      if (idx >= 0) {
        document.querySelectorAll('.model-btn').forEach(b => b.classList.remove('active'));
        const target = document.querySelector('.model-btn[data-index="' + idx + '"]');
        if (target) target.classList.add('active');
        currentIndex = idx;
        document.getElementById('promptHint').textContent = MODELS[idx].hint;
        localStorage.setItem('ciel_model_' + PAGE_KEY, String(idx));
      }
    }
    if (p.width) document.getElementById('width').value = p.width;
    if (p.height) document.getElementById('height').value = p.height;
    if (p.steps) document.getElementById('steps').value = p.steps;
    if (p.seed) document.getElementById('seed').value = p.seed;
    if (p.cfg) { document.getElementById('cfg').value = p.cfg; document.getElementById('cfg-val').textContent = p.cfg; }
    if (p.quality) { document.getElementById('quality').value = p.quality; document.getElementById('quality-val').textContent = p.quality; }
    if (p.loras && p.loras.length) {
      document.querySelector('.lora-section').open = true;
      p.loras.forEach((l, i) => {
        if (i > 0) addLoraRow();
        const rows = document.querySelectorAll('.lora-row');
        const row = rows[rows.length - 1];
        row.querySelector('.lora-url').value = l.url || '';
        if (l.strength != null) { row.querySelector('.lora-strength').value = l.strength; row.querySelector('.lora-strength-val').textContent = l.strength; }
      });
    } else if (p.lora_url) {
      document.querySelector('.lora-section').open = true;
      document.querySelector('.lora-row .lora-url').value = p.lora_url;
      if (p.lora_strength != null) { document.querySelector('.lora-row .lora-strength').value = p.lora_strength; document.querySelector('.lora-row .lora-strength-val').textContent = p.lora_strength; }
    }
  }
})();

// Model selector (click handler + persistence already set up by persistModel)
document.querySelectorAll('.model-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.model-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentIndex = parseInt(btn.dataset.index);
    const m = MODELS[currentIndex];
    document.getElementById('steps').value = m.steps;
    document.getElementById('cfg').value = m.cfg;
    document.getElementById('cfg-val').textContent = m.cfg;
    document.getElementById('promptHint').textContent = m.hint;
  });
});

// Range sliders
document.querySelectorAll('input[type="range"]').forEach(r => {
  r.addEventListener('input', () => {
    const valEl = document.getElementById(r.id + '-val');
    if (valEl) valEl.textContent = r.value;
  });
});

function log(msg, cls) {
  const area = document.getElementById('logArea');
  const el = document.getElementById('log');
  area.style.display = 'block';
  const time = new Date().toLocaleTimeString('ja-JP');
  const span = document.createElement('span');
  if (cls) span.className = cls;
  span.textContent = `[${time}] ${msg}\n`;
  el.appendChild(span);
  el.scrollTop = el.scrollHeight;
}

document.getElementById('submitBtn').addEventListener('click', async () => {
  const m = MODELS[currentIndex];
  if (!m.id) { alert(T.err_pod_config); return; }

  const prompt = document.getElementById('prompt').value.trim();
  if (!prompt) { alert(T.err_enter_prompt); return; }

  const loras = [];
  document.querySelectorAll('.lora-row').forEach(row => {
    const url = row.querySelector('.lora-url').value.trim();
    if (url) loras.push({ url, strength: parseFloat(row.querySelector('.lora-strength').value) });
  });
  const inputData = {
    prompt, negative_prompt: document.getElementById('negative').value.trim() || undefined,
    width: parseInt(document.getElementById('width').value), height: parseInt(document.getElementById('height').value),
    steps: parseInt(document.getElementById('steps').value), seed: parseInt(document.getElementById('seed').value),
    cfg: parseFloat(document.getElementById('cfg').value), quality: parseInt(document.getElementById('quality').value),
    loras: loras.length ? loras : undefined
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = T.generating;
  document.getElementById('imageModal').classList.remove('show');
  log(`[${m.name}] ${inputData.width}x${inputData.height}, steps=${inputData.steps}, cfg=${inputData.cfg}`);

  try {
    log(T.log_submitting);
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'image', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log(T.err_insufficient, 'error'); btn.disabled = false; btn.textContent = T.generate; return; }
    if (!data.id) { log(T.err_error + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = T.generate; return; }
    log(T.log_job_id + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log(T.log_request_error + e.message, 'error'); btn.disabled = false; btn.textContent = T.generate; }
});

function updatePendingStatus(idx, status) {
  const el = document.querySelector(`.pending-job[data-index="${idx}"]`);
  if (el) el.classList.add(status);
}

function pollStatus(endpointId, jobId, btn, pendingIdx) {
  if (polling) clearInterval(polling);
  log(T.log_waiting);
  polling = setInterval(async () => {
    try {
      const res = await fetch(`/api/status.php?endpoint_id=${endpointId}&job_id=${jobId}`);
      const data = await res.json();
      log(T.log_status + data.status);
      if (data.status === 'COMPLETED') {
        clearInterval(polling);
        const cost = data.cost_user ? ` / $${data.cost_user.toFixed(6)}` : '';
        log(`${T.log_complete}${data.executionTime}ms${cost}`, 'success');
        const imgUrl = data.job_db_id ? '/api/file.php?job_id=' + data.job_db_id : '';
        showImage(imgUrl); btn.disabled = false; btn.textContent = T.generate;
        if (pendingIdx !== undefined) updatePendingStatus(pendingIdx, 'done');
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log(T.log_failed + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = T.generate;
        if (pendingIdx !== undefined) updatePendingStatus(pendingIdx, 'failed');
      }
    } catch (e) { log(T.log_polling_error + e.message, 'error'); }
  }, 5000);
}

// LoRA multi-row management
const LORA_MAX = 10;
function addLoraRow() {
  const container = document.getElementById('loraRows');
  const rows = container.querySelectorAll('.lora-row');
  if (rows.length >= LORA_MAX) return;
  const idx = rows.length;
  const div = document.createElement('div');
  div.className = 'lora-row';
  div.dataset.index = idx;
  div.innerHTML = `
    <div class="lora-row-header">
      <span class="lora-row-label">LoRA ${idx + 1}</span>
      <button type="button" class="lora-remove-btn" onclick="removeLoraRow(this)" title="&times;">&times;</button>
    </div>
    <div class="field">
      <label>${T._lora_url || 'LoRA URL'}</label>
      <input type="text" class="lora-url" placeholder="${T._lora_url_placeholder || 'https://example.com/my-style.safetensors'}">
    </div>
    <div class="field">
      <label>${T._lora_strength || 'LoRA Strength'}: <span class="lora-strength-val">0.8</span></label>
      <input type="range" class="lora-strength" min="-2.0" max="2.0" step="0.1" value="0.8">
    </div>`;
  container.appendChild(div);
  div.querySelector('.lora-strength').addEventListener('input', function() {
    this.closest('.field').querySelector('.lora-strength-val').textContent = this.value;
  });
  updateLoraLabels();
  if (container.querySelectorAll('.lora-row').length >= LORA_MAX) document.getElementById('loraAddBtn').style.display = 'none';
}
function removeLoraRow(btn) {
  const row = btn.closest('.lora-row');
  const container = document.getElementById('loraRows');
  if (container.querySelectorAll('.lora-row').length <= 1) {
    row.querySelector('.lora-url').value = '';
    row.querySelector('.lora-strength').value = 0.8;
    row.querySelector('.lora-strength-val').textContent = '0.8';
    return;
  }
  row.remove();
  updateLoraLabels();
  document.getElementById('loraAddBtn').style.display = '';
}
function updateLoraLabels() {
  document.querySelectorAll('.lora-row').forEach((row, i) => {
    row.dataset.index = i;
    row.querySelector('.lora-row-label').textContent = 'LoRA ' + (i + 1);
  });
}
// Wire up range sliders for dynamically added rows
document.getElementById('loraRows').addEventListener('input', function(e) {
  if (e.target.classList.contains('lora-strength')) {
    e.target.closest('.field').querySelector('.lora-strength-val').textContent = e.target.value;
  }
});

function showImage(dataUrl) {
  const m = MODELS[currentIndex];
  const filename = m.name.replace(/\s+/g, '_') + '_' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19) + '.jpg';
  document.getElementById('resultImage').src = dataUrl;
  const dl = document.getElementById('downloadBtn');
  dl.href = dataUrl;
  dl.download = filename;
  document.getElementById('imageModal').classList.add('show');
}
function closeImageModal(e) {
  document.getElementById('imageModal').classList.remove('show');
}

// Resume polling for pending/processing jobs from previous session
if (PENDING_JOBS.length > 0) {
  const container = document.getElementById('pendingJobs');
  document.getElementById('logArea').style.display = 'block';

  PENDING_JOBS.forEach((job, i) => {
    const el = document.createElement('div');
    el.className = 'pending-job' + (i === 0 ? ' active' : '');
    el.dataset.index = i;
    const time = job.created_at.slice(11, 16);
    const model = job.endpoint_name || '';
    el.innerHTML = `<span class="pj-status"></span>${model} ${time} ${job.runpod_job_id.slice(0, 6)}`;
    el.addEventListener('click', () => switchPendingJob(i));
    container.appendChild(el);
  });

  function switchPendingJob(idx) {
    if (polling) clearInterval(polling);
    container.querySelectorAll('.pending-job').forEach(el => el.classList.remove('active'));
    container.querySelector(`[data-index="${idx}"]`).classList.add('active');
    const job = PENDING_JOBS[idx];
    document.getElementById('log').textContent = '';
    log(`Resuming job: ${job.runpod_job_id} (${job.created_at})`);
    pollStatus(job.endpoint_id, job.runpod_job_id, document.getElementById('submitBtn'), idx);
  }

  // Auto-resume the latest
  switchPendingJob(0);
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
