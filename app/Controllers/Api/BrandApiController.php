<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\BrandService;
use RuntimeException;

final class BrandApiController extends Controller
{
    public function __construct(
        private readonly BrandService $brands,
    ) {
    }

    public function index(): array
    {
        return $this->guarded(fn (): array => $this->brands->list());
    }

    public function show(int $brandId): array
    {
        return $this->guarded(fn (): array => $this->brands->find($brandId));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->brands->create(Request::all()), 201);
    }

    public function update(int $brandId): array
    {
        return $this->guarded(fn (): array => $this->brands->update($brandId, Request::all()));
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
