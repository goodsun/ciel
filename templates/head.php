<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>le ciel — <?= htmlspecialchars($pageTitle ?? t('title_default')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --bg: #06060c;
  --bg-panel: #0e0e1a;
  --bg-input: #0a0a14;
  --border: rgba(255,255,255,0.06);
  --border-hover: rgba(160,190,240,0.3);
  --text: #d0d4e0;
  --text-dim: #70748a;
  --accent: #8ba4d4;
  --accent-bright: #a0bef0;
  --serif: 'EB Garamond', Georgia, 'Times New Roman', serif;
  --sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
  --mono: 'SF Mono', Monaco, 'Cascadia Code', monospace;
}

.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

body {
  font-family: var(--sans);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.container { max-width: 900px; margin: 0 auto; padding: 24px; }

/* Form */
.panel { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 6px; padding: 24px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-family: var(--serif); font-size: 0.9rem; color: var(--accent); margin-bottom: 6px; letter-spacing: 0.03em; }
.field input[type="text"], .field textarea, .field select { width: 100%; padding: 10px 12px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 0.88rem; font-family: var(--sans); transition: border-color 0.3s; }
.field input[type="text"]:focus, .field textarea:focus, .field select:focus { outline: none; border-color: var(--border-hover); }
.field textarea { resize: vertical; min-height: 60px; }
.field input[type="range"] { width: 100%; accent-color: var(--accent); }
.field .hint { font-size: 0.75rem; color: var(--text-dim); margin-top: 4px; }

/* Drop zone */
.drop-zone { border: 1px dashed var(--border-hover); border-radius: 6px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; min-height: 120px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; }
.drop-zone:hover, .drop-zone.dragover { border-color: var(--accent); background: rgba(139,164,212,0.04); }
.drop-zone img { max-width: 100%; max-height: 200px; border-radius: 4px; }
.drop-zone input { display: none; }
.drop-zone .placeholder { color: var(--text-dim); font-size: 0.85rem; }

/* Row */
.row { display: flex; gap: 16px; }
.row .field { flex: 1; }

/* Tabs */
.tabs { display: flex; gap: 2px; margin-bottom: 0; }
.tab { padding: 10px 24px; background: transparent; border: 1px solid var(--border); border-bottom: none; border-radius: 4px 4px 0 0; cursor: pointer; font-family: var(--serif); font-size: 0.9rem; color: var(--text-dim); letter-spacing: 0.03em; transition: all 0.3s; }
.tab.active { color: var(--accent-bright); border-color: var(--border-hover); background: var(--bg-panel); }

/* API settings */
.api-settings { background: var(--bg-input); border: 1px solid var(--border); border-radius: 6px; padding: 16px; margin-bottom: 24px; }
.api-settings summary { cursor: pointer; color: var(--accent); font-size: 0.85rem; }
.api-settings .setting-group { margin-top: 12px; }
.api-settings .setting-group label { display: block; font-size: 0.75rem; color: var(--text-dim); margin-bottom: 4px; }
.api-settings .fields { display: flex; gap: 12px; }
.api-settings input { flex: 1; padding: 8px; background: var(--bg-panel); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 0.85rem; }

/* Model selector */
.model-selector { display: flex; gap: 6px; margin-bottom: 24px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin; padding-bottom: 6px; }
.model-selector::-webkit-scrollbar { height: 3px; }
.model-selector::-webkit-scrollbar-track { background: transparent; }
.model-selector::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }
.model-btn { padding: 10px 20px; background: transparent; border: 1px solid var(--border); border-radius: 4px; cursor: pointer; font-family: var(--serif); font-size: 0.9rem; color: var(--text-dim); letter-spacing: 0.03em; transition: all 0.3s; flex-shrink: 0; white-space: nowrap; }
.model-btn:hover { border-color: var(--border-hover); color: var(--text); }
.model-btn.active { color: var(--accent-bright); border-color: var(--border-hover); background: rgba(160,190,240,0.04); }
.model-btn .sub { display: block; font-family: var(--sans); font-size: 0.68rem; color: var(--text-dim); margin-top: 3px; letter-spacing: 0; opacity: 0.6; }
.model-btn.active .sub { opacity: 0.8; }
.model-btn.disabled-ep { opacity: 0.4; border-style: dashed; }
.ep-off { color: #e06060; font-family: var(--sans); font-size: 0.68rem; font-weight: 500; letter-spacing: 0.05em; }

/* LoRA section */
.lora-section { margin-bottom: 16px; border: 1px solid var(--border); border-radius: 4px; padding: 12px; }
.lora-section summary { cursor: pointer; color: var(--text-dim); font-size: 0.85rem; user-select: none; letter-spacing: 0.02em; }
.lora-section[open] summary { color: var(--accent); margin-bottom: 4px; }
.lora-row { border: 1px solid var(--border); border-radius: 4px; padding: 10px; margin-top: 10px; position: relative; }
.lora-row-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.lora-row-label { color: var(--text-dim); font-family: var(--serif); font-size: 0.85rem; }
.lora-remove-btn { background: none; border: none; color: var(--text-dim); font-size: 1.2rem; cursor: pointer; padding: 0 4px; line-height: 1; transition: color 0.2s; }
.lora-remove-btn:hover { color: #e06060; }
.lora-add-btn { margin-top: 10px; padding: 8px 16px; background: transparent; border: 1px dashed var(--border-hover); border-radius: 4px; color: var(--text-dim); font-size: 0.85rem; cursor: pointer; width: 100%; transition: all 0.3s; }
.lora-add-btn:hover { border-color: var(--accent); color: var(--accent); }

/* Button */
.submit-btn { width: 100%; padding: 14px; background: transparent; border: 1px solid var(--border-hover); color: #fff; font-family: var(--serif); font-size: 1rem; letter-spacing: 0.1em; cursor: pointer; transition: all 0.3s; border-radius: 0; }
.submit-btn:hover { background: rgba(160,190,240,0.08); border-color: rgba(160,190,240,0.5); }
.submit-btn:disabled { opacity: 0.3; cursor: not-allowed; }

/* Log */
.log-area { margin-top: 24px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 4px; padding: 16px; display: none; }
.log-area h3 { font-family: var(--serif); font-size: 0.9rem; color: var(--accent); margin-bottom: 8px; letter-spacing: 0.05em; }
.log-area .log { font-family: var(--mono); font-size: 0.78rem; color: var(--text-dim); white-space: pre-wrap; max-height: 300px; overflow-y: auto; line-height: 1.7; }
.log .error { color: #e07070; }
.log .success { color: #70d090; }

/* Result */
.result-area { margin-top: 16px; display: none; }
.result-area img, .result-area video { width: 100%; border-radius: 4px; background: #000; }
.download-btn { display: inline-block; margin-top: 8px; padding: 8px 20px; background: transparent; border: 1px solid rgba(112,208,144,0.3); border-radius: 0; color: #70d090; text-decoration: none; font-family: var(--serif); font-size: 0.88rem; letter-spacing: 0.06em; transition: all 0.3s; }
.download-btn:hover { background: rgba(112,208,144,0.06); border-color: rgba(112,208,144,0.5); }
</style>
<?php if (!empty($pageStyles)): ?>
<style><?= $pageStyles ?></style>
<?php endif; ?>
<style>
.guest-hide { display: none !important; }
.guest-login-btn { display: none; width: 100%; padding: 14px; background: transparent; border: 1px solid var(--border-hover); color: var(--accent-bright); font-family: var(--serif); font-size: 1rem; letter-spacing: 0.1em; cursor: pointer; text-decoration: none; text-align: center; border-radius: 0; transition: all 0.3s; }
.guest-login-btn:hover { background: rgba(160,190,240,0.08); border-color: rgba(160,190,240,0.5); }
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
