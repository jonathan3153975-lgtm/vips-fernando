<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetRepository $passwordResets,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    public function request(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->passwordResets->create((int) $user['id'], $token, $expiresAt);

        $this->auditLogs->create(
            (int) $user['tenant_id'],
            (int) $user['id'],
            'auth.password_reset_requested',
            'user',
            (int) $user['id'],
        );
    }
}
