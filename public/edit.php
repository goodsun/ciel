<?php
require __DIR__ . '/../src/bootstrap.php';
if (empty($podEdit)) { http_response_code(404); exit; }
$pageTitle = t('title_edit');
$pageHeading = t('title_edit');
$pageStyles = ':root { --accent: #c4a0ff; } .panel { background: #1e1a2e; } .api-settings { background: #0d0b1a; } .api-settings input { background: #1e1a2e; } .field input[type="text"], .field textarea { background: #0d0b1a; } .submit-btn { background: linear-gradient(135deg, #6a4fa5, #c4a0ff); }';
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstEdit = $podEdit[0] ?? null;
?>

<?php if (count($podEdit) > 1): ?>
  <div class="model-selector">
<?php foreach ($podEdit as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' disabled-ep' : '' ?>" data-index="<?= $i ?>"><?= htmlspecialchars($m['name']) ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' <span class="ep-off">OFF</span>' : '' ?></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="panel" style="display:block;">
    <div class="field">
      <label for="file-edit"><?= t('source_image') ?></label>
      <div class="drop-zone" id="drop-edit">
        <input type="file" id="file-edit" accept="image/*">
        <span class="placeholder"><?= t('drop_image') ?></span>
      </div>
    </div>
    <div class="field">
      <label for="prompt"><?= t('edit_instruction') ?></label>
      <textarea id="prompt" placeholder="remove the sunglasses / make it a watercolor painting / change the background to a beach..."></textarea>
<?php if ($firstEdit && $firstEdit['hint']): ?>
      <div class="hint"><?= htmlspecialchars($firstEdit['hint']) ?></div>
<?php else: ?>
      <div class="hint"><?= htmlspecialchars($firstEdit['name'] ?? 'Editor') ?></div>
<?php endif; ?>
    </div>
    <div class="field">
      <label for="negative"><?= t('negative_prompt') ?></label>
      <textarea id="negative" placeholder="lowres, bad anatomy, blurry..."></textarea>
    </div>
    <div class="row">
      <div class="field"><label for="steps"><?= t('steps') ?></label><input type="text" id="steps" value="<?= $firstEdit['steps'] ?? 20 ?>"></div>
      <div class="field"><label for="seed"><?= t('seed') ?></label><input type="text" id="seed" value="42"></div>
      <div class="field">
        <label for="quality"><?= t('jpeg_quality') ?>: <span id="quality-val">90</span></label>
        <input type="range" id="quality" min="1" max="100" value="90">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" id="submitBtn"><?= t('edit_start') ?></button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>"><?= t('login_to_edit') ?></a>
  </div>

  <div class="log-area" id="logArea">
    <h3><?= t('log') ?></h3>
    <div class="log" id="log"></div>
  </div>

  <div class="result-area" id="resultArea">
    <img id="resultImage">
    <a class="download-btn" id="downloadBtn" download="output.jpg"><?= t('download') ?></a>
  </div>

<script>
const T = <?= json_encode([
    'edit_start'         => t('edit_start'),
    'editing'            => t('editing'),
    'log_submitting'     => t('log_submitting'),
    'log_job_id'         => t('log_job_id'),
    'log_waiting'        => t('log_waiting'),
    'log_status'         => t('log_status'),
    'log_complete'       => t('log_complete'),
    'log_failed'         => t('log_failed'),
    'log_request_error'  => t('log_request_error'),
    'log_polling_error'  => t('log_polling_error'),
    'err_pod_config'     => t('err_pod_config'),
    'err_select_source'  => t('err_select_source'),
    'err_enter_instruction' => t('err_enter_instruction'),
    'err_insufficient'   => t('err_insufficient'),
    'err_error'          => t('err_error'),
], JSON_UNESCAPED_UNICODE) ?>;
const MODELS = <?= json_encode($podEdit, JSON_UNESCAPED_UNICODE) ?>;
let editImage = null;
let polling = null;
let currentIndex = 0;
currentIndex = persistModel(function(idx) {
  currentIndex = idx;
  const m = MODELS[idx];
  document.getElementById('steps').value = m.steps;
});
persistPrompts('prompt', 'negative');
persistFields(['steps', 'seed', 'quality']);

// Restore reused params
(function() {
  const saved = localStorage.getItem('ciel_reuse_edit');
  if (saved) {
    localStorage.removeItem('ciel_reuse_edit');
    const p = JSON.parse(saved);
    if (p.steps) document.getElementById('steps').value = p.steps;
    if (p.seed) document.getElementById('seed').value = p.seed;
    if (p.quality) { document.getElementById('quality').value = p.quality; document.getElementById('quality-val').textContent = p.quality; }
  }
})();

// Model selector (if multiple)
document.querySelectorAll('.model-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.model-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentIndex = parseInt(btn.dataset.index);
    const m = MODELS[currentIndex];
    document.getElementById('steps').value = m.steps;
  });
});

document.querySelectorAll('input[type="range"]').forEach(r => {
  r.addEventListener('input', () => {
    const valEl = document.getElementById(r.id + '-val');
    if (valEl) valEl.textContent = r.value;
  });
});

const zone = document.getElementById('drop-edit');
const fileInput = document.getElementById('file-edit');
zone.addEventListener('click', () => fileInput.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('dragover');
  if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', () => { if (fileInput.files.length) handleFile(fileInput.files[0]); });

function handleFile(file) {
  const reader = new FileReader();
  reader.onload = e => {
    editImage = e.target.result.split(',')[1];
    zone.innerHTML = `<img src="${e.target.result}">`;
  };
  reader.readAsDataURL(file);
}

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
  if (!m || !m.id) { alert(T.err_pod_config); return; }
  if (!editImage) { alert(T.err_select_source); return; }

  const prompt = document.getElementById('prompt').value.trim();
  if (!prompt) { alert(T.err_enter_instruction); return; }

  const inputData = {
    prompt, negative_prompt: document.getElementById('negative').value.trim() || undefined,
    image_base64: editImage, steps: parseInt(document.getElementById('steps').value),
    seed: parseInt(document.getElementById('seed').value), quality: parseInt(document.getElementById('quality').value)
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = T.editing;
  document.getElementById('resultArea').style.display = 'none';
  log(`edit: steps=${inputData.steps}, seed=${inputData.seed}`);

  try {
    log(T.log_submitting);
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'edit', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log(T.err_insufficient, 'error'); btn.disabled = false; btn.textContent = T.edit_start; return; }
    if (!data.id) { log(T.err_error + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = T.edit_start; return; }
    log(T.log_job_id + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log(T.log_request_error + e.message, 'error'); btn.disabled = false; btn.textContent = T.edit_start; }
});

function pollStatus(endpointId, jobId, btn) {
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
        showImage(imgUrl); btn.disabled = false; btn.textContent = T.edit_start;
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log(T.log_failed + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = T.edit_start;
      }
    } catch (e) { log(T.log_polling_error + e.message, 'error'); }
  }, 5000);
}

function showImage(dataUrl) {
  document.getElementById('resultImage').src = dataUrl;
  document.getElementById('downloadBtn').href = dataUrl;
  document.getElementById('resultArea').style.display = 'block';
  const a = document.createElement('a');
  a.href = dataUrl; a.download = 'edit_' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19) + '.jpg'; a.click();
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
