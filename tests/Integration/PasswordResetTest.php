<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\AuditLogRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\PasswordResetService;
use RuntimeException;

final class PasswordResetTest extends ApiIntegrationTestCase
{
    public function testRequestDoesNotRevealWhetherTheEmailExists(): void
    {
        $existing = $this->dispatchJson('POST', '/api/v1/auth/password-reset', ['email' => 'admin1@example.com']);
        $unknown = $this->dispatchJson('POST', '/api/v1/auth/password-reset', ['email' => 'ninguem@example.com']);

        self::assertSame(202, $existing['status']);
        self::assertSame(202, $unknown['status']);
        self::assertSame($existing['body'], $unknown['body'], 'Respostas identicas evitam enumeracao de contas.');
    }

    public function testRequestWithoutEmailIsRejected(): void
    {
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/auth/password-reset', [])['status']);
    }

    public function testTokenIsStoredHashed(): void
    {
        $token = $this->requestToken('admin1@example.com');

        $row = $this->fetchOne('SELECT token FROM password_resets WHERE user_id = :id ORDER BY id DESC LIMIT 1', ['id' => 1]);

        self::assertNotSame($token, $row['token'], 'O token nao pode ficar em claro no banco.');
        self::assertSame(hash('sha256', $token), $row['token']);
    }

    public function testRequestingAgainInvalidatesThePreviousToken(): void
    {
        $first = $this->requestToken('admin1@example.com');
        $second = $this->requestToken('admin1@example.com');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Link invalido ou expirado');
        $this->service()->confirm($first, 'nova-senha-123');
    }

