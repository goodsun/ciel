<?php
// Load .env (use .env.local on localhost)
$isLocal = php_sapi_name() === 'cli'
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$envLocal = __DIR__ . '/../.env.local';
$envFile = ($isLocal && file_exists($envLocal)) ? $envLocal : __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            putenv(trim($line));
        }
    }
}

session_start([
    'cookie_samesite' => 'Lax',
    'cookie_httponly'  => true,
    'cookie_secure'    => !$isLocal,
]);

// Auto-login as admin on localhost (requires explicit opt-in)
if ($isLocal && getenv('AUTO_LOGIN_DEV') === 'true' && empty($_SESSION['user'])) {
    $adminGoogleId = explode(',', getenv('ADMIN_GOOGLE_IDS') ?: '')[0];
    if ($adminGoogleId) {
        require_once __DIR__ . '/db.php';
        $stmt = getDb()->prepare('SELECT id, google_id, email, name, balance FROM users WHERE google_id = ?');
        $stmt->execute([$adminGoogleId]);
        $dbUser = $stmt->fetch();
        if ($dbUser) {
            $_SESSION['user'] = $dbUser;
        }
    }
}

// i18n
$SUPPORTED_LANGS = ['en', 'ja', 'zh', 'ko', 'es'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $SUPPORTED_LANGS, true)) {
    setcookie('lang', $_GET['lang'], time() + 86400 * 365, '/');
    $CURRENT_LANG = $_GET['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $SUPPORTED_LANGS, true)) {
    $CURRENT_LANG = $_COOKIE['lang'];
} else {
    $CURRENT_LANG = 'en';
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach ($SUPPORTED_LANGS as $l) {
        if (stripos($accept, $l) !== false) { $CURRENT_LANG = $l; break; }
    }
}

$_LANG = require __DIR__ . '/../lang/' . $CURRENT_LANG . '.php';
$_LANG_EN = ($CURRENT_LANG !== 'en') ? require __DIR__ . '/../lang/en.php' : $_LANG;

function t(string $key): string {
    global $_LANG, $_LANG_EN;
    return $_LANG[$key] ?? $_LANG_EN[$key] ?? $key;
}

// #6: CSRF token generation and validation
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo 'CSRF token mismatch';
        exit;
    }
}

// Load endpoints from DB
require_once __DIR__ . '/db.php';

function loadEndpoints(string $type): array {
    $stmt = getDb()->prepare(
        'SELECT endpoint_id AS id, name, steps, cfg, hint
         FROM endpoints WHERE type = ? AND is_active = 1 ORDER BY sort_order'
    );
    $stmt->execute([$type]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['steps'] = (int)$r['steps'];
        $r['cfg']   = (float)$r['cfg'];
    }
    return $rows;
}

require_once __DIR__ . '/crypto.php';

$podImage  = loadEndpoints('image');
$podVideo  = loadEndpoints('video');
$podEdit   = loadEndpoints('edit');
