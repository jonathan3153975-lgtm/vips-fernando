<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\StockService;

final class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    /**
     * Saldos em duas visoes: o alerta de reposicao (abaixo do minimo) e o
     * inventario completo. A aba de reposicao e o que o usuario procura ao abrir
     * a tela — o que esta acabando e o que precisa de compra.
     */
    public function index(): array
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(100, max(1, (int) Request::query('per_page', 15)));

        $filters = [];

        foreach (['search', 'below_minimum'] as $key) {
            $value = Request::query($key);

            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }

        $result = $this->paginate($filters, $perPage, ($page - 1) * $perPage);
        $labels = $this->stock->movementTypeLabels();

        return $this->view('stock/index', $this->layout([
            'title' => 'Estoque',
            'balances' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
            'lowStockCount' => $this->stock->countBalances(['below_minimum' => '1']),
            'movementTypes' => $labels,
        ]));
    }

    /**
     * Historico de movimentacoes com os mesmos filtros da API. Paginado e sem
     * tela de edicao: ajuste e reserva acontecem pela API, por enquanto.
     */
    public function movements(): array
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(100, max(1, (int) Request::query('per_page', 15)));

        $filters = [];

        foreach (['product_id', 'type', 'from', 'to'] as $key) {
            $value = Request::query($key);

            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }

        $movements = $this->stock->movements($filters, $perPage, ($page - 1) * $perPage);

        return $this->view('stock/movements', $this->layout([
            'title' => 'Movimentacoes de estoque',
            'movements' => $movements,
            'total' => $this->stock->countMovements($filters),
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
            'movementTypes' => $this->stock->movementTypeLabels(),
            'activeNav' => 'stock-movements',
        ]));
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    private function paginate(array $filters, int $perPage, int $offset): array
    {
        return [
            'data' => $this->stock->balances($filters, $perPage, $offset),
            'total' => $this->stock->countBalances($filters),
        ];
    }

    private function layout(array $data): array
    {
        return $data + [
            'currentUser' => $this->auth->user(),
            'session' => $this->session,
            'csrfField' => Csrf::field($this->session),
            'canManageUsers' => $this->auth->hasPermission('users.manage'),
            'canManageSettings' => $this->auth->hasPermission('settings.manage'),
            'canAdjustStock' => $this->auth->hasPermission('stock.adjust'),
            'activeNav' => 'stock',
        ];
    }
}
