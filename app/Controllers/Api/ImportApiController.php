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
        $method = Request::input('allocation_method');

        return $this->guardedWrite(fn (): array => $this->imports->complete(
            $importId,
            $method === null || $method === '' ? null : (string) $method,
        ));
    }

    public function reopen(int $importId): array
    {
        return $this->guardedWrite(fn (): array => $this->imports->reopen($importId));
    }

    public function expenses(int $importId): array
    {
        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->imports->expenses($importId, $this->filters(['category', 'status']), $this->page(), $this->perPage()),
        ));
    }

    public function items(int $importId): array
    {
        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->imports->items($importId, $this->filters(['sku']), $this->page(), $this->perPage()),
        ));
    }

    /**
     * @param array{data: list<array<string, mixed>>, total: int} $result
     */
    private function paginated(array $result): array
    {
        return $this->json([
            'data' => $result['data'],
            'meta' => [
                'page' => $this->page(),
                'per_page' => $this->perPage(),
                'total' => $result['total'],
            ],
        ]);
    }

    /**
     * @param list<string> $allowed
     *
     * @return array<string, string>
     */
    private function filters(array $allowed): array
    {
        $filters = [];

        foreach ($allowed as $key) {
            $value = Request::query($key);

            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }

        return $filters;
    }

    private function page(): int
    {
        return max(1, (int) Request::query('page', 1));
    }

    private function perPage(): int
    {
        return min(100, max(1, (int) Request::query('per_page', 15)));
    }

    private function guardedWrite(callable $callback, int $status = 200): array
    {
        return $this->guard(fn (): array => $this->json(['data' => $callback()], $status));
    }

    /**
     * Para respostas ja montadas (ex.: listagens paginadas), em que nao se pode
     * envolver o retorno em ['data' => ...].
     */
    private function guardedResponse(callable $callback): array
    {
        return $this->guard($callback);
    }

    private function guard(callable $callback): array
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
