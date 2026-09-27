<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function create(): array
    {
        return $this->view('auth/login', [
            'error' => $this->session->pull('auth_error'),
            'oldEmail' => $this->session->pull('old_email', ''),
            'csrfField' => Csrf::field($this->session),
        ]);
    }

    public function store(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->session->flash('auth_error', 'Informe email e senha.');
            $this->session->flash('old_email', $email);

            return ResponseFactory::redirect('/login');
        }

        if (!$this->auth->attempt($email, $password)) {
            $this->session->flash('auth_error', 'Credenciais invalidas ou usuario inativo.');
            $this->session->flash('old_email', $email);

            return ResponseFactory::redirect('/login');
        }

        return ResponseFactory::redirect('/dashboard');
    }

    public function destroy(): array
    {
        $this->auth->logout();

        return ResponseFactory::redirect('/login');
    }
}
