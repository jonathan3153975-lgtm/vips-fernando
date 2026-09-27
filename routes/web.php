<?php

declare(strict_types=1);

use App\Core\Session;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ImportApiController;
use App\Controllers\Api\ProductApiController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\PermissionMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\ImportRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\HealthService;
use App\Services\ImportService;
use App\Services\PasswordResetService;
use App\Services\ProductService;

$router->get('/', static fn (): array => (new HomeController())->index());
$router->get('/health', static fn (): array => (new HealthController(new HealthService()))->show());

$session = new Session();
$auditLogRepository = new AuditLogRepository();
$userRepository = new UserRepository();
$authService = new AuthService($userRepository, $auditLogRepository, $session);
$passwordResetService = new PasswordResetService($userRepository, new PasswordResetRepository(), $auditLogRepository);
$importService = new ImportService(new ImportRepository(), $auditLogRepository);
$productService = new ProductService(new ProductRepository(), $auditLogRepository);

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
