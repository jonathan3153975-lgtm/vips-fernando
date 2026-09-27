<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class UnscopedQueryException extends RuntimeException
{
    public static function for(string $repository, string $sql): self
    {
        return new self(sprintf(
            '%s executou uma query sem escopo de tenant. Toda query deste repositorio precisa do predicado ":tenant_id", '
            . 'que e preenchido automaticamente. Query: %s',
            $repository,
            preg_replace('/\s+/', ' ', trim($sql)) ?? $sql,
        ));
    }
}
