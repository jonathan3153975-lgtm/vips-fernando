<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Application
{
    private static ?self $instance = null;

    public function __construct(
        private readonly array $config,
        private readonly string $routesPath,
    ) {
        self::$instance = $this;
    }

    public function run(): void
    {
        $router = new Router();
        require $this->routesPath;

        try {
            $response = $router->dispatch(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $this->resolvePath(),
            );

            http_response_code($response['status']);

            foreach ($response['headers'] as $name => $value) {
                header($name . ': ' . $value);
            }

            echo $response['body'];
        } catch (Throwable $throwable) {
            $this->renderException($throwable);
        }
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Aplicacao nao inicializada.');
        }

        return self::$instance;
    }

    private function resolvePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return $path === false || $path === null ? '/' : $path;
    }

    private function renderException(Throwable $throwable): void
    {
        $debug = (bool) $this->config('app.debug', false);

        Logger::exception($throwable);

        http_response_code(500);

        $message = $debug
            ? $throwable->getMessage()
            : 'Erro interno do servidor.';

        require dirname(__DIR__, 2) . '/resources/views/errors/500.php';
    }
}
