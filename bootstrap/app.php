<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Logger;
use App\Core\Session;
use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

$config = [
    'app' => require dirname(__DIR__) . '/config/app.php',
    'database' => require dirname(__DIR__) . '/config/database.php',
];

date_default_timezone_set($config['app']['timezone']);

$sessionConfig = $config['app']['session'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($sessionConfig['cookie']);
    session_set_cookie_params([
        'lifetime' => $sessionConfig['lifetime'] * 60,
        'path' => '/',
        'secure' => $sessionConfig['secure'],
        'httponly' => $sessionConfig['httponly'],
        'samesite' => $sessionConfig['samesite'],
    ]);
    session_start();
}

$session = new Session();

if ($session->isIdleExpired($sessionConfig['idle_timeout'])) {
    Logger::info('Sessao encerrada por inatividade.', ['path' => $_SERVER['REQUEST_URI'] ?? '/']);
    $session->invalidate();
    $session->put('_flash', ['auth_error' => 'Sua sessao expirou por inatividade. Entre novamente.']);
}

$session->touch();

if ($config['app']['env'] === 'production') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if ($sessionConfig['secure']) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

return new Application(
    $config,
    dirname(__DIR__) . '/routes/web.php'
);
