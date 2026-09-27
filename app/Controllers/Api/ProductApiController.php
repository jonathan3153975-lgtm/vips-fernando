<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\ProductService;
use RuntimeException;

final class ProductApiController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
    ) {
    }

    public function index(): array
    {
        $items = $this->products->list();

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
        return $this->guarded(fn (): array => $this->products->create(Request::all()), 201);
    }

    public function update(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->update($productId, Request::all()));
    }

    public function stock(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->stock($productId));
    }

    private function guarded(callable $callback, int $status = 200): array
    {
        try {
            return $this->json(['data' => $callback()], $status);
        } catch (NotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }
}
