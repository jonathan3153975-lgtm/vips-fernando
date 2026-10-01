<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class BrandRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT id, tenant_id, name, created_at, updated_at
             FROM brands
             WHERE tenant_id = :tenant_id
             ORDER BY name ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $brandId): ?array
    {
        return $this->selectOne(
            'SELECT id, tenant_id, name, created_at, updated_at
             FROM brands
             WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $brandId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $brandId): array
    {
        $brand = $this->find($brandId);

        if ($brand === null) {
            throw NotFoundException::entity('Marca', 'nao encontrada.');
        }

        return $brand;
    }

    public function exists(int $brandId): bool
    {
        return $this->selectValue(
            'SELECT id FROM brands WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $brandId],
        ) !== null;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM brands WHERE tenant_id = :tenant_id AND name = :name';
        $params = ['name' => $name];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        return $this->selectValue($sql . ' LIMIT 1', $params) !== null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $brandId = $this->insert(
            'INSERT INTO brands (tenant_id, name, created_at, updated_at)
             VALUES (:tenant_id, :name, :created_at, :updated_at)',
            [
                'name' => $data['name'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->find($brandId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $brandId, array $data): array
    {
        $current = $this->findOrFail($brandId);

        $this->run(
            'UPDATE brands SET
                name = :name,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'name' => $data['name'] ?? $current['name'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $brandId,
            ],
        );

        return $this->find($brandId) ?? [];
    }
}
