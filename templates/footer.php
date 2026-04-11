  <footer style="margin-top:48px;padding:24px 0;border-top:1px solid var(--border, rgba(255,255,255,0.06));text-align:center;font-size:0.78rem;color:var(--text-dim, #70748a);">
    <p style="letter-spacing:0.03em;"><?= sprintf(t('copyright'), date('Y')) ?></p>
    <p style="margin-top:10px;">
<?php
global $CURRENT_LANG;
$langs = ['en' => 'EN', 'ja' => 'JA', 'zh' => 'ZH', 'ko' => 'KO', 'es' => 'ES'];
$parts = [];
foreach ($langs as $code => $label) {
    if ($code === $CURRENT_LANG) {
        $parts[] = '<span style="color:var(--accent-bright, #a0bef0);">' . $label . '</span>';
    } else {
        $parts[] = '<a href="?lang=' . $code . '" style="color:var(--text-dim, #70748a);text-decoration:none;transition:color 0.3s;">' . $label . '</a>';
    }
}
echo implode(' &middot; ', $parts);
?>
      &middot; <a href="service.php?lang=<?= $CURRENT_LANG ?>" style="color:var(--text-dim, #70748a);text-decoration:none;transition:color 0.3s;"><?= t('terms_of_service') ?></a>
    </p>
  </footer>
</div>
</body>
</html>
