<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Session;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\BrandApiController;
use App\Controllers\Api\CategoryApiController;
use App\Controllers\Api\CustomerApiController;
use App\Controllers\Api\ExchangeRateApiController;
use App\Controllers\Api\ImportApiController;
use App\Controllers\Api\ProductApiController;
use App\Controllers\Api\RoleApiController;
use App\Controllers\Api\StockApiController;
use App\Controllers\Api\SupplierApiController;
use App\Controllers\Api\UserApiController;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\RoleController;
use App\Controllers\StockController;
use App\Controllers\TenantController;
use App\Controllers\UserController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\ImportRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\RoleRepository;
use App\Repositories\StockRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\CustomerService;
use App\Services\ExchangeRateService;
use App\Services\HealthService;
use App\Services\ImportService;
use App\Services\PasswordResetService;
use App\Services\PricingService;
use App\Services\ProductService;
use App\Services\RoleService;
use App\Services\StockService;
use App\Services\SupplierService;
use App\Services\TenantService;
use App\Services\UserService;

$router->get('/', static fn (): array => (new HomeController())->index());
$router->get('/health', static fn (): array => (new HealthController(new HealthService()))->show());

$session = new Session();
$auditLogRepository = new AuditLogRepository();
$userRepository = new UserRepository();
$tenantRepository = new TenantRepository();
$authService = new AuthService($userRepository, $auditLogRepository, $tenantRepository, $session);
$passwordResetService = new PasswordResetService($userRepository, new PasswordResetRepository(), $auditLogRepository);
$exchangeRateRepository = new ExchangeRateRepository();
$exchangeRateService = new ExchangeRateService($exchangeRateRepository, $auditLogRepository);
$supplierRepository = new SupplierRepository();
$supplierService = new SupplierService($supplierRepository, $auditLogRepository);
$stockRepository = new StockRepository();
$importService = new ImportService(new ImportRepository(), $auditLogRepository, $exchangeRateService, $supplierRepository, $stockRepository);
$productRepository = new ProductRepository();
$categoryRepository = new CategoryRepository();
$brandRepository = new BrandRepository();
$categoryService = new CategoryService($categoryRepository, $auditLogRepository);
$brandService = new BrandService($brandRepository, $auditLogRepository);
$pricingService = new PricingService($productRepository);
$productService = new ProductService(
    $productRepository,
    $categoryRepository,
    $brandRepository,
    $supplierRepository,
    $auditLogRepository,
    $pricingService,
);
$roleRepository = new RoleRepository();
$stockService = new StockService($stockRepository);
$customerService = new CustomerService(new CustomerRepository(), $auditLogRepository);
$tenantService = new TenantService(
    $tenantRepository,
    $auditLogRepository,
    (string) Application::getInstance()->config('app.timezone', 'America/Sao_Paulo'),
);

$newAuthController = static fn (): AuthController => new AuthController(
    $authService,
    $passwordResetService,
    $session,
    (bool) Application::getInstance()->config('app.debug', false),
);

$router->use(static fn (callable $next): array => (new CsrfMiddleware($session))->handle($next));

$guest = static fn (callable $next): array => (new GuestMiddleware($authService))->handle($next);
$auth = static fn (callable $next): array => (new AuthMiddleware($authService))->handle($next);
$permission = static fn (string $name): callable => static fn (callable $next): array => (new PermissionMiddleware($authService, $name))->handle($next);

$router->get('/login', static fn (): array => $newAuthController()->create(), [$guest]);
$router->post('/login', static fn (): array => $newAuthController()->store(), [$guest]);
$router->post('/logout', static fn (): array => $newAuthController()->destroy(), [$auth]);
$router->get('/esqueci-senha', static fn (): array => $newAuthController()->forgotPasswordForm(), [$guest]);
$router->post('/esqueci-senha', static fn (): array => $newAuthController()->forgotPassword(), [$guest]);
$router->get('/redefinir-senha', static fn (): array => $newAuthController()->resetPasswordForm(), [$guest]);
$router->post('/redefinir-senha', static fn (): array => $newAuthController()->resetPassword(), [$guest]);

$router->get('/dashboard', static fn (): array => (new DashboardController($authService, $session))->index(), [
	$auth,
	$permission('dashboard.view'),
]);

$router->post('/api/v1/auth/login', static fn (): array => (new AuthApiController($authService, $passwordResetService))->login(), [$guest]);
$router->post('/api/v1/auth/logout', static fn (): array => (new AuthApiController($authService, $passwordResetService))->logout(), [$auth]);
$router->post('/api/v1/auth/password-reset', static fn (): array => (new AuthApiController($authService, $passwordResetService))->passwordReset(), [$guest]);
$router->post('/api/v1/auth/password-reset/confirm', static fn (): array => (new AuthApiController($authService, $passwordResetService))->passwordResetConfirm(), [$guest]);

