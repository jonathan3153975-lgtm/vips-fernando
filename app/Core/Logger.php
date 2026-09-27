<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Logger
{
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'warning' => 300,
        'error' => 400,
    ];

    private const REDACTED_KEYS = ['password', 'password_confirmation', 'token', 'secret', 'authorization'];

    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function exception(Throwable $throwable, string $message = 'Excecao nao tratada.'): void
    {
        self::write('error', $message, [
            'exception' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'error' => $throwable->getMessage(),
        ]);
    }

    /**
     * @return array{path: string, level: string, debug: bool}
     */
    public static function configuration(): array
    {
        $config = self::applicationConfiguration();
        $fallback = dirname(__DIR__, 2) . '/storage/logs/app.log';

        $path = $config['logging.path'] ?? self::environmentValue('LOG_PATH') ?? $fallback;
        $level = $config['logging.level'] ?? self::environmentValue('LOG_LEVEL') ?? 'info';
        $level = is_string($level) ? strtolower($level) : 'info';

        return [
            'path' => is_string($path) && $path !== '' ? $path : $fallback,
            'level' => isset(self::LEVELS[$level]) ? $level : 'info',
            'debug' => (bool) ($config['app.debug'] ?? false),
        ];
    }

    private static function write(string $level, string $message, array $context): void
    {
        $configuration = self::configuration();

        if (!self::shouldLog($level, $configuration)) {
            return;
        }

        $entry = sprintf(
            '[%s] %s: %s%s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : ' ' . self::encode($context),
            PHP_EOL,
        );

        $directory = dirname($configuration['path']);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log('ImportControl: diretorio de log inexistente: ' . $directory);

            return;
        }

        if (file_put_contents($configuration['path'], $entry, FILE_APPEND | LOCK_EX) === false) {
            error_log('ImportControl: falha ao gravar log em ' . $configuration['path'] . '.');
        }
    }

    /**
     * @param array{path: string, level: string, debug: bool} $configuration
     */
    private static function shouldLog(string $level, array $configuration): bool
    {
        if ($level === 'debug' && $configuration['debug'] === false) {
            return false;
        }

        return (self::LEVELS[$level] ?? self::LEVELS['info']) >= self::LEVELS[$configuration['level']];
    }

    /**
     * @return array<string, mixed>
     */
    private static function applicationConfiguration(): array
    {
        try {
            $application = Application::getInstance();

            return [
                'logging.path' => $application->config('logging.path'),
                'logging.level' => $application->config('logging.level'),
                'app.debug' => $application->config('app.debug', false),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    private static function environmentValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    private static function encode(array $context): string
    {
        return (string) json_encode(self::redact($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $context[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
