<?php

declare(strict_types=1);

$environment = (string) env('APP_ENV', 'production');
$isProduction = $environment === 'production';

return [
    'name' => env('APP_NAME', 'ImportControl'),
    'env' => $environment,
    'debug' => $isProduction ? false : filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'idle_timeout' => (int) env('SESSION_IDLE_TIMEOUT', 30),
        'cookie' => 'importcontrol_session',
        'secure' => filter_var(env('SESSION_SECURE_COOKIE', $isProduction), FILTER_VALIDATE_BOOL),
        'httponly' => true,
        'samesite' => env('SESSION_SAME_SITE', 'Lax'),
    ],
    'logging' => [
        'path' => env('LOG_PATH', dirname(__DIR__) . '/storage/logs/app.log'),
        'level' => env('LOG_LEVEL', $isProduction ? 'info' : 'debug'),
    ],
];