$router->get('/api/v1/imports', static fn (): array => (new ImportApiController($importService))->index(), [
	$auth,
	$permission('imports.view'),
]);
$router->post('/api/v1/imports', static fn (): array => (new ImportApiController($importService))->store(), [
	$auth,
	$permission('imports.create'),
]);
$router->put('/api/v1/imports/{id}', static fn (int $id): array => (new ImportApiController($importService))->update($id), [
	$auth,
	$permission('imports.create'),
]);
$router->post('/api/v1/imports/{id}/expenses', static fn (int $id): array => (new ImportApiController($importService))->storeExpense($id), [
	$auth,
	$permission('imports.create'),
]);
$router->post('/api/v1/imports/{id}/items', static fn (int $id): array => (new ImportApiController($importService))->storeItem($id), [
	$auth,
	$permission('imports.create'),
]);
$router->post('/api/v1/imports/{id}/complete', static fn (int $id): array => (new ImportApiController($importService))->complete($id), [
	$auth,
	$permission('imports.complete'),
]);
$router->post('/api/v1/imports/{id}/reopen', static fn (int $id): array => (new ImportApiController($importService))->reopen($id), [
	$auth,
	$permission('imports.complete'),
]);
$router->get('/api/v1/imports/{id}/expenses', static fn (int $id): array => (new ImportApiController($importService))->expenses($id), [
	$auth,
	$permission('imports.view'),
]);
$router->get('/api/v1/imports/{id}/items', static fn (int $id): array => (new ImportApiController($importService))->items($id), [
	$auth,
	$permission('imports.view'),
]);

$router->get('/api/v1/exchange-rates', static fn (): array => (new ExchangeRateApiController($exchangeRateService))->index(), [
	$auth,
	$permission('imports.view'),
]);
$router->post('/api/v1/exchange-rates', static fn (): array => (new ExchangeRateApiController($exchangeRateService))->store(), [
	$auth,
	$permission('imports.create'),
]);

$router->get('/api/v1/suppliers', static fn (): array => (new SupplierApiController($supplierService))->index(), [
	$auth,
	$permission('imports.view'),
]);
$router->post('/api/v1/suppliers', static fn (): array => (new SupplierApiController($supplierService))->store(), [
	$auth,
	$permission('imports.create'),
]);
$router->get('/api/v1/suppliers/{id}', static fn (int $id): array => (new SupplierApiController($supplierService))->show($id), [
	$auth,
	$permission('imports.view'),
]);
$router->put('/api/v1/suppliers/{id}', static fn (int $id): array => (new SupplierApiController($supplierService))->update($id), [
	$auth,
	$permission('imports.create'),
]);

$router->get('/api/v1/categories', static fn (): array => (new CategoryApiController($categoryService))->index(), [
	$auth,
	$permission('products.view'),
]);
$router->post('/api/v1/categories', static fn (): array => (new CategoryApiController($categoryService))->store(), [
	$auth,
	$permission('products.create'),
]);
$router->get('/api/v1/categories/{id}', static fn (int $id): array => (new CategoryApiController($categoryService))->show($id), [
	$auth,
	$permission('products.view'),
]);
$router->put('/api/v1/categories/{id}', static fn (int $id): array => (new CategoryApiController($categoryService))->update($id), [
	$auth,
	$permission('products.edit'),
]);

$router->get('/api/v1/brands', static fn (): array => (new BrandApiController($brandService))->index(), [
	$auth,
	$permission('products.view'),
]);
$router->post('/api/v1/brands', static fn (): array => (new BrandApiController($brandService))->store(), [
	$auth,
	$permission('products.create'),
]);
$router->get('/api/v1/brands/{id}', static fn (int $id): array => (new BrandApiController($brandService))->show($id), [
	$auth,
	$permission('products.view'),
]);
$router->put('/api/v1/brands/{id}', static fn (int $id): array => (new BrandApiController($brandService))->update($id), [
	$auth,
	$permission('products.edit'),
]);

$router->get('/api/v1/products', static fn (): array => (new ProductApiController($productService))->index(), [
	$auth,
	$permission('products.view'),
]);
$router->post('/api/v1/products', static fn (): array => (new ProductApiController($productService))->store(), [
	$auth,
	$permission('products.create'),
]);
$router->get('/api/v1/products/{id}', static fn (int $id): array => (new ProductApiController($productService))->show($id), [
	$auth,
	$permission('products.view'),
]);
$router->put('/api/v1/products/{id}', static fn (int $id): array => (new ProductApiController($productService))->update($id), [
	$auth,
	$permission('products.edit'),
]);
$router->delete('/api/v1/products/{id}', static fn (int $id): array => (new ProductApiController($productService))->destroy($id), [
	$auth,
	$permission('products.edit'),
]);
$router->get('/api/v1/products/{id}/stock', static fn (int $id): array => (new ProductApiController($productService))->stock($id), [
	$auth,
	$permission('stock.view'),
]);
$router->get('/api/v1/products/{id}/price-suggestion', static fn (int $id): array => (new ProductApiController($productService))->suggestPrice($id), [
	$auth,
	$permission('products.view'),
]);

