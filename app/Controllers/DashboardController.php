<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    public function index(): array
    {
        return $this->view('dashboard/index', [
            'user' => $this->auth->user(),
        ]);
    }
}
