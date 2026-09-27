<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository extends TenantScopedRepository
{
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

    public function findById(int $userId): ?array
    {
        return $this->selectOne(
            'SELECT u.*, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.tenant_id = :tenant_id AND u.id = :id
             LIMIT 1',
            ['id' => $userId],
        );
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
