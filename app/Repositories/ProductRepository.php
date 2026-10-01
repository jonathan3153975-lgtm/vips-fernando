<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;
use RuntimeException;

final class ProductRepository extends TenantScopedRepository
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT p.*, pp.cost_price, pp.sale_price, pp.minimum_price, pp.margin
             FROM products p
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE p.tenant_id = :tenant_id
             ORDER BY p.id DESC'
        );
    }

    /**
     * Listagem com filtros (categoria, marca, status, busca) e paginacao.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function paginate(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->filter($filters);

        return $this->selectAll(
            'SELECT p.*, pp.cost_price, pp.sale_price, pp.minimum_price, pp.margin
             FROM products p
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE p.tenant_id = :tenant_id' . $where . '
             ORDER BY p.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countFiltered(array $filters = []): int
    {
        [$where, $params] = $this->filter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM products p
             WHERE p.tenant_id = :tenant_id' . $where,
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $productId): ?array
    {
        return $this->selectOne(
            'SELECT p.*, pp.cost_price, pp.sale_price, pp.minimum_price, pp.margin
             FROM products p
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE p.tenant_id = :tenant_id AND p.id = :id
             LIMIT 1',
            ['id' => $productId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $productId): array
    {
        $product = $this->find($productId);

        if ($product === null) {
            throw NotFoundException::entity('Produto', 'nao encontrado.');
        }

        return $product;
    }

    /**
     * Custo real congelado no rateio da importacao, para um item vinculado.
     * Retorna null se o item nao existir neste tenant ou ainda nao tiver custo
     * real (importacao nao concluida).
     */
    public function realUnitCostForItem(int $importItemId): ?float
    {
        $row = $this->selectOne(
            'SELECT ii.real_unit_cost, i.status AS import_status
             FROM import_items ii
             INNER JOIN imports i ON i.id = ii.import_id
             WHERE ii.tenant_id = :tenant_id AND ii.id = :id
             LIMIT 1',
            ['id' => $importItemId],
        );

        if ($row === null) {
            return null;
        }

        // Sem conclusao da importacao o rateio ainda nao existe; o custo real so
        // passa a valer quando a importacao esta congelada (COMPLETED).
        if ((string) $row['import_status'] !== 'COMPLETED') {
            return null;
        }

        return (float) $row['real_unit_cost'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $defaultImportItemId = $data['default_import_item_id'] ?? null;

        // Produto, preco e estoque sao gravados juntos: deixar o preco ou o
        // estoque de fora produziria um produto sem precificacao.
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $productId = $this->insert(
                'INSERT INTO products (
                    tenant_id, category_id, brand_id, supplier_id, default_import_item_id,
                    sku, barcode, name, description, unit, status, created_at, updated_at
                ) VALUES (
                    :tenant_id, :category_id, :brand_id, :supplier_id, :default_import_item_id,
                    :sku, :barcode, :name, :description, :unit, :status, :created_at, :updated_at
                )',
                [
                    'category_id' => $data['category_id'] ?? null,
                    'brand_id' => $data['brand_id'] ?? null,
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'default_import_item_id' => $defaultImportItemId,
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'unit' => $data['unit'] ?? 'UN',
                    'status' => $data['status'] ?? self::STATUS_ACTIVE,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );

            $this->upsertPrice($productId, $this->withDefaultCost($data['price'] ?? [], $defaultImportItemId));
            $this->ensureStock($productId);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();

            throw $throwable;
        }

        return $this->find($productId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $productId, array $data): array
    {
        $current = $this->findOrFail($productId);

        $this->run(
            'UPDATE products SET
                category_id = :category_id,
                brand_id = :brand_id,
                supplier_id = :supplier_id,
                default_import_item_id = :default_import_item_id,
                sku = :sku,
                barcode = :barcode,
                name = :name,
                description = :description,
                unit = :unit,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $current['category_id'],
                'brand_id' => array_key_exists('brand_id', $data) ? $data['brand_id'] : $current['brand_id'],
                'supplier_id' => array_key_exists('supplier_id', $data) ? $data['supplier_id'] : $current['supplier_id'],
                'default_import_item_id' => array_key_exists('default_import_item_id', $data)
                    ? $data['default_import_item_id']
                    : $current['default_import_item_id'],
                'sku' => $data['sku'] ?? $current['sku'],
                'barcode' => $data['barcode'] ?? $current['barcode'],
                'name' => $data['name'] ?? $current['name'],
                'description' => $data['description'] ?? $current['description'],
                'unit' => $data['unit'] ?? $current['unit'],
                'status' => $data['status'] ?? $current['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $productId,
            ],
        );

        $itemId = array_key_exists('default_import_item_id', $data)
            ? $data['default_import_item_id']
            : $current['default_import_item_id'];

        // Recalcula o custo quando o item de importacao vinculado muda (e nenhum
        // custo explicito foi enviado): o preco antigo passaria a descrever outro
        // item, e nao o custo real do novo.
        $itemChanged = array_key_exists('default_import_item_id', $data)
            && (string) $data['default_import_item_id'] !== (string) $current['default_import_item_id'];

        if ((isset($data['price']) && is_array($data['price'])) || $itemChanged) {
            $this->upsertPrice($productId, $this->withDefaultCost($data['price'] ?? [], $itemId));
        }

        $this->ensureStock($productId);

        return $this->find($productId) ?? [];
    }

    /**
     * Produto com estoque movimentado ou historico de venda nao pode ser
     * excluido — o registro e referenciado por stock_movements e sale_items.
     * Nesse caso a orientacao e desativar (status INACTIVE).
     */
    public function assertDeletable(int $productId): void
    {
        $stockQuantity = (float) $this->selectValue(
            'SELECT COALESCE(quantity, 0) - COALESCE(reserved_quantity, 0)
             FROM stock
             WHERE tenant_id = :tenant_id AND product_id = :product_id
             LIMIT 1',
            ['product_id' => $productId],
        );

        if (abs($stockQuantity) > 0.0001) {
            throw new RuntimeException(
                'Produto com estoque em aberto nao pode ser excluido. Desative-o (INACTIVE) para preservar o historico.'
            );
        }

        $movements = (int) $this->selectValue(
            'SELECT COUNT(*) FROM stock_movements WHERE tenant_id = :tenant_id AND product_id = :product_id',
            ['product_id' => $productId],
        );

        if ($movements > 0) {
            throw new RuntimeException(
                'Produto com historico de movimentacao nao pode ser excluido. Desative-o (INACTIVE) em vez de excluir.'
            );
        }

        // sale_items nao tem tenant_id: o escopo vem de sales, entao a
        // verificacao desce pela venda (sem duplicar o marcador :tenant_id, que
        // nao pode se repetir sob prepares nativos).
        $sales = (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             WHERE s.tenant_id = :tenant_id AND si.product_id = :product_id',
            ['product_id' => $productId],
        );

        if ($sales > 0) {
            throw new RuntimeException(
                'Produto com historico de vendas nao pode ser excluido. Desative-o (INACTIVE) em vez de excluir.'
            );
        }
    }

    public function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM products WHERE tenant_id = :tenant_id AND sku = :sku';
        $params = ['sku' => $sku];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        return $this->selectValue($sql . ' LIMIT 1', $params) !== null;
    }

    public function importItemExists(int $importItemId): bool
    {
        return $this->selectValue(
            'SELECT id FROM import_items WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $importItemId],
        ) !== null;
    }

    /**
     * Desativar preserva o registro e o historico — a alternativa recomendada
     * quando a exclusao e bloqueada por estoque ou vendas.
     *
     * @return array<string, mixed>
     */
    public function deactivate(int $productId): array
    {
        $this->findOrFail($productId);

        $this->run(
            'UPDATE products SET status = :status, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'status' => self::STATUS_INACTIVE,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $productId,
            ],
        );

        return $this->find($productId) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function stock(int $productId): array
    {
        $stock = $this->selectOne(
            'SELECT product_id, quantity, reserved_quantity, minimum_quantity
             FROM stock
             WHERE tenant_id = :tenant_id AND product_id = :product_id
             LIMIT 1',
            ['product_id' => $productId],
        );

        if ($stock === null) {
            throw NotFoundException::entity('Produto', 'nao encontrado.');
        }

        return $stock;
    }

    private function ensureStock(int $productId): void
    {
        $exists = $this->selectOne(
            'SELECT id FROM stock WHERE tenant_id = :tenant_id AND product_id = :product_id LIMIT 1',
            ['product_id' => $productId],
        );

        if ($exists !== null) {
            return;
        }

        $this->run(
            'INSERT INTO stock (tenant_id, product_id, quantity, reserved_quantity, minimum_quantity, updated_at)
             VALUES (:tenant_id, :product_id, 0, 0, 0, :updated_at)',
            [
                'product_id' => $productId,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Se o produto esta vinculado a um item de importacao concluido e nenhum
     * `cost_price` foi informado, preenche com o custo real rateado.
     *
     * @param array<string, mixed> $price
     * @param mixed $defaultImportItemId
     *
     * @return array<string, mixed>
     */
    private function withDefaultCost(array $price, $defaultImportItemId): array
    {
        if (isset($price['cost_price'])) {
            return $price;
        }

        if ($defaultImportItemId === null || $defaultImportItemId === '') {
            return $price;
        }

        $realUnitCost = $this->realUnitCostForItem((int) $defaultImportItemId);

        if ($realUnitCost === null) {
            return $price;
        }

        return ['cost_price' => $realUnitCost] + $price;
    }

    /**
     * @param array<string, mixed> $price
     */
    private function upsertPrice(int $productId, array $price): void
    {
        $priceId = $this->selectValue(
            'SELECT id FROM product_prices
             WHERE product_id = :product_id
               AND product_id IN (SELECT id FROM products WHERE tenant_id = :tenant_id)
             LIMIT 1',
            ['product_id' => $productId],
        );

        $timestamp = date('Y-m-d H:i:s');

        if ($priceId === null) {
            $this->run(
                'INSERT INTO product_prices (
                    product_id, cost_price, sale_price, minimum_price, margin, created_at, updated_at
                )
                 SELECT p.id, :cost_price, :sale_price, :minimum_price, :margin, :created_at, :updated_at
                 FROM products p
                 WHERE p.tenant_id = :tenant_id AND p.id = :product_id',
                [
                    'product_id' => $productId,
                    'cost_price' => $price['cost_price'] ?? 0,
                    'sale_price' => $price['sale_price'] ?? 0,
                    'minimum_price' => $price['minimum_price'] ?? 0,
                    'margin' => $price['margin'] ?? 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );

            return;
        }

        $this->run(
            'UPDATE product_prices SET
                cost_price = :cost_price,
                sale_price = :sale_price,
                minimum_price = :minimum_price,
                margin = :margin,
                updated_at = :updated_at
             WHERE product_id = :product_id
               AND product_id IN (SELECT id FROM products WHERE tenant_id = :tenant_id)',
            [
                'product_id' => $productId,
                'cost_price' => $price['cost_price'] ?? 0,
                'sale_price' => $price['sale_price'] ?? 0,
                'minimum_price' => $price['minimum_price'] ?? 0,
                'margin' => $price['margin'] ?? 0,
                'updated_at' => $timestamp,
            ],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function filter(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['category_id'])) {
            $where .= ' AND p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $where .= ' AND p.brand_id = :brand_id';
            $params['brand_id'] = (int) $filters['brand_id'];
        }

        if (!empty($filters['status'])) {
            $where .= ' AND p.status = :status';
            $params['status'] = strtoupper((string) $filters['status']);
        }

        if (!empty($filters['search'])) {
            // Dois placeholders distintos: repetir :search quebraria com
            // ATTR_EMULATE_PREPARES = false (HY093).
            $where .= ' AND (p.name LIKE :search_name OR p.sku LIKE :search_sku)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_sku'] = '%' . $filters['search'] . '%';
        }

        return [$where, $params];
    }
}
