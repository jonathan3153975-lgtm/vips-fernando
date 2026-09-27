<?php

declare(strict_types=1);

namespace Tests\Integration;

final class AuthApiTest extends ApiIntegrationTestCase
{
    public function testLoginEndpointAuthenticatesAndStoresSession(): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame(1, $payload['data']['tenant_id']);
        self::assertContains('imports.create', $payload['data']['permissions']);
        self::assertSame(1, $_SESSION['auth']['tenant_id']);
    }
}
