<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\CustomerService;
use RuntimeException;

/**
 * Telas de cliente. Na Etapa 6 a escrita de cadastro acontece por aqui (form
 * nativo) e por API; historico comercial e leitura.
 */
final class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function index(): array
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(100, max(1, (int) Request::query('per_page', 15)));

        $filters = [];

        foreach (['search', 'status', 'has_sales'] as $key) {
            $value = Request::query($key);

            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }

        $result = $this->customers->paginate($filters, $perPage, ($page - 1) * $perPage);

        return $this->view('customers/index', $this->layout([
            'title' => 'Clientes',
            'customers' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
        ]));
    }

    /**
     * Historico comercial do cliente: resumo + vendas.
     */
    public function show(int $customerId): array
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(100, max(1, (int) Request::query('per_page', 15)));

        $filters = [];
        $status = Request::query('status');

        if (is_string($status) && trim($status) !== '') {
            $filters['status'] = trim($status);
        }

        try {
            $detail = $this->customers->show($customerId);
            $history = $this->customers->history($customerId, $filters, $perPage, ($page - 1) * $perPage);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect('/clientes');
        }

        return $this->view('customers/show', $this->layout([
            'title' => 'Cliente',
            'customer' => $detail['customer'],
            'summary' => $detail['summary'],
            'purchases' => $history['data'],
            'purchaseTotal' => $history['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
        ]));
    }

    public function store(): array
    {
        $data = [
            'name' => (string) Request::input('name', ''),
            'document' => Request::input('document'),
            'phone' => Request::input('phone'),
            'whatsapp' => Request::input('whatsapp'),
            'email' => Request::input('email'),
            'address' => Request::input('address'),
            'notes' => Request::input('notes'),
        ];

        try {
            $customer = $this->customers->create($data);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect('/clientes');
        }

        $this->session->flash('notice', 'Cliente "' . $customer['name'] . '" cadastrado com sucesso.');

        return ResponseFactory::redirect('/clientes');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function layout(array $data): array
    {
        return $data + [
            'currentUser' => $this->auth->user(),
            'session' => $this->session,
            'csrfField' => Csrf::field($this->session),
            'canManageUsers' => $this->auth->hasPermission('users.manage'),
            'canManageSettings' => $this->auth->hasPermission('settings.manage'),
            'canCreateCustomers' => $this->auth->hasPermission('customers.create'),
            'canEditCustomers' => $this->auth->hasPermission('customers.edit'),
            'canDeleteCustomers' => $this->auth->hasPermission('customers.delete'),
            'activeNav' => 'customers',
        ];
    }
}