<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\ImportService;
use RuntimeException;

final class ImportApiController extends Controller
{
    public function __construct(
        private readonly ImportService $imports,
    ) {
    }

    public function index(): array
    {
        $items = $this->imports->list();

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
        return $this->guardedWrite(fn (): array => $this->imports->create(Request::all()), 201);
    }

    public function update(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->update($importId, Request::all()));
    }

    public function storeExpense(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->addExpense($importId, Request::all()), 201);
    }

    public function storeItem(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->addItem($importId, Request::all()), 201);
    }

    public function complete(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->complete($importId));
    }

    private function guardedWrite(callable $callback, int $status = 200): array
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
