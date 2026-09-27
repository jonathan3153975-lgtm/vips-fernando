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

    public function list(int $tenantId): array
    {
        return $this->imports->allByTenant($tenantId);
    }

    public function create(int $tenantId, int $userId, array $data): array
    {
        $this->validateImport($data);
        $import = $this->imports->create($tenantId, $data);

        $this->auditLogs->create($tenantId, $userId, 'imports.create', 'import', (int) $import['id']);

        return $import;
    }

    public function update(int $tenantId, int $userId, int $importId, array $data): array
    {
        if ($this->imports->findForTenant($tenantId, $importId) === null) {
            throw new RuntimeException('Importacao nao encontrada.');
        }

        $import = $this->imports->update($tenantId, $importId, $data);
        $this->auditLogs->create($tenantId, $userId, 'imports.update', 'import', $importId);

        return $import;
    }

    public function addExpense(int $tenantId, int $userId, int $importId, array $data): array
    {
        if ($this->imports->findForTenant($tenantId, $importId) === null) {
            throw new RuntimeException('Importacao nao encontrada.');
        }

        foreach (['category', 'description', 'currency', 'amount', 'exchange_rate', 'expense_date'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $expense = $this->imports->addExpense($tenantId, $importId, $data);
        $this->auditLogs->create($tenantId, $userId, 'imports.expense.create', 'import', $importId, ['expense_id' => $expense['id']]);

        return $expense;
    }

    public function addItem(int $tenantId, int $userId, int $importId, array $data): array
    {
        if ($this->imports->findForTenant($tenantId, $importId) === null) {
            throw new RuntimeException('Importacao nao encontrada.');
        }

        foreach (['product_name', 'quantity', 'unit_cost_foreign', 'exchange_rate'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $item = $this->imports->addItem($tenantId, $importId, $data);
        $this->auditLogs->create($tenantId, $userId, 'imports.item.create', 'import', $importId, ['item_id' => $item['id']]);

        return $item;
    }

    public function complete(int $tenantId, int $userId, int $importId): array
    {
        $import = $this->imports->findForTenant($tenantId, $importId);

        if ($import === null) {
            throw new RuntimeException('Importacao nao encontrada.');
        }

        $expenseTotal = $this->imports->expenseTotal($importId);
        $itemTotals = $this->imports->itemTotals($importId);
        $itemsCost = (float) $itemTotals['total_cost_local'];
        $itemsQuantity = (float) $itemTotals['total_quantity'];

        $this->imports->allocateExpenses($importId, $expenseTotal);
        $completed = $this->imports->complete($tenantId, $importId, $itemsCost + $expenseTotal, $expenseTotal, $itemsQuantity);

        $this->auditLogs->create($tenantId, $userId, 'imports.complete', 'import', $importId);

        return $completed;
    }

    private function validateImport(array $data): void
    {
        foreach (['name', 'country', 'start_date', 'currency', 'exchange_rate'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }
    }
}
