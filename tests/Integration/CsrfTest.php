<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Csrf;

final class CsrfTest extends ApiIntegrationTestCase
{
    public function testLoginPageRendersCsrfField(): void
    {
        $response = $this->dispatchForm('GET', '/login');
        $token = $this->csrfToken();

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('name="_token"', $response['body']);
        self::assertStringContainsString('value="' . $token . '"', $response['body']);
    }

    public function testWebPostWithoutTokenIsRejected(): void
    {
        $_SERVER['HTTP_REFERER'] = '/login';

        $response = $this->dispatchForm('POST', '/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(302, $response['status']);
        self::assertSame('/login', $response['headers']['Location']);
        self::assertArrayNotHasKey('auth', $_SESSION);
        self::assertSame(
            'Sessao expirada ou requisicao invalida. Tente novamente.',
            $_SESSION['_flash']['auth_error'] ?? null
        );
    }

    public function testWebPostWithInvalidTokenIsRejected(): void
    {
        $_SERVER['HTTP_REFERER'] = '/login';

        $response = $this->dispatchForm('POST', '/login', [
            Csrf::FIELD_NAME => 'token-invalido',
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(302, $response['status']);
        self::assertArrayNotHasKey('auth', $_SESSION);
    }

    public function testWebPostWithValidTokenAuthenticates(): void
    {
        $_SERVER['HTTP_REFERER'] = '/login';

        $response = $this->dispatchForm('POST', '/login', [
            Csrf::FIELD_NAME => $this->csrfToken(),
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(302, $response['status']);
        self::assertSame('/dashboard', $response['headers']['Location']);
        self::assertSame(1, $_SESSION['auth']['tenant_id']);
    }

    public function testLogoutWithoutTokenDoesNotInvalidateSession(): void
    {
        $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertArrayHasKey('auth', $_SESSION);

        $_SERVER['HTTP_REFERER'] = '/dashboard';
        $response = $this->dispatchForm('POST', '/logout');

        self::assertSame(302, $response['status']);
        self::assertArrayHasKey('auth', $_SESSION);
    }

    public function testApiRejectsNonJsonPayload(): void
    {
        $response = $this->dispatchForm('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(415, $response['status']);
        self::assertSame(
            'A API aceita apenas payloads application/json.',
            $this->responseJson($response)['message']
        );
        self::assertArrayNotHasKey('auth', $_SESSION);
    }
}
