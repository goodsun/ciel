<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/stripe.php';
requireLogin();

// #6: CSRF validation
verifyCsrfToken();

$amount = $_POST['amount'] ?? '';
$amountFloat = (float)$amount;

if ($amountFloat < 1 || $amountFloat > 100) {
    http_response_code(400);
    echo 'Amount must be between $1 and $100';
    exit;
}

$amountCents = (int)($amountFloat * 100);
$userId = $_SESSION['user']['id'];

$session = createCheckoutSession($userId, $amountCents);

if (empty($session['id'])) {
    http_response_code(500);
    echo 'Failed to create checkout session';
    exit;
}

// Record pending purchase
require_once __DIR__ . '/../src/db.php';
$db = getDb();
$db->prepare('INSERT INTO purchases (user_id, stripe_session_id, amount, status) VALUES (?, ?, ?, ?)')
   ->execute([$userId, $session['id'], $amountFloat, 'pending']);

header('Location: ' . $session['url']);
