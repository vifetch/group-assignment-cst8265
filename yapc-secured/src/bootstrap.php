<?php
declare(strict_types=1);

const SESSION_TIMEOUT = 3600;

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || getenv('YAPC_FORCE_SECURE_COOKIE') === '1';

session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit('Authentication required.');
    }
}
