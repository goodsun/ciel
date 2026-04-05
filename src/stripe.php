<?php

// #10: Validate Stripe checkout session_id format
function isValidStripeSessionId(string $sessionId): bool {
    return (bool)preg_match('/^cs_(?:test|live)_[A-Za-z0-9]+$/', $sessionId);
}

function stripeRequest(string $method, string $endpoint, array $params = []): array {
    $url = 'https://api.stripe.com/v1' . $endpoint;
    $ch = curl_init();

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . getenv('STRIPE_SECRET_KEY')],
        CURLOPT_TIMEOUT        => 30,
    ];

    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
        $opts[CURLOPT_URL] = $url;
    } else {
        $opts[CURLOPT_URL] = $url . ($params ? '?' . http_build_query($params) : '');
    }

    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

function createCheckoutSession(int $userId, int $amountCents): array {
    $appUrl = getenv('APP_URL');
    return stripeRequest('POST', '/checkout/sessions', [
        'mode'                 => 'payment',
        'currency'             => 'usd',
        'line_items[0][price_data][currency]'    => 'usd',
        'line_items[0][price_data][unit_amount]'  => $amountCents,
        'line_items[0][price_data][product_data][name]' => 'CIEL Credits',
        'line_items[0][quantity]' => 1,
        'success_url'          => $appUrl . '/purchase_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'           => $appUrl . '/mypage.php',
        'metadata[user_id]'    => $userId,
    ]);
}

function retrieveCheckoutSession(string $sessionId): array {
    return stripeRequest('GET', '/checkout/sessions/' . $sessionId);
}
