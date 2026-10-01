<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\StockService;
use RuntimeException;

/**
 * Estoque. As leituras (saldos, historico, minimo) usam `stock.view`; toda
 * escrita usa `stock.adjust`, porque mexe em saldo e nao em cadastro.
 */
final class StockApiController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
    ) {
    }

    /**
     * Saldos com disponivel, reservado e alerta de reposicao. Aceita
     * `below_minimum=1` para a tela/listagem de reposicao.
     */
    public function index(): array
    {
        $filters = $this->filters(['product_id', 'search', 'below_minimum']);

        return $this->guardedResponse(fn (): array => $this->paginated([
            'data' => $this->stock->balances($filters, $this->perPage(), ($this->page() - 1) * $this->perPage()),
            'total' => $this->stock->countBalances($filters),
        ]));
    }

    /**
     * Historico de movimentacoes, filtrado por produto, tipo, usuario e periodo.
     */
    public function movements(): array
    {
        $filters = $this->filters(['product_id', 'type', 'user_id', 'from', 'to']);

        return $this->guardedResponse(fn (): array => $this->paginated([
            'data' => $this->stock->movements($filters, $this->perPage(), ($this->page() - 1) * $this->perPage()),
            'total' => $this->stock->countMovements($filters),
        ]));
    }

    public function show(int $productId): array
    {
        return $this->guarded(fn (): array => $this->stock->balance($productId));
    }

    public function adjust(): array
    {
        return $this->guarded(fn (): array => $this->stock->adjust(Request::all()), 201);
    }

    public function reserve(): array
    {
        return $this->guarded(fn (): array => $this->stock->reserve(Request::all()), 201);
    }

    public function release(): array
    {
        return $this->guarded(fn (): array => $this->stock->release(Request::all()));
    }

    public function consume(): array
    {
        return $this->guarded(fn (): array => $this->stock->consume(Request::all()));
    }

    public function setMinimum(int $productId): array
    {
        return $this->guarded(fn (): array => $this->stock->setMinimumQuantity(
            array_merge(Request::all(), ['product_id' => $productId]),
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
