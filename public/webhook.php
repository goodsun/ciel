<?php
// Stripe Webhook handler
// Receives checkout.session.completed events and credits user balance.
// Duplicate prevention: purchases.status === 'completed' guard.
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret    = getenv('STRIPE_WEBHOOK_SECRET');

if (!$secret) {
    error_log('[CIEL webhook] STRIPE_WEBHOOK_SECRET not configured');
    http_response_code(500);
    exit;
}

// --- Stripe signature verification (HMAC-SHA256, no SDK) ---
// Expected header format: t=<timestamp>,v1=<signature>[,v1=<signature>...]
$parts = [];
foreach (explode(',', $sigHeader) as $part) {
    $kv = explode('=', $part, 2);
    if (count($kv) === 2) {
        $parts[$kv[0]] = $kv[1];
    }
}

if (empty($parts['t']) || empty($parts['v1'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature header']);
    exit;
}

$timestamp = (int)$parts['t'];
// Reject events older than 5 minutes to prevent replay attacks
if (abs(time() - $timestamp) > 300) {
    http_response_code(400);
    echo json_encode(['error' => 'Timestamp too old']);
    exit;
}

$signedPayload = $timestamp . '.' . $payload;
$expectedSig   = hash_hmac('sha256', $signedPayload, $secret);

if (!hash_equals($expectedSig, $parts['v1'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Signature mismatch']);
    exit;
}
// --- End signature verification ---

$event = json_decode($payload, true);
if (!$event || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Only handle checkout.session.completed
if ($event['type'] !== 'checkout.session.completed') {
    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

$session   = $event['data']['object'] ?? [];
$sessionId = $session['id'] ?? '';

if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session id']);
    exit;
}

if (($session['payment_status'] ?? '') !== 'paid') {
    http_response_code(200);
    echo json_encode(['received' => true, 'skipped' => 'payment_not_paid']);
    exit;
}

$db = getDb();

// Fetch purchase record
$stmt = $db->prepare('SELECT * FROM purchases WHERE stripe_session_id = ?');
$stmt->execute([$sessionId]);
$purchase = $stmt->fetch();

if (!$purchase) {
    // Webhook arrived before purchase row was created (race condition).
    // Return 503 so Stripe retries (exponential backoff over ~72h).
    error_log('[CIEL webhook] Purchase record not found for session_id=' . $sessionId . ' — returning 503 for retry');
    http_response_code(503);
    header('Retry-After: 30');
    echo json_encode(['error' => 'purchase_not_yet_created']);
    exit;
}

// Duplicate prevention: already completed
if ($purchase['status'] === 'completed') {
    http_response_code(200);
    echo json_encode(['received' => true, 'skipped' => 'already_completed']);
    exit;
}

$userId    = (int)$purchase['user_id'];
$amount    = (float)$purchase['amount'];
$paymentId = $session['payment_intent'] ?? '';

// #5: Verify Stripe amount_total matches DB amount
$stripeAmountCents = (int)($session['amount_total'] ?? 0);
$dbAmountCents     = (int)round($amount * 100);
if ($stripeAmountCents !== $dbAmountCents) {
    error_log(sprintf(
        '[CIEL webhook] Amount mismatch: stripe=%d cents, db=%d cents, session_id=%s',
        $stripeAmountCents, $dbAmountCents, $sessionId
    ));
    // Do not credit — amount discrepancy requires manual review
    http_response_code(200);
    echo json_encode(['received' => true, 'skipped' => 'amount_mismatch']);
    exit;
}

$db->beginTransaction();
try {
    // Update purchase status
    $db->prepare('UPDATE purchases SET stripe_payment_id = ?, status = ?, updated_at = NOW() WHERE id = ?')
       ->execute([$paymentId, 'completed', $purchase['id']]);

    // Credit balance
    $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
       ->execute([$amount, $userId]);

    // Get new balance
    $stmtBal = $db->prepare('SELECT balance FROM users WHERE id = ?');
    $stmtBal->execute([$userId]);
    $newBalance = $stmtBal->fetchColumn();

    // Record transaction
    $db->prepare(
        'INSERT INTO transactions (user_id, type, amount, balance, purchase_id, note) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId, 'purchase', $amount, $newBalance, $purchase['id'],
        'Stripe purchase $' . number_format($amount, 2)
    ]);

    $db->commit();

} catch (Exception $e) {
    $db->rollBack();
    error_log('[CIEL webhook] Transaction failed for session_id=' . $sessionId . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Processing failed']);
    exit;
}

http_response_code(200);
echo json_encode(['received' => true]);
