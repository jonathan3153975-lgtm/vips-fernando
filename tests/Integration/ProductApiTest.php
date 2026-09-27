<?php

declare(strict_types=1);

namespace Tests\Integration;

final class ProductApiTest extends ApiIntegrationTestCase
{
    public function testProductLifecycleAndStockEndpoint(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status']);

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-001',
            'name' => 'Produto Teste',
            'description' => 'Produto de integracao',
            'unit' => 'UN',
            'price' => [
                'cost_price' => 100,
                'sale_price' => 150,
                'minimum_price' => 130,
                'margin' => 50,
            ],
        ]);

        $createPayload = $this->responseJson($create);
        $productId = $createPayload['data']['id'];

        self::assertSame(201, $create['status']);
        self::assertSame('PROD-001', $createPayload['data']['sku']);

        $update = $this->dispatchJson('PUT', '/api/v1/products/' . $productId, [
            'name' => 'Produto Teste Atualizado',
            'price' => [
                'cost_price' => 110,
                'sale_price' => 170,
                'minimum_price' => 140,
                'margin' => 54.55,
            ],
        ]);

        self::assertSame(200, $update['status']);
        self::assertSame('Produto Teste Atualizado', $this->responseJson($update)['data']['name']);

        $stock = $this->dispatchJson('GET', '/api/v1/products/' . $productId . '/stock');
        $stockPayload = $this->responseJson($stock);

        self::assertSame(200, $stock['status']);
        self::assertSame(0.0, (float) $stockPayload['data']['quantity']);

        $auditLogs = $this->fetchAllRows('SELECT action FROM audit_logs WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY id', [
            'entity_type' => 'product',
            'entity_id' => $productId,
        ]);

        self::assertSame([
            ['action' => 'products.create'],
            ['action' => 'products.update'],
        ], $auditLogs);
    }
}