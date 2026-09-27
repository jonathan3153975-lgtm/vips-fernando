<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\TenantContext;
use App\Repositories\AuditLogRepository;
use App\Repositories\TenantRepository;
use App\Support\TenantFormatter;
use RuntimeException;

final class TenantService
{
    private const ALLOWED_DATE_FORMAT_CHARS = 'djDlNSwzmMnFotLYyaAgGhHis';

    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly AuditLogRepository $auditLogs,
        private readonly string $sourceTimezone = 'America/Sao_Paulo',
    ) {
    }

    /**
     * Configuracoes do tenant ativo, com defaults caso a linha nao exista.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $settings = $this->tenants->settings();

        if ($settings === null) {
            $this->tenants->ensureSettings();
            $settings = $this->tenants->settings();
        }

        return $this->normalize(is_array($settings) ? $settings : []);
    }

    /**
     * Atualizacao parcial: as chaves ausentes mantem o valor atual.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function updateSettings(array $data): array
    {
        $merged = array_merge($this->settings(), $this->onlyProvided($data));

        $validated = $this->validate($merged);

        $this->tenants->saveSettings($validated);

        $this->auditLogs->create('settings.update', 'tenant', null, [
            'currency' => $validated['currency'],
            'timezone' => $validated['timezone'],
            'language' => $validated['language'],
            'date_format' => $validated['date_format'],
        ]);

        return $validated;
    }

    public function formatter(): TenantFormatter
    {
        return new TenantFormatter($this->settings(), $this->sourceTimezone);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function onlyProvided(array $data): array
    {
        $allowed = ['currency', 'timezone', 'language', 'date_format'];
        $provided = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $provided[$key] = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
            }
        }

        return $provided;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function validate(array $settings): array
    {
        $currency = strtoupper((string) ($settings['currency'] ?? ''));

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new RuntimeException('Moeda invalida. Informe o codigo ISO de 3 letras, como BRL ou USD.');
        }

        $timezone = (string) ($settings['timezone'] ?? '');

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new RuntimeException('Fuso horario invalido: ' . $timezone);
        }

        $language = (string) ($settings['language'] ?? '');

        if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $language) !== 1) {
            throw new RuntimeException('Idioma invalido. Use o formato pt-BR.');
        }

        $dateFormat = (string) ($settings['date_format'] ?? '');
        $this->assertSafeDateFormat($dateFormat);

        return [
            'currency' => $currency,
            'timezone' => $timezone,
            'language' => $language,
            'date_format' => $dateFormat,
        ];
    }

    /**
     * O formato vai direto para `date()`. Restringir os caracteres evita que um
     * valor arbitrario vire saida imprevisivel (ex.: 'U', 'c' ou escapes).
     */
    private function assertSafeDateFormat(string $format): void
    {
        if ($format === '') {
            throw new RuntimeException('Formato de data obrigatorio.');
        }

        $characters = str_split($format);
        $separators = ['/', '-', '.', ',', ':', ' '];

        foreach ($characters as $character) {
            if (in_array($character, $separators, true)) {
                continue;
            }

            if (strpos(self::ALLOWED_DATE_FORMAT_CHARS, $character) === false) {
                throw new RuntimeException('Formato de data contem caractere nao permitido: ' . $character);
            }
        }
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function normalize(array $settings): array
    {
        return [
            'currency' => strtoupper((string) ($settings['currency'] ?? '') ?: TenantContext::DEFAULT_CURRENCY),
            'timezone' => (string) ($settings['timezone'] ?? '') ?: TenantContext::DEFAULT_TIMEZONE,
            'language' => (string) ($settings['language'] ?? '') ?: TenantContext::DEFAULT_LANGUAGE,
            'date_format' => (string) ($settings['date_format'] ?? '') ?: TenantContext::DEFAULT_DATE_FORMAT,
        ];
    }
}
