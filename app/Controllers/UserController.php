<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\NotFoundException;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\RoleService;
use App\Services\TenantService;
use App\Services\UserService;
use RuntimeException;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly RoleService $roles,
        private readonly TenantService $tenants,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function index(): array
    {
        return $this->view('users/index', $this->layout([
            'title' => 'Usuarios',
            'users' => $this->users->list(),
            'roles' => $this->roles->list(),
        ]));
    }

    public function show(int $userId): array
    {
        try {
            $user = $this->users->find($userId);
        } catch (NotFoundException) {
            return $this->view('errors/404', [
                'message' => 'Usuario nao encontrado.',
            ], 404);
        }

        return $this->view('users/show', $this->layout([
            'title' => $user['name'],
            'user' => $user,
        ]));
    }

    /**
     * Bloqueia um usuario. Os formularios das telas nao podem postar direto na
     * API: rotas /api/ exigem application/json e um form nativo envia
     * x-www-form-urlencoded, que o CsrfMiddleware rejeita com 415.
     */
    public function block(int $userId): array
    {
        return $this->changeStatus($userId, true);
    }

    public function activate(int $userId): array
    {
        return $this->changeStatus($userId, false);
    }

    private function changeStatus(int $userId, bool $blocking): array
    {
        $label = $blocking ? 'bloqueado' : 'ativado';
        $path = '/usuarios/' . $userId;

        try {
            $user = $blocking
                ? $this->users->block($userId)
                : $this->users->activate($userId);
        } catch (NotFoundException) {
            return $this->view('errors/404', ['message' => 'Usuario nao encontrado.'], 404);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect($path);
        }

        $this->session->flash('notice', 'Usuario ' . $user['name'] . ' ' . $label . ' com sucesso.');

        return ResponseFactory::redirect($path);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function layout(array $data): array
    {
        $auth = $this->auth->user();

        return $data + [
            'currentUser' => $auth,
            'session' => $this->session,
            'csrfField' => Csrf::field($this->session),
            'canManageUsers' => $this->auth->hasPermission('users.manage'),
            'canManageSettings' => $this->auth->hasPermission('settings.manage'),
            'formatter' => $this->tenants->formatter(),
        ];
    }
}
