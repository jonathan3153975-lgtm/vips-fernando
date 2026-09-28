<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\SupplierRepository;
use RuntimeException;

final class SupplierService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function __construct(
        private readonly SupplierRepository $suppliers,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->suppliers->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $supplierId): array
    {
        return $this->suppliers->findOrFail($supplierId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Campo obrigatorio ausente: name');
        }

        if ($this->suppliers->nameExists($name)) {
            throw new RuntimeException('Ja existe um fornecedor com este nome.');
        }

        $supplier = $this->suppliers->create($data + ['name' => $name]);

        $this->auditLogs->create('suppliers.create', 'supplier', (int) $supplier['id']);

        return $supplier;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $supplierId, array $data): array
    {
        $this->suppliers->findOrFail($supplierId);

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw new RuntimeException('Campo obrigatorio ausente: name');
            }

            if ($this->suppliers->nameExists($name, $supplierId)) {
                throw new RuntimeException('Ja existe um fornecedor com este nome.');
            }

            $data['name'] = $name;
        }

        if (array_key_exists('status', $data)) {
            $status = strtoupper((string) $data['status']);

            if (!in_array($status, self::STATUSES, true)) {
                throw new RuntimeException('Status invalido. Use ACTIVE ou INACTIVE.');
            }

            $data['status'] = $status;
        }

        $supplier = $this->suppliers->update($supplierId, $data);

        $this->auditLogs->create('suppliers.update', 'supplier', $supplierId);

        return $supplier;
    }
}
