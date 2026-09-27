<?php

declare(strict_types=1);

use App\Core\Application;
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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['app']['session']['cookie']);
    session_set_cookie_params([
        'lifetime' => $config['app']['session']['lifetime'] * 60,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

return new Application(
    $config,
    dirname(__DIR__) . '/routes/web.php'
);
