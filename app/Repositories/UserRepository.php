<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare(
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

    public function updateLastLogin(int $userId): void
    {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare('UPDATE users SET last_login = :last_login WHERE id = :id');
        $statement->execute([
            'id' => $userId,
            'last_login' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<string>
     */
    public function permissionsForUser(int $userId): array
    {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare(
            'SELECT p.name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    public function findById(int $userId): ?array
    {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
