<?php

declare(strict_types=1);

namespace Tests\Integration;

final class ImportCostTest extends ApiIntegrationTestCase
{
    public function testValueAllocationSumsExactlyToExpenseTotal(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        for ($index = 0; $index < 3; $index++) {
            $this->addItem($importId, ['product_name' => 'Item ' . $index, 'quantity' => 1, 'unit_cost_foreign' => 100, 'exchange_rate' => 1]);
        }

        $this->addExpense($importId, ['amount' => 100, 'exchange_rate' => 1]);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        self::assertSame(200, $complete['status']);
        self::assertSame('COMPLETED', $this->responseJson($complete)['data']['status']);

        // 100,00 em 3 itens iguais: 33,33 + 33,33 + 33,34, sem residuo.
        $allocated = array_map(
            static fn (array $row): float => (float) $row['allocated_expense'],
            $this->fetchAllRows('SELECT allocated_expense FROM import_items WHERE import_id = :id ORDER BY id', ['id' => $importId]),
        );
        self::assertSame([33.33, 33.33, 33.34], $allocated);
        self::assertSame(100.0, round(array_sum($allocated), 2), 'A soma dos rateios deve ser exatamente o total.');
    }

    public function testQuantityAllocationUsesQuantity(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 100, 'exchange_rate' => 1]);
        $this->addItem($importId, ['product_name' => 'B', 'quantity' => 3, 'unit_cost_foreign' => 100, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['amount' => 100, 'exchange_rate' => 1]);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete', ['allocation_method' => 'QUANTITY']);
        self::assertSame(200, $complete['status']);

        $allocated = array_map(
            static fn (array $row): float => (float) $row['allocated_expense'],
            $this->fetchAllRows('SELECT allocated_expense FROM import_items WHERE import_id = :id ORDER BY id', ['id' => $importId]),
        );
        self::assertSame([25.0, 75.0], $allocated, 'Quantidade 1:3 recebe 25/75.');
        self::assertSame(100.0, round(array_sum($allocated), 2));
        self::assertSame('QUANTITY', $this->fetchOne('SELECT allocation_method FROM imports WHERE id = :id', ['id' => $importId])['allocation_method']);
    }

    public function testZeroExpensesLeavesAllocationAtZero(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 2, 'unit_cost_foreign' => 50, 'exchange_rate' => 1]);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        self::assertSame(200, $complete['status']);
        self::assertSame(0.0, (float) $this->responseJson($complete)['data']['total_expenses']);
        self::assertSame(100.0, (float) $this->responseJson($complete)['data']['invested_amount']);

        $item = $this->fetchOne('SELECT allocated_expense, real_unit_cost FROM import_items WHERE import_id = :id', ['id' => $importId]);
        self::assertSame(0.0, (float) $item['allocated_expense']);
        self::assertSame(50.0, (float) $item['real_unit_cost']);
    }

    public function testSingleItemAbsorbsTheWholeExpense(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['amount' => 9.99, 'exchange_rate' => 1]);

        $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        $item = $this->fetchOne('SELECT allocated_expense FROM import_items WHERE import_id = :id', ['id' => $importId]);
        self::assertSame(9.99, (float) $item['allocated_expense']);
    }

    public function testNegativeExpenseStillSumsExactly(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 100, 'exchange_rate' => 1]);
        $this->addItem($importId, ['product_name' => 'B', 'quantity' => 1, 'unit_cost_foreign' => 100, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['amount' => -10, 'exchange_rate' => 1]);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        self::assertSame(200, $complete['status']);

        $allocated = array_map(
            static fn (array $row): float => (float) $row['allocated_expense'],
            $this->fetchAllRows('SELECT allocated_expense FROM import_items WHERE import_id = :id ORDER BY id', ['id' => $importId]),
        );
        self::assertSame(-10.0, round(array_sum($allocated), 2));
    }

    public function testMultipleCurrenciesUseConvertedAmounts(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 100, 'exchange_rate' => 2]);
        $this->addExpense($importId, ['currency' => 'EUR', 'amount' => 10, 'exchange_rate' => 5]);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        $data = $this->responseJson($complete)['data'];

        self::assertSame(50.0, (float) $data['total_expenses'], 'A despesa entra pelo converted_amount.');
        self::assertSame(250.0, (float) $data['invested_amount'], '200 de item + 50 de despesa.');
    }

    public function testCompleteWithoutItemsIsRejectedAndWritesNothing(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('ao menos um item', $this->responseJson($response)['message']);

        $import = $this->fetchOne('SELECT status, completed_at, invested_amount FROM imports WHERE id = :id', ['id' => $importId]);
        self::assertSame('PLANNED', $import['status']);
        self::assertNull($import['completed_at']);
        self::assertSame(0.0, (float) $import['invested_amount']);
    }

    public function testCompleteRejectsUnknownAllocationMethod(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete', ['allocation_method' => 'PESO']);

        self::assertSame(400, $response['status']);
        self::assertSame('PLANNED', $this->fetchOne('SELECT status FROM imports WHERE id = :id', ['id' => $importId])['status']);
    }

    public function testCompletedImportIsFrozenAndCannotBeEdited(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['amount' => 5, 'exchange_rate' => 1]);

        $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        $import = $this->fetchOne('SELECT status, completed_at, total_expenses FROM imports WHERE id = :id', ['id' => $importId]);
        self::assertSame('COMPLETED', $import['status']);
        self::assertNotNull($import['completed_at'], 'O congelamento precisa registrar o instante.');
        self::assertSame(5.0, (float) $import['total_expenses']);

        self::assertSame(400, $this->dispatchJson('PUT', '/api/v1/imports/' . $importId, ['name' => 'Novo'])['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/items', $this->itemPayload())['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', $this->expensePayload())['status']);

        // Os valores congelados nao mudam com as tentativas.
        self::assertSame(5.0, (float) $this->fetchOne('SELECT total_expenses FROM imports WHERE id = :id', ['id' => $importId])['total_expenses']);
        self::assertSame(5.0, (float) $this->fetchOne('SELECT allocated_expense FROM import_items WHERE import_id = :id', ['id' => $importId])['allocated_expense']);
    }

    public function testCompleteTwiceIsRejected(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);
        $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('ja concluida', $this->responseJson($response)['message']);
    }

    public function testReopenAllowsRecalculation(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['amount' => 5, 'exchange_rate' => 1]);
        $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        $reopen = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/reopen');
        self::assertSame(200, $reopen['status']);
        self::assertSame('IN_PROGRESS', $this->responseJson($reopen)['data']['status']);
        self::assertNull($this->fetchOne('SELECT completed_at FROM imports WHERE id = :id', ['id' => $importId])['completed_at']);

        // Edicao volta a funcionar e o novo fechamento recalcula.
        $this->addItem($importId, ['product_name' => 'B', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);
        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        self::assertSame(200, $complete['status']);

        $allocated = $this->fetchAllRows('SELECT allocated_expense FROM import_items WHERE import_id = :id ORDER BY id', ['id' => $importId]);
        self::assertSame(5.0, round(array_sum(array_map(static fn (array $r): float => (float) $r['allocated_expense'], $allocated)), 2));
    }

    public function testReopenNonCompletedIsRejected(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/reopen');

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('concluida', $this->responseJson($response)['message']);
    }

    public function testCancelledImportCannotBeEditedOrCompleted(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10, 'exchange_rate' => 1]);

        self::assertSame(200, $this->dispatchJson('PUT', '/api/v1/imports/' . $importId, ['status' => 'CANCELLED'])['status']);

        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/items', $this->itemPayload())['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);
    }

    public function testUpdateCannotSetCompletedStatusDirectly(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $response = $this->dispatchJson('PUT', '/api/v1/imports/' . $importId, ['status' => 'COMPLETED']);

        self::assertSame(400, $response['status']);
        self::assertSame('PLANNED', $this->fetchOne('SELECT status FROM imports WHERE id = :id', ['id' => $importId])['status']);
    }

    public function testExchangeRateCrudAndUpsert(): void
    {
        $this->loginAs('admin1@example.com');

        $created = $this->dispatchJson('POST', '/api/v1/exchange-rates', [
            'currency' => 'usd', 'rate' => 5.20, 'reference_date' => '2026-08-01', 'source' => 'Manual',
        ]);
        self::assertSame(201, $created['status']);
        self::assertSame('USD', $this->responseJson($created)['data']['currency']);

        $updated = $this->dispatchJson('POST', '/api/v1/exchange-rates', [
            'currency' => 'USD', 'rate' => 5.30, 'reference_date' => '2026-08-01',
        ]);
        self::assertSame(201, $updated['status']);
        self::assertSame(5.30, (float) $this->responseJson($updated)['data']['rate'], 'Mesma moeda e data atualiza a cotacao.');

        $list = $this->dispatchJson('GET', '/api/v1/exchange-rates');
        self::assertCount(1, $this->responseJson($list)['data'], 'Nao duplica (tenant, moeda, data).');
    }

    public function testExchangeRateRejectsInvalidInput(): void
    {
        $this->loginAs('admin1@example.com');

        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'DOLAR', 'rate' => 5, 'reference_date' => '2026-08-01'])['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'USD', 'rate' => 0, 'reference_date' => '2026-08-01'])['status']);
        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'USD', 'rate' => 5, 'reference_date' => '01/08/2026'])['status']);
    }

    public function testExpenseUsesRateInEffectOnTheDateWhenNotInformed(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'USD', 'rate' => 5.00, 'reference_date' => '2026-08-01']);
        $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'USD', 'rate' => 5.50, 'reference_date' => '2026-08-10']);

        $expense = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', [
            'category' => 'HOTEL', 'description' => 'Hospedagem', 'currency' => 'USD',
            'amount' => 10, 'expense_date' => '2026-08-05',
        ]);

        self::assertSame(201, $expense['status']);
        self::assertSame(5.00, (float) $this->responseJson($expense)['data']['exchange_rate'], 'Usa a cotacao vigente em 05/08 (5,00).');
        self::assertSame(50.0, (float) $this->responseJson($expense)['data']['converted_amount']);
    }

    public function testItemUsesRateFromImportCurrencyAndStartDate(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport(['start_date' => '2026-08-05']);
        $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'USD', 'rate' => 5.00, 'reference_date' => '2026-08-01']);

        $item = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/items', [
            'product_name' => 'A', 'quantity' => 1, 'unit_cost_foreign' => 10,
        ]);

        self::assertSame(201, $item['status']);
        self::assertSame(5.00, (float) $this->responseJson($item)['data']['exchange_rate']);
        self::assertSame(50.0, (float) $this->responseJson($item)['data']['total_cost_local']);
    }

    public function testExpenseWithoutAnyRateIsRejected(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', [
            'category' => 'HOTEL', 'description' => 'Hospedagem', 'currency' => 'GBP',
            'amount' => 10, 'expense_date' => '2026-08-05',
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Cotacao', $this->responseJson($response)['message']);
    }

    public function testSupplierCrudAndDuplicateName(): void
    {
        $this->loginAs('admin1@example.com');

        $created = $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Fornecedor A', 'country' => 'US']);
        self::assertSame(201, $created['status']);
        $supplierId = (int) $this->responseJson($created)['data']['id'];

        self::assertSame(400, $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Fornecedor A'])['status'], 'Nome unico por tenant.');

        $list = $this->dispatchJson('GET', '/api/v1/suppliers');
        self::assertCount(1, $this->responseJson($list)['data']);

        $show = $this->dispatchJson('GET', '/api/v1/suppliers/' . $supplierId);
        self::assertSame('Fornecedor A', $this->responseJson($show)['data']['name']);

        $updated = $this->dispatchJson('PUT', '/api/v1/suppliers/' . $supplierId, ['city' => 'Miami']);
        self::assertSame('Miami', $this->responseJson($updated)['data']['city']);
    }

    public function testSameSupplierNameInAnotherTenantIsAllowed(): void
    {
        $this->loginAs('admin1@example.com');
        $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Global Supplier']);

        $this->loginAs('admin2@example.com');
        $created = $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Global Supplier']);

        self::assertSame(201, $created['status'], 'Unicidade de fornecedor e por tenant.');
    }

    public function testExpenseRejectsSupplierFromAnotherTenant(): void
    {
        $this->loginAs('admin2@example.com');
        $foreignSupplierId = (int) $this->responseJson(
            $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Fornecedor Tenant 2']),
        )['data']['id'];

        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', $this->expensePayload() + ['supplier_id' => $foreignSupplierId]);

        self::assertSame(400, $response['status']);
        self::assertSame('Fornecedor invalido.', $this->responseJson($response)['message']);
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM import_expenses WHERE import_id = :id', ['id' => $importId])['total']);
    }

    public function testExpenseAcceptsSupplierFromOwnTenant(): void
    {
        $this->loginAs('admin1@example.com');
        $supplierId = (int) $this->responseJson(
            $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'Fornecedor Local']),
        )['data']['id'];

        $importId = $this->createImport();
        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', $this->expensePayload() + ['supplier_id' => $supplierId]);

        self::assertSame(201, $response['status']);
        self::assertSame($supplierId, (int) $this->responseJson($response)['data']['supplier_id']);
    }

    public function testExpenseListingSupportsFiltersAndPagination(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();

        $this->addExpense($importId, ['category' => 'HOTEL', 'amount' => 1, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['category' => 'FOOD', 'amount' => 2, 'exchange_rate' => 1]);
        $this->addExpense($importId, ['category' => 'HOTEL', 'amount' => 3, 'exchange_rate' => 1]);

        $all = $this->dispatchJson('GET', '/api/v1/imports/' . $importId . '/expenses');
        self::assertSame(3, $this->responseJson($all)['meta']['total']);

        $filtered = $this->dispatchJson('GET', '/api/v1/imports/' . $importId . '/expenses?category=HOTEL');
        self::assertSame(2, $this->responseJson($filtered)['meta']['total']);

        $paged = $this->dispatchJson('GET', '/api/v1/imports/' . $importId . '/expenses?per_page=1&page=2');
        $payload = $this->responseJson($paged);
        self::assertCount(1, $payload['data']);
        self::assertSame(2, $payload['meta']['page']);
        self::assertSame(3, $payload['meta']['total']);
    }

    public function testItemListingSupportsSkuFilter(): void
    {
        $this->loginAs('admin1@example.com');
        $importId = $this->createImport();
        $this->addItem($importId, ['product_name' => 'A', 'sku' => 'SKU-A', 'quantity' => 1, 'unit_cost_foreign' => 1, 'exchange_rate' => 1]);
        $this->addItem($importId, ['product_name' => 'B', 'sku' => 'SKU-B', 'quantity' => 1, 'unit_cost_foreign' => 1, 'exchange_rate' => 1]);

        $filtered = $this->dispatchJson('GET', '/api/v1/imports/' . $importId . '/items?sku=SKU-B');

        self::assertSame(1, $this->responseJson($filtered)['meta']['total']);
        self::assertSame('B', $this->responseJson($filtered)['data'][0]['product_name']);
    }

    public function testExpenseListingOfAnotherTenantReturnsNotFound(): void
    {
        $this->loginAs('admin1@example.com');

        // Importacao id 10 pertence ao tenant 2 (fixture).
        self::assertSame(404, $this->dispatchJson('GET', '/api/v1/imports/10/expenses')['status']);
        self::assertSame(404, $this->dispatchJson('GET', '/api/v1/imports/10/items')['status']);
    }

    public function testExchangeRatesAndSuppliersAreIsolatedByTenant(): void
    {
        $this->loginAs('admin2@example.com');
        $this->dispatchJson('POST', '/api/v1/exchange-rates', ['currency' => 'EUR', 'rate' => 6.10, 'reference_date' => '2026-08-01']);
        $this->dispatchJson('POST', '/api/v1/suppliers', ['name' => 'So do Tenant 2']);

        $this->loginAs('admin1@example.com');
        self::assertSame([], $this->responseJson($this->dispatchJson('GET', '/api/v1/exchange-rates'))['data']);
        self::assertSame([], $this->responseJson($this->dispatchJson('GET', '/api/v1/suppliers'))['data']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createImport(array $overrides = []): int
    {
        $response = $this->dispatchJson('POST', '/api/v1/imports', $overrides + [
            'name' => 'Importacao Teste',
            'country' => 'US',
            'start_date' => '2026-08-01',
            'currency' => 'USD',
            'exchange_rate' => 5.00,
        ]);

        self::assertSame(201, $response['status'], 'Falha ao criar importacao: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function addItem(int $importId, array $overrides): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/items', $overrides + [
            'product_name' => 'Item',
            'quantity' => 1,
            'unit_cost_foreign' => 10,
            'exchange_rate' => 1,
        ]);

        self::assertSame(201, $response['status'], 'Falha ao adicionar item: ' . $response['body']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function addExpense(int $importId, array $overrides): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', $overrides + $this->expensePayload());

        self::assertSame(201, $response['status'], 'Falha ao adicionar despesa: ' . $response['body']);
    }

    /**
     * @return array<string, mixed>
     */
    private function expensePayload(): array
    {
        return [
            'category' => 'HOTEL',
            'description' => 'Hospedagem',
            'currency' => 'USD',
            'amount' => 10,
            'expense_date' => '2026-08-02',
            'exchange_rate' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(): array
    {
        return ['product_name' => 'Novo', 'quantity' => 1, 'unit_cost_foreign' => 5, 'exchange_rate' => 1];
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
}
