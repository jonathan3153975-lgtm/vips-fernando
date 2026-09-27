<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Request;

final class RoleManagementTest extends ApiIntegrationTestCase
{
    private const ADMIN_ROLE = 1;

    private const VIEWER_ROLE = 2;

    private const OTHER_TENANT_ROLE = 3;

    public function testListOnlyReturnsRolesOfCurrentTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/roles');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame([self::ADMIN_ROLE, self::VIEWER_ROLE], array_column($payload['data'], 'id'));
        self::assertSame([1, 1], array_column($payload['data'], 'tenant_id'));
    }

    public function testShowRoleOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/roles/' . self::OTHER_TENANT_ROLE);

        self::assertSame(404, $response['status']);
        self::assertSame('Perfil nao encontrado.', $this->responseJson($response)['message']);
    }

    public function testUpdatePermissionsOfRoleFromAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('PUT', '/api/v1/roles/' . self::OTHER_TENANT_ROLE . '/permissions', [
            'permission_ids' => [1],
        ]);

        self::assertSame(404, $response['status']);
        self::assertNotSame(
            [1],
            $this->permissionIdsForRole(self::OTHER_TENANT_ROLE),
            'As permissoes do outro tenant nao podem ter sido alteradas.',
        );
    }

    public function testDeleteRoleOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('DELETE', '/api/v1/roles/' . self::OTHER_TENANT_ROLE);

        self::assertSame(404, $response['status']);
        self::assertNotNull($this->fetchOne('SELECT id FROM roles WHERE id = :id', ['id' => self::OTHER_TENANT_ROLE]));
    }

    public function testPermissionCatalogIsGlobal(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/roles/permissions');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertContains('users.manage', array_column($payload['data'], 'name'));
        self::assertContains('users.view', array_column($payload['data'], 'name'));
    }

    public function testCreateRoleWithPermissions(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'Auditor',
            'description' => 'Somente leitura',
            'permission_ids' => [2, 5],
        ]);

        self::assertSame(201, $response['status']);

        $created = $this->responseJson($response)['data'];
        self::assertSame(1, (int) $created['tenant_id']);
        self::assertSame(0, (int) $created['is_system']);
        self::assertSame(['imports.view', 'products.view'], array_column($created['permissions'], 'name'));
    }

    public function testCreateRoleRejectsDuplicateName(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'admin',
            'permission_ids' => [],
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Ja existe um perfil com este nome.', $this->responseJson($response)['message']);
    }

    public function testSameRoleNameInAnotherTenantIsAllowed(): void
    {
        $this->loginAs('admin2@example.com');

        // O tenant 1 tem um perfil chamado "viewer"; o tenant 2 nao tem.
        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'viewer',
            'permission_ids' => [2],
        ]);

        self::assertSame(201, $response['status'], 'O indice de perfis e por (tenant_id, name).');
        self::assertSame(2, (int) $this->responseJson($response)['data']['tenant_id']);
    }

    public function testDuplicateRoleNameWithinSameTenantIsRejected(): void
    {
        $this->loginAs('admin2@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'admin',
            'permission_ids' => [2],
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Ja existe um perfil com este nome.', $this->responseJson($response)['message']);
    }

    public function testCreateRoleRejectsUnknownPermission(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'Perfil Ruim',
            'permission_ids' => [2, 9999],
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame('Permissao inexistente: 9999', $this->responseJson($response)['message']);
        self::assertNull($this->fetchOne('SELECT id FROM roles WHERE name = :name', ['name' => 'Perfil Ruim']));
    }

    public function testReplacePermissionsIsIdempotentAndDoesNotDuplicate(): void
    {
        $this->loginAs('admin1@example.com');

        $roleId = $this->createRole('Temporario', [2]);

        $first = $this->dispatchJson('PUT', '/api/v1/roles/' . $roleId . '/permissions', [
            'permission_ids' => [2, 5],
        ]);
        self::assertSame(200, $first['status']);
        self::assertSame([2, 5], $this->permissionIdsForRole($roleId));

        $second = $this->dispatchJson('PUT', '/api/v1/roles/' . $roleId . '/permissions', [
            'permission_ids' => [2, 5, 5, 2],
        ]);
        self::assertSame(200, $second['status']);
        self::assertSame([2, 5], $this->permissionIdsForRole($roleId), 'Nao deve duplicar linhas.');

        $cleared = $this->dispatchJson('PUT', '/api/v1/roles/' . $roleId . '/permissions', [
            'permission_ids' => [],
        ]);
        self::assertSame(200, $cleared['status']);
        self::assertSame([], $this->permissionIdsForRole($roleId));
    }

    public function testCannotRemoveUserManagementFromTheOnlyRoleGrantingIt(): void
    {
        $this->loginAs('admin1@example.com');

        $usersManageId = $this->permissionIdByName('users.manage');

        $response = $this->dispatchJson('PUT', '/api/v1/roles/' . self::ADMIN_ROLE . '/permissions', [
            'permission_ids' => [1, 2, 3],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString(
            'unico perfil que a concede',
            $this->responseJson($response)['message'],
        );
        self::assertContains($usersManageId, $this->permissionIdsForRole(self::ADMIN_ROLE));
    }

    public function testCanRemoveUserManagementWhenAnotherRoleGrantsIt(): void
    {
        $this->loginAs('admin1@example.com');

        $otherRoleId = $this->createRole('Administrador Extra', [9, 10]);
        self::assertNotSame([], $this->permissionIdsForRole($otherRoleId));

        $response = $this->dispatchJson('PUT', '/api/v1/roles/' . self::ADMIN_ROLE . '/permissions', [
            'permission_ids' => [1, 2, 3],
        ]);

        self::assertSame(200, $response['status']);
        self::assertNotContains($this->permissionIdByName('users.manage'), $this->permissionIdsForRole(self::ADMIN_ROLE));
    }

    public function testCannotDeleteSystemRole(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('DELETE', '/api/v1/roles/' . self::ADMIN_ROLE);

        self::assertSame(400, $response['status']);
        self::assertSame('Perfis do sistema nao podem ser excluidos.', $this->responseJson($response)['message']);
        self::assertNotNull($this->fetchOne('SELECT id FROM roles WHERE id = :id', ['id' => self::ADMIN_ROLE]));
    }

    public function testCannotDeleteRoleWithUsers(): void
    {
        $this->loginAs('admin1@example.com');

        $roleId = $this->createRole('Com Usuarios', [2]);
        $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Membro',
            'email' => 'membro@exemplo.com',
            'password' => 'senha-segura-123',
            'role_id' => $roleId,
        ]);

        $response = $this->dispatchJson('DELETE', '/api/v1/roles/' . $roleId);

        self::assertSame(400, $response['status']);
        self::assertSame('Ha usuarios neste perfil. Reatribua-os antes de excluir.', $this->responseJson($response)['message']);
    }

    public function testDeleteOwnRoleWithoutUsersRemovesItsPermissions(): void
    {
        $this->loginAs('admin1@example.com');

        $roleId = $this->createRole('Descartavel', [2, 5]);

        $response = $this->dispatchJson('DELETE', '/api/v1/roles/' . $roleId);

        self::assertSame(200, $response['status']);
        self::assertTrue((bool) $this->responseJson($response)['data']['deleted']);
        self::assertNull($this->fetchOne('SELECT id FROM roles WHERE id = :id', ['id' => $roleId]));
        self::assertSame([], $this->fetchAllRows('SELECT id FROM role_permissions WHERE role_id = :id', ['id' => $roleId]));
    }

    public function testRoleNameIsScopedToTenantOnUpdate(): void
    {
        $this->loginAs('admin1@example.com');

        $roleId = $this->createRole('Renomeavel', []);

        $response = $this->dispatchJson('PUT', '/api/v1/roles/' . $roleId, ['name' => 'viewer']);

        self::assertSame(400, $response['status']);
        self::assertSame('Renomeavel', $this->fetchOne('SELECT name FROM roles WHERE id = :id', ['id' => $roleId])['name']);
    }

    public function testViewerCannotManageRoles(): void
    {
        $this->loginAs('viewer1@example.com');

        self::assertSame(403, $this->dispatchJson('GET', '/api/v1/roles')['status']);

        $create = $this->dispatchJson('POST', '/api/v1/roles', ['name' => 'Nao Autorizado', 'permission_ids' => []]);
        self::assertSame(403, $create['status']);
        self::assertNull($this->fetchOne('SELECT id FROM roles WHERE name = :name', ['name' => 'Nao Autorizado']));
    }

    public function testRolesPageRendersOnlyCurrentTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchPage('GET', '/perfis');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('admin', $response['body']);
        self::assertStringContainsString('viewer', $response['body']);
        self::assertStringNotContainsString('Tenant Two', $response['body']);
    }

    public function testRoleDetailPageListsGrantedAndAvailablePermissions(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchPage('GET', '/perfis/' . self::VIEWER_ROLE);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('imports.view', $response['body']);
        self::assertStringContainsString('name="permission_ids[]"', $response['body']);
    }

    public function testRoleDetailPageOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        self::assertSame(404, $this->dispatchPage('GET', '/perfis/' . self::OTHER_TENANT_ROLE)['status']);
    }

    /**
     * O formulario nativo envia x-www-form-urlencoded, que o CsrfMiddleware
     * rejeita com 415 em qualquer rota /api/. A tela por isso posta em
     * /perfis/{id}/permissoes, e nao na API.
     */
    public function testPermissionFormSavesThroughWebRoute(): void
    {
        $this->loginAs('admin1@example.com');
        $roleId = $this->createRole('Via Formulario', [2, 5]);

        $response = $this->dispatchUserForm('POST', '/perfis/' . $roleId . '/permissoes', [
            'permission_ids' => ['3', '5'],
        ]);

        self::assertSame(302, $response['status'], 'Form web nao pode ser rejeitado como JSON.');
        self::assertSame('/perfis/' . $roleId, $response['headers']['Location']);
        self::assertSame([3, 5], $this->permissionIdsForRole($roleId));
    }

    public function testUnmarkingEveryCheckboxClearsPermissions(): void
    {
        $this->loginAs('admin1@example.com');
        $roleId = $this->createRole('A Limpar', [2, 5]);

        // Sem nenhuma caixa marcada o navegador nao envia o campo.
        $response = $this->dispatchUserForm('POST', '/perfis/' . $roleId . '/permissoes', []);

        self::assertSame(302, $response['status']);
        self::assertSame([], $this->permissionIdsForRole($roleId));
    }

    /**
     * Regressao: com o corpo urlencoded preenchido (como no SAPI real), o
     * parser do Request nao pode tentar JSON e descartar o $_POST, senao o
     * formulario salva zero permissoes em silencio.
     */
    public function testPermissionFormSavesWhenTheRawBodyIsPresent(): void
    {
        $this->loginAs('admin1@example.com');
        $roleId = $this->createRole('Corpo Presente', [2]);

        $response = $this->dispatchNativeForm('POST', '/perfis/' . $roleId . '/permissoes', [
            'permission_ids' => ['2', '5'],
        ]);

        self::assertSame(302, $response['status']);
        self::assertSame([2, 5], $this->permissionIdsForRole($roleId), 'O $_POST do form foi descartado.');
    }

    public function testFormPostKeepsJsonAndFormBodiesApart(): void
    {
        $_POST = ['name' => 'Do Form'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/roles';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_SERVER['__BODY__'] = 'name=Do+Form';

        self::assertSame(['name' => 'Do Form'], Request::all());

        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['__BODY__'] = '{"name":"Do Json"}';

        self::assertSame(['name' => 'Do Json'], Request::all());
    }

    public function testPermissionFormRejectsTheOrphanedAdminInvariantWithoutWriting(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/perfis/' . self::ADMIN_ROLE . '/permissoes', [
            'permission_ids' => ['1', '2', '3'],
        ]);

        self::assertSame(302, $response['status']);
        self::assertContains($this->permissionIdByName('users.manage'), $this->permissionIdsForRole(self::ADMIN_ROLE));
    }

    public function testPermissionFormOnRoleOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');
        $before = $this->permissionIdsForRole(self::OTHER_TENANT_ROLE);

        $response = $this->dispatchUserForm('POST', '/perfis/' . self::OTHER_TENANT_ROLE . '/permissoes', [
            'permission_ids' => ['2'],
        ]);

        self::assertSame(404, $response['status']);
        self::assertSame($before, $this->permissionIdsForRole(self::OTHER_TENANT_ROLE));
    }

    public function testViewerCannotSavePermissionsThroughTheForm(): void
    {
        $this->loginAs('viewer1@example.com');
        $before = $this->permissionIdsForRole(self::VIEWER_ROLE);

        $response = $this->dispatchUserForm('POST', '/perfis/' . self::VIEWER_ROLE . '/permissoes', [
            'permission_ids' => ['1'],
        ]);

        self::assertSame(403, $response['status']);
        self::assertSame($before, $this->permissionIdsForRole(self::VIEWER_ROLE));
    }

    /**
     * @param list<int> $permissionIds
     */
    private function createRole(string $name, array $permissionIds): int
    {
        $response = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => $name,
            'permission_ids' => $permissionIds,
        ]);

        self::assertSame(201, $response['status'], 'Falha ao criar perfil ' . $name . ': ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    private function permissionIdByName(string $name): int
    {
        $row = $this->fetchOne('SELECT id FROM permissions WHERE name = :name', ['name' => $name]);

        self::assertNotNull($row, 'Permissao ausente no catalogo: ' . $name);

        return (int) $row['id'];
    }

    /**
     * @return list<int>
     */
    private function permissionIdsForRole(int $roleId): array
    {
        $rows = $this->fetchAllRows(
            'SELECT permission_id FROM role_permissions WHERE role_id = :id ORDER BY permission_id',
            ['id' => $roleId],
        );

        return array_map(static fn (array $row): int => (int) $row['permission_id'], $rows);
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
