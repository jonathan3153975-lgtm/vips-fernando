<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Session;
use App\Core\TenantContext;
use App\Core\UnscopedQueryException;
use PDO;

abstract class TenantScopedRepository extends BaseRepository
{
    private const TENANT_PLACEHOLDER = ':tenant_id';

    private ?TenantContext $tenants = null;

    protected function tenants(): TenantContext
    {
        return $this->tenants ??= new TenantContext(new Session());
    }

    protected function tenantId(): int
    {
        return $this->tenants()->id();
    }

    protected function userId(): int
    {
        return $this->tenants()->userId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function selectAll(string $sql, array $bindings = []): array
    {
        $bindings = $this->bindTenant($sql, $bindings);
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function selectOne(string $sql, array $bindings = []): ?array
    {
        $bindings = $this->bindTenant($sql, $bindings);
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function selectValue(string $sql, array $bindings = []): mixed
    {
        $bindings = $this->bindTenant($sql, $bindings);
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @return list<string>
     */
    protected function selectColumn(string $sql, array $bindings = []): array
    {
        $bindings = $this->bindTenant($sql, $bindings);
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    protected function run(string $sql, array $bindings = []): int
    {
        $bindings = $this->bindTenant($sql, $bindings);
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    protected function insert(string $sql, array $bindings = []): int
    {
        $this->run($sql, $bindings);

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * O valor do tenant e sempre injetado aqui: o repository nao aceita um
     * tenantId vindo de quem chama, e recusa qualquer query sem o predicado.
     *
     * @param array<string, mixed> $bindings
     *
     * @return array<string, mixed>
     */
    private function bindTenant(string $sql, array $bindings): array
    {
        if (!str_contains($sql, self::TENANT_PLACEHOLDER)) {
            throw UnscopedQueryException::for(static::class, $sql);
        }

        $bindings['tenant_id'] = $this->tenantId();

        return $bindings;
    }
}
