<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): array
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $template . '.php';

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
            'body' => (string) ob_get_clean(),
        ];
    }

    protected function json(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
    }

    protected function noContent(): array
    {
        return [
            'status' => 204,
            'headers' => [],
            'body' => '',
        ];
    }
}
