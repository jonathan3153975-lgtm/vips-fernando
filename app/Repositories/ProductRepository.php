<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;

final class ProductRepository
{
    public function allByTenant(int $tenantId): array
    {
        $statement = $this->pdo()->prepare(
            'SELECT p.*, pp.cost_price, pp.sale_price, pp.minimum_price, pp.margin
             FROM products p
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE p.tenant_id = :tenant_id
             ORDER BY p.id DESC'
        );
        $statement->execute(['tenant_id' => $tenantId]);

        return $statement->fetchAll() ?: [];
    }

    public function findForTenant(int $tenantId, int $productId): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT p.*, pp.cost_price, pp.sale_price, pp.minimum_price, pp.margin
             FROM products p
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE p.tenant_id = :tenant_id AND p.id = :id
             LIMIT 1'
        );
        $statement->execute(['tenant_id' => $tenantId, 'id' => $productId]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function create(int $tenantId, array $data): array
    {
        $pdo = $this->pdo();
        $timestamp = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            'INSERT INTO products (
                tenant_id, category_id, brand_id, supplier_id, default_import_item_id,
                sku, barcode, name, description, unit, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :category_id, :brand_id, :supplier_id, NULL,
                :sku, :barcode, :name, :description, :unit, :status, :created_at, :updated_at
            )'
        );

        $statement->execute([
            'tenant_id' => $tenantId,
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
        ]);

        $productId = (int) $pdo->lastInsertId();
        $this->upsertPrice($productId, $data['price'] ?? []);
        $this->ensureStock($tenantId, $productId);

        return $this->findForTenant($tenantId, $productId) ?? [];
    }

    public function update(int $tenantId, int $productId, array $data): array
    {
        $current = $this->findForTenant($tenantId, $productId);

        if ($current === null) {
            return [];
        }

        $statement = $this->pdo()->prepare(
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
             WHERE tenant_id = :tenant_id AND id = :id'
        );

        $statement->execute([
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
            'tenant_id' => $tenantId,
            'id' => $productId,
        ]);

        if (isset($data['price']) && is_array($data['price'])) {
            $this->upsertPrice($productId, $data['price']);
        }

        $this->ensureStock($tenantId, $productId);

        return $this->findForTenant($tenantId, $productId) ?? [];
    }

    public function stockForTenant(int $tenantId, int $productId): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT product_id, quantity, reserved_quantity, minimum_quantity
             FROM stock
             WHERE tenant_id = :tenant_id AND product_id = :product_id
             LIMIT 1'
        );
        $statement->execute([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
        ]);

        $stock = $statement->fetch();

        return $stock === false ? null : $stock;
    }

    private function ensureStock(int $tenantId, int $productId): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO stock (tenant_id, product_id, quantity, reserved_quantity, minimum_quantity, updated_at)
             SELECT :tenant_id, :product_id, 0, 0, 0, :updated_at
             WHERE NOT EXISTS (
                 SELECT 1 FROM stock WHERE tenant_id = :tenant_id_check AND product_id = :product_id_check
             )'
        );

        $timestamp = date('Y-m-d H:i:s');

        $statement->execute([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'updated_at' => $timestamp,
            'tenant_id_check' => $tenantId,
            'product_id_check' => $productId,
        ]);
    }

    private function upsertPrice(int $productId, array $price): void
    {
        $pdo = $this->pdo();
        $existing = $pdo->prepare('SELECT id FROM product_prices WHERE product_id = :product_id LIMIT 1');
        $existing->execute(['product_id' => $productId]);
        $priceId = $existing->fetchColumn();

        if ($priceId === false) {
            $statement = $pdo->prepare(
                'INSERT INTO product_prices (
                    product_id, cost_price, sale_price, minimum_price, margin, created_at, updated_at
                ) VALUES (
                    :product_id, :cost_price, :sale_price, :minimum_price, :margin, :created_at, :updated_at
                )'
            );
            $statement->execute([
                'product_id' => $productId,
                'cost_price' => $price['cost_price'] ?? 0,
                'sale_price' => $price['sale_price'] ?? 0,
                'minimum_price' => $price['minimum_price'] ?? 0,
                'margin' => $price['margin'] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $statement = $pdo->prepare(
            'UPDATE product_prices SET
                cost_price = :cost_price,
                sale_price = :sale_price,
                minimum_price = :minimum_price,
                margin = :margin,
                updated_at = :updated_at
             WHERE product_id = :product_id'
        );
        $statement->execute([
            'product_id' => $productId,
            'cost_price' => $price['cost_price'] ?? 0,
            'sale_price' => $price['sale_price'] ?? 0,
            'minimum_price' => $price['minimum_price'] ?? 0,
            'margin' => $price['margin'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function pdo(): \PDO
    {
        $app = Application::getInstance();

        return Database::connect($app->config('database'));
    }
}
