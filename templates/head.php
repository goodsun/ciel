<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CIEL — <?= htmlspecialchars($pageTitle ?? t('title_default')) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0a0f; color: #e0e0e0; min-height: 100vh; }
.container { max-width: 900px; margin: 0 auto; padding: 24px; }

/* Form */
.panel { background: #16213e; border: 1px solid #2a2a4a; border-radius: 8px; padding: 24px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 0.85rem; color: var(--accent, #8bb4ff); margin-bottom: 6px; }
.field input[type="text"], .field textarea, .field select { width: 100%; padding: 10px; background: #0d1b2a; border: 1px solid #2a2a4a; border-radius: 6px; color: #e0e0e0; font-size: 0.9rem; }
.field textarea { resize: vertical; min-height: 60px; }
.field input[type="range"] { width: 100%; accent-color: var(--accent, #8bb4ff); }
.field .hint { font-size: 0.75rem; color: #555; margin-top: 4px; }

/* Drop zone */
.drop-zone { border: 2px dashed #2a2a4a; border-radius: 8px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; min-height: 120px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; }
.drop-zone:hover, .drop-zone.dragover { border-color: var(--accent, #8bb4ff); background: rgba(139,180,255,0.05); }
.drop-zone img { max-width: 100%; max-height: 200px; border-radius: 4px; }
.drop-zone input { display: none; }
.drop-zone .placeholder { color: #555; font-size: 0.85rem; }

/* Row */
.row { display: flex; gap: 16px; }
.row .field { flex: 1; }

/* Tabs */
.tabs { display: flex; gap: 4px; margin-bottom: 24px; }
.tab { padding: 10px 20px; background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 8px 8px 0 0; cursor: pointer; font-size: 0.9rem; color: #888; transition: all 0.2s; }
.tab.active { background: #16213e; color: var(--accent, #8bb4ff); border-bottom-color: #16213e; }

/* API settings */
.api-settings { background: #0d1b2a; border: 1px solid #2a2a4a; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
.api-settings summary { cursor: pointer; color: var(--accent, #8bb4ff); font-size: 0.85rem; }
.api-settings .setting-group { margin-top: 12px; }
.api-settings .setting-group label { display: block; font-size: 0.75rem; color: #888; margin-bottom: 4px; }
.api-settings .fields { display: flex; gap: 12px; }
.api-settings input { flex: 1; padding: 8px; background: #16213e; border: 1px solid #2a2a4a; border-radius: 6px; color: #e0e0e0; font-size: 0.85rem; }

/* Model selector */
.model-selector { display: flex; gap: 4px; margin-bottom: 24px; }
.model-btn { padding: 10px 20px; background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 8px; cursor: pointer; font-size: 0.9rem; color: #888; transition: all 0.2s; }
.model-btn.active { color: var(--accent, #8bb4ff); border-color: var(--accent, #8bb4ff); background: #16213e; }
.model-btn .sub { display: block; font-size: 0.7rem; color: #555; margin-top: 2px; }
.model-btn.active .sub { color: #6690cc; }

/* Button */
.submit-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #4a6fa5, #8bb4ff); border: none; border-radius: 8px; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
.submit-btn:hover { opacity: 0.9; }
.submit-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Log */
.log-area { margin-top: 24px; background: #0d1b2a; border: 1px solid #2a2a4a; border-radius: 8px; padding: 16px; display: none; }
.log-area h3 { font-size: 0.85rem; color: var(--accent, #8bb4ff); margin-bottom: 8px; }
.log-area .log { font-family: 'SF Mono', Monaco, monospace; font-size: 0.8rem; color: #aaa; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
.log .error { color: #ff6b6b; }
.log .success { color: #6bff9e; }

/* Result */
.result-area { margin-top: 16px; display: none; }
.result-area img, .result-area video { width: 100%; border-radius: 8px; background: #000; }
.download-btn { display: inline-block; margin-top: 8px; padding: 8px 16px; background: #2a4a2a; border: 1px solid #3a6a3a; border-radius: 6px; color: #6bff9e; text-decoration: none; font-size: 0.85rem; }
</style>
<?php if (!empty($pageStyles)): ?>
<style><?= $pageStyles ?></style>
<?php endif; ?>
<style>
.guest-hide { display: none !important; }
.guest-login-btn { display: none; width: 100%; padding: 14px; background: linear-gradient(135deg, #3a5a8a, #5a8abf); border: none; border-radius: 8px; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
.guest-show { display: block !important; }
</style>
<script>
const IS_LOGGED_IN = <?= json_encode(isset($_SESSION['user'])) ?>;
const PAGE_KEY = <?= json_encode(basename($_SERVER['SCRIPT_NAME'], '.php')) ?>;

function persistPrompts(promptId, negativeId, suffix) {
  const key = 'ciel_prompt_' + PAGE_KEY + (suffix || '');
  const p = document.getElementById(promptId);
  const n = negativeId ? document.getElementById(negativeId) : null;
  const saved = localStorage.getItem(key);
  if (saved) {
    const d = JSON.parse(saved);
    if (p && d.p) p.value = d.p;
    if (n && d.n) n.value = d.n;
  }
  function save() {
    localStorage.setItem(key, JSON.stringify({ p: p ? p.value : '', n: n ? n.value : '' }));
  }
  if (p) p.addEventListener('input', save);
  if (n) n.addEventListener('input', save);
}

function persistFields(fieldIds, suffix) {
  const key = 'ciel_fields_' + PAGE_KEY + (suffix || '');
  const saved = localStorage.getItem(key);
  if (saved) {
    const d = JSON.parse(saved);
    for (const id of fieldIds) {
      const el = document.getElementById(id);
      if (el && d[id] !== undefined) {
        el.value = d[id];
        const valEl = document.getElementById(id + '-val');
        if (valEl) valEl.textContent = d[id];
      }
    }
  }
  function save() {
    const data = {};
    for (const id of fieldIds) {
      const el = document.getElementById(id);
      if (el) data[id] = el.value;
    }
    localStorage.setItem(key, JSON.stringify(data));
  }
  for (const id of fieldIds) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', save);
  }
}

function persistModel(callback) {
  const key = 'ciel_model_' + PAGE_KEY;
  const saved = localStorage.getItem(key);
  const btns = document.querySelectorAll('.model-btn');
  if (saved && btns.length) {
    const idx = parseInt(saved);
    btns.forEach(b => b.classList.remove('active'));
    const target = document.querySelector('.model-btn[data-index="' + idx + '"]');
    if (target) {
      target.classList.add('active');
      if (callback) callback(idx);
    }
  }
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      localStorage.setItem(key, btn.dataset.index);
    });
  });
  return saved ? parseInt(saved) : 0;
}
</script>
</head>
<body>
<div class="container">
