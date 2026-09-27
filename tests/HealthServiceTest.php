<?php

declare(strict_types=1);

namespace Tests;

use App\Services\HealthService;
use PHPUnit\Framework\TestCase;

final class HealthServiceTest extends TestCase
{
    public function testStatusReturnsOperationalPayload(): void
    {
        $service = new HealthService();
        $status = $service->status();

        self::assertSame('ok', $status['status']);
        self::assertSame('importcontrol', $status['service']);
        self::assertArrayHasKey('timestamp', $status);
    }
}
