<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\HealthService;

final class HealthController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService,
    ) {
    }

    public function show(): array
    {
        return $this->json($this->healthService->status());
    }
}
