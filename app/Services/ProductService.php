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

    public function list(int $tenantId): array
    {
        return $this->products->allByTenant($tenantId);
    }

    public function create(int $tenantId, int $userId, array $data): array
    {
        foreach (['sku', 'name'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $product = $this->products->create($tenantId, $data);
        $this->auditLogs->create($tenantId, $userId, 'products.create', 'product', (int) $product['id']);

        return $product;
    }

    public function update(int $tenantId, int $userId, int $productId, array $data): array
    {
        if ($this->products->findForTenant($tenantId, $productId) === null) {
            throw new RuntimeException('Produto nao encontrado.');
        }

        $product = $this->products->update($tenantId, $productId, $data);
        $this->auditLogs->create($tenantId, $userId, 'products.update', 'product', $productId);

        return $product;
    }

    public function stock(int $tenantId, int $productId): array
    {
        $stock = $this->products->stockForTenant($tenantId, $productId);

        if ($stock === null) {
            throw new RuntimeException('Produto nao encontrado.');
        }

        return $stock;
    }
}
