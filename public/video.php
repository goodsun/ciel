<?php
require __DIR__ . '/../src/bootstrap.php';
if (empty($podVideo)) { http_response_code(404); exit; }
$pageTitle = t('title_video');
$pageHeading = t('title_video');
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstVideo = $podVideo[0] ?? null;
?>

<?php if (count($podVideo) > 1): ?>
  <div class="model-selector">
<?php foreach ($podVideo as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' disabled-ep' : '' ?>" data-index="<?= $i ?>"><?= htmlspecialchars($m['name']) ?><?= empty($m['is_active']) && isset($m['is_active']) ? ' <span class="ep-off">OFF</span>' : '' ?></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="tabs">
    <div class="tab active" data-tab="i2v"><?= t('tab_i2v') ?></div>
    <div class="tab" data-tab="flf2v"><?= t('tab_flf2v') ?></div>
  </div>

  <!-- I2V Panel -->
  <div class="panel active" id="panel-i2v" style="display:block;border-radius:0 4px 4px 4px;border-top:none;">
    <div class="field">
      <label for="file-i2v"><?= t('input_image') ?></label>
      <div class="drop-zone" id="drop-i2v">
        <input type="file" id="file-i2v" accept="image/*">
        <span class="placeholder"><?= t('drop_image') ?></span>
      </div>
    </div>
    <div class="field">
      <label for="prompt-i2v"><?= t('prompt') ?></label>
      <textarea id="prompt-i2v" placeholder="a girl in kimono gently picks up a clay bowl..."></textarea>
    </div>
    <div class="row">
      <div class="field">
        <label for="seconds-i2v"><?= t('seconds') ?>: <span id="sec-val-i2v">5</span><?= t('sec_suffix') ?></label>
        <input type="range" id="seconds-i2v" min="1" max="10" value="5">
      </div>
      <div class="field">
        <label for="steps-i2v"><?= t('steps') ?></label>
        <input type="text" id="steps-i2v" value="10">
      </div>
      <div class="field">
        <label for="cfg-i2v"><?= t('cfg') ?></label>
        <input type="text" id="cfg-i2v" value="3.0">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" onclick="submitJob('i2v')"><?= t('generate') ?></button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>"><?= t('login_to_generate') ?></a>
    <p style="color:var(--text-dim);font-size:0.75rem;margin-top:10px;text-align:center;"><?= sprintf(t('tos_reminder'), $CURRENT_LANG) ?></p>
  </div>

  <!-- FLF2V Panel -->
  <div class="panel" id="panel-flf2v" style="display:none;border-radius:0 4px 4px 4px;border-top:none;">
    <div class="row">
      <div class="field">
        <label for="file-flf2v-start"><?= t('start_image') ?></label>
        <div class="drop-zone" id="drop-flf2v-start">
          <input type="file" id="file-flf2v-start" accept="image/*">
          <span class="placeholder"><?= t('start_frame') ?></span>
        </div>
      </div>
      <div class="field">
        <label for="file-flf2v-end"><?= t('end_image') ?></label>
        <div class="drop-zone" id="drop-flf2v-end">
          <input type="file" id="file-flf2v-end" accept="image/*">
          <span class="placeholder"><?= t('end_frame') ?></span>
        </div>
      </div>
    </div>
    <div class="field">
      <label for="prompt-flf2v"><?= t('prompt') ?></label>
      <textarea id="prompt-flf2v" placeholder="the girl smoothly transitions from the first pose to the second pose"></textarea>
    </div>
    <div class="row">
      <div class="field">
        <label for="seconds-flf2v"><?= t('seconds') ?>: <span id="sec-val-flf2v">5</span><?= t('sec_suffix') ?></label>
        <input type="range" id="seconds-flf2v" min="1" max="10" value="5">
      </div>
      <div class="field">
        <label for="steps-flf2v"><?= t('steps') ?></label>
        <input type="text" id="steps-flf2v" value="10">
      </div>
      <div class="field">
        <label for="cfg-flf2v"><?= t('cfg') ?></label>
        <input type="text" id="cfg-flf2v" value="3.0">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" onclick="submitJob('flf2v')"><?= t('generate') ?></button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>"><?= t('login_to_generate') ?></a>
    <p style="color:var(--text-dim);font-size:0.75rem;margin-top:10px;text-align:center;"><?= sprintf(t('tos_reminder'), $CURRENT_LANG) ?></p>
  </div>

  <div class="log-area" id="logArea">
    <h3><?= t('log') ?></h3>
    <div class="log" id="log"></div>
  </div>

  <div class="video-modal-overlay" id="videoModal" onclick="closeVideoModal(event)">
    <div class="video-modal" onclick="event.stopPropagation()">
      <span class="video-modal-close" onclick="closeVideoModal(event)">&times;</span>
      <video controls autoplay id="resultVideo" style="max-width:100%;max-height:70vh;border-radius:4px;"></video>
      <div style="display:flex;gap:12px;margin-top:12px;justify-content:center;">
        <a class="download-btn" id="downloadBtn" download="output.mp4"><?= t('download') ?></a>
        <a class="download-btn" href="/generated.php" style="border-color:var(--border-hover);color:var(--accent-bright);"><?= t('generated') ?></a>
      </div>
      <p style="color:#666;font-size:0.75rem;margin-top:12px;"><?= t('content_notice') ?></p>
    </div>
  </div>
  <style>
  .video-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1000; align-items:center; justify-content:center; cursor:pointer; }
  .video-modal-overlay.show { display:flex; }
  .video-modal { position:relative; max-width:90%; text-align:center; }
  .video-modal-close { position:absolute; top:-30px; right:0; color:#888; font-size:1.5rem; cursor:pointer; z-index:1001; }
  </style>

<script>
const T = <?= json_encode([
    'generate'           => t('generate'),
    'generating'         => t('generating'),
    'log_submitting'     => t('log_submitting'),
    'log_job_id'         => t('log_job_id'),
    'log_waiting'        => t('log_waiting'),
    'log_status'         => t('log_status'),
    'log_complete'       => t('log_complete'),
    'log_failed'         => t('log_failed'),
    'log_request_error'  => t('log_request_error'),
    'log_polling_error'  => t('log_polling_error'),
    'err_pod_config'     => t('err_pod_config'),
    'err_select_image'   => t('err_select_image'),
    'err_select_both_images' => t('err_select_both_images'),
    'err_insufficient'   => t('err_insufficient'),
    'err_error'          => t('err_error'),
], JSON_UNESCAPED_UNICODE) ?>;
const MODELS = <?= json_encode($podVideo, JSON_UNESCAPED_UNICODE) ?>;
const images = { 'i2v': null, 'flf2v-start': null, 'flf2v-end': null };
let polling = null;
let currentIndex = 0;
currentIndex = persistModel(function(idx) { currentIndex = idx; });
persistPrompts('prompt-i2v', null, '_i2v');
persistPrompts('prompt-flf2v', null, '_flf2v');
persistFields(['seconds-i2v', 'steps-i2v', 'cfg-i2v'], '_i2v');
persistFields(['seconds-flf2v', 'steps-flf2v', 'cfg-flf2v'], '_flf2v');

// Restore reused params
(function() {
  const saved = localStorage.getItem('ciel_reuse_video');
  if (saved) {
    localStorage.removeItem('ciel_reuse_video');
    const p = JSON.parse(saved);
    if (p.steps) { document.getElementById('steps-i2v').value = p.steps; document.getElementById('steps-flf2v').value = p.steps; }
    if (p.cfg) { document.getElementById('cfg-i2v').value = p.cfg; document.getElementById('cfg-flf2v').value = p.cfg; }
    if (p.length) {
      const sec = Math.round((p.length - 1) / 16);
      document.getElementById('seconds-i2v').value = sec;
      document.getElementById('sec-val-i2v').textContent = sec;
    }
  }
})();

// Model selector (if multiple)
document.querySelectorAll('.model-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.model-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentIndex = parseInt(btn.dataset.index);
  });
});

