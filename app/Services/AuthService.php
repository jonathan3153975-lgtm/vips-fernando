<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogRepository $auditLogs,
        private readonly TenantRepository $tenants,
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

        $this->session->put('auth', [
            'user_id' => (int) $user['id'],
            'tenant_id' => (int) $user['tenant_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'tenant_name' => $user['tenant_name'],
            'permissions' => [],
        ]);

        try {
            $permissions = $this->users->permissionsForUser((int) $user['id']);

            if ($this->tenants->settings() === null) {
                $this->tenants->ensureSettings();
            }

            $settings = $this->tenants->settings();
        } catch (\Throwable $exception) {
            $this->session->forget('auth');
            $this->session->invalidate();

            throw $exception;
        }

        $auth = $this->session->get('auth');
        $auth['permissions'] = $permissions;
        $auth['settings'] = is_array($settings) ? $settings : [];
        $this->session->put('auth', $auth);

        $this->users->updateLastLogin((int) $user['id']);
        $this->auditLogs->create('auth.login', 'user', (int) $user['id'], ['email' => $user['email']]);

        return true;
    }

    public function logout(): void
    {
        $user = $this->user();

        if ($user !== null) {
            $this->auditLogs->create('auth.logout', 'user', (int) $user['user_id']);
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

    /**
     * Mantem a copia das configuracoes na sessao alinhada apos uma alteracao,
     * para que a propria requisicao seguinte ja exiba o novo formato.
     *
     * @param array<string, mixed> $settings
     */
    public function syncTenantSettings(array $settings): void
    {
        $auth = $this->user();

        if ($auth === null) {
            return;
        }

        $auth['settings'] = $settings;
        $this->session->put('auth', $auth);
    }
}
