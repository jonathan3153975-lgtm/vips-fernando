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
        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->products->paginate(
                $this->filters(['category_id', 'brand_id', 'status', 'search']),
                $this->perPage(),
                ($this->page() - 1) * $this->perPage(),
            ),
        ));
    }

    public function show(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->find($productId));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->products->create(Request::all()), 201);
    }

    public function update(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->update($productId, Request::all()));
    }

    public function destroy(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->delete($productId));
    }

    public function stock(int $productId): array
    {
        return $this->guarded(fn (): array => $this->products->stock($productId));
    }

    /**
     * Sugestao de preco e leitura pura (GET): os parametros vem da query string,
     * nao de um corpo. Ler de Request::input() devolveria sempre o default.
     */
    public function suggestPrice(int $productId): array
    {
        $margin = (float) Request::query('margin', 0);
        $minimum = Request::query('minimum_price');

        return $this->guarded(fn (): array => $this->products->suggestPrice(
            $productId,
            $margin,
            $minimum === null || $minimum === '' ? null : (float) $minimum,
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

    private function guarded(callable $callback, int $status = 200): array
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
