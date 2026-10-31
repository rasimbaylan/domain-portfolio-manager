<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

$configFile = APP_ROOT . '/config/config.php';

if (!is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="tr"><meta charset="utf-8"><title>Kurulum gerekli</title>';
    echo '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:4rem auto;padding:0 1rem">';
    echo '<h1>Kurulum tamamlanmadı</h1>';
    echo '<p>Uygulamayı kullanmadan önce terminalde şu komutu çalıştırın:</p>';
    echo '<pre style="background:#f4f6f9;padding:1rem;border-radius:8px">php bin/install.php</pre>';
    echo '</body></html>';
    exit;
}

/** @var array{app: array<string, mixed>, db: array<string, mixed>} $appConfig */
$appConfig = require $configFile;
$GLOBALS['appConfig'] = $appConfig;

$isDebug = ($appConfig['app']['env'] ?? 'production') === 'development';

error_reporting(E_ALL);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');

$logDir = APP_ROOT . '/storage/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}

date_default_timezone_set((string)($appConfig['app']['timezone'] ?? 'Europe/Istanbul'));

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443');

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; script-src 'self'; style-src 'self'");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$cookiePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/') . '/';
if ($cookiePath === '//' || $cookiePath === '') {
    $cookiePath = '/';
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string)($appConfig['app']['session_name'] ?? 'opencode_portfolio'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '5');

    session_start();
}

require_once APP_ROOT . '/app/helpers.php';
require_once APP_ROOT . '/app/icons.php';
require_once APP_ROOT . '/app/Database.php';
require_once APP_ROOT . '/app/Csrf.php';
require_once APP_ROOT . '/app/Auth.php';
require_once APP_ROOT . '/app/DomainRepository.php';

\App\Database::configure($appConfig['db']);
