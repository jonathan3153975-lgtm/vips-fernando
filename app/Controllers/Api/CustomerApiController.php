<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\CustomerService;
use RuntimeException;

/**
 * Clientes e historico comercial (EPIC 07).
 *
 * Permissoes: leitura em `customers.view`; gravar em `customers.create`; editar
 * em `customers.edit`; apagar/bloquear em `customers.delete`. O DELETE responde
 * 200 com `blocked: true|false` — bloqueado quando o cliente tem historico,
 * apagado quando nao tem. Nao e 204: quem chamou precisa saber qual dos dois
 * aconteceu.
 */
final class CustomerApiController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
    ) {
    }

    /**
     * `search` cobre nome, documento e contato; `status` filtra ACTIVE/INACTIVE;
     * `has_sales=1|0` separa quem ja comprou de quem nunca comprou.
     */
    public function index(): array
    {
        $filters = $this->filters(['search', 'status', 'has_sales']);

        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->customers->paginate($filters, $this->perPage(), ($this->page() - 1) * $this->perPage()),
        ));
    }

    /**
     * Detalhe: cadastro + resumo comercial (total gasto, compras, ultima compra,
     * ticket medio, lucro) numa chamada so.
     */
    public function show(int $customerId): array
    {
        return $this->guarded(fn (): array => $this->customers->show($customerId));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->customers->create(Request::all()), 201);
    }

    public function update(int $customerId): array
    {
        return $this->guarded(fn (): array => $this->customers->update($customerId, Request::all()));
    }

    public function destroy(int $customerId): array
    {
        return $this->guarded(function () use ($customerId): array {
            $result = $this->customers->delete($customerId);

            return [
                'customer' => $result['customer'],
                'blocked' => $result['blocked'],
                'purchases' => $result['purchases'],
            ];
        });
    }

    /**
     * Historico comercial: as vendas do cliente, paginado, com filtro opcional
     * por situacao da venda.
     */
    public function history(int $customerId): array
    {
        $filters = $this->filters(['status']);

        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->customers->history($customerId, $filters, $this->perPage(), ($this->page() - 1) * $this->perPage()),
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