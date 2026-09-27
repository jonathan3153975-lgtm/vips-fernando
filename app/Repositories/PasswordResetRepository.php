<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Tabela global (sem tenant_id): o reset acontece antes de existir sessao, e o
 * vinculo com o tenant vem do usuario. O token nunca e gravado em claro — so o
 * hash SHA-256; um vazamento do banco nao permite redefinir senha.
 */
final class PasswordResetRepository extends BaseRepository
{
    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, created_at)
             VALUES (:user_id, :token, :expires_at, :created_at)'
        );

        $statement->execute([
            'user_id' => $userId,
            'token' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByToken(string $tokenHash): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT id, user_id, expires_at, used_at, created_at
             FROM password_resets
             WHERE token = :token
             LIMIT 1'
        );

        $statement->execute(['token' => $tokenHash]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function markUsed(int $id): void
    {
        $statement = $this->pdo()->prepare(
            'UPDATE password_resets SET used_at = :used_at WHERE id = :id AND used_at IS NULL'
        );

        $statement->execute([
            'used_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    /**
     * Remove os tokens anteriores do usuario: pedir um novo invalida o antigo,
     * de modo que so o ultimo link enviado funciona.
     */
    public function deleteForUser(int $userId): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
    }
}
