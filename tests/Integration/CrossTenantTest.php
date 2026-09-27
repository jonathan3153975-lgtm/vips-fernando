<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\MissingTenantException;
use App\Core\UnscopedQueryException;
use App\Repositories\TenantScopedRepository;
use App\Repositories\UserRepository;

final class CrossTenantTest extends ApiIntegrationTestCase
{
    private const IMPORT_OTHER_TENANT = 10;

    public function testListNeverLeaksImportsOfAnotherTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/imports');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame([], $payload['data']);
        self::assertSame(0, $payload['meta']['total']);
    }

    public function testUpdateOfImportFromAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('PUT', '/api/v1/imports/' . self::IMPORT_OTHER_TENANT, [
            'name' => 'Sequestrado',
        ]);

        $payload = $this->responseJson($response);

        self::assertSame(404, $response['status']);
        self::assertSame('Importacao nao encontrada.', $payload['message']);
        self::assertSame('Importacao Tenant 2', $this->importSeed()['name']);
    }

    public function testAddExpenseToImportOfAnotherTenantReturnsNotFoundAndWritesNothing(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . self::IMPORT_OTHER_TENANT . '/expenses', [
            'category' => 'HOTEL',
            'description' => 'Despesa intrusa',
            'currency' => 'USD',
            'amount' => 999,
            'exchange_rate' => 5.40,
            'expense_date' => '2026-08-03',
        ]);

        self::assertSame(404, $response['status']);
        self::assertSame([], $this->rows('SELECT * FROM import_expenses WHERE import_id = :id', ['id' => self::IMPORT_OTHER_TENANT]));
        self::assertSame('PLANNED', $this->importSeed()['status']);
    }

    public function testAddItemToImportOfAnotherTenantReturnsNotFoundAndWritesNothing(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . self::IMPORT_OTHER_TENANT . '/items', [
            'product_name' => 'Produto intruso',
            'quantity' => 5,
            'unit_cost_foreign' => 10,
            'exchange_rate' => 5.40,
        ]);

        self::assertSame(404, $response['status']);
        self::assertSame([], $this->rows('SELECT * FROM import_items WHERE import_id = :id', ['id' => self::IMPORT_OTHER_TENANT]));
    }

    public function testCompleteOfImportFromAnotherTenantReturnsNotFoundAndWritesNothing(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . self::IMPORT_OTHER_TENANT . '/complete');

        self::assertSame(404, $response['status']);

        $import = $this->importSeed();
        self::assertSame('PLANNED', $import['status']);
        self::assertSame(0.0, (float) $import['invested_amount']);
        self::assertSame(0.0, (float) $import['total_expenses']);
        self::assertSame(0.0, (float) $import['total_items']);
    }

    public function testCrossTenantProductRoutesReturnNotFound(): void
    {
        $this->loginAs('admin2@example.com');

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-T2',
            'name' => 'Produto do Tenant 2',
        ]);

        self::assertSame(201, $create['status']);
        $productId = (int) $this->responseJson($create)['data']['id'];

        $this->loginAs('admin1@example.com');

        $update = $this->dispatchJson('PUT', '/api/v1/products/' . $productId, [
            'name' => 'Sequestrado',
        ]);

        $stock = $this->dispatchJson('GET', '/api/v1/products/' . $productId . '/stock');

        self::assertSame(404, $update['status']);
        self::assertSame('Produto nao encontrado.', $this->responseJson($update)['message']);
        self::assertSame(404, $stock['status']);
        self::assertSame('Produto nao encontrado.', $this->responseJson($stock)['message']);
        self::assertSame('Produto do Tenant 2', $this->productName($productId));
    }

    public function testProductListOfAnotherTenantIsEmpty(): void
    {
        $this->loginAs('admin2@example.com');
        $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'PROD-T2', 'name' => 'Produto do Tenant 2']);

        $this->loginAs('admin1@example.com');

        $response = $this->dispatchJson('GET', '/api/v1/products');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame([], $payload['data']);
    }

    public function testFindDoesNotReturnUserOfAnotherTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $users = new UserRepository();

        self::assertNotNull($users->find(1), 'Usuario do proprio tenant deve ser encontrado.');
        self::assertNull($users->find(3), 'Usuario de outro tenant nao pode ser encontrado.');
    }

    public function testPermissionsCannotBeReadForUserOfAnotherTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $users = new UserRepository();

        self::assertContains('imports.view', $users->permissionsForUser(1));
        self::assertSame([], $users->permissionsForUser(3));
    }

    public function testScopedRepositoryRefusesQueryWithoutTenantPredicate(): void
    {
        $this->loginAs('admin1@example.com');

        $repository = new class () extends TenantScopedRepository {
            public function semEscopo(): array
            {
                return $this->selectAll('SELECT * FROM users');
            }
        };

        $this->expectException(UnscopedQueryException::class);
        $this->expectExceptionMessageMatches('/UserRepository|anonymous/');

        $repository->semEscopo();
    }

    public function testScopedRepositoryFailsWithoutActiveTenant(): void
    {
        $repository = new class () extends TenantScopedRepository {
            public function listar(): array
            {
                return $this->selectAll('SELECT * FROM products WHERE tenant_id = :tenant_id');
            }
        };

        $this->expectException(MissingTenantException::class);

        $repository->listar();
    }

    private function loginAs(string $email): void
    {
        $_SESSION = [];

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'Login falhou para ' . $email);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql, array $params): array
    {
        return $this->fetchAllRows($sql, $params);
    }

    /**
     * @return array<string, mixed>
     */
    private function importSeed(): array
    {
        return $this->fetchOne('SELECT * FROM imports WHERE id = :id', ['id' => self::IMPORT_OTHER_TENANT]) ?? [];
    }

    private function productName(int $productId): string
    {
        $row = $this->fetchOne('SELECT name FROM products WHERE id = :id', ['id' => $productId]);

        return (string) ($row['name'] ?? '');
    }
}
