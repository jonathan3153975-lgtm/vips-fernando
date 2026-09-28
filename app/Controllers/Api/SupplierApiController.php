<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\SupplierService;
use RuntimeException;

final class SupplierApiController extends Controller
{
    public function __construct(
        private readonly SupplierService $suppliers,
    ) {
    }

    public function index(): array
    {
        return $this->json(['data' => $this->suppliers->list()]);
    }

    public function show(int $supplierId): array
    {
        return $this->guarded(fn (): array => $this->json(['data' => $this->suppliers->find($supplierId)]));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->json(['data' => $this->suppliers->create(Request::all())], 201));
    }

    public function update(int $supplierId): array
    {
        return $this->guarded(fn (): array => $this->json(['data' => $this->suppliers->update($supplierId, Request::all())]));
    }

    private function guarded(callable $callback): array
    {
        try {
            return $callback();
        } catch (NotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }
}
