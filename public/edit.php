<?php
require __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'Image Editor';
$pageHeading = 'Image Editor';
$pageStyles = ':root { --accent: #c4a0ff; } .panel { background: #1e1a2e; } .api-settings { background: #0d0b1a; } .api-settings input { background: #1e1a2e; } .field input[type="text"], .field textarea { background: #0d0b1a; } .submit-btn { background: linear-gradient(135deg, #6a4fa5, #c4a0ff); }';
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
$firstEdit = $podEdit[0] ?? null;
?>

<?php if (count($podEdit) > 1): ?>
  <div class="model-selector">
<?php foreach ($podEdit as $i => $m): ?>
    <div class="model-btn<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"><?= htmlspecialchars($m['name']) ?></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="panel" style="display:block;">
    <div class="field">
      <label for="file-edit">編集元画像</label>
      <div class="drop-zone" id="drop-edit">
        <input type="file" id="file-edit" accept="image/*">
        <span class="placeholder">クリックまたはドラッグ＆ドロップで画像を選択</span>
      </div>
    </div>
    <div class="field">
      <label for="prompt">編集指示</label>
      <textarea id="prompt" placeholder="remove the sunglasses / make it a watercolor painting / change the background to a beach..."></textarea>
<?php if ($firstEdit && $firstEdit['hint']): ?>
      <div class="hint"><?= htmlspecialchars($firstEdit['hint']) ?></div>
<?php else: ?>
      <div class="hint"><?= htmlspecialchars(($firstEdit['name'] ?? 'Editor') . ': 画像に対するテキスト指示で編集します') ?></div>
<?php endif; ?>
    </div>
    <div class="field">
      <label for="negative">ネガティブプロンプト</label>
      <textarea id="negative" placeholder="lowres, bad anatomy, blurry..."></textarea>
    </div>
    <div class="row">
      <div class="field"><label for="steps">ステップ数</label><input type="text" id="steps" value="<?= $firstEdit['steps'] ?? 20 ?>"></div>
      <div class="field"><label for="seed">シード</label><input type="text" id="seed" value="42"></div>
      <div class="field">
        <label for="quality">JPEG品質: <span id="quality-val">90</span></label>
        <input type="range" id="quality" min="1" max="100" value="90">
      </div>
    </div>
    <button class="submit-btn<?= !isLoggedIn() ? ' guest-hide' : '' ?>" id="submitBtn">編集開始</button>
    <a href="/login.php" class="guest-login-btn<?= !isLoggedIn() ? ' guest-show' : '' ?>">Login with Google to Edit</a>
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
  if (!m || !m.id) { alert('Pod設定が不足しています'); return; }
  if (!editImage) { alert('編集元画像を選択してください'); return; }

  const prompt = document.getElementById('prompt').value.trim();
  if (!prompt) { alert('編集指示を入力してください'); return; }

  const inputData = {
    prompt, negative_prompt: document.getElementById('negative').value.trim() || undefined,
    image_base64: editImage, steps: parseInt(document.getElementById('steps').value),
    seed: parseInt(document.getElementById('seed').value), quality: parseInt(document.getElementById('quality').value)
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = '送信中...';
  document.getElementById('resultArea').style.display = 'none';
  log(`edit: steps=${inputData.steps}, seed=${inputData.seed}`);

  try {
    log('ジョブを投入中...');
    const res = await fetch('/api/run.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint_id: m.id, type: 'edit', input: inputData }) });
    const data = await res.json();
    if (res.status === 402) { log('残高不足です。マイページからクレジットを購入してください。', 'error'); btn.disabled = false; btn.textContent = '編集開始'; return; }
    if (!data.id) { log('エラー: ' + JSON.stringify(data), 'error'); btn.disabled = false; btn.textContent = '編集開始'; return; }
    log('ジョブID: ' + data.id);
    pollStatus(m.id, data.id, btn);
  } catch (e) { log('リクエストエラー: ' + e.message, 'error'); btn.disabled = false; btn.textContent = '編集開始'; }
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
        showImage(data.output.image); btn.disabled = false; btn.textContent = '編集開始';
      } else if (data.status === 'FAILED') {
        clearInterval(polling); log('ジョブ失敗: ' + JSON.stringify(data.error), 'error');
        btn.disabled = false; btn.textContent = '編集開始';
      }
    } catch (e) { log('ポーリングエラー: ' + e.message, 'error'); }
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
