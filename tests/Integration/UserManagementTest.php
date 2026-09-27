<?php

declare(strict_types=1);

namespace Tests\Integration;

final class UserManagementTest extends ApiIntegrationTestCase
{
    private const ADMIN_ROLE = 1;

    private const VIEWER_ROLE = 2;

    private const OTHER_TENANT_ROLE = 3;

    private const ADMIN_USER = 1;

    private const VIEWER_USER = 2;

    private const OTHER_TENANT_USER = 3;

    public function testListOnlyReturnsUsersOfCurrentTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/users');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertCount(2, $payload['data']);
        self::assertSame([1, 2], array_column($payload['data'], 'id'));
        self::assertSame([1, 1], array_column($payload['data'], 'tenant_id'));
    }

    public function testShowUserOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/users/' . self::OTHER_TENANT_USER);

        self::assertSame(404, $response['status']);
        self::assertSame('Usuario nao encontrado.', $this->responseJson($response)['message']);
    }

    public function testUpdateUserOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('PUT', '/api/v1/users/' . self::OTHER_TENANT_USER, [
            'name' => 'Sequestrado',
        ]);

        self::assertSame(404, $response['status']);
        self::assertSame(
            'Admin Two',
            $this->fetchOne('SELECT name FROM users WHERE id = :id', ['id' => self::OTHER_TENANT_USER])['name'],
        );
    }

    public function testCreateUserHashesPasswordAndLogsAudit(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Novo Usuario',
            'email' => 'novo@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(201, $response['status']);

        $created = $this->responseJson($response)['data'];
        self::assertSame(1, (int) $created['tenant_id']);
        self::assertSame('novo@exemplo.com', $created['email']);
        self::assertSame('ACTIVE', $created['status']);
        self::assertArrayNotHasKey('password', $created);

        $stored = $this->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => $created['id']]);
        self::assertNotSame('senha-segura-123', $stored['password']);
        self::assertTrue(password_verify('senha-segura-123', $stored['password']));

        $audit = $this->fetchAllRows('SELECT action FROM audit_logs WHERE entity_id = :id', ['id' => $created['id']]);
        self::assertSame([['action' => 'users.create']], $audit);
    }

    public function testCreatedUserCanLogIn(): void
    {
        $this->loginAs('admin1@example.com');
        $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Novo Usuario',
            'email' => 'novo@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        $_SESSION = [];

        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'novo@exemplo.com',
            'password' => 'senha-segura-123',
        ]);

        self::assertSame(200, $login['status']);
        self::assertSame(['imports.view'], $this->responseJson($login)['data']['permissions']);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Duplicado',
            'email' => 'admin1@example.com',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Este e-mail ja esta em uso.', $this->responseJson($response)['message']);
    }

    public function testDuplicateEmailFromAnotherTenantIsAlsoRejected(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Colisao',
            'email' => 'admin2@example.com',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Este e-mail ja esta em uso.', $this->responseJson($response)['message']);
    }

    public function testWeakPasswordIsRejected(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Senha Fraca',
            'email' => 'fraca@exemplo.com',
            'password' => '123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame(
            'A senha deve ter ao menos 8 caracteres.',
            $this->responseJson($response)['message'],
        );
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Email Ruim',
            'email' => 'nao-e-email',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('E-mail invalido.', $this->responseJson($response)['message']);
    }

    public function testCannotAssignRoleFromAnotherTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Escalada',
            'email' => 'escalada@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::OTHER_TENANT_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Perfil invalido.', $this->responseJson($response)['message']);
        self::assertNull($this->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => 'escalada@exemplo.com']));
    }

    public function testCannotDemoteUserToRoleFromAnotherTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('PUT', '/api/v1/users/2', [
            'role_id' => self::OTHER_TENANT_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Perfil invalido.', $this->responseJson($response)['message']);
        self::assertSame(
            self::VIEWER_ROLE,
            (int) $this->fetchOne('SELECT role_id FROM users WHERE id = 2')['role_id'],
        );
    }

    public function testCannotBlockTheLastActiveAdmin(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users/1/block');

        self::assertSame(400, $response['status']);
        self::assertSame(
            'Nao e possivel remover o ultimo administrador ativo do tenant.',
            $this->responseJson($response)['message'],
        );
        self::assertSame('ACTIVE', $this->fetchOne('SELECT status FROM users WHERE id = 1')['status']);
    }

    public function testCannotDemoteTheLastActiveAdmin(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('PUT', '/api/v1/users/1', [
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame(
            'Nao e possivel remover o ultimo administrador ativo do tenant.',
            $this->responseJson($response)['message'],
        );
        self::assertSame(
            self::ADMIN_ROLE,
            (int) $this->fetchOne('SELECT role_id FROM users WHERE id = 1')['role_id'],
        );
    }

    public function testCannotBlockTheOnlyAdminOfAnotherTenantAffectingThisOne(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users/3/block');

        self::assertSame(404, $response['status']);
        self::assertSame('ACTIVE', $this->fetchOne('SELECT status FROM users WHERE id = 3')['status']);
    }

    public function testCanBlockAdminWhenAnotherActiveAdminExists(): void
    {
        $this->loginAs('admin1@example.com');
        $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Segundo Admin',
            'email' => 'admin2-local@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::ADMIN_ROLE,
        ]);

        $response = $this->dispatchJson('POST', '/api/v1/users/1/block');

        self::assertSame(200, $response['status']);
        self::assertSame('INACTIVE', $this->responseJson($response)['data']['status']);
    }

    public function testCanDemoteAdminWhenAnotherActiveAdminExists(): void
    {
        $this->loginAs('admin1@example.com');
        $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Segundo Admin',
            'email' => 'admin2-local@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::ADMIN_ROLE,
        ]);

        $response = $this->dispatchJson('PUT', '/api/v1/users/1', [
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(200, $response['status']);
        self::assertSame(self::VIEWER_ROLE, (int) $this->responseJson($response)['data']['role_id']);
    }

    public function testInactiveAdminLosingRoleIsAllowed(): void
    {
        $this->loginAs('admin1@example.com');

        foreach ([['b', 'Admin B'], ['c', 'Admin C']] as [$slug, $name]) {
            $this->dispatchJson('POST', '/api/v1/users', [
                'name' => $name,
                'email' => $slug . '@exemplo.com',
                'password' => 'senha-segura-123',
                'role_id' => self::ADMIN_ROLE,
            ]);
        }

        $adminB = (int) $this->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => 'b@exemplo.com'])['id'];

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/users/1/block')['status']);
        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/users/' . $adminB . '/block')['status']);

        $response = $this->dispatchJson('PUT', '/api/v1/users/' . $adminB, [
            'role_id' => self::VIEWER_ROLE,
        ]);

        self::assertSame(
            200,
            $response['status'],
            'Despromover quem ja esta inativo nao reduz a contagem de administradores ativos.',
        );
    }

    public function testCanBlockNonAdminUser(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users/2/block');

        self::assertSame(200, $response['status']);
        self::assertSame('INACTIVE', $this->responseJson($response)['data']['status']);

        $reactivated = $this->dispatchJson('POST', '/api/v1/users/2/activate');
        self::assertSame(200, $reactivated['status']);
        self::assertSame('ACTIVE', $this->responseJson($reactivated)['data']['status']);
    }

    public function testChangePasswordRequiresMinimumLength(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users/2/password', ['password' => 'abc']);

        self::assertSame(400, $response['status']);
    }

    public function testChangePasswordStoresHash(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/users/2/password', ['password' => 'nova-senha-123']);

        self::assertSame(200, $response['status']);

        $stored = $this->fetchOne('SELECT password FROM users WHERE id = 2')['password'];
        self::assertTrue(password_verify('nova-senha-123', $stored));
    }

    public function testViewerCannotManageUsers(): void
    {
        $this->loginAs('viewer1@example.com');

        $list = $this->dispatchJson('GET', '/api/v1/users');
        self::assertSame(403, $list['status']);

        $create = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Nao Autorizado',
            'email' => 'nao-autorizado@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => self::VIEWER_ROLE,
        ]);
        self::assertSame(403, $create['status']);
        self::assertNull($this->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => 'nao-autorizado@exemplo.com']));
    }

    public function testUsersWebPageRendersOnlyCurrentTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchPage('GET', '/usuarios');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Admin One', $response['body']);
        self::assertStringContainsString('Viewer One', $response['body']);
        self::assertStringNotContainsString('Admin Two', $response['body']);
        self::assertStringContainsString('href="/perfis"', $response['body']);
    }

    public function testUserShowPageOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchPage('GET', '/usuarios/' . self::OTHER_TENANT_USER);

        self::assertSame(404, $response['status']);
    }

    public function testViewerCannotOpenUsersPage(): void
    {
        $this->loginAs('viewer1@example.com');

        $response = $this->dispatchPage('GET', '/usuarios');

        self::assertSame(403, $response['status']);
    }

    /**
     * O formulario nativo nao pode postar em /api/: o CsrfMiddleware so aceita
     * application/json e responderia 415. A tela posta em /usuarios/{id}/bloquear.
     */
    public function testBlockFormWorksThroughWebRoute(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/usuarios/' . self::VIEWER_USER . '/bloquear');

        self::assertSame(302, $response['status'], 'Form web nao pode ser rejeitado como JSON.');
        self::assertSame('/usuarios/' . self::VIEWER_USER, $response['headers']['Location']);
        self::assertSame('INACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::VIEWER_USER],
        )['status']);
    }

    public function testActivateFormWorksThroughWebRoute(): void
    {
        $this->loginAs('admin1@example.com');

        $this->dispatchUserForm('POST', '/usuarios/' . self::VIEWER_USER . '/bloquear');
        $response = $this->dispatchUserForm('POST', '/usuarios/' . self::VIEWER_USER . '/ativar');

        self::assertSame(302, $response['status']);
        self::assertSame('ACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::VIEWER_USER],
        )['status']);
    }

    public function testBlockFormOnLastActiveAdminFlashesErrorAndKeepsUserActive(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/usuarios/' . self::ADMIN_USER . '/bloquear');

        self::assertSame(302, $response['status'], 'Regressao de regra volta para a tela, nao erro 500.');
        self::assertSame('ACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::ADMIN_USER],
        )['status']);

        $page = $this->dispatchPage('GET', '/usuarios/' . self::ADMIN_USER);
        self::assertSame(200, $page['status']);
        self::assertStringContainsString('ultimo administrador', $page['body']);
    }

    public function testBlockFormOnUserOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/usuarios/' . self::OTHER_TENANT_USER . '/bloquear');

        self::assertSame(404, $response['status']);
        self::assertSame('ACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::OTHER_TENANT_USER],
        )['status']);
    }

    public function testViewerCannotUseTheBlockForm(): void
    {
        $this->loginAs('viewer1@example.com');

        $response = $this->dispatchUserForm('POST', '/usuarios/' . self::ADMIN_USER . '/bloquear');

        self::assertSame(403, $response['status']);
        self::assertSame('ACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::ADMIN_USER],
        )['status']);
    }

    public function testFormPostWithoutCsrfTokenIsRejected(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchForm('POST', '/usuarios/' . self::VIEWER_USER . '/bloquear');

        self::assertSame(302, $response['status']);
        self::assertSame('ACTIVE', $this->fetchOne(
            'SELECT status FROM users WHERE id = :id',
            ['id' => self::VIEWER_USER],
        )['status'], 'Sem token CSRF nada pode mudar.');
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
