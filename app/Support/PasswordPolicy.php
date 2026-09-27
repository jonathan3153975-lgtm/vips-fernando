<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Regra unica de forca de senha, compartilhada pelo cadastro/troca de usuario
 * (UserService) e pela redefinicao (PasswordResetService), para nao divergirem.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public static function assertAcceptable(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new RuntimeException(
                'A senha deve ter ao menos ' . self::MIN_LENGTH . ' caracteres.'
            );
        }
    }
}
