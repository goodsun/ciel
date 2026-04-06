<?php require_once __DIR__ . '/../src/auth.php'; ?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <h1 style="color:var(--accent,#8bb4ff);margin:0;">CIEL — <?= htmlspecialchars($pageHeading ?? '') ?>
      <span style="font-size:0.85rem;margin-left:12px;">
<?php
$nav = [];
if (!empty($podVideo))  $nav['/video.php']  = t('nav_video');
if (!empty($podImage))  $nav['/image.php']  = t('nav_image');
if (!empty($podEdit))   $nav['/edit.php']   = t('nav_edit');
$nav['/generated.php'] = t('nav_generated');
foreach ($nav as $href => $label):
    if (basename($_SERVER['SCRIPT_NAME']) === basename($href)) continue;
?>
        <a href="<?= $href ?>" style="color:#555;text-decoration:none;margin:0 4px;"><?= $label ?></a>
<?php endforeach; ?>
      </span>
    </h1>
    <div style="font-size:0.85rem;">
<?php if (isLoggedIn()): $user = currentUser(); ?>
      <a href="/mypage.php" style="color:#888;text-decoration:none;"><?= htmlspecialchars($user['name'] ?: $user['email']) ?></a>
      <a href="/logout.php" style="color:#555;text-decoration:none;margin-left:8px;"><?= t('logout') ?></a>
<?php else: ?>
      <a href="/login.php" style="color:#8bb4ff;text-decoration:none;"><?= t('login') ?></a>
<?php endif; ?>
    </div>
  </div>
