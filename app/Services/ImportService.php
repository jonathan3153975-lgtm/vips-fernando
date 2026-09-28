<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\ImportRepository;
use App\Repositories\SupplierRepository;
use RuntimeException;

final class ImportService
{
    /** @var list<string> */
    private const EDITABLE_STATUSES = [
        ImportRepository::STATUS_PLANNED,
        ImportRepository::STATUS_IN_PROGRESS,
    ];

    /**
     * Status que podem ser atribuidos pela edicao. COMPLETED so via complete(),
     * para nao pular o calculo e o congelamento.
     *
     * @var list<string>
     */
    private const ASSIGNABLE_STATUSES = [
        ImportRepository::STATUS_PLANNED,
        ImportRepository::STATUS_IN_PROGRESS,
        ImportRepository::STATUS_CANCELLED,
    ];

    public function __construct(
        private readonly ImportRepository $imports,
        private readonly AuditLogRepository $auditLogs,
        private readonly ExchangeRateService $exchangeRates,
        private readonly SupplierRepository $suppliers,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->imports->all();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $this->validateImport($data);

        if (isset($data['allocation_method'])) {
            $this->assertAllocationMethod((string) $data['allocation_method']);
        }

        if (isset($data['status']) && !in_array(strtoupper((string) $data['status']), self::ASSIGNABLE_STATUSES, true)) {
            throw new RuntimeException('Status invalido na criacao. Use PLANNED ou IN_PROGRESS.');
        }

        $import = $this->imports->create($data);

        $this->auditLogs->create('imports.create', 'import', (int) $import['id']);

        return $import;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $importId, array $data): array
    {
        $current = $this->imports->findOrFail($importId);
        $this->assertEditable($current);

        if (isset($data['status'])) {
            $status = strtoupper((string) $data['status']);

            if (!in_array($status, self::ASSIGNABLE_STATUSES, true)) {
                throw new RuntimeException('Status invalido. Use PLANNED, IN_PROGRESS ou CANCELLED.');
            }

            $data['status'] = $status;
        }

        if (isset($data['allocation_method'])) {
            $this->assertAllocationMethod((string) $data['allocation_method']);
        }

        $import = $this->imports->update($importId, $data);

        $this->auditLogs->create('imports.update', 'import', $importId);

        return $import;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addExpense(int $importId, array $data): array
    {
        $import = $this->imports->findOrFail($importId);
        $this->assertEditable($import);

        foreach (['category', 'description', 'currency', 'amount', 'expense_date'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $data['exchange_rate'] = $this->resolveRate(
            $import,
            (string) $data['currency'],
            (string) $data['expense_date'],
            $data['exchange_rate'] ?? null,
        );

        $this->assertSupplierBelongsToTenant($data['supplier_id'] ?? null);

        $expense = $this->imports->addExpense($importId, $data);

        $this->auditLogs->create('imports.expense.create', 'import', $importId, ['expense_id' => $expense['id']]);

        return $expense;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addItem(int $importId, array $data): array
    {
        $import = $this->imports->findOrFail($importId);
        $this->assertEditable($import);

        foreach (['product_name', 'quantity', 'unit_cost_foreign'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        // Itens nao tem data propria: usa-se a data de inicio da importacao.
        $data['exchange_rate'] = $this->resolveRate(
            $import,
            (string) $import['currency'],
            (string) $import['start_date'],
            $data['exchange_rate'] ?? null,
        );

        $this->assertSupplierBelongsToTenant($data['supplier_id'] ?? null);

        $item = $this->imports->addItem($importId, $data);

        $this->auditLogs->create('imports.item.create', 'import', $importId, ['item_id' => $item['id']]);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(int $importId, ?string $allocationMethod = null): array
    {
        $import = $this->imports->findOrFail($importId);

        if ($import['status'] === ImportRepository::STATUS_COMPLETED) {
            throw new RuntimeException('Importacao ja concluida. Reabra antes de recalcular.');
        }

        if ($import['status'] === ImportRepository::STATUS_CANCELLED) {
            throw new RuntimeException('Importacao cancelada nao pode ser concluida.');
        }

        $method = $allocationMethod ?? (string) ($import['allocation_method'] ?? ImportRepository::ALLOCATION_VALUE);
        $this->assertAllocationMethod($method);

        $completed = $this->imports->freeze($importId, $method);

        $this->auditLogs->create('imports.complete', 'import', $importId, [
            'allocation_method' => $method,
            'invested_amount' => $completed['invested_amount'] ?? null,
        ]);

        return $completed;
    }

    /**
     * Reabre explicitamente uma importacao concluida. E a unica via para editar
     * depois do fechamento; fica registrada na auditoria (baseline 69 §5).
     *
     * @return array<string, mixed>
     */
    public function reopen(int $importId): array
    {
        $import = $this->imports->findOrFail($importId);

        if ($import['status'] !== ImportRepository::STATUS_COMPLETED) {
            throw new RuntimeException('Somente uma importacao concluida pode ser reaberta.');
        }

        $reopened = $this->imports->reopen($importId);

        $this->auditLogs->create('imports.reopen', 'import', $importId);

        return $reopened;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function expenses(int $importId, array $filters, int $page, int $perPage): array
    {
        $this->imports->findOrFail($importId);

        return [
            'data' => $this->imports->expenses($importId, $filters, $perPage, ($page - 1) * $perPage),
            'total' => $this->imports->countExpenses($importId, $filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function items(int $importId, array $filters, int $page, int $perPage): array
    {
        $this->imports->findOrFail($importId);

        return [
            'data' => $this->imports->items($importId, $filters, $perPage, ($page - 1) * $perPage),
            'total' => $this->imports->countItems($importId, $filters),
        ];
    }

    /**
     * @param array<string, mixed> $import
     */
    private function assertEditable(array $import): void
    {
        if (!in_array($import['status'], self::EDITABLE_STATUSES, true)) {
            throw new RuntimeException(
                'Importacao ' . $import['status'] . ' nao pode ser alterada. Reabra antes de editar.'
            );
        }
    }

    private function assertAllocationMethod(string $method): void
    {
        if (!in_array($method, ImportRepository::ALLOCATION_METHODS, true)) {
            throw new RuntimeException(
                'Metodo de rateio invalido. Use ' . implode(' ou ', ImportRepository::ALLOCATION_METHODS) . '.'
            );
        }
    }

    /**
     * Resolve a cotacao a aplicar: a informada; senao a vigente na data
     * (exchange_rates); senao a da propria importacao, se a moeda for a mesma.
     *
     * @param array<string, mixed> $import
     */
    private function resolveRate(array $import, string $currency, string $date, mixed $provided): float
    {
        if ($provided !== null && $provided !== '' && (float) $provided > 0) {
            return (float) $provided;
        }

        $currency = strtoupper($currency);
        $rate = $this->exchangeRates->rateFor($currency, $date);

        if ($rate === null && $currency === strtoupper((string) $import['currency'])) {
            $importRate = (float) $import['exchange_rate'];

            if ($importRate > 0) {
                return $importRate;
            }
        }

        if ($rate === null) {
            throw new RuntimeException(
                'Cotacao nao informada e sem registro em exchange_rates para ' . $currency . ' em ' . $date . '.'
            );
        }

        return $rate;
    }

    private function assertSupplierBelongsToTenant(mixed $supplierId): void
    {
        if ($supplierId === null || $supplierId === '') {
            return;
        }

        if (!is_numeric($supplierId) || !$this->suppliers->exists((int) $supplierId)) {
            throw new RuntimeException('Fornecedor invalido.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateImport(array $data): void
    {
        foreach (['name', 'country', 'start_date', 'currency', 'exchange_rate'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }
    }
}
