<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\MigrationFailedException;
use App\Database\Migrator;

$app = require __DIR__ . '/../bootstrap/app.php';

if (($app->config('app.env') ?? 'production') === 'production' && !in_array('--force', $argv, true)) {
    fwrite(STDERR, 'Ambiente de producao detectado.' . PHP_EOL);
    fwrite(STDERR, 'migrate:fresh destroi todas as tabelas e todos os dados. Use --force para confirmar.' . PHP_EOL);
    exit(1);
}

$migrator = new Migrator(Database::connect($app->config('database')), __DIR__ . '/migrations');

try {
    exit($migrator->fresh());
} catch (MigrationFailedException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    fwrite(STDERR, '  MySQL nao suporta DDL transacional: cada CREATE/DROP TABLE e aplicado imediatamente.' . PHP_EOL);
    fwrite(STDERR, '  Verifique o estado com SHOW TABLES; e ajuste antes de reexecutar.' . PHP_EOL);
    exit(1);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
