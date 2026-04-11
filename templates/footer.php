  <footer style="margin-top:48px;padding:24px 0;border-top:1px solid #2a2a4a;text-align:center;font-size:0.8rem;color:#666;">
    <p><?= sprintf(t('copyright'), date('Y')) ?></p>
    <p style="margin-top:12px;">
<?php
global $CURRENT_LANG;
$langs = ['en' => 'EN', 'ja' => 'JA', 'zh' => 'ZH', 'ko' => 'KO', 'es' => 'ES'];
$parts = [];
foreach ($langs as $code => $label) {
    if ($code === $CURRENT_LANG) {
        $parts[] = '<span style="color:var(--accent,#8bb4ff);">' . $label . '</span>';
    } else {
        $parts[] = '<a href="?lang=' . $code . '" style="color:#555;text-decoration:none;">' . $label . '</a>';
    }
}
echo implode(' | ', $parts);
?>
      | <a href="service.php?lang=<?= $CURRENT_LANG ?>" style="color:#555;text-decoration:none;"><?= t('terms_of_service') ?></a>
    </p>
  </footer>
</div>
</body>
</html>
