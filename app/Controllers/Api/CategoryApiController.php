<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\CategoryService;
use RuntimeException;

final class CategoryApiController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {
    }

    public function index(): array
    {
        return $this->guarded(fn (): array => $this->categories->list());
    }

    public function show(int $categoryId): array
    {
        return $this->guarded(fn (): array => $this->categories->find($categoryId));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->categories->create(Request::all()), 201);
    }

    public function update(int $categoryId): array
    {
        return $this->guarded(fn (): array => $this->categories->update($categoryId, Request::all()));
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
