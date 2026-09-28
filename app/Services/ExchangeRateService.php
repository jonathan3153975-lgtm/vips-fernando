<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\ExchangeRateRepository;
use RuntimeException;

final class ExchangeRateService
{
    public function __construct(
        private readonly ExchangeRateRepository $rates,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->rates->all();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $currency = strtoupper(trim((string) ($data['currency'] ?? '')));
        $rate = (string) ($data['rate'] ?? '');
        $referenceDate = trim((string) ($data['reference_date'] ?? ''));
        $source = isset($data['source']) ? trim((string) $data['source']) : null;

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new RuntimeException('Moeda invalida. Informe o codigo ISO de 3 letras, como USD.');
        }

        if (!is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Cotacao invalida. Informe um valor maior que zero.');
        }

        if ($this->parseDate($referenceDate) === null) {
            throw new RuntimeException('Data de referencia invalida. Use o formato YYYY-MM-DD.');
        }

        $rate = $this->rates->upsert([
            'currency' => $currency,
            'rate' => (float) $rate,
            'reference_date' => $referenceDate,
            'source' => $source === '' ? null : $source,
        ]);

        $this->auditLogs->create('exchange_rates.store', 'exchange_rate', (int) $rate['id'], [
            'currency' => $currency,
            'reference_date' => $referenceDate,
        ]);

        return $rate;
    }

    /**
     * Cotacao historica vigente para a moeda na data informada.
     */
    public function rateFor(string $currency, string $date): ?float
    {
        if ($this->parseDate($date) === null) {
            return null;
        }

        return $this->rates->rateFor($currency, $date);
    }

    private function parseDate(string $date): ?string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return null;
        }

        return $date;
    }
}
