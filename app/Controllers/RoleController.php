<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\RoleService;
use RuntimeException;

final class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function index(): array
    {
        return $this->view('roles/index', $this->layout([
            'title' => 'Perfis',
            'roles' => $this->roles->list(),
        ]));
    }

    public function show(int $roleId): array
    {
        try {
            $role = $this->roles->find($roleId);
        } catch (NotFoundException) {
            return $this->view('errors/404', [
                'message' => 'Perfil nao encontrado.',
            ], 404);
        }

        return $this->view('roles/show', $this->layout([
            'title' => $role['name'],
            'role' => $role,
            'permissions' => $role['permissions'],
            'available' => $this->roles->availablePermissions(),
        ]));
    }

    /**
     * Salva as permissoes marcadas no formulario. Mesmo motivo de
     * UserController::block(): o form nativo nao pode postar em /api/.
     */
    public function updatePermissions(int $roleId): array
    {
        $path = '/perfis/' . $roleId;
        $ids = Request::input('permission_ids', []);

        try {
            $role = $this->roles->update($roleId, [
                'permission_ids' => is_array($ids) ? $ids : [],
            ]);
        } catch (NotFoundException) {
            return $this->view('errors/404', ['message' => 'Perfil nao encontrado.'], 404);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect($path);
        }

        $this->session->flash(
            'notice',
            'Permissoes do perfil ' . $role['name'] . ' atualizadas (' . count($role['permissions']) . ').',
        );

        return ResponseFactory::redirect($path);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function layout(array $data): array
    {
        return $data + [
            'currentUser' => $this->auth->user(),
            'session' => $this->session,
            'csrfField' => Csrf::field($this->session),
            'canManageUsers' => $this->auth->hasPermission('users.manage'),
            'canManageSettings' => $this->auth->hasPermission('settings.manage'),
        ];
    }
}
