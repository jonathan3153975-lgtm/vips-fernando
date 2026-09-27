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
            'auth_version' => (int) ($user['auth_version'] ?? 0),
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
        $auth = $this->user();

        if ($auth === null) {
            return false;
        }

        if (!$this->sessionIsCurrent($auth)) {
            $this->session->forget('auth');
            $this->session->invalidate();

            return false;
        }

        return true;
    }

    /**
     * A sessao guarda a auth_version vigente no login. Trocar a senha incrementa
     * a versao no banco (inclusive a redefinicao), entao uma sessao antiga deixa
     * de bater aqui e cai na proxima requisicao — e como "invalidar todas as
     * sessoes" funciona com sessao em arquivo, sem indice por usuario.
     *
     * @param array<string, mixed> $auth
     */
    private function sessionIsCurrent(array $auth): bool
    {
        $userId = (int) ($auth['user_id'] ?? 0);

        if ($userId <= 0) {
            return false;
        }

        $current = $this->users->authVersion($userId);

        return $current !== null && $current === (int) ($auth['auth_version'] ?? 0);
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
