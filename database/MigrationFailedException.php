<?php

declare(strict_types=1);

namespace App\Database;

use RuntimeException;
use Throwable;

final class MigrationFailedException extends RuntimeException
{
    public static function for(string $migration, string $direction, Throwable $previous): self
    {
        return new self(
            sprintf('[failed] %s (%s): %s', $migration, $direction, $previous->getMessage()),
            0,
            $previous
        );
    }
}
