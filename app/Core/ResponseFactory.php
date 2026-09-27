<?php

declare(strict_types=1);

namespace App\Core;

final class ResponseFactory
{
    public static function redirect(string $location, int $status = 302): array
    {
        return [
            'status' => $status,
            'headers' => ['Location' => $location],
            'body' => '',
        ];
    }

    public static function json(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
    }
}