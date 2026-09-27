<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogRepository $auditLogs,
        private readonly Session $session,
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || $user['status'] !== 'ACTIVE') {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);

        $permissions = $this->users->permissionsForUser((int) $user['id']);

        $this->session->put('auth', [
            'user_id' => (int) $user['id'],
            'tenant_id' => (int) $user['tenant_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'tenant_name' => $user['tenant_name'],
            'permissions' => $permissions,
        ]);

        $this->users->updateLastLogin((int) $user['id']);
        $this->auditLogs->create(
            (int) $user['tenant_id'],
            (int) $user['id'],
            'auth.login',
            'user',
            (int) $user['id'],
            ['email' => $user['email']]
        );

        return true;
    }

    public function logout(): void
    {
        $user = $this->user();

        if ($user !== null) {
            $this->auditLogs->create(
                $user['tenant_id'],
                $user['user_id'],
                'auth.logout',
                'user',
                $user['user_id'],
            );
        }

        $this->session->forget('auth');
        $this->session->invalidate();
    }

    public function check(): bool
    {
        return $this->session->get('auth') !== null;
    }

    public function user(): ?array
    {
        $auth = $this->session->get('auth');

        return is_array($auth) ? $auth : null;
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array($permission, $user['permissions'], true);
    }
}
