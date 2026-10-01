<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;
use RuntimeException;
use Throwable;

final class ImportRepository extends TenantScopedRepository
{
    public const STATUS_PLANNED = 'PLANNED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const ALLOCATION_VALUE = 'VALUE';

    public const ALLOCATION_QUANTITY = 'QUANTITY';

    /** @var list<string> */
    public const ALLOCATION_METHODS = [self::ALLOCATION_VALUE, self::ALLOCATION_QUANTITY];

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
        $allocationMethod = (string) ($data['allocation_method'] ?? self::ALLOCATION_VALUE);

        $importId = $this->insert(
            'INSERT INTO imports (
                tenant_id, responsible_user_id, name, description, country, city,
                start_date, end_date, currency, exchange_rate, status, allocation_method,
                invested_amount, total_expenses, total_items, created_at, updated_at
            ) VALUES (
                :tenant_id, :responsible_user_id, :name, :description, :country, :city,
                :start_date, :end_date, :currency, :exchange_rate, :status, :allocation_method,
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
                'status' => $data['status'] ?? self::STATUS_PLANNED,
                'allocation_method' => $allocationMethod,
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
                allocation_method = :allocation_method,
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
                'allocation_method' => $data['allocation_method'] ?? $current['allocation_method'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $importId,
            ],
        );