// Estoque. Leitura com stock.view; toda escrita com stock.adjust, porque mexem
// em saldo. O id do produto vai na URL no lugar de adjustment/{id} para nao
// confundir com o id da propria movimentacao.
$router->get('/api/v1/stock', static fn (): array => (new StockApiController($stockService))->index(), [
	$auth,
	$permission('stock.view'),
]);
$router->get('/api/v1/stock/movements', static fn (): array => (new StockApiController($stockService))->movements(), [
	$auth,
	$permission('stock.view'),
]);
$router->post('/api/v1/stock/adjustments', static fn (): array => (new StockApiController($stockService))->adjust(), [
	$auth,
	$permission('stock.adjust'),
]);
$router->post('/api/v1/stock/reservations', static fn (): array => (new StockApiController($stockService))->reserve(), [
	$auth,
	$permission('stock.adjust'),
]);
$router->post('/api/v1/stock/reservations/release', static fn (): array => (new StockApiController($stockService))->release(), [
	$auth,
	$permission('stock.adjust'),
]);
$router->post('/api/v1/stock/consumptions', static fn (): array => (new StockApiController($stockService))->consume(), [
	$auth,
	$permission('stock.adjust'),
]);
$router->get('/api/v1/stock/{id}', static fn (int $id): array => (new StockApiController($stockService))->show($id), [
	$auth,
	$permission('stock.view'),
]);
$router->put('/api/v1/stock/{id}/minimum-quantity', static fn (int $id): array => (new StockApiController($stockService))->setMinimum($id), [
	$auth,
	$permission('stock.adjust'),
]);

$router->get('/produtos', static fn (): array => (new ProductController($productService, $categoryService, $brandService, $supplierService, $authService, $session))->index(), [
	$auth,
	$permission('products.view'),
]);
$router->post('/produtos', static fn (): array => (new ProductController($productService, $categoryService, $brandService, $supplierService, $authService, $session))->store(), [
	$auth,
	$permission('products.create'),
]);

// Telas de estoque: leitura com stock.view. As escritas continuam so na API,
// por enquanto.
$newStockController = static fn (): StockController => new StockController($stockService, $authService, $session);

$router->get('/estoque', static fn (): array => $newStockController()->index(), [
	$auth,
	$permission('stock.view'),
]);
$router->get('/estoque/movimentacoes', static fn (): array => $newStockController()->movements(), [
	$auth,
	$permission('stock.view'),
]);

// Clientes (EPIC 07). Leitura em customers.view; o cadastro tambem aceita o
// form nativo em POST /clientes com customers.create. Edicao e bloqueio ficam
// so na API, como em produtos e estoque.
$newCustomerController = static fn (): CustomerController => new CustomerController($customerService, $authService, $session);
$newCustomerApiController = static fn (): CustomerApiController => new CustomerApiController($customerService);

$router->get('/clientes', static fn (): array => $newCustomerController()->index(), [
	$auth,
	$permission('customers.view'),
]);
$router->post('/clientes', static fn (): array => $newCustomerController()->store(), [
	$auth,
	$permission('customers.create'),
]);
// O roteador compila `{id}` como `[^/]+` num regex ancorado, entao
// `/clientes/123` casa aqui e `/clientes/123/historico` nao: nao ha risco de um
// `{id}` "engolir" o segmento do historico, e nem precisa de regex inline.
$router->get('/clientes/{id}', static fn (int $id): array => $newCustomerController()->show($id), [
	$auth,
	$permission('customers.view'),
]);

$router->get('/api/v1/customers', static fn (): array => $newCustomerApiController()->index(), [
	$auth,
	$permission('customers.view'),
]);
$router->post('/api/v1/customers', static fn (): array => $newCustomerApiController()->store(), [
	$auth,
	$permission('customers.create'),
]);
$router->get('/api/v1/customers/{id}', static fn (int $id): array => $newCustomerApiController()->show($id), [
	$auth,
	$permission('customers.view'),
]);
$router->put('/api/v1/customers/{id}', static fn (int $id): array => $newCustomerApiController()->update($id), [
	$auth,
	$permission('customers.edit'),
]);
$router->delete('/api/v1/customers/{id}', static fn (int $id): array => $newCustomerApiController()->destroy($id), [
	$auth,
	$permission('customers.delete'),
]);
// Historico comercial do cliente. Nao ha ambiguidade com o `{id}` acima: o
// regex da rota `/api/v1/customers/{id}` e ancorado e o parametro e `[^/]+`.
$router->get('/api/v1/customers/{id}/history', static fn (int $id): array => $newCustomerApiController()->history($id), [
	$auth,
	$permission('customers.view'),
]);

