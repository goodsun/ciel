<?php
require __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'Video Generator';
$pageHeading = 'Video Generator';
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstVideo = $podVideo[0] ?? null;
?>

<?php if (count($podVideo) > 1): ?>
  <div class="model-selector">
<?php foreach ($podVideo as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"><?= htmlspecialchars($m['name']) ?></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="tabs">
    <div class="tab active" data-tab="i2v">I2V（画像→動画）</div>
    <div class="tab" data-tab="flf2v">FLF2V（開始・終了画像→動画）</div>
  </div>

  <!-- I2V Panel -->
  <div class="panel active" id="panel-i2v" style="display:block;border-radius:0 8px 8px 8px;">
    <div class="field">
      <label for="file-i2v">入力画像</label>
      <div class="drop-zone" id="drop-i2v">
        <input type="file" id="file-i2v" accept="image/*">
        <span class="placeholder">クリックまたはドラッグ＆ドロップで画像を選択</span>
      </div>
    </div>
    <div class="field">
      <label for="prompt-i2v">プロンプト</label>
      <textarea id="prompt-i2v" placeholder="a girl in kimono gently picks up a clay bowl..."></textarea>
    </div>
    <div class="row">
      <div class="field">
        <label for="seconds-i2v">秒数: <span id="sec-val-i2v">5</span>秒</label>
        <input type="range" id="seconds-i2v" min="1" max="10" value="5">
      </div>
      <div class="field">
        <label for="steps-i2v">ステップ数</label>
        <input type="text" id="steps-i2v" value="10">
      </div>
      <div class="field">
        <label for="cfg-i2v">CFG</label>
        <input type="text" id="cfg-i2v" value="3.0">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" onclick="submitJob('i2v')">生成開始</button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>">Login with Google to Generate</a>
  </div>

  <!-- FLF2V Panel -->
  <div class="panel" id="panel-flf2v">
    <div class="row">
      <div class="field">
        <label for="file-flf2v-start">開始画像</label>
        <div class="drop-zone" id="drop-flf2v-start">
          <input type="file" id="file-flf2v-start" accept="image/*">
          <span class="placeholder">開始フレーム</span>
        </div>
      </div>
      <div class="field">
        <label for="file-flf2v-end">終了画像</label>
        <div class="drop-zone" id="drop-flf2v-end">
          <input type="file" id="file-flf2v-end" accept="image/*">
          <span class="placeholder">終了フレーム</span>
        </div>
      </div>
    </div>
    <div class="field">
      <label for="prompt-flf2v">プロンプト</label>
      <textarea id="prompt-flf2v" placeholder="the girl smoothly transitions from the first pose to the second pose"></textarea>
    </div>
    <div class="row">
      <div class="field">
        <label for="seconds-flf2v">秒数: <span id="sec-val-flf2v">5</span>秒</label>
        <input type="range" id="seconds-flf2v" min="1" max="10" value="5">
      </div>
      <div class="field">
        <label for="steps-flf2v">ステップ数</label>
        <input type="text" id="steps-flf2v" value="10">
      </div>
      <div class="field">
        <label for="cfg-flf2v">CFG</label>
        <input type="text" id="cfg-flf2v" value="3.0">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" onclick="submitJob('flf2v')">生成開始</button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>">Login with Google to Generate</a>
  </div>

  <div class="log-area" id="logArea">
    <h3>ログ</h3>
    <div class="log" id="log"></div>
  </div>

  <div class="result-area" id="resultArea">
    <video controls id="resultVideo"></video>
    <a class="download-btn" id="downloadBtn" download="output.mp4">ダウンロード</a>
  </div>

<script>
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
  if (!m || !m.id) { alert('Pod設定が不足しています'); return; }

  const seconds = parseInt(document.getElementById('seconds-' + mode).value);
  const steps = parseInt(document.getElementById('steps-' + mode).value);
  const cfg = parseFloat(document.getElementById('cfg-' + mode).value);
  const prompt = document.getElementById('prompt-' + mode).value.trim();
  const length = 16 * seconds + 1;
  let inputData;

  if (mode === 'i2v') {
    if (!images['i2v']) { alert('画像を選択してください'); return; }
    const [w, h] = await getResolution(images['i2v']);
    log(`I2V: ${w}x${h}, ${seconds}秒 (${length}フレーム)`);
    inputData = { prompt, negative_prompt: 'blurry, low quality, distorted', seed: 42, cfg, width: w, height: h, length, steps, image_base64: images['i2v'] };
  } else {
    if (!images['flf2v-start'] || !images['flf2v-end']) { alert('開始画像と終了画像を選択してください'); return; }
    const [w, h] = await getResolution(images['flf2v-start']);
    log(`FLF2V: ${w}x${h}, ${seconds}秒 (${length}フレーム)`);
    inputData = { prompt, negative_prompt: 'blurry, low quality, distorted', seed: 42, cfg, width: w, height: h, length, steps, image_base64: images['flf2v-start'], end_image_base64: images['flf2v-end'] };
  }

  const btn = document.querySelector('#panel-' + mode + ' .submit-btn');
  btn.disabled = true; btn.textContent = '送信中...';
  document.getElementById('resultArea').style.display = 'none';

  try {
    log('ジョブを投入中...');
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'video', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log('残高不足です。マイページからクレジットを購入してください。', 'error'); btn.disabled = false; btn.textContent = '生成開始'; return; }
    if (!data.id) { log('エラー: ' + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = '生成開始'; return; }
    log('ジョブID: ' + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log('リクエストエラー: ' + e.message, 'error'); btn.disabled = false; btn.textContent = '生成開始'; }
}

function pollStatus(endpointId, jobId, btn) {
  if (polling) clearInterval(polling);
  log('完了を待機中...');
  polling = setInterval(async () => {
    try {
      const res = await fetch(`/api/status.php?endpoint_id=${endpointId}&job_id=${jobId}`);
      const data = await res.json();
      log('ステータス: ' + data.status);
      if (data.status === 'COMPLETED') {
        clearInterval(polling);
        const cost = data.cost_user ? ` / $${data.cost_user.toFixed(6)}` : '';
        log(`完了! 実行時間: ${data.executionTime}ms${cost}`, 'success');
        showVideo(data.output.video); btn.disabled = false; btn.textContent = '生成開始';
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log('ジョブ失敗: ' + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = '生成開始';
      }
    } catch (e) { log('ポーリングエラー: ' + e.message, 'error'); }
  }, 10000);
}

function showVideo(base64) {
  const bin = atob(base64);
  const arr = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
  const blob = new Blob([arr], { type: 'video/mp4' });
  const url = URL.createObjectURL(blob);
  document.getElementById('resultVideo').src = url;
  document.getElementById('downloadBtn').href = url;
  document.getElementById('resultArea').style.display = 'block';
  const a = document.createElement('a');
  a.href = url; a.download = 'output_' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19) + '.mp4'; a.click();
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
