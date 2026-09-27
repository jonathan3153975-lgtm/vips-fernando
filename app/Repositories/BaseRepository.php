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
}
