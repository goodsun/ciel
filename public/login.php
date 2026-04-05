<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/auth.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$_SESSION['login_redirect'] = $_SERVER['HTTP_REFERER'] ?? '/';

header('Location: ' . getGoogleAuthUrl());
