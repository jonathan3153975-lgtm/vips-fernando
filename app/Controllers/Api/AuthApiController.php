<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use RuntimeException;

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

    public function passwordResetConfirm(): array
    {
        $token = trim((string) Request::input('token', ''));
        $password = (string) Request::input('password', '');

        if ($token === '' || $password === '') {
            return $this->json(['message' => 'Token e senha sao obrigatorios.'], 400);
        }

        try {
            $this->passwordReset->confirm($token, $password);
        } catch (RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }

        return $this->json(['message' => 'Senha redefinida com sucesso.']);
    }
}
