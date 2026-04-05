<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/user.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    http_response_code(400);
    echo 'Authorization code missing';
    exit;
}

$tokens = exchangeCodeForTokens($code);
if (empty($tokens['access_token'])) {
    http_response_code(500);
    echo 'Failed to exchange authorization code';
    exit;
}

$userInfo = getGoogleUserInfo($tokens['access_token']);
if (empty($userInfo['id'])) {
    http_response_code(500);
    echo 'Failed to get user info';
    exit;
}

$dbUser = upsertUser(
    $userInfo['id'],
    $userInfo['email'] ?? '',
    $userInfo['name'] ?? ''
);

// #7: Regenerate session ID after login to prevent session fixation
session_regenerate_id(true);

$_SESSION['user'] = [
    'id'        => $dbUser['id'],
    'google_id' => $dbUser['google_id'],
    'email'     => $dbUser['email'],
    'name'      => $dbUser['name'],
    'balance'   => $dbUser['balance'],
    'picture'   => $userInfo['picture'] ?? '',
];

$redirect = $_SESSION['login_redirect'] ?? '/';
unset($_SESSION['login_redirect']);
header('Location: ' . $redirect);
