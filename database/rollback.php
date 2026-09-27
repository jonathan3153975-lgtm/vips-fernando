<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\MigrationFailedException;
use App\Database\Migrator;

$batch = null;

foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^\d+$/', $argument) === 1) {
        $batch = (int) $argument;
    }
}

$app = require __DIR__ . '/../bootstrap/app.php';
$migrator = new Migrator(Database::connect($app->config('database')), __DIR__ . '/migrations');

try {
    exit($migrator->rollback($batch));
} catch (MigrationFailedException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    fwrite(STDERR, '  MySQL nao suporta DDL transacional: cada CREATE/DROP TABLE e aplicado imediatamente.' . PHP_EOL);
    fwrite(STDERR, '  Verifique o estado com SHOW TABLES; e ajuste antes de reexecutar.' . PHP_EOL);
    exit(1);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