        return $this->find($importId) ?? [];
    }

    /**
     * Reabre uma importacao concluida para reprocessamento explicito. Os
     * valores congelados permanecem ate uma nova conclusao.
     *
     * @return array<string, mixed>
     */
    public function reopen(int $importId): array
    {
        $this->findOrFail($importId);

        $this->run(
            'UPDATE imports
             SET status = :status, completed_at = NULL, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'status' => self::STATUS_IN_PROGRESS,
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

    /**
     * Lista de despesas com filtros e paginacao.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function expenses(int $importId, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        $this->findOrFail($importId);

        [$where, $params] = $this->expenseFilter($filters);

        return $this->selectAll(
            'SELECT * FROM import_expenses
             WHERE tenant_id = :tenant_id AND import_id = :import_id' . $where . '
             ORDER BY id
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            ['import_id' => $importId] + $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countExpenses(int $importId, array $filters = []): int
    {
        $this->findOrFail($importId);

        [$where, $params] = $this->expenseFilter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*) FROM import_expenses
             WHERE tenant_id = :tenant_id AND import_id = :import_id' . $where,
            ['import_id' => $importId] + $params,
        );
    }

    /**
     * Lista de itens com filtros e paginacao.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function items(int $importId, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        $this->findOrFail($importId);

        [$where, $params] = $this->itemFilter($filters);

        return $this->selectAll(
            'SELECT * FROM import_items
             WHERE tenant_id = :tenant_id AND import_id = :import_id' . $where . '
             ORDER BY id
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            ['import_id' => $importId] + $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countItems(int $importId, array $filters = []): int
    {
        $this->findOrFail($importId);

        [$where, $params] = $this->itemFilter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*) FROM import_items
             WHERE tenant_id = :tenant_id AND import_id = :import_id' . $where,
            ['import_id' => $importId] + $params,
        );
    }

    /**
     * Calcula o rateio e congela os valores da importacao numa unica transacao.
     *
     * Todo o calculo (leitura dos itens, soma das despesas, gravacao do rateio e
     * atualizacao dos totais) roda junto: uma falha no meio nao deixa a
     * importacao parcialmente calculada.
     *
     * @return array<string, mixed>
     */
    public function freeze(int $importId, string $allocationMethod): array
    {
        if (!in_array($allocationMethod, self::ALLOCATION_METHODS, true)) {
            throw new RuntimeException('Metodo de rateio invalido: ' . $allocationMethod);
        }

        $this->findOrFail($importId);

        // Entra numa transacao externa quando ja existir uma (o fechamento da
        // importacao compartilha transacao com a entrada de estoque). Abrir uma
        // segunda aqui faria o MySQL recusar por "active transaction".
        return $this->transactional(fn (): array => $this->freezeWithin($importId, $allocationMethod));
    }

    /**
     * Corpo transacional do fechamento, executado dentro da transacao de quem
     * chamou (esta ou uma externa).
     *
     * @return array<string, mixed>
     */
    private function freezeWithin(int $importId, string $allocationMethod): array
    {
        $items = $this->selectAll(
            'SELECT id, quantity, total_cost_local
             FROM import_items
             WHERE tenant_id = :tenant_id AND import_id = :import_id
             ORDER BY id',
            ['import_id' => $importId],
        );

        if ($items === []) {
            throw new RuntimeException('Adicione ao menos um item antes de concluir a importacao.');
        }

        $expenseTotal = (float) $this->selectValue(
            'SELECT COALESCE(SUM(converted_amount), 0)
             FROM import_expenses
             WHERE tenant_id = :tenant_id AND import_id = :import_id',
            ['import_id' => $importId],
        );

        $allocationCents = $this->allocateCents($items, $expenseTotal, $allocationMethod);

        $totalCostLocal = 0.0;
        $totalQuantity = 0.0;

        foreach ($items as $index => $item) {
            $allocated = $allocationCents[$index] / 100;
            $quantity = (float) $item['quantity'];
            $realUnitCost = $quantity > 0
                ? round(((float) $item['total_cost_local'] + $allocated) / $quantity, 2)
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

            $totalCostLocal += (float) $item['total_cost_local'];
            $totalQuantity += $quantity;
        }

        $this->run(
            'UPDATE imports
             SET status = :status,
                 allocation_method = :allocation_method,
                 invested_amount = :invested_amount,
                 total_expenses = :total_expenses,
                 total_items = :total_items,
                 completed_at = :completed_at,
                 updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'status' => self::STATUS_COMPLETED,
                'allocation_method' => $allocationMethod,
                'invested_amount' => round($totalCostLocal + $expenseTotal, 2),
                'total_expenses' => round($expenseTotal, 2),
                'total_items' => $totalQuantity,
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $importId,
            ],
        );

        return $this->find($importId) ?? [];
    }

    /**
     * Executa o callback numa transacao. Se ja existir uma transacao externa,
     * apenas participa dela: e o que permite concluir a importacao e dar entrada
     * no estoque como uma operacao so — se a entrada falhar, a importacao nao
     * fica concluida.
     */
    public function transactional(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $owns = !$pdo->inTransaction();

        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if ($owns) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $throwable) {
            if ($owns) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * Distribui as despesas em centavos inteiros. Cada item recebe
     * `round(base_do_item / base_total * total)`, exceto o ultimo, que absorve a
     * diferenca — assim a soma dos rateios e EXATAMENTE o total de despesas, sem
     * residuo de centavos. Trabalhar em centavos evita o erro de arredondamento
     * de ponto flutuante.
     *
     * @param list<array<string, mixed>> $items
     *
     * @return list<int>
     */
    private function allocateCents(array $items, float $expenseTotal, string $allocationMethod): array
    {
        $expenseCents = (int) round($expenseTotal * 100);

        $bases = array_map(
            static fn (array $item): float => $allocationMethod === self::ALLOCATION_QUANTITY
                ? (float) $item['quantity']
                : (float) $item['total_cost_local'],
            $items,
        );

        $baseSum = array_sum($bases);

        if ($baseSum <= 0) {
            throw new RuntimeException(
                'Nao ha base para o rateio por ' . strtolower($allocationMethod)
                . ': confira quantidade e custo dos itens.'
            );
        }

        $count = count($items);
        $remaining = $expenseCents;
        $allocations = [];

        for ($index = 0; $index < $count; $index++) {
            if ($index === $count - 1) {
                $allocations[$index] = $remaining;
                continue;
            }

            $share = (int) round($expenseCents * ($bases[$index] / $baseSum));
            $allocations[$index] = $share;
            $remaining -= $share;
        }

        return $allocations;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function expenseFilter(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['category'])) {
            $where .= ' AND category = :category';
            $params['category'] = (string) $filters['category'];
        }

        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = strtoupper((string) $filters['status']);
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function itemFilter(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['sku'])) {
            $where .= ' AND sku = :sku';
            $params['sku'] = (string) $filters['sku'];
        }

        return [$where, $params];
    }
}
