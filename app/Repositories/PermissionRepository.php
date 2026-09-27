<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * `permissions` e catalogo global: nao tem `tenant_id` e nao carrega dado de
 * negocio de nenhum tenant. Por isso fica em `BaseRepository` e nao passa pela
 * camada com escopo.
 */
final class PermissionRepository extends BaseRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->selectAll('SELECT id, name, description FROM permissions ORDER BY name');
    }

    public function idByName(string $name): ?int
    {
        $statement = $this->pdo()->prepare('SELECT id FROM permissions WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);

        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * @return list<array<string, int>>
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $ids)));

        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $statement = $this->pdo()->prepare(
            'SELECT id FROM permissions WHERE id IN (' . implode(', ', $placeholders) . ')'
        );
        $statement->execute($params);

        $found = array_map(static fn ($id): int => (int) $id, $statement->fetchAll(PDO::FETCH_COLUMN) ?: []);

        sort($found);

        return array_map(static fn (int $id): array => ['id' => $id], $found);
    }
}
