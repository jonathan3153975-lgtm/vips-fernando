<?php

declare(strict_types=1);

namespace App\Core;

final class TenantContext
{
    public const DEFAULT_CURRENCY = 'BRL';

    public const DEFAULT_TIMEZONE = 'America/Sao_Paulo';

    public const DEFAULT_LANGUAGE = 'pt-BR';

    public const DEFAULT_DATE_FORMAT = 'd/m/Y';

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
     * Configuracoes do tenant carregadas na sessao no login. Sessoes antigas,
     * anteriores a este recurso, caem nos defaults do schema.
     *
     * @return array{currency: string, timezone: string, language: string, date_format: string}
     */
    public function settings(): array
    {
        $auth = $this->auth();
        $settings = is_array($auth['settings'] ?? null) ? $auth['settings'] : [];

        return [
            'currency' => (string) ($settings['currency'] ?? '') ?: self::DEFAULT_CURRENCY,
            'timezone' => (string) ($settings['timezone'] ?? '') ?: self::DEFAULT_TIMEZONE,
            'language' => (string) ($settings['language'] ?? '') ?: self::DEFAULT_LANGUAGE,
            'date_format' => (string) ($settings['date_format'] ?? '') ?: self::DEFAULT_DATE_FORMAT,
        ];
    }

    public function currency(): string
    {
        return $this->settings()['currency'];
    }

    public function timezone(): string
    {
        return $this->settings()['timezone'];
    }

    public function language(): string
    {
        return $this->settings()['language'];
    }

    public function dateFormat(): string
    {
        return $this->settings()['date_format'];
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
