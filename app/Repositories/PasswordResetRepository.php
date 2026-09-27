<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;

final class PasswordResetRepository
{
    public function create(int $userId, string $token, string $expiresAt): void
    {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, created_at)
             VALUES (:user_id, :token, :expires_at, :created_at)'
        );

        $statement->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
