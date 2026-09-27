<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function index(): array
    {
        return $this->view('dashboard/index', [
            'user' => $this->auth->user(),
            'csrfField' => Csrf::field($this->session),
        ]);
    }
}
