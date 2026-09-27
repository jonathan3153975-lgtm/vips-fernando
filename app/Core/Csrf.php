<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public const FIELD_NAME = '_token';

    public const HEADER_NAME = 'X-CSRF-TOKEN';

    public static function token(Session $session): string
    {
        return $session->token();
    }

    public static function field(Session $session): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::FIELD_NAME,
            htmlspecialchars(self::token($session), ENT_QUOTES, 'UTF-8'),
        );
    }

    public static function validate(Session $session, mixed $submitted): bool
    {
        if (!is_string($submitted) || $submitted === '') {
            return false;
        }

        return hash_equals(self::token($session), $submitted);
    }

    public static function submittedToken(): mixed
    {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        if (array_key_exists(self::FIELD_NAME, $_POST)) {
            return $_POST[self::FIELD_NAME];
        }

        $body = Request::rawBody();

        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !array_key_exists(self::FIELD_NAME, $decoded)) {
            return null;
        }

        return $decoded[self::FIELD_NAME];
    }
}
