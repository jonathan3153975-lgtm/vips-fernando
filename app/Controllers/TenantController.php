<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\TenantService;
use RuntimeException;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenants,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function edit(): array
    {
        return $this->view('tenant/settings', $this->layout([
            'title' => 'Configuracoes',
            'settings' => $this->tenants->settings(),
            'formatter' => $this->tenants->formatter(),
        ]));
    }

    public function update(): array
    {
        $data = [
            'currency' => (string) Request::input('currency', ''),
            'timezone' => (string) Request::input('timezone', ''),
            'language' => (string) Request::input('language', ''),
            'date_format' => (string) Request::input('date_format', ''),
        ];

        try {
            $settings = $this->tenants->updateSettings($data);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect('/configuracoes');
        }

        $this->auth->syncTenantSettings($settings);
        $this->session->flash('notice', 'Configuracoes atualizadas com sucesso.');

        return ResponseFactory::redirect('/configuracoes');
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
            'canManageSettings' => $this->auth->hasPermission('settings.manage'),
            'activeNav' => 'settings',
        ];
    }
}
