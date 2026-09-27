<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\PasswordResetService;

final class AuthApiController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly PasswordResetService $passwordReset,
    ) {
    }

    public function login(): array
    {
        $email = trim((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');

        if ($email === '' || $password === '') {
            return $this->json(['message' => 'Email e senha sao obrigatorios.'], 400);
        }

        if (!$this->auth->attempt($email, $password)) {
            return $this->json(['message' => 'Credenciais invalidas ou usuario inativo.'], 401);
        }

        return $this->json(['data' => $this->auth->user() ?? []]);
    }

    public function logout(): array
    {
        $this->auth->logout();

        return $this->noContent();
    }

    public function passwordReset(): array
    {
        $email = trim((string) Request::input('email', ''));

        if ($email === '') {
            return $this->json(['message' => 'Email e obrigatorio.'], 400);
        }

        $this->passwordReset->request($email);

        return $this->json(['message' => 'Solicitacao recebida.'], 202);
    }
}