// Tabs
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => { p.classList.remove('active'); p.style.display = 'none'; });
    tab.classList.add('active');
    const panel = document.getElementById('panel-' + tab.dataset.tab);
    panel.classList.add('active');
    panel.style.display = 'block';
  });
});

document.querySelectorAll('input[type="range"]').forEach(r => {
  r.addEventListener('input', () => {
    const id = r.id.replace('seconds-', 'sec-val-');
    document.getElementById(id).textContent = r.value;
  });
});

document.querySelectorAll('.drop-zone').forEach(zone => {
  const input = zone.querySelector('input');
  const key = zone.id.replace('drop-', '');
  zone.addEventListener('click', () => input.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    if (e.dataTransfer.files.length) handleFile(zone, key, e.dataTransfer.files[0]);
  });
  input.addEventListener('change', () => {
    if (input.files.length) handleFile(zone, key, input.files[0]);
  });
});

function handleFile(zone, key, file) {
  const reader = new FileReader();
  reader.onload = e => {
    images[key] = e.target.result.split(',')[1];
    zone.innerHTML = `<img src="${e.target.result}">`;
  };
  reader.readAsDataURL(file);
}

function getResolution(base64) {
  return new Promise(resolve => {
    const img = new Image();
    img.onload = () => {
      let w = img.width, h = img.height;
      if (w < h) { resolve([480, Math.round(h * 480 / w / 16) * 16]); }
      else { resolve([Math.round(w * 480 / h / 16) * 16, 480]); }
    };
    img.src = 'data:image/png;base64,' + base64;
  });
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

async function submitJob(mode) {
  const m = MODELS[currentIndex];
  if (!m || !m.id) { alert(T.err_pod_config); return; }

  const seconds = parseInt(document.getElementById('seconds-' + mode).value);
  const steps = parseInt(document.getElementById('steps-' + mode).value);
  const cfg = parseFloat(document.getElementById('cfg-' + mode).value);
  const prompt = document.getElementById('prompt-' + mode).value.trim();
  const length = 16 * seconds + 1;
  let inputData;

  if (mode === 'i2v') {
    if (!images['i2v']) { alert(T.err_select_image); return; }
    const [w, h] = await getResolution(images['i2v']);
    log(`I2V: ${w}x${h}, ${seconds}s (${length}f)`);
    inputData = { prompt, negative_prompt: 'blurry, low quality, distorted', seed: 42, cfg, width: w, height: h, length, steps, image_base64: images['i2v'] };
  } else {
    if (!images['flf2v-start'] || !images['flf2v-end']) { alert(T.err_select_both_images); return; }
    const [w, h] = await getResolution(images['flf2v-start']);
    log(`FLF2V: ${w}x${h}, ${seconds}s (${length}f)`);
    inputData = { prompt, negative_prompt: 'blurry, low quality, distorted', seed: 42, cfg, width: w, height: h, length, steps, image_base64: images['flf2v-start'], end_image_base64: images['flf2v-end'] };
  }

  const btn = document.querySelector('#panel-' + mode + ' .submit-btn');
  btn.disabled = true; btn.textContent = T.generating;

  try {
    log(T.log_submitting);
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'video', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log(T.err_insufficient, 'error'); btn.disabled = false; btn.textContent = T.generate; return; }
    if (!data.id) { log(T.err_error + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = T.generate; return; }
    log(T.log_job_id + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log(T.log_request_error + e.message, 'error'); btn.disabled = false; btn.textContent = T.generate; }
}

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
        const vidUrl = data.job_db_id ? '/api/file.php?job_id=' + data.job_db_id : '';
        showVideo(vidUrl); btn.disabled = false; btn.textContent = T.generate;
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log(T.log_failed + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = T.generate;
      }
    } catch (e) { log(T.log_polling_error + e.message, 'error'); }
  }, 10000);
}

function showVideo(url) {
  document.getElementById('resultVideo').src = url;
  document.getElementById('downloadBtn').href = url;
  document.getElementById('videoModal').classList.add('show');
}
function closeVideoModal(e) {
  const modal = document.getElementById('videoModal');
  modal.classList.remove('show');
  const v = document.getElementById('resultVideo');
  if (v) v.pause();
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