$userService = new UserService($userRepository, $roleRepository, $auditLogRepository);
$roleService = new RoleService($roleRepository, new PermissionRepository(), $auditLogRepository);

$router->get('/api/v1/users', static fn (): array => (new UserApiController($userService))->index(), [
	$auth,
	$permission('users.view'),
]);
$router->post('/api/v1/users', static fn (): array => (new UserApiController($userService))->store(), [
	$auth,
	$permission('users.manage'),
]);
$router->get('/api/v1/users/{id}', static fn (int $id): array => (new UserApiController($userService))->show($id), [
	$auth,
	$permission('users.view'),
]);
$router->put('/api/v1/users/{id}', static fn (int $id): array => (new UserApiController($userService))->update($id), [
	$auth,
	$permission('users.manage'),
]);
$router->post('/api/v1/users/{id}/password', static fn (int $id): array => (new UserApiController($userService))->storePassword($id), [
	$auth,
	$permission('users.manage'),
]);
$router->post('/api/v1/users/{id}/block', static fn (int $id): array => (new UserApiController($userService))->block($id), [
	$auth,
	$permission('users.manage'),
]);
$router->post('/api/v1/users/{id}/activate', static fn (int $id): array => (new UserApiController($userService))->activate($id), [
	$auth,
	$permission('users.manage'),
]);

$router->get('/api/v1/roles', static fn (): array => (new RoleApiController($roleService))->index(), [
	$auth,
	$permission('users.view'),
]);
$router->post('/api/v1/roles', static fn (): array => (new RoleApiController($roleService))->store(), [
	$auth,
	$permission('users.manage'),
]);
$router->get('/api/v1/roles/permissions', static fn (): array => (new RoleApiController($roleService))->permissions(), [
	$auth,
	$permission('users.view'),
]);
$router->get('/api/v1/roles/{id}', static fn (int $id): array => (new RoleApiController($roleService))->show($id), [
	$auth,
	$permission('users.view'),
]);
$router->put('/api/v1/roles/{id}', static fn (int $id): array => (new RoleApiController($roleService))->update($id), [
	$auth,
	$permission('users.manage'),
]);
$router->put('/api/v1/roles/{id}/permissions', static fn (int $id): array => (new RoleApiController($roleService))->updatePermissions($id), [
	$auth,
	$permission('users.manage'),
]);
$router->delete('/api/v1/roles/{id}', static fn (int $id): array => (new RoleApiController($roleService))->destroy($id), [
	$auth,
	$permission('users.manage'),
]);

$router->get('/usuarios', static fn (): array => (new UserController($userService, $roleService, $tenantService, $authService, $session))->index(), [
	$auth,
	$permission('users.view'),
]);
$router->get('/usuarios/{id}', static fn (int $id): array => (new UserController($userService, $roleService, $tenantService, $authService, $session))->show($id), [
	$auth,
	$permission('users.view'),
]);
$router->post('/usuarios/{id}/bloquear', static fn (int $id): array => (new UserController($userService, $roleService, $tenantService, $authService, $session))->block($id), [
	$auth,
	$permission('users.manage'),
]);
$router->post('/usuarios/{id}/ativar', static fn (int $id): array => (new UserController($userService, $roleService, $tenantService, $authService, $session))->activate($id), [
	$auth,
	$permission('users.manage'),
]);
$router->get('/perfis', static fn (): array => (new RoleController($roleService, $authService, $session))->index(), [
	$auth,
	$permission('users.view'),
]);
$router->get('/perfis/{id}', static fn (int $id): array => (new RoleController($roleService, $authService, $session))->show($id), [
	$auth,
	$permission('users.view'),
]);
$router->post('/perfis/{id}/permissoes', static fn (int $id): array => (new RoleController($roleService, $authService, $session))->updatePermissions($id), [
	$auth,
	$permission('users.manage'),
]);
$router->get('/configuracoes', static fn (): array => (new TenantController($tenantService, $authService, $session))->edit(), [
	$auth,
	$permission('settings.manage'),
]);
$router->post('/configuracoes', static fn (): array => (new TenantController($tenantService, $authService, $session))->update(), [
	$auth,
	$permission('settings.manage'),
]);
