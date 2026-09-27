<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class UserRepository extends TenantScopedRepository
{
    public const ADMIN_PERMISSION = 'users.manage';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Autenticacao: roda antes de existir tenant na sessao, portanto nao pode
     * passar pela camada com escopo. E a unica leitura de usuario por e-mail
     * do sistema, e o login e o unico ponto em que o tenant ainda e desconhecido.
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT u.*, t.name AS tenant_name, r.name AS role_name
             FROM users u
             INNER JOIN tenants t ON t.id = u.tenant_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /**
     * Leitura sem escopo de tenant, usada apenas pelo fluxo de redefinicao de
     * senha, que roda antes da sessao existir. O user_id ja foi resolvido por um
     * token validado; este metodo so completa os dados para a auditoria.
     *
     * @return array<string, mixed>|null
     */
    public function findByIdWithoutTenant(int $userId): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT id, tenant_id, name, email, status FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /**
     * Consulta global deliberada: `uq_users_email` e unico por email, nao por
     * (tenant, email). O login depende dessa unicidade para desambiguar o
     * tenant, entao a verificacao precisa ter o mesmo escopo do indice.
     */
    public function emailExistsInAnyTenant(string $email, ?int $ignoreUserId = null): bool    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => mb_strtolower(trim($email))];

        if ($ignoreUserId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreUserId;
        }

        $statement = $this->pdo()->prepare($sql . ' LIMIT 1');
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT u.id, u.tenant_id, u.role_id, u.name, u.email, u.phone, u.avatar,
                    u.status, u.last_login, u.created_at,
                    r.name AS role_name, r.is_system AS role_is_system
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.tenant_id = :tenant_id
             ORDER BY u.name'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $userId): ?array
    {
        return $this->selectOne(
            'SELECT u.id, u.tenant_id, u.role_id, u.name, u.email, u.phone, u.avatar,
                    u.status, u.last_login, u.created_at,
                    r.name AS role_name, r.is_system AS role_is_system
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.tenant_id = :tenant_id AND u.id = :id
             LIMIT 1',
            ['id' => $userId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $userId): array
    {
        $user = $this->find($userId);

        if ($user === null) {
            throw NotFoundException::entity('Usuario', 'nao encontrado.');
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $userId = $this->insert(
            'INSERT INTO users (
                tenant_id, role_id, name, email, phone, password, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :role_id, :name, :email, :phone, :password, :status, :created_at, :updated_at
            )',
            [
                'role_id' => (int) $data['role_id'],
                'name' => $data['name'],
                'email' => mb_strtolower(trim((string) $data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => $data['status'] ?? self::STATUS_ACTIVE,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->findOrFail($userId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $userId, array $data): array
    {
        $current = $this->findOrFail($userId);

        $this->run(
            'UPDATE users SET
                role_id = :role_id,
                name = :name,
                email = :email,
                phone = :phone,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'role_id' => isset($data['role_id']) ? (int) $data['role_id'] : (int) $current['role_id'],
                'name' => $data['name'] ?? $current['name'],
                'email' => isset($data['email'])
                    ? mb_strtolower(trim((string) $data['email']))
                    : (string) $current['email'],
                'phone' => array_key_exists('phone', $data) ? $data['phone'] : $current['phone'],
                'status' => $data['status'] ?? $current['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $userId,
            ],
        );

        return $this->findOrFail($userId);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->run(
            'UPDATE users SET password = :password, auth_version = auth_version + 1, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'password' => $passwordHash,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $userId,
            ],
        );
    }

    /**
     * Usada pelo fluxo de redefinicao, que roda sem sessao (o usuario esta
     * justamente bloqueado fora). O vinculo e o user_id ja resolvido pelo token
     * — a autorizacao e o proprio token, consumido pelo PasswordResetService.
     * Incrementa auth_version para derrubar sessoes abertas com a senha antiga.
     */
    public function updatePasswordWithoutTenant(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo()->prepare(
            'UPDATE users SET password = :password, auth_version = auth_version + 1, updated_at = :updated_at
             WHERE id = :id'
        );

        $statement->execute([
            'password' => $passwordHash,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $userId,
        ]);
    }

    /**
     * Versao de autenticacao atual, para o AuthService comparar com a guardada
     * na sessao. Requer sessao valida, entao passa pela camada com escopo.
     */
    public function authVersion(int $userId): ?int
    {
        $value = $this->selectValue(
            'SELECT auth_version FROM users WHERE tenant_id = :tenant_id AND id = :id',
            ['id' => $userId],
        );

        return $value === null ? null : (int) $value;
    }

    /**
     * @return list<string>
     */
    public function permissionsForUser(int $userId): array
    {
        return $this->selectColumn(
            'SELECT p.name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.tenant_id = :tenant_id AND u.id = :user_id',
            ['user_id' => $userId],
        );
    }

    /**
     * Um "admin" e quem tem a permissao de gestao de usuarios, e nao quem tem o
     * perfil chamado "admin": assim o nome do perfil pode mudar sem quebrar a
     * regra de protecao.
     */
    public function hasAdminPermission(int $userId): bool
    {
        $found = $this->selectValue(
            'SELECT 1
             FROM users u
             INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.tenant_id = :tenant_id AND u.id = :user_id AND p.name = :permission
             LIMIT 1',
            [
                'user_id' => $userId,
                'permission' => self::ADMIN_PERMISSION,
            ],
        );

        return $found !== null;
    }

    public function countActiveAdmins(?int $ignoreUserId = null): int
    {
        $sql = 'SELECT COUNT(DISTINCT u.id)
                FROM users u
                INNER JOIN role_permissions rp ON rp.role_id = u.role_id
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE u.tenant_id = :tenant_id
                  AND u.status = :status
                  AND p.name = :permission';
        $params = [
            'status' => self::STATUS_ACTIVE,
            'permission' => self::ADMIN_PERMISSION,
        ];

        if ($ignoreUserId !== null) {
            $sql .= ' AND u.id <> :ignore_id';
            $params['ignore_id'] = $ignoreUserId;
        }

        return (int) $this->selectValue($sql, $params);
    }

    public function updateLastLogin(int $userId): void
    {
        $this->run(
            'UPDATE users SET last_login = :last_login
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'id' => $userId,
                'last_login' => date('Y-m-d H:i:s'),
            ],
        );
    }
}
