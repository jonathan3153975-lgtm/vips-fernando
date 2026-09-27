<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\ImportRepository;
use RuntimeException;

final class ImportService
{
    public function __construct(
        private readonly ImportRepository $imports,
        private readonly AuditLogRepository $auditLogs,
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
        foreach (['category', 'description', 'currency', 'amount', 'exchange_rate', 'expense_date'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

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
        foreach (['product_name', 'quantity', 'unit_cost_foreign', 'exchange_rate'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $item = $this->imports->addItem($importId, $data);
        $this->auditLogs->create('imports.item.create', 'import', $importId, ['item_id' => $item['id']]);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(int $importId): array
    {
        $expenseTotal = $this->imports->expenseTotal($importId);
        $itemTotals = $this->imports->itemTotals($importId);

        $this->imports->allocateExpenses($importId, $expenseTotal);
        $completed = $this->imports->complete(
            $importId,
            $itemTotals['total_cost_local'] + $expenseTotal,
            $expenseTotal,
            $itemTotals['total_quantity'],
        );

        $this->auditLogs->create('imports.complete', 'import', $importId);

        return $completed;
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
