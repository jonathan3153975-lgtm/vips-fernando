<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\ResponseFactory;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SupplierService;
use RuntimeException;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
        private readonly BrandService $brands,
        private readonly SupplierService $suppliers,
        private readonly AuthService $auth,
        private readonly Session $session,
    ) {
    }

    public function index(): array
    {
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(100, max(1, (int) Request::query('per_page', 15)));

        $filters = [];

        foreach (['category_id', 'brand_id', 'status', 'search'] as $key) {
            $value = Request::query($key);

            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }

        $result = $this->products->paginate($filters, $perPage, ($page - 1) * $perPage);

        return $this->view('products/index', $this->layout([
            'title' => 'Produtos',
            'products' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
            'categories' => $this->categories->list(),
            'brands' => $this->brands->list(),
            'suppliers' => $this->suppliers->list(),
        ]));
    }

    public function store(): array
    {
        $data = [
            'sku' => (string) Request::input('sku', ''),
            'name' => (string) Request::input('name', ''),
            'description' => (string) Request::input('description', ''),
            'unit' => (string) Request::input('unit', 'UN'),
            'category_id' => $this->nullableInt(Request::input('category_id')),
            'brand_id' => $this->nullableInt(Request::input('brand_id')),
            'supplier_id' => $this->nullableInt(Request::input('supplier_id')),
            'default_import_item_id' => $this->nullableInt(Request::input('default_import_item_id')),
        ];

        $margin = Request::input('margin');

        $price = [];

        if (is_string($margin) && trim($margin) !== '') {
            $price['margin'] = (float) $margin;
        }

        $salePrice = Request::input('sale_price');

        if (is_string($salePrice) && trim($salePrice) !== '') {
            $price['sale_price'] = (float) $salePrice;
        }

        $minimumPrice = Request::input('minimum_price');

        if (is_string($minimumPrice) && trim($minimumPrice) !== '') {
            $price['minimum_price'] = (float) $minimumPrice;
        }

        $costPrice = Request::input('cost_price');

        if (is_string($costPrice) && trim($costPrice) !== '') {
            $price['cost_price'] = (float) $costPrice;
        }

        if ($price !== []) {
            $data['price'] = $price;
        }

        try {
            $product = $this->products->create($data);
        } catch (RuntimeException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return ResponseFactory::redirect('/produtos');
        }

        $this->session->flash('notice', 'Produto "' . $product['name'] . '" criado com sucesso.');

        return ResponseFactory::redirect('/produtos');
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return (int) $value;
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
            'canCreateProducts' => $this->auth->hasPermission('products.create'),
            'activeNav' => 'products',
        ];
    }
}
