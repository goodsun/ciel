<?php
require __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($CURRENT_LANG) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CIEL — <?= t('lp_subtitle') ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0a0f; color: #e0e0e0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.lp { text-align: center; padding: 2rem; }
.lp h1 { font-size: 3rem; color: #8bb4ff; margin-bottom: 0.5rem; letter-spacing: 0.2em; }
.lp p { color: #888; font-size: 1.1rem; margin-bottom: 2rem; }
.lp nav { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.lp nav a { padding: 12px 28px; background: #16213e; border: 1px solid #2a2a4a; border-radius: 8px; color: #8bb4ff; text-decoration: none; font-size: 0.95rem; transition: all 0.2s; }
.lp nav a:hover { background: #1a2a4e; border-color: #8bb4ff; }
.lp footer { margin-top: 3rem; font-size: 0.8rem; color: #555; }
.lp footer a { color: #8bb4ff; text-decoration: none; }
.lang-selector { margin-top: 1rem; font-size: 0.8rem; }
</style>
</head>
<body>
<div class="lp">
  <h1>CIEL</h1>
  <p><?= t('lp_subtitle') ?></p>
  <nav>
<?php if (!empty($podImage)): ?>
    <a href="image.php"><?= t('title_image') ?></a>
<?php endif; ?>
<?php if (!empty($podVideo)): ?>
    <a href="video.php"><?= t('title_video') ?></a>
<?php endif; ?>
<?php if (!empty($podEdit)): ?>
    <a href="edit.php"><?= t('title_edit') ?></a>
<?php endif; ?>
    <a href="login.php" style="background:#2a3a5e;border-color:#4a6fa5;"><?= t('lp_login') ?></a>
  </nav>
  <footer>
    <p><?= sprintf(t('copyright'), date('Y')) ?></p>
    <p><a href="service.php?lang=<?= $CURRENT_LANG ?>"><?= t('terms_of_service') ?></a></p>
    <p class="lang-selector">
<?php
$langs = ['en' => 'EN', 'ja' => 'JA', 'zh' => 'ZH', 'ko' => 'KO', 'es' => 'ES'];
$parts = [];
foreach ($langs as $code => $label) {
    if ($code === $CURRENT_LANG) {
        $parts[] = '<span style="color:#8bb4ff;">' . $label . '</span>';
    } else {
        $parts[] = '<a href="?lang=' . $code . '">' . $label . '</a>';
    }
}
echo implode(' | ', $parts);
?>
    </p>
  </footer>
</div>
</body>
</html>
