<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;

final class PermissionMiddleware extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly string $permission,
    ) {
    }

    public function handle(callable $next): array
    {
        if (!$this->auth->hasPermission($this->permission)) {
            if (Request::isApi()) {
                return $this->json([
                    'message' => 'Voce nao possui permissao para acessar este recurso.',
                ], 403);
            }

            return $this->view('errors/403', [
                'message' => 'Voce nao possui permissao para acessar este recurso.',
            ], 403);
        }

        return $next();
    }
}
