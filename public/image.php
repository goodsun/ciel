<?php
require __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'Image Generator';
$pageHeading = 'Image Generator';
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
      <label for="prompt">プロンプト</label>
      <textarea id="prompt" placeholder="1girl, blue hair, cherry blossoms, garden, detailed illustration..."></textarea>
      <div class="hint" id="promptHint"><?= htmlspecialchars($firstModel['hint'] ?? '') ?></div>
    </div>
    <div class="field">
      <label for="negative">ネガティブプロンプト</label>
      <textarea id="negative" placeholder="lowres, bad anatomy, blurry..."></textarea>
    </div>
    <div class="row">
      <div class="field"><label for="width">幅</label><input type="text" id="width" value="1024"></div>
      <div class="field"><label for="height">高さ</label><input type="text" id="height" value="1024"></div>
      <div class="field"><label for="steps">ステップ数</label><input type="text" id="steps" value="<?= $firstModel['steps'] ?? 25 ?>"></div>
      <div class="field"><label for="seed">シード</label><input type="text" id="seed" value="42"></div>
    </div>
    <div class="row">
      <div class="field">
        <label for="cfg">CFG: <span id="cfg-val"><?= $firstModel['cfg'] ?? 7.0 ?></span></label>
        <input type="range" id="cfg" min="1" max="15" step="0.5" value="<?= $firstModel['cfg'] ?? 7.0 ?>">
      </div>
      <div class="field">
        <label for="quality">JPEG品質: <span id="quality-val">90</span></label>
        <input type="range" id="quality" min="1" max="100" value="90">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" id="submitBtn">生成開始</button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>">Login with Google to Generate</a>
  </div>

  <div class="log-area" id="logArea">
    <h3>ログ</h3>
    <div class="log" id="log"></div>
  </div>

  <div class="result-area" id="resultArea">
    <img id="resultImage">
    <a class="download-btn" id="downloadBtn" download="output.jpg">ダウンロード</a>
  </div>

<script>
const MODELS = <?= json_encode($podImage, JSON_UNESCAPED_UNICODE) ?>;
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
    if (p.width) document.getElementById('width').value = p.width;
    if (p.height) document.getElementById('height').value = p.height;
    if (p.steps) document.getElementById('steps').value = p.steps;
    if (p.seed) document.getElementById('seed').value = p.seed;
    if (p.cfg) { document.getElementById('cfg').value = p.cfg; document.getElementById('cfg-val').textContent = p.cfg; }
    if (p.quality) { document.getElementById('quality').value = p.quality; document.getElementById('quality-val').textContent = p.quality; }
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
  if (!m.id) { alert('Pod設定が不足しています'); return; }

  const prompt = document.getElementById('prompt').value.trim();
  if (!prompt) { alert('プロンプトを入力してください'); return; }

  const inputData = {
    prompt, negative_prompt: document.getElementById('negative').value.trim() || undefined,
    width: parseInt(document.getElementById('width').value), height: parseInt(document.getElementById('height').value),
    steps: parseInt(document.getElementById('steps').value), seed: parseInt(document.getElementById('seed').value),
    cfg: parseFloat(document.getElementById('cfg').value), quality: parseInt(document.getElementById('quality').value)
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = '送信中...';
  document.getElementById('resultArea').style.display = 'none';
  log(`[${m.name}] ${inputData.width}x${inputData.height}, steps=${inputData.steps}, cfg=${inputData.cfg}`);

  try {
    log('ジョブを投入中...');
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'image', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log('残高不足です。マイページからクレジットを購入してください。', 'error'); btn.disabled = false; btn.textContent = '生成開始'; return; }
    if (!data.id) { log('エラー: ' + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = '生成開始'; return; }
    log('ジョブID: ' + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log('リクエストエラー: ' + e.message, 'error'); btn.disabled = false; btn.textContent = '生成開始'; }
});

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
        showImage(data.output.image); btn.disabled = false; btn.textContent = '生成開始';
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log('ジョブ失敗: ' + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = '生成開始';
      }
    } catch (e) { log('ポーリングエラー: ' + e.message, 'error'); }
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
