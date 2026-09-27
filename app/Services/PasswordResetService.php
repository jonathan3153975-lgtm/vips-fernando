<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Support\PasswordPolicy;
use RuntimeException;

final class PasswordResetService
{
    public const TOKEN_TTL_HOURS = 1;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetRepository $passwordResets,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * Gera o token e guarda apenas o hash. Devolve o token em claro para o
     * chamador montar o link, ou null se o e-mail nao existir — a resposta ao
     * cliente deve ser generica nos dois casos, para nao revelar quais contas
     * existem.
     */
    public function request(string $email): ?string
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_TTL_HOURS . ' hour'));
        $userId = (int) $user['id'];

        // Pedir um novo link invalida o anterior.
        $this->passwordResets->deleteForUser($userId);
        $this->passwordResets->create($userId, $this->hash($token), $expiresAt);

        $this->auditLogs->createForTenant(
            (int) $user['tenant_id'],
            $userId,
            'auth.password_reset_requested',
            'user',
            $userId,
        );

        return $token;
    }

    /**
     * Valida o token (existe, nao usado e nao expirado), troca a senha e derruba
     * todas as sessoes do usuario.
     *
     * @return int id do usuario
     */
    public function confirm(string $token, string $password): int
    {
        if (trim($token) === '') {
            throw new RuntimeException('Link invalido ou expirado.');
        }

        PasswordPolicy::assertAcceptable($password);

        $reset = $this->passwordResets->findByToken($this->hash($token));

        if ($reset === null) {
            throw new RuntimeException('Link invalido ou expirado.');
        }

        if ($reset['used_at'] !== null) {
            throw new RuntimeException('Este link ja foi utilizado. Solicite um novo.');
        }

        if (strtotime((string) $reset['expires_at']) < time()) {
            throw new RuntimeException('Link invalido ou expirado.');
        }

        $userId = (int) $reset['user_id'];
        $user = $this->users->findByIdWithoutTenant($userId);

        if ($user === null) {
            throw new RuntimeException('Link invalido ou expirado.');
        }

        if ($user['status'] !== 'ACTIVE') {
            throw new RuntimeException('Usuario inativo. Procure um administrador.');
        }

        // updatePasswordWithoutTenant incrementa auth_version: as sessoes
        // abertas com a senha antiga caem na proxima requisicao.
        $this->users->updatePasswordWithoutTenant($userId, password_hash($password, PASSWORD_DEFAULT));
        $this->passwordResets->markUsed((int) $reset['id']);

        $this->auditLogs->createForTenant(
            (int) $user['tenant_id'],
            $userId,
            'auth.password_reset_completed',
            'user',
            $userId,
        );

        return $userId;
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
