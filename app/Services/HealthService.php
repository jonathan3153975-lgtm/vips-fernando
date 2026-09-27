<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;
use PDO;
use Throwable;

final class HealthService
{
    public function status(): array
    {
        $database = $this->databaseStatus();

        return [
            'status' => $database['ok'] ? 'ok' : 'degraded',
            'service' => 'importcontrol',
            'environment' => $this->environment(),
            'timestamp' => date(DATE_ATOM),
            'checks' => [
                'database' => $database,
            ],
        ];
    }

    /**
     * @return array{ok: bool, driver: string, message: string|null}
     */
    private function databaseStatus(): array
    {
        try {
            $application = Application::getInstance();
            $configuration = $application->config('database');
        } catch (Throwable $throwable) {
            return [
                'ok' => false,
                'driver' => 'unknown',
                'message' => 'Configuracao de banco indisponivel: ' . $throwable->getMessage(),
            ];
        }

        if (!is_array($configuration)) {
            return [
                'ok' => false,
                'driver' => 'unknown',
                'message' => 'Configuracao de banco ausente.',
            ];
        }

        $driver = (string) ($configuration['connections'][$configuration['default']]['driver'] ?? 'unknown');

        try {
            Database::connect($configuration)->query('SELECT 1');
        } catch (Throwable $throwable) {
            return [
                'ok' => false,
                'driver' => $driver,
                'message' => $throwable->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'driver' => $driver,
            'message' => null,
        ];
    }

    private function environment(): string
    {
        try {
            return (string) (Application::getInstance()->config('app.env', 'unknown'));
        } catch (Throwable) {
            return 'unknown';
        }
    }
}
