<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Historico cambial por tenant. A cotacao e historica: nunca deve ser
 * recalculada com valor novo (manual 4:219-243, manual 7:145-147).
 */
final class ExchangeRateRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT id, currency, rate, reference_date, source, created_at
             FROM exchange_rates
             WHERE tenant_id = :tenant_id
             ORDER BY reference_date DESC, currency ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $rateId): ?array
    {
        return $this->selectOne(
            'SELECT id, currency, rate, reference_date, source, created_at
             FROM exchange_rates
             WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $rateId],
        );
    }

    /**
     * Grava ou atualiza a cotacao do par (moeda, data). Portatil entre MySQL e
     * SQLite: verifica antes, sem depender de ON DUPLICATE KEY.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function upsert(array $data): array
    {
        $currency = strtoupper((string) $data['currency']);
        $referenceDate = (string) $data['reference_date'];

        $existingId = $this->selectValue(
            'SELECT id FROM exchange_rates
             WHERE tenant_id = :tenant_id AND currency = :currency AND reference_date = :reference_date',
            ['currency' => $currency, 'reference_date' => $referenceDate],
        );

        if ($existingId !== null) {
            $this->run(
                'UPDATE exchange_rates
                 SET rate = :rate, source = :source
                 WHERE tenant_id = :tenant_id AND id = :id',
                [
                    'rate' => $data['rate'],
                    'source' => $data['source'] ?? null,
                    'id' => (int) $existingId,
                ],
            );

            return $this->find((int) $existingId) ?? [];
        }

        $rateId = $this->insert(
            'INSERT INTO exchange_rates (tenant_id, currency, rate, reference_date, source, created_at)
             VALUES (:tenant_id, :currency, :rate, :reference_date, :source, :created_at)',
            [
                'currency' => $currency,
                'rate' => $data['rate'],
                'reference_date' => $referenceDate,
                'source' => $data['source'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        );

        return $this->find($rateId) ?? [];
    }

    /**
     * Cotacao vigente: a mais recente com reference_date <= a data pedida.
     */
    public function rateFor(string $currency, string $date): ?float
    {
        $rate = $this->selectValue(
            'SELECT rate FROM exchange_rates
             WHERE tenant_id = :tenant_id AND currency = :currency AND reference_date <= :reference_date
             ORDER BY reference_date DESC
             LIMIT 1',
            ['currency' => strtoupper($currency), 'reference_date' => $date],
        );

        return $rate === null ? null : (float) $rate;
    }
}
