<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\Migration;

$app = require __DIR__ . '/../bootstrap/app.php';
$pdo = Database::connect($app->config('database'));

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
);

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$batch = ((int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')->fetchColumn()) + 1;

$migrationFiles = glob(__DIR__ . '/migrations/*.php') ?: [];
sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile);

    if (in_array($migrationName, $applied, true)) {
        continue;
    }

    /** @var Migration $migration */
    $migration = require $migrationFile;

    $pdo->beginTransaction();

    try {
        $migration->up($pdo);

        $statement = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)');
        $statement->execute([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);

        $pdo->commit();
        echo '[migrated] ' . $migrationName . PHP_EOL;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        fwrite(STDERR, '[failed] ' . $migrationName . ': ' . $throwable->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo 'Migracoes sincronizadas.' . PHP_EOL;
