<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function all(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $body = self::rawBody();

        if (str_contains($contentType, 'application/json') || $body !== '') {
            $decoded = json_decode($body, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $data = self::all();

        return $data[$key] ?? $default;
    }

    public static function isApi(): bool
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/api/');
    }

    private static function rawBody(): string
    {
        return (string) ($_SERVER['__BODY__'] ?? file_get_contents('php://input'));
    }
}
