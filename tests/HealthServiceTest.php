<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Application;
use App\Services\HealthService;
use PHPUnit\Framework\TestCase;

final class HealthServiceTest extends TestCase
{
    public function testStatusReportsDegradedWhenDatabaseIsUnavailable(): void
    {
        new Application($this->unreachableDatabaseConfig(), __DIR__);

        $status = (new HealthService())->status();

        self::assertSame('degraded', $status['status']);
        self::assertSame('importcontrol', $status['service']);
        self::assertArrayHasKey('timestamp', $status);
        self::assertFalse($status['checks']['database']['ok']);
        self::assertSame('sqlite', $status['checks']['database']['driver']);
        self::assertNotNull($status['checks']['database']['message']);
    }

    public function testConfigurationNeverEnablesDebugInProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'true';

        $config = require dirname(__DIR__) . '/config/app.php';

        unset($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);

        self::assertIsArray($config);
        self::assertFalse($config['debug']);
        self::assertTrue($config['session']['secure']);
        self::assertTrue($config['session']['httponly']);
    }

    public function testConfigurationAllowsDebugOutsideProduction(): void
    {
        $_ENV['APP_ENV'] = 'local';
        $_ENV['APP_DEBUG'] = 'true';

        $config = require dirname(__DIR__) . '/config/app.php';

        unset($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);

        self::assertIsArray($config);
        self::assertTrue($config['debug']);
        self::assertFalse($config['session']['secure']);
        self::assertContains($config['session']['samesite'], ['Lax', 'Strict', 'None']);
    }

    private function unreachableDatabaseConfig(): array
    {
        return [
            'app' => [
                'name' => 'ImportControl Test',
                'env' => 'testing',
                'debug' => false,
                'timezone' => 'America/Sao_Paulo',
            ],
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => sys_get_temp_dir() . '/importcontrol-diretorio-inexistente/db.sqlite',
                    ],
                ],
            ],
        ];
    }
}
