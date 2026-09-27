<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;

final class ImportRepository
{
    public function allByTenant(int $tenantId): array
    {
        $pdo = $this->pdo();
        $statement = $pdo->prepare('SELECT * FROM imports WHERE tenant_id = :tenant_id ORDER BY id DESC');
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll() ?: [];
    }

    public function findForTenant(int $tenantId, int $importId): ?array
    {
        $pdo = $this->pdo();
        $statement = $pdo->prepare('SELECT * FROM imports WHERE tenant_id = :tenant_id AND id = :id LIMIT 1');
        $statement->execute(['tenant_id' => $tenantId, 'id' => $importId]);
        $import = $statement->fetch();

        return $import === false ? null : $import;
    }

    public function create(int $tenantId, array $data): array
    {
        $pdo = $this->pdo();
        $timestamp = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            'INSERT INTO imports (
                tenant_id, responsible_user_id, name, description, country, city,
                start_date, end_date, currency, exchange_rate, status,
                invested_amount, total_expenses, total_items, created_at, updated_at
            ) VALUES (
                :tenant_id, :responsible_user_id, :name, :description, :country, :city,
                :start_date, :end_date, :currency, :exchange_rate, :status,
                0, 0, 0, :created_at, :updated_at
            )'
        );

        $statement->execute([
            'tenant_id' => $tenantId,
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
        ]);

        return $this->findForTenant($tenantId, (int) $pdo->lastInsertId()) ?? [];
    }

    public function update(int $tenantId, int $importId, array $data): array
    {
        $pdo = $this->pdo();
        $current = $this->findForTenant($tenantId, $importId);

        if ($current === null) {
            return [];
        }

        $statement = $pdo->prepare(
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
            WHERE tenant_id = :tenant_id AND id = :id'
        );

        $statement->execute([
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
            'tenant_id' => $tenantId,
            'id' => $importId,
        ]);

        return $this->findForTenant($tenantId, $importId) ?? [];
    }

    public function addExpense(int $tenantId, int $importId, array $data): array
    {
        $pdo = $this->pdo();
        $timestamp = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            'INSERT INTO import_expenses (
                tenant_id, import_id, supplier_id, category, description, currency,
                amount, exchange_rate, converted_amount, expense_date, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :import_id, :supplier_id, :category, :description, :currency,
                :amount, :exchange_rate, :converted_amount, :expense_date, :status, :created_at, :updated_at
            )'
        );

        $convertedAmount = $data['converted_amount'] ?? round((float) $data['amount'] * (float) $data['exchange_rate'], 2);

        $statement->execute([
            'tenant_id' => $tenantId,
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
        ]);

        $expenseId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT * FROM import_expenses WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $expenseId]);

        return $statement->fetch() ?: [];
    }

    public function addItem(int $tenantId, int $importId, array $data): array
    {
        $pdo = $this->pdo();
        $timestamp = date('Y-m-d H:i:s');
        $unitCostLocal = round((float) $data['unit_cost_foreign'] * (float) $data['exchange_rate'], 2);
        $totalCostLocal = round($unitCostLocal * (float) $data['quantity'], 2);

        $statement = $pdo->prepare(
            'INSERT INTO import_items (
                tenant_id, import_id, supplier_id, product_name, sku, quantity,
                unit_cost_foreign, exchange_rate, unit_cost_local, total_cost_local,
                allocated_expense, real_unit_cost, created_at, updated_at
            ) VALUES (
                :tenant_id, :import_id, :supplier_id, :product_name, :sku, :quantity,
                :unit_cost_foreign, :exchange_rate, :unit_cost_local, :total_cost_local,
                0, :real_unit_cost, :created_at, :updated_at
            )'
        );

        $statement->execute([
            'tenant_id' => $tenantId,
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
        ]);

        $itemId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT * FROM import_items WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $itemId]);

        return $statement->fetch() ?: [];
    }

    public function expenseTotal(int $importId): float
    {
        $pdo = $this->pdo();
        $statement = $pdo->prepare('SELECT COALESCE(SUM(converted_amount), 0) FROM import_expenses WHERE import_id = :import_id');
        $statement->execute(['import_id' => $importId]);

        return (float) $statement->fetchColumn();
    }

    public function itemTotals(int $importId): array
    {
        $pdo = $this->pdo();
        $statement = $pdo->prepare(
            'SELECT COALESCE(SUM(total_cost_local), 0) AS total_cost_local,
                    COALESCE(SUM(quantity), 0) AS total_quantity
             FROM import_items WHERE import_id = :import_id'
        );
        $statement->execute(['import_id' => $importId]);

        return $statement->fetch() ?: ['total_cost_local' => 0, 'total_quantity' => 0];
    }

    public function allocateExpenses(int $importId, float $expenseTotal): void
    {
        $pdo = $this->pdo();
        $itemsStatement = $pdo->prepare('SELECT id, quantity, total_cost_local FROM import_items WHERE import_id = :import_id ORDER BY id');
        $itemsStatement->execute(['import_id' => $importId]);
        $items = $itemsStatement->fetchAll() ?: [];

        $costBase = array_sum(array_map(static fn (array $item): float => (float) $item['total_cost_local'], $items));

        foreach ($items as $item) {
            $ratio = $costBase > 0 ? ((float) $item['total_cost_local'] / $costBase) : 0;
            $allocated = round($expenseTotal * $ratio, 2);
            $realUnitCost = (float) $item['quantity'] > 0
                ? round((((float) $item['total_cost_local']) + $allocated) / (float) $item['quantity'], 2)
                : 0.00;

            $statement = $pdo->prepare(
                'UPDATE import_items
                 SET allocated_expense = :allocated_expense,
                     real_unit_cost = :real_unit_cost,
                     updated_at = :updated_at
                 WHERE id = :id'
            );

            $statement->execute([
                'allocated_expense' => $allocated,
                'real_unit_cost' => $realUnitCost,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $item['id'],
            ]);
        }
    }

    public function complete(int $tenantId, int $importId, float $investedAmount, float $expenseTotal, float $itemsTotal): array
    {
        $pdo = $this->pdo();
        $statement = $pdo->prepare(
            'UPDATE imports
             SET status = :status,
                 invested_amount = :invested_amount,
                 total_expenses = :total_expenses,
                 total_items = :total_items,
                 updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id'
        );
        $statement->execute([
            'status' => 'COMPLETED',
            'invested_amount' => $investedAmount,
            'total_expenses' => $expenseTotal,
            'total_items' => $itemsTotal,
            'updated_at' => date('Y-m-d H:i:s'),
            'tenant_id' => $tenantId,
            'id' => $importId,
        ]);

        return $this->findForTenant($tenantId, $importId) ?? [];
    }

    private function pdo(): \PDO
    {
        $app = Application::getInstance();

        return Database::connect($app->config('database'));
    }
}
