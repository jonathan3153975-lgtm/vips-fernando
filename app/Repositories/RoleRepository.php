<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class RoleRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT r.id, r.tenant_id, r.name, r.description, r.is_system, r.created_at,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.tenant_id = r.tenant_id) AS user_count
             FROM roles r
             WHERE r.tenant_id = :tenant_id
             ORDER BY r.name'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $roleId): ?array
    {
        return $this->selectOne(
            'SELECT r.id, r.tenant_id, r.name, r.description, r.is_system, r.created_at,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.tenant_id = r.tenant_id) AS user_count
             FROM roles r
             WHERE r.tenant_id = :tenant_id AND r.id = :id
             LIMIT 1',
            ['id' => $roleId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $roleId): array
    {
        $role = $this->find($roleId);

        if ($role === null) {
            throw NotFoundException::entity('Perfil', 'nao encontrado.');
        }

        return $role;
    }

    /**
     * O indice `uq_roles_tenant_name` e por (tenant_id, name); a consulta
     * espelha o mesmo escopo.
     */
    public function nameExists(string $name, ?int $ignoreRoleId = null): bool
    {
        $sql = 'SELECT id FROM roles WHERE tenant_id = :tenant_id AND name = :name';
        $params = ['name' => trim($name)];

        if ($ignoreRoleId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreRoleId;
        }

        return $this->selectValue($sql . ' LIMIT 1', $params) !== null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $roleId = $this->insert(
            'INSERT INTO roles (tenant_id, name, description, is_system, created_at, updated_at)
             VALUES (:tenant_id, :name, :description, 0, :created_at, :updated_at)',
            [
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->findOrFail($roleId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $roleId, array $data): array
    {
        $current = $this->findOrFail($roleId);

        $this->run(
            'UPDATE roles SET
                name = :name,
                description = :description,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'name' => isset($data['name']) ? trim((string) $data['name']) : (string) $current['name'],
                'description' => array_key_exists('description', $data) ? $data['description'] : $current['description'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $roleId,
            ],
        );

        return $this->findOrFail($roleId);
    }

    public function delete(int $roleId): void
    {
        $this->run('DELETE FROM roles WHERE tenant_id = :tenant_id AND id = :id', ['id' => $roleId]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function permissions(int $roleId): array
    {
        return $this->selectAll(
            'SELECT p.id, p.name, p.description
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
               AND rp.role_id IN (SELECT id FROM roles WHERE tenant_id = :tenant_id)
             ORDER BY p.name',
            ['role_id' => $roleId],
        );
    }

    /**
     * @return list<int>
     */
    public function permissionIds(int $roleId): array
    {
        $ids = $this->selectColumn(
            'SELECT rp.permission_id
             FROM role_permissions rp
             WHERE rp.role_id = :role_id
               AND rp.role_id IN (SELECT id FROM roles WHERE tenant_id = :tenant_id)',
            ['role_id' => $roleId],
        );

        return array_map(static fn ($id): int => (int) $id, $ids);
    }

    public function hasPermission(int $roleId, string $permission): bool
    {
        $found = $this->selectValue(
            'SELECT 1
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
               AND rp.role_id IN (SELECT id FROM roles WHERE tenant_id = :tenant_id)
               AND p.name = :permission
             LIMIT 1',
            [
                'role_id' => $roleId,
                'permission' => $permission,
            ],
        );

        return $found !== null;
    }

    public function countRolesWithPermission(string $permission): int
    {
        return (int) $this->selectValue(
            'SELECT COUNT(DISTINCT r.id)
             FROM roles r
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE r.tenant_id = :tenant_id AND p.name = :permission',
            ['permission' => $permission],
        );
    }

    /**
     * Substitui o conjunto de permissoes do perfil. As duas operacem exigem que
     * o perfil pertenca ao tenant atual, e o INSERT deriva o role_id do proprio
     * `roles`, entao nao aceita role_id vindo de quem chama.
     *
     * DELETE e INSERT correm na mesma transacao: um erro no meio nao pode
     * deixar o perfil sem nenhuma permissao.
     *
     * @param list<int> $permissionIds
     */
    public function replacePermissions(int $roleId, array $permissionIds): void
    {
        $this->findOrFail($roleId);

        $permissionIds = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $permissionIds)));

        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $this->run(
                'DELETE FROM role_permissions
                 WHERE role_id = :role_id
                   AND role_id IN (SELECT id FROM roles WHERE tenant_id = :tenant_id)',
                ['role_id' => $roleId],
            );

            if ($permissionIds !== []) {
                $placeholders = [];
                $params = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'role_id' => $roleId,
                ];

                foreach ($permissionIds as $index => $permissionId) {
                    $key = 'permission_' . $index;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $permissionId;
                }

                $this->run(
                    'INSERT INTO role_permissions (role_id, permission_id, created_at)
                     SELECT r.id, p.id, :created_at
                     FROM roles r
                     INNER JOIN permissions p ON p.id IN (' . implode(', ', $placeholders) . ')
                     WHERE r.tenant_id = :tenant_id AND r.id = :role_id',
                    $params
                );
            }

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();

            throw $throwable;
        }
    }
}
