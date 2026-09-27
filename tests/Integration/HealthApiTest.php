<?php

declare(strict_types=1);

namespace Tests\Integration;

final class HealthApiTest extends ApiIntegrationTestCase
{
    public function testHealthCheckReportsOkWhenDatabaseIsReachable(): void
    {
        $response = $this->dispatchForm('GET', '/health');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('importcontrol', $payload['service']);
        self::assertTrue($payload['checks']['database']['ok']);
        self::assertNull($payload['checks']['database']['message']);
    }

    public function testHealthCheckReportsDegradedWhenDatabaseIsUnreachable(): void
    {
        $response = $this->dispatchWithDatabaseConfig([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => sys_get_temp_dir() . '/importcontrol-diretorio-inexistente/db.sqlite',
                ],
            ],
        ], 'GET', '/health');

        $payload = $this->responseJson($response);

        self::assertSame(503, $response['status']);
        self::assertSame('degraded', $payload['status']);
        self::assertFalse($payload['checks']['database']['ok']);
        self::assertNotNull($payload['checks']['database']['message']);
    }
}
