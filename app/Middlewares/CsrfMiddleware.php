<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;

/**
 * Protecao contra CSRF applied nas rotas web.
 *
 * Rotas sob /api/ nao exigem token porque a API aceita apenas payload
 * application/json, o que impede o navegador de montar o pedido cross-origin
 * sem passar por preflight CORS. O requisito e verificado aqui.
 */
final class CsrfMiddleware extends Controller
{
    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function handle(callable $next): array
    {
        if (!Request::isUnsafeMethod()) {
            return $next();
        }

        if (Request::isApi()) {
            return $this->enforceJsonOnly($next);
        }

        if (Csrf::validate($this->session, Csrf::submittedToken())) {
            return $next();
        }

        Logger::warning('Requisicao web bloqueada por token CSRF ausente ou invalido.', [
            'method' => Request::method(),
            'path' => (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        $this->session->flash('auth_error', 'Sessao expirada ou requisicao invalida. Tente novamente.');
        $this->session->flash('old_email', (string) ($_POST['email'] ?? ''));

        return $this->redirectBack();
    }

    private function enforceJsonOnly(callable $next): array
    {
        if (Request::isJson()) {
            return $next();
        }

        Logger::warning('Requisicao de API bloqueada por content-type nao suportado.', [
            'method' => Request::method(),
            'path' => (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'),
            'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? ''),
        ]);

        return $this->json([
            'message' => 'A API aceita apenas payloads application/json.',
        ], 415);
    }

    private function redirectBack(): array
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = is_string($referer) && $referer !== '' ? (parse_url($referer, PHP_URL_PATH) ?: '/') : '/';

        if (str_starts_with((string) $path, '/api/')) {
            return $this->json(['message' => 'Token CSRF invalido.'], 419);
        }

        return [
            'status' => 302,
            'headers' => ['Location' => (string) $path],
            'body' => '',
        ];
    }
}
