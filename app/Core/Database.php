<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    /**
     * @var array<string, PDO>
     */
    private static array $connections = [];

    public static function connect(array $config): PDO
    {
        $connection = $config['connections'][$config['default']];

        $cacheKey = md5(json_encode($connection, JSON_THROW_ON_ERROR));

        if (isset(self::$connections[$cacheKey])) {
            return self::$connections[$cacheKey];
        }

        if (($connection['driver'] ?? 'mysql') === 'sqlite') {
            $pdo = new PDO('sqlite:' . $connection['database']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');

            return self::$connections[$cacheKey] = $pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $connection['host'],
            $connection['port'],
            $connection['database'],
            $connection['charset'],
        );

        return self::$connections[$cacheKey] = new PDO(
            $dsn,
            $connection['username'],
            $connection['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public static function disconnectAll(): void
    {
        self::$connections = [];
    }
}
