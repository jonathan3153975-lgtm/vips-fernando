<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use RuntimeException;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly PasswordResetService $passwordReset,
        private readonly Session $session,
        private readonly bool $debug = false,
    ) {
    }

    public function create(): array
    {
        return $this->view('auth/login', [
            'error' => $this->session->pull('auth_error'),
            'notice' => $this->session->pull('auth_notice'),
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

    public function forgotPasswordForm(): array
    {
        return $this->view('auth/forgot-password', [
            'error' => $this->session->pull('auth_error'),
            'notice' => $this->session->pull('auth_notice'),
            'devLink' => $this->session->pull('auth_dev_link'),
            'oldEmail' => $this->session->pull('old_email', ''),
            'csrfField' => Csrf::field($this->session),
        ]);
    }

    public function forgotPassword(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->session->flash('auth_error', 'Informe um email valido.');
            $this->session->flash('old_email', $email);

            return ResponseFactory::redirect('/esqueci-senha');
        }

        $token = $this->passwordReset->request($email);

        // Resposta sempre generica: nao revela se o e-mail esta cadastrado.
        $this->session->flash('auth_notice', 'Se o email estiver cadastrado, enviaremos as instrucoes.');

        // Sem envio de email (P2), o link so aparece em ambiente de debug para
        // permitir o teste manual do fluxo.
        if ($token !== null && $this->debug) {
            $this->session->flash('auth_dev_link', '/redefinir-senha?token=' . urlencode($token));
        }

        return ResponseFactory::redirect('/esqueci-senha');
    }

    public function resetPasswordForm(): array
    {
        return $this->view('auth/reset-password', [
            'token' => (string) ($_GET['token'] ?? ''),
            'error' => $this->session->pull('auth_error'),
            'csrfField' => Csrf::field($this->session),
        ]);
    }

    public function resetPassword(): array
    {
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $back = '/redefinir-senha?token=' . urlencode($token);

        if ($password !== $confirmation) {
            $this->session->flash('auth_error', 'As senhas nao coincidem.');

            return ResponseFactory::redirect($back);
        }

        try {
            $this->passwordReset->confirm($token, $password);
        } catch (RuntimeException $exception) {
            $this->session->flash('auth_error', $exception->getMessage());

            return ResponseFactory::redirect($back);
        }

        $this->session->flash('auth_notice', 'Senha redefinida com sucesso. Entre com a nova senha.');

        return ResponseFactory::redirect('/login');
    }
}
