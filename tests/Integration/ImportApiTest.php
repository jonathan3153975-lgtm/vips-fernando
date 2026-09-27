<?php

declare(strict_types=1);

namespace Tests\Integration;

final class ImportApiTest extends ApiIntegrationTestCase
{
    public function testRbacBlocksUserWithoutCreatePermission(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'viewer1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status']);

        $response = $this->dispatchJson('POST', '/api/v1/imports', [
            'name' => 'Importacao sem permissao',
            'country' => 'US',
            'start_date' => '2026-08-10',
            'currency' => 'USD',
            'exchange_rate' => 5.22,
        ]);

        $payload = $this->responseJson($response);

        self::assertSame(403, $response['status']);
        self::assertSame('Voce nao possui permissao para acessar este recurso.', $payload['message']);
    }

    public function testImportsListIsIsolatedByTenant(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status']);

        $create = $this->dispatchJson('POST', '/api/v1/imports', [
            'name' => 'Importacao Tenant 1',
            'description' => 'Criada em teste',
            'country' => 'US',
            'city' => 'Orlando',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-20',
            'currency' => 'USD',
            'exchange_rate' => 5.31,
        ]);

        self::assertSame(201, $create['status']);

        $response = $this->dispatchJson('GET', '/api/v1/imports');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertCount(1, $payload['data']);
        self::assertSame('Importacao Tenant 1', $payload['data'][0]['name']);
        self::assertSame(1, $payload['data'][0]['tenant_id']);
    }

    public function testImportFlowCreatesAuditAndCompletesOperation(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status']);

        $create = $this->dispatchJson('POST', '/api/v1/imports', [
            'name' => 'Importacao Completa',
            'description' => 'Fluxo completo',
            'country' => 'US',
            'city' => 'Miami',
            'start_date' => '2026-08-21',
            'end_date' => '2026-08-28',
            'currency' => 'USD',
            'exchange_rate' => 5.50,
        ]);

        $createPayload = $this->responseJson($create);
        $importId = $createPayload['data']['id'];

        $expense = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/expenses', [
            'category' => 'HOTEL',
            'description' => 'Hospedagem',
            'currency' => 'USD',
            'amount' => 100,
            'exchange_rate' => 5.50,
            'expense_date' => '2026-08-22',
        ]);

        self::assertSame(201, $expense['status']);

        $item = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/items', [
            'product_name' => 'AirPods Pro',
            'sku' => 'AIRPODS-PRO',
            'quantity' => 2,
            'unit_cost_foreign' => 200,
            'exchange_rate' => 5.50,
        ]);

        self::assertSame(201, $item['status']);

        $complete = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');
        $completePayload = $this->responseJson($complete);

        self::assertSame(200, $complete['status']);
        self::assertSame('COMPLETED', $completePayload['data']['status']);
        self::assertSame(550.0, (float) $completePayload['data']['total_expenses']);
        self::assertSame(2.0, (float) $completePayload['data']['total_items']);
        self::assertSame(2750.0, (float) $completePayload['data']['invested_amount']);

        $importItem = $this->fetchOne('SELECT allocated_expense, real_unit_cost FROM import_items WHERE import_id = :import_id', ['import_id' => $importId]);
        self::assertNotNull($importItem);
        self::assertSame(550.0, (float) $importItem['allocated_expense']);
        self::assertSame(1375.0, (float) $importItem['real_unit_cost']);

        $auditLogs = $this->fetchAllRows('SELECT action FROM audit_logs WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY id', [
            'entity_type' => 'import',
            'entity_id' => $importId,
        ]);

        self::assertSame([
            ['action' => 'imports.create'],
            ['action' => 'imports.expense.create'],
            ['action' => 'imports.item.create'],
            ['action' => 'imports.complete'],
        ], $auditLogs);
    }
}

