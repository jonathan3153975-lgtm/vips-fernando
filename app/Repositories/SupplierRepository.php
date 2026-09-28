<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

final class SupplierRepository extends TenantScopedRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll(
            'SELECT id, name, country, city, contact_name, email, phone, notes, status, created_at, updated_at
             FROM suppliers
             WHERE tenant_id = :tenant_id
             ORDER BY name ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $supplierId): ?array
    {
        return $this->selectOne(
            'SELECT id, name, country, city, contact_name, email, phone, notes, status, created_at, updated_at
             FROM suppliers
             WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $supplierId],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $supplierId): array
    {
        $supplier = $this->find($supplierId);

        if ($supplier === null) {
            throw NotFoundException::entity('Fornecedor', 'nao encontrado.');
        }

        return $supplier;
    }

    public function exists(int $supplierId): bool
    {
        return $this->selectValue(
            'SELECT id FROM suppliers WHERE tenant_id = :tenant_id AND id = :id LIMIT 1',
            ['id' => $supplierId],
        ) !== null;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM suppliers WHERE tenant_id = :tenant_id AND name = :name';
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

        $supplierId = $this->insert(
            'INSERT INTO suppliers (
                tenant_id, name, country, city, contact_name, email, phone, notes, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :name, :country, :city, :contact_name, :email, :phone, :notes, :status, :created_at, :updated_at
            )',
            [
                'name' => $data['name'],
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'ACTIVE',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->find($supplierId) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $supplierId, array $data): array
    {
        $current = $this->findOrFail($supplierId);

        $this->run(
            'UPDATE suppliers SET
                name = :name,
                country = :country,
                city = :city,
                contact_name = :contact_name,
                email = :email,
                phone = :phone,
                notes = :notes,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'name' => $data['name'] ?? $current['name'],
                'country' => $data['country'] ?? $current['country'],
                'city' => $data['city'] ?? $current['city'],
                'contact_name' => $data['contact_name'] ?? $current['contact_name'],
                'email' => $data['email'] ?? $current['email'],
                'phone' => $data['phone'] ?? $current['phone'],
                'notes' => $data['notes'] ?? $current['notes'],
                'status' => $data['status'] ?? $current['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $supplierId,
            ],
        );

        return $this->find($supplierId) ?? [];
    }
}
