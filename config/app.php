<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ImportControl'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'cookie' => 'importcontrol_session',
    ],
];
