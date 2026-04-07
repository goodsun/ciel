<?php
require __DIR__ . '/../src/bootstrap.php';
if (empty($podImage)) { http_response_code(404); exit; }
$pageTitle = t('title_image');
$pageHeading = t('title_image');
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstModel = $podImage[0] ?? null;
?>

<?php if (!empty($podImage)): ?>
  <div class="model-selector">
<?php foreach ($podImage as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
      <?= htmlspecialchars($m['name']) ?>
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
    <details class="lora-section">
      <summary><?= t('lora_settings') ?></summary>
      <div class="field" style="margin-top:12px;">
        <label for="lora_url"><?= t('lora_url') ?></label>
        <input type="text" id="lora_url" placeholder="<?= t('lora_url_placeholder') ?>">
        <div class="hint"><?= t('lora_none') ?></div>
      </div>
      <div class="field">
        <label for="lora_strength"><?= t('lora_strength') ?>: <span id="lora_strength-val">0.8</span></label>
        <input type="range" id="lora_strength" min="-2.0" max="2.0" step="0.1" value="0.8">
      </div>
    </details>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" id="submitBtn"><?= t('generate') ?></button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>"><?= t('login_to_generate') ?></a>
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
], JSON_UNESCAPED_UNICODE) ?>;
const MODELS = <?= json_encode($podImage, JSON_UNESCAPED_UNICODE) ?>;
let polling = null;
let currentIndex = 0;
persistPrompts('prompt', 'negative');
persistFields(['width', 'height', 'steps', 'seed', 'cfg', 'quality', 'lora_url', 'lora_strength']);
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
    if (p.width) document.getElementById('width').value = p.width;
    if (p.height) document.getElementById('height').value = p.height;
    if (p.steps) document.getElementById('steps').value = p.steps;
    if (p.seed) document.getElementById('seed').value = p.seed;
    if (p.cfg) { document.getElementById('cfg').value = p.cfg; document.getElementById('cfg-val').textContent = p.cfg; }
    if (p.quality) { document.getElementById('quality').value = p.quality; document.getElementById('quality-val').textContent = p.quality; }
    if (p.lora_url) { document.getElementById('lora_url').value = p.lora_url; document.querySelector('.lora-section').open = true; }
    if (p.lora_strength) { document.getElementById('lora_strength').value = p.lora_strength; document.getElementById('lora_strength-val').textContent = p.lora_strength; }
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

  const loraUrl = document.getElementById('lora_url').value.trim();
  const loraStrength = parseFloat(document.getElementById('lora_strength').value);
  const inputData = {
    prompt, negative_prompt: document.getElementById('negative').value.trim() || undefined,
    width: parseInt(document.getElementById('width').value), height: parseInt(document.getElementById('height').value),
    steps: parseInt(document.getElementById('steps').value), seed: parseInt(document.getElementById('seed').value),
    cfg: parseFloat(document.getElementById('cfg').value), quality: parseInt(document.getElementById('quality').value),
    lora_url: loraUrl || undefined,
    lora_strength: loraUrl ? loraStrength : undefined
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = T.generating;
  document.getElementById('resultArea').style.display = 'none';
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
        showImage(imgUrl); btn.disabled = false; btn.textContent = T.generate;
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log(T.log_failed + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = T.generate;
      }
    } catch (e) { log(T.log_polling_error + e.message, 'error'); }
  }, 5000);
}

function showImage(dataUrl) {
  document.getElementById('resultImage').src = dataUrl;
  document.getElementById('downloadBtn').href = dataUrl;
  document.getElementById('resultArea').style.display = 'block';
  const m = MODELS[currentIndex];
  const a = document.createElement('a');
  a.href = dataUrl; a.download = m.name.replace(/\s+/g, '_') + '_' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19) + '.jpg'; a.click();
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
