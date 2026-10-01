<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\CategoryRepository;
use RuntimeException;

final class CategoryService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->categories->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $categoryId): array
    {
        return $this->categories->findOrFail($categoryId);
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

        if ($this->categories->nameExists($name)) {
            throw new RuntimeException('Ja existe uma categoria com este nome.');
        }

        $this->validateParent($data['parent_id'] ?? null, null);
        $this->validateStatus($data);

        $category = $this->categories->create($data + ['name' => $name]);
        $this->auditLogs->create('categories.create', 'category', (int) $category['id']);

        return $category;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $categoryId, array $data): array
    {
        $this->categories->findOrFail($categoryId);

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw new RuntimeException('Campo obrigatorio ausente: name');
            }

            if ($this->categories->nameExists($name, $categoryId)) {
                throw new RuntimeException('Ja existe uma categoria com este nome.');
            }

            $data['name'] = $name;
        }

        $this->validateStatus($data);

        if (array_key_exists('parent_id', $data)) {
            $this->validateParent($data['parent_id'], $categoryId);
        }

        $category = $this->categories->update($categoryId, $data);
        $this->auditLogs->create('categories.update', 'category', $categoryId);

        return $category;
    }

    /**
     * Valida o status e o grava normalizado, para que create e update sigam a
     * mesma regra: um "active" aceito aqui nao pode acabar gravado em minuscula.
     *
     * @param array<string, mixed> $data
     */
    private function validateStatus(array &$data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }

        $status = strtoupper((string) $data['status']);

        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status invalido. Use ACTIVE ou INACTIVE.');
        }

        $data['status'] = $status;
    }

    /**
     * O pai precisa existir no mesmo tenant e nao pode gerar ciclo (a categoria
     * nao pode ser pai de si mesma nem ficar abaixo de si mesma).
     */
    private function validateParent(mixed $parentId, ?int $categoryId): void
    {
        if ($parentId === null || $parentId === '') {
            return;
        }

        $parentId = (int) $parentId;

        if ($categoryId !== null && $parentId === $categoryId) {
            throw new RuntimeException('Uma categoria nao pode ser pai de si mesma.');
        }

        if (!$this->categories->exists($parentId)) {
            throw new RuntimeException('Categoria pai nao encontrada.');
        }

        if ($categoryId !== null && $this->isDescendant($parentId, $categoryId)) {
            throw new RuntimeException('Nao e possivel mover uma categoria para baixo dela mesma.');
        }
    }

    /**
     * Verifica se $ancestorId esta na arvore acima de $categoryId.
     */
    private function isDescendant(int $ancestorId, int $categoryId): bool
    {
        $current = $this->categories->find($ancestorId);
        $visited = [];

        while ($current !== null && $current['parent_id'] !== null) {
            $parentId = (int) $current['parent_id'];

            if (isset($visited[$parentId])) {
                return true;
            }

            if ($parentId === $categoryId) {
                return true;
            }

            $visited[$parentId] = true;
            $current = $this->categories->find($parentId);
        }

        return false;
    }
}
