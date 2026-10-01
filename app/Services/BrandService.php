<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\BrandRepository;
use RuntimeException;

final class BrandService
{
    public function __construct(
        private readonly BrandRepository $brands,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->brands->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $brandId): array
    {
        return $this->brands->findOrFail($brandId);
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

        if ($this->brands->nameExists($name)) {
            throw new RuntimeException('Ja existe uma marca com este nome.');
        }

        $brand = $this->brands->create($data + ['name' => $name]);
        $this->auditLogs->create('brands.create', 'brand', (int) $brand['id']);

        return $brand;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $brandId, array $data): array
    {
        $this->brands->findOrFail($brandId);

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw new RuntimeException('Campo obrigatorio ausente: name');
            }

            if ($this->brands->nameExists($name, $brandId)) {
                throw new RuntimeException('Ja existe uma marca com este nome.');
            }

            $data['name'] = $name;
        }

        $brand = $this->brands->update($brandId, $data);
        $this->auditLogs->create('brands.update', 'brand', $brandId);

        return $brand;
    }
}
