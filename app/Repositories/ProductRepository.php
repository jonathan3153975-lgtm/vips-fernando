<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class ProductRepository extends TenantScopedRepository
{
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
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $productId = $this->insert(
            'INSERT INTO products (
                tenant_id, category_id, brand_id, supplier_id, default_import_item_id,
                sku, barcode, name, description, unit, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :category_id, :brand_id, :supplier_id, NULL,
                :sku, :barcode, :name, :description, :unit, :status, :created_at, :updated_at
            )',
            [
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'unit' => $data['unit'] ?? 'UN',
                'status' => $data['status'] ?? 'ACTIVE',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        $this->upsertPrice($productId, $data['price'] ?? []);
        $this->ensureStock($productId);

        return $this->find($productId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $productId, array $data): array
    {
        $current = $this->find($productId);

        if ($current === null) {
            throw NotFoundException::entity('Produto');
        }

        $this->run(
            'UPDATE products SET
                category_id = :category_id,
                brand_id = :brand_id,
                supplier_id = :supplier_id,
                sku = :sku,
                barcode = :barcode,
                name = :name,
                description = :description,
                unit = :unit,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'category_id' => $data['category_id'] ?? $current['category_id'],
                'brand_id' => $data['brand_id'] ?? $current['brand_id'],
                'supplier_id' => $data['supplier_id'] ?? $current['supplier_id'],
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

        if (isset($data['price']) && is_array($data['price'])) {
            $this->upsertPrice($productId, $data['price']);
        }

        $this->ensureStock($productId);

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
            throw NotFoundException::entity('Produto');
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
}
