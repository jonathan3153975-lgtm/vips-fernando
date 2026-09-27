<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\ResponseFactory;
use App\Services\AuthService;

final class GuestMiddleware
{
    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    public function handle(callable $next): array
    {
        if ($this->auth->check()) {
            if (Request::isApi()) {
                return ResponseFactory::json(['message' => 'Sessao ja autenticada.'], 409);
            }

            return ResponseFactory::redirect('/dashboard');
        }

        return $next();
    }
}
