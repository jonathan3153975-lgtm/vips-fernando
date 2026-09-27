<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ProductService;
use RuntimeException;

final class ProductApiController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly ProductService $products,
    ) {
    }

    public function index(): array
    {
        $tenantId = (int) ($this->auth->user()['tenant_id'] ?? 0);
        $items = $this->products->list($tenantId);

        return $this->json([
            'data' => $items,
            'meta' => [
                'page' => 1,
                'per_page' => 15,
                'total' => count($items),
            ],
        ]);
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->products->create(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            Request::all(),
        ), 201);
    }

    public function update(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->update(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            $productId,
            Request::all(),
        ));
    }

    public function stock(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->stock(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            $productId,
        ));
    }

    private function guarded(callable $callback, int $status = 200): array
    {
        try {
            return $this->json(['data' => $callback()], $status);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $httpStatus = $message === 'Produto nao encontrado.' ? 404 : 400;

            return $this->json(['message' => $message], $httpStatus);
        }
    }
}
