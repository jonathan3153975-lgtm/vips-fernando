<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

date_default_timezone_set('America/Sao_Paulo');

$_ENV['LOG_PATH'] = sys_get_temp_dir() . '/importcontrol-test.log';
$_SERVER['LOG_PATH'] = $_ENV['LOG_PATH'];
$_ENV['LOG_LEVEL'] = 'debug';
$_SERVER['LOG_LEVEL'] = 'debug';
