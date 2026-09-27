<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use RuntimeException;

final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->roles->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $roleId): array
    {
        $role = $this->roles->findOrFail($roleId);

        $role['permissions'] = $this->roles->permissions($roleId);

        return $role;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function availablePermissions(): array
    {
        return $this->permissions->all();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Campo obrigatorio ausente: name');
        }

        if ($this->roles->nameExists($name)) {
            throw new RuntimeException('Ja existe um perfil com este nome.');
        }

        // Valida antes de criar: senao uma permissao invalida deixaria um perfil
        // orfao no banco, ja que a escrita acontece em duas etapas.
        $permissionIds = $this->sanitizePermissionIds($data['permission_ids'] ?? []);

        $role = $this->roles->create([
            'name' => $name,
            'description' => $data['description'] ?? null,
        ]);

        $this->roles->replacePermissions($role['id'], $permissionIds);

        $this->auditLogs->create('roles.create', 'role', (int) $role['id']);

        return $this->find((int) $role['id']);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $roleId, array $data): array
    {
        $current = $this->roles->findOrFail($roleId);

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw new RuntimeException('Campo obrigatorio ausente: name');
            }

            if ($this->roles->nameExists($name, $roleId)) {
                throw new RuntimeException('Ja existe um perfil com este nome.');
            }

            $data['name'] = $name;
        }

        // Toda validacao acontece antes de qualquer escrita.
        $permissionIds = null;

        if (array_key_exists('permission_ids', $data)) {
            $permissionIds = $this->sanitizePermissionIds($data['permission_ids']);
            $this->assertDoesNotOrphanAdministrators($roleId, $permissionIds, (string) $current['name']);
        }

        $role = $this->roles->update($roleId, $data);

        if ($permissionIds !== null) {
            $this->roles->replacePermissions($roleId, $permissionIds);
        }

        $this->auditLogs->create('roles.update', 'role', $roleId);

        return $this->find($roleId);
    }

    public function delete(int $roleId): void
    {
        $role = $this->roles->findOrFail($roleId);

        if ((int) $role['is_system'] === 1) {
            throw new RuntimeException('Perfis do sistema nao podem ser excluidos.');
        }

        if ((int) $role['user_count'] > 0) {
            throw new RuntimeException('Ha usuarios neste perfil. Reatribua-os antes de excluir.');
        }

        $this->roles->replacePermissions($roleId, []);
        $this->roles->delete($roleId);

        $this->auditLogs->create('roles.delete', 'role', $roleId);
    }

    /**
     * Tirar `users.manage` do ultimo perfil que a concede deixaria o tenant
     * sem nenhum administrador, e ninguem mais conseguiria reverter a mudanca.
     *
     * @param mixed $permissionIds
     */
    private function assertDoesNotOrphanAdministrators(int $roleId, $permissionIds, string $roleName): void
    {
        if (!$this->roles->hasPermission($roleId, 'users.manage')) {
            return;
        }

        $requested = array_map(static fn ($id): int => (int) $id, (array) $permissionIds);

        if (in_array($this->adminPermissionId(), $requested, true)) {
            return;
        }

        if ($this->countRolesGranting('users.manage') <= 1) {
            throw new RuntimeException(
                'Nao e possivel remover a permissao de gestao de usuarios do perfil "' . $roleName . '": '
                . 'ele e o unico perfil que a concede no tenant.'
            );
        }
    }

    private function countRolesGranting(string $permission): int
    {
        return $this->roles->countRolesWithPermission($permission);
    }

    private function adminPermissionId(): int
    {
        $id = $this->permissions->idByName('users.manage');

        if ($id === null) {
            throw new RuntimeException('Permissao users.manage ausente no catalogo.');
        }

        return $id;
    }

    /**
     * @param mixed $permissionIds
     *
     * @return list<int>
     */
    private function sanitizePermissionIds($permissionIds): array
    {
        $requested = array_values(array_unique(array_map(static fn ($id): int => (int) $id, (array) $permissionIds)));

        if ($requested === []) {
            return [];
        }

        $existing = array_column($this->permissions->findMany($requested), 'id');
        $missing = array_diff($requested, $existing);

        if ($missing !== []) {
            throw new RuntimeException('Permissao inexistente: ' . implode(', ', $missing));
        }

        return $existing;
    }
}
