<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    /**
    * @var array<string, list<array{path: string, regex: string, parameterNames: list<string>, handler: callable, middleware: list<callable>}>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->map('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->map('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->map('PUT', $path, $handler, $middleware);
    }

    public function dispatch(string $method, string $path): array
    {
        $method = strtoupper($method);
        $normalizedPath = rtrim($path, '/') ?: '/';

        $route = $this->matchRoute($method, $normalizedPath);

        if ($route === null) {
            http_response_code(404);
            ob_start();
            require dirname(__DIR__, 2) . '/resources/views/errors/404.php';

            return [
                'status' => 404,
                'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
                'body' => (string) ob_get_clean(),
            ];
        }

        $response = $this->runPipeline($route['handler'], $route['middleware'], $route['parameters']);

        if (!is_array($response) || !isset($response['status'], $response['headers'], $response['body'])) {
            throw new RuntimeException('Handler retornou resposta invalida.');
        }

        return $response;
    }

    private function map(string $method, string $path, callable $handler, array $middleware): void
    {
        $normalizedPath = rtrim($path, '/') ?: '/';
        [$regex, $parameterNames] = $this->compilePath($normalizedPath);

        $this->routes[strtoupper($method)][] = [
            'path' => $normalizedPath,
            'regex' => $regex,
            'parameterNames' => $parameterNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function runPipeline(callable $handler, array $middleware, array $parameters): array
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            static fn (callable $next, callable $current): callable => static fn (): array => $current($next),
            static fn (): array => $handler(...$parameters),
        );

        return $pipeline();
    }

    private function matchRoute(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $parameters = [];

            foreach ($route['parameterNames'] as $parameterName) {
                $parameters[] = isset($matches[$parameterName]) ? $this->castParameter($matches[$parameterName]) : null;
            }

            return [
                'handler' => $route['handler'],
                'middleware' => $route['middleware'],
                'parameters' => $parameters,
            ];
        }

        return null;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function compilePath(string $path): array
    {
        $parameterNames = [];
        $segments = explode('/', trim($path, '/'));
        $compiledSegments = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
                $parameterNames[] = $matches[1];
                $compiledSegments[] = '(?<' . $matches[1] . '>[^/]+)';
                continue;
            }

            $compiledSegments[] = preg_quote($segment, '#');
        }

        if ($compiledSegments === []) {
            return ['#^/$#', $parameterNames];
        }

        return ['#^/' . implode('/', $compiledSegments) . '$#', $parameterNames];
    }

    private function castParameter(string $parameter): int|string
    {
        return ctype_digit($parameter) ? (int) $parameter : $parameter;
    }
}
