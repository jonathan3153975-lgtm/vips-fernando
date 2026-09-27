<?php

declare(strict_types=1);

namespace App\Core;

final class TenantContext
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function isActive(): bool
    {
        return $this->auth() !== null;
    }

    public function id(): int
    {
        $auth = $this->auth();

        if ($auth === null || (int) ($auth['tenant_id'] ?? 0) <= 0) {
            throw new MissingTenantException();
        }

        return (int) $auth['tenant_id'];
    }

    public function userId(): int
    {
        $auth = $this->auth();

        if ($auth === null || (int) ($auth['user_id'] ?? 0) <= 0) {
            throw new MissingTenantException();
        }

        return (int) $auth['user_id'];
    }

    public function name(): string
    {
        $auth = $this->auth();

        return (string) ($auth['tenant_name'] ?? '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function auth(): ?array
    {
        $auth = $this->session->get('auth');

        return is_array($auth) ? $auth : null;
    }
}
