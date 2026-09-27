<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const LAST_ACTIVITY_KEY = '_last_activity';

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function invalidate(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public function token(): string
    {
        if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token']) || $_SESSION['_csrf_token'] === '') {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public function isIdleExpired(int $idleTimeoutMinutes): bool
    {
        if ($idleTimeoutMinutes <= 0 || !isset($_SESSION['auth'])) {
            return false;
        }

        $lastActivity = $_SESSION[self::LAST_ACTIVITY_KEY] ?? null;

        if (!is_int($lastActivity)) {
            return false;
        }

        return (time() - $lastActivity) > ($idleTimeoutMinutes * 60);
    }

    public function touch(): void
    {
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
    }
}
