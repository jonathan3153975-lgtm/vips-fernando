<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class NotFoundException extends RuntimeException
{
    public static function entity(string $entity, string $suffix = 'nao encontrado.'): self
    {
        return new self($entity . ' ' . $suffix);
    }
}
