<?php
// Purchase success landing page.
// Balance crediting is handled exclusively by webhook.php (Stripe Webhook).
// This page verifies the session belongs to the current user and shows a confirmation.
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/stripe.php';
requireLogin();

$sessionId = $_GET['session_id'] ?? '';

// #10: Validate Stripe session_id format (cs_test_... or cs_live_...)
if (!$sessionId || !preg_match('/^cs_(?:test|live)_[A-Za-z0-9]+$/', $sessionId)) {
    header('Location: /mypage.php');
    exit;
}

$db = getDb();
$userId = $_SESSION['user']['id'];

// Confirm the purchase record belongs to this user
$stmt = $db->prepare('SELECT * FROM purchases WHERE stripe_session_id = ? AND user_id = ?');
$stmt->execute([$sessionId, $userId]);
$purchase = $stmt->fetch();

if (!$purchase) {
    header('Location: /mypage.php');
    exit;
}

// #5: Verify Stripe amount_total matches DB amount (log discrepancy)
$session = retrieveCheckoutSession($sessionId);
if (!empty($session['amount_total'])) {
    $stripeAmountCents = (int)$session['amount_total'];
    $dbAmountCents     = (int)round((float)$purchase['amount'] * 100);
    if ($stripeAmountCents !== $dbAmountCents) {
        error_log(sprintf(
            '[CIEL purchase_success] Amount mismatch: stripe=%d cents, db=%d cents, session_id=%s',
            $stripeAmountCents, $dbAmountCents, $sessionId
        ));
    }
}

// Show confirmation regardless of current purchase status.
// Webhook handles balance crediting; status may be 'pending' momentarily.
$pageTitle   = t('title_purchase_complete');
$pageHeading = t('title_purchase_complete');
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
?>

  <div class="panel" style="display:block;text-align:center;padding:40px 24px;">
    <div style="font-size:3rem;margin-bottom:16px;">&#10003;</div>
    <h2 style="color:#6bff9e;margin-bottom:12px;"><?= t('payment_received') ?></h2>
    <p style="color:#aaa;margin-bottom:8px;">
      <?= sprintf(t('payment_msg'), number_format((float)$purchase['amount'], 2)) ?>
    </p>
    <p style="color:#888;font-size:0.85rem;margin-bottom:24px;">
      <?= t('credits_soon') ?>
    </p>
    <a href="/mypage.php" style="display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#4a6fa5,#8bb4ff);border-radius:6px;color:#fff;text-decoration:none;font-weight:600;">
      <?= t('go_mypage') ?>
    </a>
  </div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
