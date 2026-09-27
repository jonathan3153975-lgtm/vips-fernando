<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Session;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ImportApiController;
use App\Controllers\Api\ProductApiController;
use App\Controllers\Api\RoleApiController;
use App\Controllers\Api\UserApiController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\RoleController;
use App\Controllers\TenantController;
use App\Controllers\UserController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\ImportRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\HealthService;
use App\Services\ImportService;
use App\Services\PasswordResetService;
use App\Services\ProductService;
use App\Services\RoleService;
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
$importService = new ImportService(new ImportRepository(), $auditLogRepository);
$productService = new ProductService(new ProductRepository(), $auditLogRepository);
$roleRepository = new RoleRepository();
$tenantService = new TenantService(
    $tenantRepository,
    $auditLogRepository,
    (string) Application::getInstance()->config('app.timezone', 'America/Sao_Paulo'),
);

$router->use(static fn (callable $next): array => (new CsrfMiddleware($session))->handle($next));

$guest = static fn (callable $next): array => (new GuestMiddleware($authService))->handle($next);
$auth = static fn (callable $next): array => (new AuthMiddleware($authService))->handle($next);
$permission = static fn (string $name): callable => static fn (callable $next): array => (new PermissionMiddleware($authService, $name))->handle($next);

$router->get('/login', static fn (): array => (new AuthController($authService, $session))->create(), [$guest]);
$router->post('/login', static fn (): array => (new AuthController($authService, $session))->store(), [$guest]);
$router->post('/logout', static fn (): array => (new AuthController($authService, $session))->destroy(), [$auth]);

$router->get('/dashboard', static fn (): array => (new DashboardController($authService, $session))->index(), [
	$auth,
	$permission('dashboard.view'),
]);

$router->post('/api/v1/auth/login', static fn (): array => (new AuthApiController($authService, $passwordResetService))->login(), [$guest]);
$router->post('/api/v1/auth/logout', static fn (): array => (new AuthApiController($authService, $passwordResetService))->logout(), [$auth]);
$router->post('/api/v1/auth/password-reset', static fn (): array => (new AuthApiController($authService, $passwordResetService))->passwordReset(), [$guest]);

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

$router->get('/api/v1/products', static fn (): array => (new ProductApiController($productService))->index(), [
	$auth,
	$permission('products.view'),
]);
$router->post('/api/v1/products', static fn (): array => (new ProductApiController($productService))->store(), [
	$auth,
	$permission('products.create'),
]);
$router->put('/api/v1/products/{id}', static fn (int $id): array => (new ProductApiController($productService))->update($id), [
	$auth,
	$permission('products.edit'),
]);
$router->get('/api/v1/products/{id}/stock', static fn (int $id): array => (new ProductApiController($productService))->stock($id), [
	$auth,
	$permission('stock.view'),
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
