<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function all(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(self::rawBody(), true);

            return is_array($decoded) ? $decoded : [];
        }

        // application/x-www-form-urlencoded e multipart: o PHP ja populou
        // $_POST. Nao se pode tentar json_decode do corpo nesses casos: um form
        // nativo sempre tem corpo, o decode falharia e o POST inteiro seria
        // descartado como [].
        if ($_POST !== []) {
            return $_POST;
        }

        // Sem campos parseados, ainda pode ser JSON sem content-type correto.
        $decoded = json_decode(self::rawBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $data = self::all();

        return $data[$key] ?? $default;
    }

    /**
     * Parametro de query string (GET). Separado de input() porque o corpo e a
     * query sao fontes diferentes: filtrar uma listagem nao deve ler o payload.
     */
    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function isApi(): bool
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/api/');
    }

    public static function isJson(): bool
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        return str_contains($contentType, 'application/json');
    }

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isUnsafeMethod(): bool
    {
        return in_array(self::method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public static function rawBody(): string
    {
        return (string) ($_SERVER['__BODY__'] ?? file_get_contents('php://input'));
    }
}
