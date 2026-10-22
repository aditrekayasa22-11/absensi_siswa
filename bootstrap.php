<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/src/Env.php';
Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';
$GLOBALS['app_config'] = $config;

require BASE_PATH . '/src/helpers.php';
require BASE_PATH . '/src/icons.php';

spl_autoload_register(static function (string $class): void {
    $file = BASE_PATH . '/src/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

date_default_timezone_set((string) ($config['timezone'] ?? 'Asia/Jakarta'));

$debug = (bool) ($config['debug'] ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name('PRESENSIKU_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (PHP_SAPI === 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
