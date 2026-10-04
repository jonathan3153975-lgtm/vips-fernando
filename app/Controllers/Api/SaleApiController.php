<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\SaleService;
use RuntimeException;

/**
 * Vendas e checkout (EPIC 08).
 *
 * As rotas de escrita exigem a permissao que o roteiro nomeia, e nao uma
 * permissao unica de vendas: quem pode registrar uma venda nao pode, por isso
 * mesmo, conceder desconto de 40% nem reescrever o preco de tabela. As quatro
 * leituras usam `sales.view`.
 */
final class SaleApiController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
    ) {
    }

    /**
     * Listagem com filtros por situacao, cliente e periodo.
     */
    public function index(): array
    {
        $filters = $this->filters(['status', 'customer_id', 'search', 'from', 'to']);

        return $this->guardedResponse(fn (): array => $this->paginated(
            $this->sales->paginate($filters, $this->perPage(), ($this->page() - 1) * $this->perPage()),
        ));
    }

    /**
     * Detalhe: cabecalho, itens, descontos, pagamentos, devolucoes e a conta a
     * receber. Vem tudo em uma resposta porque e o que a tela de detalhe mostra.
     */
    public function show(int $saleId): array
    {
        return $this->guarded(fn (): array => $this->sales->show($saleId));
    }

    /**
     * Abre a venda com os itens e reserva o estoque. A reserva acontece aqui e
     * nao na conclusao: entre abrir o pedido e fechar, a mercadoria continua no
     * estoque e outro vendedor poderia le-va.
     */
    public function store(): array
    {
        return $this->guarded(fn (): array => $this->sales->create(Request::all()), 201);
    }

    public function addItem(int $saleId): array
    {
        return $this->guarded(fn (): array => $this->sales->addItem($saleId, Request::all()), 201);
    }

    public function complete(int $saleId): array
    {
        return $this->guarded(fn (): array => $this->sales->complete($saleId, Request::all()));
    }

    public function cancel(int $saleId): array
    {
        return $this->guarded(fn (): array => $this->sales->cancel($saleId, Request::all()));
    }

    /**
     * Devolucao parcial ou total. Rota separada de `cancel` porque a devolucao
     * exige `sales.return` e vale para venda ja concluida, enquanto o
     * cancelamento exige `sales.cancel` e so vale para venda aberta.
     */
    public function returnItems(int $saleId): array
    {
        return $this->guarded(fn (): array => $this->sales->returnItems($saleId, Request::all()), 201);
    }

    /**
     * Listas fechadas e o limite de desconto do tenant. A tela de venda precisa
     * delas para nao ter a forma de pagamento, a situacao nem o teto do desconto
     * digitados livres pelo usuario.
     */
    public function options(): array
    {
        return $this->guarded(fn (): array => $this->sales->statuses());
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