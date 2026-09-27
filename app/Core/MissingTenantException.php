<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class MissingTenantException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Nao ha tenant ativo para executar esta operacao.');
    }
}
