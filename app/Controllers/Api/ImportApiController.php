<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ImportService;
use RuntimeException;

final class ImportApiController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly ImportService $imports,
    ) {
    }

    public function index(): array
    {
        $tenantId = (int) ($this->auth->user()['tenant_id'] ?? 0);

        return $this->json([
            'data' => $this->imports->list($tenantId),
            'meta' => [
                'page' => 1,
                'per_page' => 15,
                'total' => count($this->imports->list($tenantId)),
            ],
        ]);
    }

    public function store(): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->create(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            Request::all(),
        ), 201);
    }

    public function update(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->update(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            $importId,
            Request::all(),
        ));
    }

    public function storeExpense(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->addExpense(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            $importId,
            Request::all(),
        ), 201);
    }

    public function storeItem(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->addItem(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            $importId,
            Request::all(),
        ), 201);
    }

    public function complete(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->complete(
            (int) ($this->auth->user()['tenant_id'] ?? 0),
            (int) ($this->auth->user()['user_id'] ?? 0),
            $importId,
        ));
    }

    private function guardedWrite(callable $callback, int $status = 200): array
    {
        try {
            return $this->json(['data' => $callback()], $status);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $httpStatus = $message === 'Importacao nao encontrada.' ? 404 : 400;

            return $this->json(['message' => $message], $httpStatus);
        }
    }
}