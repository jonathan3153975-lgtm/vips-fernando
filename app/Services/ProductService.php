<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\ProductRepository;
use RuntimeException;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->products->all();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        foreach (['sku', 'name'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $product = $this->products->create($data);
        $this->auditLogs->create('products.create', 'product', (int) $product['id']);

        return $product;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $productId, array $data): array
    {
        $product = $this->products->update($productId, $data);
        $this->auditLogs->create('products.update', 'product', $productId);

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    public function stock(int $productId): array
    {
        return $this->products->stock($productId);
    }
}
