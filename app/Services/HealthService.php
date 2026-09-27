<?php

declare(strict_types=1);

namespace App\Services;

final class HealthService
{
    public function status(): array
    {
        return [
            'status' => 'ok',
            'service' => 'importcontrol',
            'timestamp' => date(DATE_ATOM),
        ];
    }
}
