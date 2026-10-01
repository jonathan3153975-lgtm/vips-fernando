<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class CategoryRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT id, tenant_id, parent_id, name, description, status, created_at, updated_at
             FROM categories
             WHERE tenant_id = :tenant_id
             ORDER BY name ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $categoryId): ?array
    {
        return $this->selectOne(
            'SELECT id, tenant_id, parent_id, name, description, status, created_at, updated_at
             FROM categories
             WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $categoryId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $categoryId): array
    {
        $category = $this->find($categoryId);

        if ($category === null) {
            throw NotFoundException::entity('Categoria', 'nao encontrada.');
        }

        return $category;
    }

    public function exists(int $categoryId): bool
    {
        return $this->selectValue(
            'SELECT id FROM categories WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $categoryId],
        ) !== null;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM categories WHERE tenant_id = :tenant_id AND name = :name';
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

        $categoryId = $this->insert(
            'INSERT INTO categories (tenant_id, parent_id, name, description, status, created_at, updated_at)
             VALUES (:tenant_id, :parent_id, :name, :description, :status, :created_at, :updated_at)',
            [
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'ACTIVE',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->find($categoryId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $categoryId, array $data): array
    {
        $current = $this->findOrFail($categoryId);

        $this->run(
            'UPDATE categories SET
                parent_id = :parent_id,
                name = :name,
                description = :description,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $current['parent_id'],
                'name' => $data['name'] ?? $current['name'],
                'description' => $data['description'] ?? $current['description'],
                'status' => $data['status'] ?? $current['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $categoryId,
            ],
        );

        return $this->find($categoryId) ?? [];
    }
}
