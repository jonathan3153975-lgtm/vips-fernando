<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class ImportRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll('SELECT * FROM imports WHERE tenant_id = :tenant_id ORDER BY id DESC');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $importId): ?array
    {
        return $this->selectOne(
            'SELECT * FROM imports WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $importId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $importId): array
    {
        $import = $this->find($importId);

        if ($import === null) {
            throw NotFoundException::entity('Importacao', 'nao encontrada.');
        }

        return $import;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $importId = $this->insert(
            'INSERT INTO imports (
                tenant_id, responsible_user_id, name, description, country, city,
                start_date, end_date, currency, exchange_rate, status,
                invested_amount, total_expenses, total_items, created_at, updated_at
            ) VALUES (
                :tenant_id, :responsible_user_id, :name, :description, :country, :city,
                :start_date, :end_date, :currency, :exchange_rate, :status,
                0, 0, 0, :created_at, :updated_at
            )',
            [
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'country' => $data['country'],
                'city' => $data['city'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'currency' => strtoupper((string) $data['currency']),
                'exchange_rate' => $data['exchange_rate'],
                'status' => $data['status'] ?? 'PLANNED',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->find($importId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $importId, array $data): array
    {
        $current = $this->findOrFail($importId);

        $this->run(
            'UPDATE imports SET
                responsible_user_id = :responsible_user_id,
                name = :name,
                description = :description,
                country = :country,
                city = :city,
                start_date = :start_date,
                end_date = :end_date,
                currency = :currency,
                exchange_rate = :exchange_rate,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'responsible_user_id' => $data['responsible_user_id'] ?? $current['responsible_user_id'],
                'name' => $data['name'] ?? $current['name'],
                'description' => $data['description'] ?? $current['description'],
                'country' => $data['country'] ?? $current['country'],
                'city' => $data['city'] ?? $current['city'],
                'start_date' => $data['start_date'] ?? $current['start_date'],
                'end_date' => $data['end_date'] ?? $current['end_date'],
                'currency' => strtoupper((string) ($data['currency'] ?? $current['currency'])),
                'exchange_rate' => $data['exchange_rate'] ?? $current['exchange_rate'],
                'status' => $data['status'] ?? $current['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $importId,
            ],
        );

        return $this->find($importId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addExpense(int $importId, array $data): array
    {
        $this->findOrFail($importId);

        $timestamp = date('Y-m-d H:i:s');
        $convertedAmount = $data['converted_amount']
            ?? round((float) $data['amount'] * (float) $data['exchange_rate'], 2);

        $expenseId = $this->insert(
            'INSERT INTO import_expenses (
                tenant_id, import_id, supplier_id, category, description, currency,
                amount, exchange_rate, converted_amount, expense_date, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :import_id, :supplier_id, :category, :description, :currency,
                :amount, :exchange_rate, :converted_amount, :expense_date, :status, :created_at, :updated_at
            )',
            [
                'import_id' => $importId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'category' => $data['category'],
                'description' => $data['description'],
                'currency' => strtoupper((string) $data['currency']),
                'amount' => $data['amount'],
                'exchange_rate' => $data['exchange_rate'],
                'converted_amount' => $convertedAmount,
                'expense_date' => $data['expense_date'],
                'status' => $data['status'] ?? 'PENDING',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->selectOne(
            'SELECT * FROM import_expenses WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $expenseId],
        ) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addItem(int $importId, array $data): array
    {
        $this->findOrFail($importId);

        $timestamp = date('Y-m-d H:i:s');
        $unitCostLocal = round((float) $data['unit_cost_foreign'] * (float) $data['exchange_rate'], 2);
        $totalCostLocal = round($unitCostLocal * (float) $data['quantity'], 2);

        $itemId = $this->insert(
            'INSERT INTO import_items (
                tenant_id, import_id, supplier_id, product_name, sku, quantity,
                unit_cost_foreign, exchange_rate, unit_cost_local, total_cost_local,
                allocated_expense, real_unit_cost, created_at, updated_at
            ) VALUES (
                :tenant_id, :import_id, :supplier_id, :product_name, :sku, :quantity,
                :unit_cost_foreign, :exchange_rate, :unit_cost_local, :total_cost_local,
                0, :real_unit_cost, :created_at, :updated_at
            )',
            [
                'import_id' => $importId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'product_name' => $data['product_name'],
                'sku' => $data['sku'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost_foreign' => $data['unit_cost_foreign'],
                'exchange_rate' => $data['exchange_rate'],
                'unit_cost_local' => $unitCostLocal,
                'total_cost_local' => $totalCostLocal,
                'real_unit_cost' => $unitCostLocal,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->selectOne(
            'SELECT * FROM import_items WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $itemId],
        ) ?? [];
    }

    public function expenseTotal(int $importId): float
    {
        $this->findOrFail($importId);

        $total = $this->selectValue(
            'SELECT COALESCE(SUM(converted_amount), 0)
             FROM import_expenses
             WHERE tenant_id = :tenant_id AND import_id = :import_id',
            ['import_id' => $importId],
        );

        return (float) $total;
    }

    /**
     * @return array{total_cost_local: float, total_quantity: float}
     */
    public function itemTotals(int $importId): array
    {
        $this->findOrFail($importId);

        $row = $this->selectOne(
            'SELECT COALESCE(SUM(total_cost_local), 0) AS total_cost_local,
                    COALESCE(SUM(quantity), 0) AS total_quantity
             FROM import_items
             WHERE tenant_id = :tenant_id AND import_id = :import_id',
            ['import_id' => $importId],
        );

        return [
            'total_cost_local' => (float) ($row['total_cost_local'] ?? 0),
            'total_quantity' => (float) ($row['total_quantity'] ?? 0),
        ];
    }

    public function allocateExpenses(int $importId, float $expenseTotal): void
    {
        $this->findOrFail($importId);

        $items = $this->selectAll(
            'SELECT id, quantity, total_cost_local
             FROM import_items
             WHERE tenant_id = :tenant_id AND import_id = :import_id
             ORDER BY id',
            ['import_id' => $importId],
        );

        $costBase = array_sum(array_map(
            static fn (array $item): float => (float) $item['total_cost_local'],
            $items,
        ));

        foreach ($items as $item) {
            $ratio = $costBase > 0 ? ((float) $item['total_cost_local'] / $costBase) : 0;
            $allocated = round($expenseTotal * $ratio, 2);
            $realUnitCost = (float) $item['quantity'] > 0
                ? round((((float) $item['total_cost_local']) + $allocated) / (float) $item['quantity'], 2)
                : 0.00;

            $this->run(
                'UPDATE import_items
                 SET allocated_expense = :allocated_expense,
                     real_unit_cost = :real_unit_cost,
                     updated_at = :updated_at
                 WHERE tenant_id = :tenant_id AND id = :id',
                [
                    'allocated_expense' => $allocated,
                    'real_unit_cost' => $realUnitCost,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id' => $item['id'],
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(int $importId, float $investedAmount, float $expenseTotal, float $itemsTotal): array
    {
        $this->findOrFail($importId);

        $this->run(
            'UPDATE imports
             SET status = :status,
                 invested_amount = :invested_amount,
                 total_expenses = :total_expenses,
                 total_items = :total_items,
                 updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'status' => 'COMPLETED',
                'invested_amount' => $investedAmount,
                'total_expenses' => $expenseTotal,
                'total_items' => $itemsTotal,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $importId,
            ],
        );

        return $this->find($importId) ?? [];
    }
}
