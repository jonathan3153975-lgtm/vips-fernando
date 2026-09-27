<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\ResponseFactory;
use App\Services\AuthService;

final class AuthMiddleware
{
    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    public function handle(callable $next): array
    {
        if (!$this->auth->check()) {
            if (Request::isApi()) {
                return ResponseFactory::json(['message' => 'Nao autenticado.'], 401);
            }

            return ResponseFactory::redirect('/login');
        }

        return $next();
    }
}
