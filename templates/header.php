<?php require_once __DIR__ . '/../src/auth.php'; ?>
  <header style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:32px;padding-bottom:16px;border-bottom:1px solid var(--border, rgba(255,255,255,0.06));">
    <div>
      <h1 style="margin:0;line-height:1;">
        <a href="/" style="font-family:var(--serif, Georgia);font-size:1.6rem;font-weight:400;color:#fff;text-decoration:none;letter-spacing:0.15em;">le ciel</a>
        <span style="font-family:var(--serif, Georgia);font-size:0.85rem;font-weight:400;color:var(--text-dim, #70748a);margin-left:10px;letter-spacing:0.05em;"><?= htmlspecialchars($pageHeading ?? '') ?></span>
      </h1>
      <nav style="margin-top:8px;display:flex;justify-content:space-between;align-items:baseline;">
        <div style="display:flex;gap:16px;">
<?php
$nav = [];
if (!empty($podVideo))  $nav['/video.php']  = t('nav_video');
if (!empty($podImage))  $nav['/image.php']  = t('nav_image');
if (!empty($podEdit))   $nav['/edit.php']   = t('nav_edit');
$nav['/generated.php'] = t('nav_generated');
foreach ($nav as $href => $label):
    $isCurrent = basename($_SERVER['SCRIPT_NAME']) === basename($href);
?>
          <a href="<?= $href ?>" style="font-size:0.78rem;letter-spacing:0.08em;text-decoration:none;color:<?= $isCurrent ? 'var(--accent-bright, #a0bef0)' : 'var(--text-dim, #70748a)' ?>;transition:color 0.3s;"><?= $label ?></a>
<?php endforeach; ?>
          <a href="/service.php?lang=<?= $CURRENT_LANG ?>" style="font-size:0.78rem;letter-spacing:0.08em;text-decoration:none;color:var(--text-dim, #70748a);transition:color 0.3s;"><?= t('terms_of_service') ?></a>
        </div>
        <div style="display:flex;gap:8px;">
<?php
global $CURRENT_LANG;
$langs = ['en' => 'EN', 'ja' => 'JA', 'zh' => 'ZH', 'ko' => 'KO', 'es' => 'ES'];
foreach ($langs as $code => $label):
    if ($code === $CURRENT_LANG): ?>
          <span style="font-size:0.72rem;letter-spacing:0.04em;color:var(--accent-bright, #a0bef0);"><?= $label ?></span>
<?php else: ?>
          <a href="?lang=<?= $code ?>" style="font-size:0.72rem;letter-spacing:0.04em;text-decoration:none;color:var(--text-dim, #70748a);transition:color 0.3s;"><?= $label ?></a>
<?php endif; endforeach; ?>
        </div>
      </nav>
    </div>
    <div style="font-size:0.82rem;text-align:right;">
<?php if (isLoggedIn()): $user = currentUser(); ?>
      <a href="/mypage.php" style="color:var(--text-dim, #70748a);text-decoration:none;transition:color 0.3s;"><?= htmlspecialchars($user['name'] ?: $user['email']) ?></a>
<?php $adminIds = array_filter(explode(',', getenv('ADMIN_GOOGLE_IDS') ?: ''));
      if (in_array($user['google_id'] ?? '', $adminIds, true)): ?>
      <a href="/admin/" style="color:#d4a060;text-decoration:none;margin-left:6px;font-size:0.72rem;letter-spacing:0.04em;">admin</a>
<?php endif; ?>
      <a href="/logout.php" style="color:var(--text-dim, #70748a);text-decoration:none;margin-left:10px;font-size:0.75rem;letter-spacing:0.04em;"><?= t('logout') ?></a>
<?php else: ?>
      <a href="/login.php" style="color:var(--accent, #8ba4d4);text-decoration:none;letter-spacing:0.04em;"><?= t('login') ?></a>
<?php endif; ?>
    </div>
  </header>
