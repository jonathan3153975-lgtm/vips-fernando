<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;
use Throwable;

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
    }

    public function up(): int
    {
        $this->ensureRepository();

        $applied = $this->appliedMigrations();
        $batch = $this->nextBatch();
        $pending = 0;

        foreach ($this->migrationFiles() as $name) {
            if (in_array($name, $applied, true)) {
                continue;
            }

            $this->run($name, 'up');
            $this->record($name, $batch);
            $pending++;
        }

        if ($pending === 0) {
            echo 'Nada a migrar. Banco sincronizado.' . PHP_EOL;
        }

        return 0;
    }

    public function rollback(?int $batch = null): int
    {
        $this->ensureRepository();

        $target = $batch ?? $this->lastBatch();

        if ($target === null) {
            echo 'Nenhuma migration aplicada para reverter.' . PHP_EOL;

            return 0;
        }

        $names = $this->appliedMigrations($target);

        if ($names === []) {
            echo 'Batch ' . $target . ' nao possui migrations aplicadas.' . PHP_EOL;

            return 0;
        }

        echo 'Revertendo batch ' . $target . ' (' . count($names) . ' migration(s)).' . PHP_EOL;

        foreach (array_reverse($names) as $name) {
            $this->run($name, 'down');
            $this->forget($name);
        }

        echo 'Batch ' . $target . ' revertido.' . PHP_EOL;

        return 0;
    }

    public function reset(): int
    {
        $this->ensureRepository();

        $names = $this->appliedMigrations();

        if ($names === []) {
            echo 'Nenhuma migration aplicada.' . PHP_EOL;

            return 0;
        }

        echo 'Revertendo ' . count($names) . ' migration(s) de todos os batches.' . PHP_EOL;

        foreach (array_reverse($names) as $name) {
            $this->run($name, 'down');
            $this->forget($name);
        }

        echo 'Schema revertido ao estado vazio.' . PHP_EOL;

        return 0;
    }

    public function fresh(): int
    {
        $exitCode = $this->reset();

        if ($exitCode !== 0) {
            return $exitCode;
        }

        echo PHP_EOL;

        return $this->up();
    }

    private function ensureRepository(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);

        return array_map(static fn (string $file): string => basename($file), $files);
    }

    /**
     * @return list<string>
     */
    private function appliedMigrations(?int $batch = null): array
    {
        if ($batch === null) {
            $statement = $this->pdo->query('SELECT migration FROM migrations ORDER BY id ASC');

            return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $statement = $this->pdo->prepare('SELECT migration FROM migrations WHERE batch = :batch ORDER BY id ASC');
        $statement->execute(['batch' => $batch]);

        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function lastBatch(): ?int
    {
        $batch = $this->pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();

        return $batch === false || $batch === null ? null : (int) $batch;
    }

    private function nextBatch(): int
    {
        $last = $this->lastBatch();

        return $last === null ? 1 : $last + 1;
    }

    private function record(string $name, int $batch): void
    {
        $statement = $this->pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)');
        $statement->execute(['migration' => $name, 'batch' => $batch]);
    }

    private function forget(string $name): void
    {
        $statement = $this->pdo->prepare('DELETE FROM migrations WHERE migration = :migration');
        $statement->execute(['migration' => $name]);
    }

    private function run(string $name, string $direction): void
    {
        $file = $this->migrationsPath . '/' . $name;

        if (!is_file($file)) {
            throw new RuntimeException('Arquivo de migration ausente: ' . $name);
        }

        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException($name . ' nao retorna uma instancia de App\Database\Migration.');
        }

        try {
            if ($direction === 'up') {
                $migration->up($this->pdo);
            } else {
                $migration->down($this->pdo);
            }
        } catch (Throwable $throwable) {
            throw MigrationFailedException::for($name, $direction, $throwable);
        }

        echo '[' . ($direction === 'up' ? 'migrated' : 'reverted') . '] ' . $name . PHP_EOL;
    }
}
