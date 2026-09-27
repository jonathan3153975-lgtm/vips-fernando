<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected function pdo(): PDO
    {
        $app = Application::getInstance();

        return Database::connect($app->config('database'));
    }

    /**
     * @param array<string, mixed> $bindings
     *
     * @return list<array<string, mixed>>
     */
    protected function selectAll(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll() ?: [];
    }
}
