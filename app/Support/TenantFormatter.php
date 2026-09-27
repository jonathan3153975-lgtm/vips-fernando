<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\TenantContext;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Formata datas e valores conforme as configuracoes do tenant.
 *
 * Fica fora das views de proposito: o manual 1 exige que a view nao carregue
 * regra de negocio, e formato de data/moeda e decisao do tenant.
 *
 * Datas sao GRAVADAS no timezone global da aplicacao (`APP_TIMEZONE`) e
 * convertidas aqui apenas para EXIBICAO. O timezone do tenant nao e aplicado
 * na escrita: em MySQL, colunas TIMESTAMP sao convertidas pelo time_zone da
 * sessao e colunas DATETIME guardam o literal, entao mudar o fuso global no
 * meio do request deslocaria o que ja esta gravado.
 */
final class TenantFormatter
{
    private const CURRENCY_SYMBOLS = [
        'BRL' => 'R$',
        'USD' => 'US$',
        'EUR' => 'EUR ',
        'GBP' => 'GBP ',
        'ARS' => 'ARS ',
        'PYG' => 'PYG ',
    ];

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly array $settings,
        private readonly string $sourceTimezone = TenantContext::DEFAULT_TIMEZONE,
    ) {
    }

    public function currency(): string
    {
        return (string) ($this->settings['currency'] ?? '') ?: TenantContext::DEFAULT_CURRENCY;
    }

    public function timezone(): string
    {
        return (string) ($this->settings['timezone'] ?? '') ?: TenantContext::DEFAULT_TIMEZONE;
    }

    public function dateFormat(): string
    {
        return (string) ($this->settings['date_format'] ?? '') ?: TenantContext::DEFAULT_DATE_FORMAT;
    }

    /**
     * Aceita 'Y-m-d H:i:s' e 'Y-m-d'. Interpreta o valor no timezone de origem
     * (o da gravacao) e exibe no timezone e formato do tenant.
     */
    public function date(?string $value, bool $withTime = false): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone($this->sourceTimezone));
            $date = $date->setTimezone(new DateTimeZone($this->timezone()));
        } catch (Throwable) {
            return $value;
        }

        $format = $this->dateFormat();

        if ($withTime && !str_contains($format, 'H') && !str_contains($format, 'g')) {
            $format .= ' H:i';
        }

        return $date->format($format);
    }

    public function money(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return $this->currencyLabel() . ' 0,00';
        }

        return $this->currencyLabel() . number_format((float) $value, 2, ',', '.');
    }

    private function currencyLabel(): string
    {
        $currency = strtoupper($this->currency());

        return self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';
    }
}