    public function testConfirmWithValidTokenChangesThePassword(): void
    {
        $token = $this->requestToken('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/auth/password-reset/confirm', [
            'token' => $token,
            'password' => 'nova-senha-123',
        ]);

        self::assertSame(200, $response['status']);

        $hash = $this->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => 1])['password'];
        self::assertTrue(password_verify('nova-senha-123', $hash));
        self::assertFalse(password_verify('secret123', $hash), 'A senha antiga nao pode continuar valendo.');
    }

    public function testConfirmWithoutFieldsIsRejected(): void
    {
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/auth/password-reset/confirm', [])['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/auth/password-reset/confirm', ['token' => 'x'])['status']);
    }

    public function testConfirmWithUnknownTokenIsRejected(): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/password-reset/confirm', [
            'token' => str_repeat('a', 64),
            'password' => 'nova-senha-123',
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Link invalido ou expirado.', $this->responseJson($response)['message']);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = $this->requestToken('admin1@example.com');
        $this->execSql(
            'UPDATE password_resets SET expires_at = :past WHERE user_id = :id',
            ['past' => date('Y-m-d H:i:s', time() - 60), 'id' => 1],
        );

        try {
            $this->service()->confirm($token, 'nova-senha-123');
            self::fail('Token expirado deveria ser rejeitado.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('expirado', $exception->getMessage());
        }

        $hash = $this->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => 1])['password'];
        self::assertTrue(password_verify('secret123', $hash));
    }

    public function testUsedTokenCannotBeReused(): void
    {
        $token = $this->requestToken('admin1@example.com');

        $this->service()->confirm($token, 'nova-senha-123');

        try {
            $this->service()->confirm($token, 'outra-senha-456');
            self::fail('Token ja usado deveria ser rejeitado.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('ja foi utilizado', $exception->getMessage());
        }
    }

    public function testWeakPasswordIsRejectedWithoutChangingThePassword(): void
    {
        $token = $this->requestToken('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/auth/password-reset/confirm', [
            'token' => $token,
            'password' => '123',
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('8 caracteres', $this->responseJson($response)['message']);

        $hash = $this->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => 1])['password'];
        self::assertTrue(password_verify('secret123', $hash), 'Senha fraca nao pode ter sido aplicada.');
    }

    public function testResetForInactiveUserIsRejected(): void
    {
        $this->execSql('UPDATE users SET status = :status WHERE id = :id', ['status' => 'INACTIVE', 'id' => 2]);
        $token = $this->requestToken('viewer1@example.com');

        try {
            $this->service()->confirm($token, 'nova-senha-123');
            self::fail('Usuario inativo deveria ser rejeitado.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('inativo', $exception->getMessage());
        }
    }

    public function testResetInvalidatesExistingSessions(): void
    {
        $this->loginAs('admin1@example.com');
        self::assertSame(200, $this->dispatchPage('GET', '/dashboard')['status'], 'Antes do reset a sessao vale.');

        $token = $this->requestToken('admin1@example.com');
        $this->service()->confirm($token, 'nova-senha-123');

        // A mesma sessao agora carrega auth_version antiga.
        $response = $this->dispatchPage('GET', '/dashboard');
        self::assertSame(302, $response['status'], 'A sessao antiga deve cair.');
        self::assertSame('/login', $response['headers']['Location']);
    }

    public function testChangingPasswordAlsoInvalidatesTheTargetSessions(): void
    {
        $this->loginAs('admin1@example.com');
        $before = (int) $this->fetchOne('SELECT auth_version FROM users WHERE id = :id', ['id' => 2])['auth_version'];

        $response = $this->dispatchJson('POST', '/api/v1/users/2/password', ['password' => 'nova-senha-123']);
        self::assertSame(200, $response['status']);

        $after = (int) $this->fetchOne('SELECT auth_version FROM users WHERE id = :id', ['id' => 2])['auth_version'];
        self::assertSame($before + 1, $after, 'Trocar a senha precisa derrubar as sessoes do usuario alvo.');
    }

    public function testLoginWithTheNewPasswordWorksAndTheOldDoesNot(): void
    {
        $token = $this->requestToken('admin1@example.com');
        $this->service()->confirm($token, 'nova-senha-123');

        $old = $this->dispatchJson('POST', '/api/v1/auth/login', ['email' => 'admin1@example.com', 'password' => 'secret123']);
        self::assertSame(401, $old['status']);

        $new = $this->dispatchJson('POST', '/api/v1/auth/login', ['email' => 'admin1@example.com', 'password' => 'nova-senha-123']);
        self::assertSame(200, $new['status']);
    }

    public function testForgotPasswordPageRenders(): void
    {
        $response = $this->dispatchPage('GET', '/esqueci-senha');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Esqueci minha senha', $response['body']);
        self::assertStringContainsString('action="/esqueci-senha"', $response['body']);
    }

    public function testResetPasswordPageRendersWithToken(): void
    {
        $response = $this->dispatchPage('GET', '/redefinir-senha?token=abc123');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Definir nova senha', $response['body']);
        self::assertStringContainsString('name="token" value="abc123"', $response['body']);
    }

    public function testLoginPageLinksToForgotPassword(): void
    {
        $response = $this->dispatchPage('GET', '/login');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('href="/esqueci-senha"', $response['body']);
    }

    public function testWebFlowFromForgotToResetToLogin(): void
    {
        $forgot = $this->dispatchUserForm('POST', '/esqueci-senha', ['email' => 'admin1@example.com']);
        self::assertSame(302, $forgot['status']);
        self::assertSame('/esqueci-senha', $forgot['headers']['Location']);

        // Em debug o link aparece na tela, ja que ainda nao ha envio de email.
        $page = $this->dispatchPage('GET', '/esqueci-senha');
        self::assertStringContainsString('Se o email estiver cadastrado', $page['body']);
        self::assertSame(1, preg_match('#/redefinir-senha\?token=([a-f0-9]+)#', $page['body'], $matches), 'O link de dev deveria aparecer.');
        $token = $matches[1];

        $form = $this->dispatchPage('GET', '/redefinir-senha?token=' . $token);
        self::assertSame(200, $form['status']);
        self::assertStringContainsString($token, $form['body']);

        $reset = $this->dispatchUserForm('POST', '/redefinir-senha', [
            'token' => $token,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);
        self::assertSame(302, $reset['status']);
        self::assertSame('/login', $reset['headers']['Location']);

        $login = $this->dispatchJson('POST', '/api/v1/auth/login', ['email' => 'admin1@example.com', 'password' => 'nova-senha-123']);
        self::assertSame(200, $login['status'], 'O fluxo web completo precisa permitir login com a nova senha.');
    }

    public function testWebResetWithMismatchedConfirmationDoesNotChangeThePassword(): void
    {
        $token = $this->requestToken('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/redefinir-senha', [
            'token' => $token,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'diferente-123',
        ]);

        self::assertSame(302, $response['status']);
        self::assertStringContainsString('/redefinir-senha?token=', $response['headers']['Location']);

        $hash = $this->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => 1])['password'];
        self::assertTrue(password_verify('secret123', $hash));
    }

    public function testWebForgotWithInvalidEmailShowsError(): void
    {
        $response = $this->dispatchUserForm('POST', '/esqueci-senha', ['email' => 'nao-e-email']);

        self::assertSame(302, $response['status']);

        $page = $this->dispatchPage('GET', '/esqueci-senha');
        self::assertStringContainsString('Informe um email valido', $page['body']);
    }

    private function service(): PasswordResetService
    {
        $this->bootApplication();

        return new PasswordResetService(
            new UserRepository(),
            new PasswordResetRepository(),
            new AuditLogRepository(),
        );
    }

    private function requestToken(string $email): string
    {
        $token = $this->service()->request($email);

        self::assertNotNull($token, 'Token deveria ser gerado para ' . $email);

        return $token;
    }

    private function loginAs(string $email): void
    {
        $_SESSION = [];

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'Login falhou para ' . $email);
    }
}
