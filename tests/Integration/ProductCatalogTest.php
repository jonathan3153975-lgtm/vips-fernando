<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;

/**
 * Etapa 4 — Catalogo, produtos e precos.
 *
 * Cobre: CRUD de categorias e marcas (com guarda de ciclo na arvore de
 * categorias), vinculo de produto com item de importacao e preenchimento
 * automatico do custo real, formacao de preco sugerido (mark-up sobre custo),
 * listagem com filtros e paginacao, bloqueio de exclusao com estoque/historico,
 * e isolamento entre tenants.
 */
final class ProductCatalogTest extends ApiIntegrationTestCase
{
    /**
     * GuestMiddleware recusa login com sessao ativa (409), entao trocar de tenant
     * exige sair antes.
     */
    private function logout(): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/logout');

        self::assertContains($response['status'], [200, 204], 'logout deve encerrar a sessao');
    }

    private function loginAsAdminOne(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status'], 'login do admin do tenant 1');
    }

    private function loginAsAdminTwo(): void
    {
        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin2@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $login['status'], 'login do admin do tenant 2');
    }

    private function switchToTenantTwo(): void
    {
        $this->logout();
        $this->loginAsAdminTwo();
    }

    // ---------------------------------------------------------------- categorias

    public function testCreatesCategoryAndListsIt(): void
    {
        $this->loginAsAdminOne();

        $create = $this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Vestuario']);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertSame('Vestuario', $payload['data']['name']);
        self::assertSame('ACTIVE', $payload['data']['status']);
        self::assertSame(1, $payload['data']['tenant_id']);

        $list = $this->dispatchJson('GET', '/api/v1/categories');
        $listPayload = $this->responseJson($list);

        self::assertSame(200, $list['status']);
        self::assertCount(1, $listPayload['data']);
    }

    public function testRejectsDuplicateCategoryName(): void
    {
        $this->loginAsAdminOne();

        $this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Vestuario']);
        $duplicate = $this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Vestuario']);

        self::assertSame(400, $duplicate['status']);
    }

    public function testRejectsCategoryWithoutName(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/categories', ['name' => '   ']);

        self::assertSame(400, $response['status']);
    }

    public function testCategoryParentMustBelongToSameTenant(): void
    {
        $this->loginAsAdminTwo();
        // A categoria do tenant 1 existe, mas o tenant 2 nao pode referencia-la.
        $this->execSql(
            "INSERT INTO categories (id, tenant_id, name, status, created_at, updated_at) VALUES (50, 1, 'Categoria Tenant 1', 'ACTIVE', '', '')"
        );

        $response = $this->dispatchJson('POST', '/api/v1/categories', [
            'name' => 'Com pai alheio',
            'parent_id' => 50,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('pai nao encontrada', $this->responseJson($response)['message']);
    }

    public function testRejectsCategoryAsItsOwnParent(): void
    {
        $this->loginAsAdminOne();

        $create = $this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Circular']);
        $categoryId = (int) $this->responseJson($create)['data']['id'];

        $response = $this->dispatchJson('PUT', '/api/v1/categories/' . $categoryId, [
            'parent_id' => $categoryId,
        ]);

        self::assertSame(400, $response['status']);
    }

    public function testRejectsMovingCategoryBelowItsOwnDescendant(): void
    {
        $this->loginAsAdminOne();

        $parent = $this->responseJson($this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Pai']))['data'];
        $child = $this->responseJson($this->dispatchJson('POST', '/api/v1/categories', [
            'name' => 'Filho',
            'parent_id' => $parent['id'],
        ]))['data'];

        // Tentar colocar o pai abaixo do filho formaria um ciclo.
        $response = $this->dispatchJson('PUT', '/api/v1/categories/' . $parent['id'], [
            'parent_id' => $child['id'],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('para baixo dela mesma', $this->responseJson($response)['message']);
    }

    public function testCategoryListIsIsolatedByTenant(): void
    {
        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/categories', ['name' => 'So Tenant 1']);

        $this->switchToTenantTwo();
        $list = $this->dispatchJson('GET', '/api/v1/categories');
        $payload = $this->responseJson($list);

        self::assertSame(200, $list['status']);
        self::assertSame([], $payload['data'], 'categoria do tenant 1 nao pode vazar para o tenant 2');
    }

    // -------------------------------------------------------------------- marcas

    public function testCreatesBrandAndListsIt(): void
    {
        $this->loginAsAdminOne();

        $create = $this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Marca Alfa']);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertSame('Marca Alfa', $payload['data']['name']);

        $list = $this->responseJson($this->dispatchJson('GET', '/api/v1/brands'));

        self::assertCount(1, $list['data']);
    }

    public function testRejectsDuplicateBrandName(): void
    {
        $this->loginAsAdminOne();

        $this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Marca Alfa']);
        $duplicate = $this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Marca Alfa']);

        self::assertSame(400, $duplicate['status']);
    }

    public function testRenamesBrand(): void
    {
        $this->loginAsAdminOne();

        $brandId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Velha']))['data']['id'];

        $response = $this->dispatchJson('PUT', '/api/v1/brands/' . $brandId, ['name' => 'Nova']);
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame('Nova', $payload['data']['name']);
    }

    // -------------------------------------------------- vinculo com custo real

    public function testProductLinkedToImportItemGetsRealCostPrice(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Camiseta', 'sku' => 'CAM-1', 'unit_cost_local' => 60.00, 'real_unit_cost' => 75.00],
        ]);

        $itemId = (int) $this->fetchOne(
            'SELECT id FROM import_items WHERE tenant_id = 1 AND import_id = ?',
            [$importId],
        )['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-1',
            'name' => 'Camiseta Basica',
            'default_import_item_id' => $itemId,
        ]);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertEquals($itemId, $payload['data']['default_import_item_id']);
        self::assertEquals(75.00, (float) $payload['data']['cost_price'], 'custo real rateado vira cost_price');
    }

    public function testExplicitCostPriceWinsOverImportedRealCost(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 60.00, 'real_unit_cost' => 75.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-MANUAL',
            'name' => 'Com custo manual',
            'default_import_item_id' => $itemId,
            'price' => ['cost_price' => 10.00, 'sale_price' => 20.00],
        ]);
        $payload = $this->responseJson($create);

        self::assertEquals(10.00, (float) $payload['data']['cost_price'], 'custo informado manualmente tem precedencia');
    }

    public function testProductKeepsZeroCostWhenImportIsNotCompleted(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Item', 'unit_cost_local' => 60.00, 'real_unit_cost' => 75.00]],
            'IN_PROGRESS'
        );
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-ABERTA',
            'name' => 'Importacao nao concluida',
            'default_import_item_id' => $itemId,
        ]);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertEquals(0.0, (float) $payload['data']['cost_price'], 'sem congelamento nao existe custo real');
    }

    public function testRejectsProductLinkedToImportItemOfAnotherTenant(): void
    {
        $this->loginAsAdminOne();

        // Item pertencente ao tenant 2.
        $importId = $this->seedCompletedImport(2, [
            ['product_name' => 'Item alheio', 'unit_cost_local' => 10.00, 'real_unit_cost' => 10.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $response = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-X',
            'name' => 'Produto cross-tenant',
            'default_import_item_id' => $itemId,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Item de importacao nao encontrado', $this->responseJson($response)['message']);
    }

    public function testRejectsProductWithCategoryOfAnotherTenant(): void
    {
        $this->loginAsAdminOne();

        $this->execSql(
            "INSERT INTO categories (id, tenant_id, name, status, created_at, updated_at) VALUES (60, 2, 'Categoria Tenant 2', 'ACTIVE', '', '')"
        );

        $response = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PROD-Y',
            'name' => 'Produto cross-tenant',
            'category_id' => 60,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Categoria nao encontrada', $this->responseJson($response)['message']);
    }

    public function testRejectsDuplicateSku(): void
    {
        $this->loginAsAdminOne();

        $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'DUP', 'name' => 'Primeiro']);
        $duplicate = $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'DUP', 'name' => 'Segundo']);

        self::assertSame(400, $duplicate['status']);
        self::assertStringContainsString('SKU', $this->responseJson($duplicate)['message']);
    }

    // ------------------------------------------------------------- precificacao

    public function testPriceSuggestionUsesMarkupOnCost(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 1000.00, 'real_unit_cost' => 1000.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PRICE-1',
            'name' => 'Produto caro',
            'default_import_item_id' => $itemId,
        ]))['data']['id'];

        $response = $this->dispatchJson('GET', '/api/v1/products/' . $productId . '/price-suggestion?margin=30');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertEquals(1000.00, (float) $payload['data']['cost_price']);
        self::assertEquals(1300.00, (float) $payload['data']['sale_price'], 'custo 1000 + 30% = 1300');
        self::assertEquals(30.00, (float) $payload['data']['margin']);
    }

    public function testPriceSuggestionHonoursMinimumPrice(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 500.00, 'real_unit_cost' => 500.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PRICE-2',
            'name' => 'Produto com minimo',
            'default_import_item_id' => $itemId,
        ]))['data']['id'];

        $payload = $this->responseJson($this->dispatchJson(
            'GET',
            '/api/v1/products/' . $productId . '/price-suggestion?margin=50&minimum_price=400'
        ));

        self::assertEquals(750.00, (float) $payload['data']['sale_price']);
        self::assertEquals(400.00, (float) $payload['data']['minimum_price'], 'minimo informado tem precedencia');
    }

    public function testPriceSuggestionFailsWithoutCompletedImport(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PRICE-3',
            'name' => 'Sem importacao',
        ]))['data']['id'];

        $response = $this->dispatchJson('GET', '/api/v1/products/' . $productId . '/price-suggestion?margin=30');
        $payload = $this->responseJson($response);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('custo real ainda nao existe', $payload['message']);
    }

    public function testPriceSuggestionRejectsNegativeMargin(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 100.00, 'real_unit_cost' => 100.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'PRICE-4',
            'name' => 'Margem negativa',
            'default_import_item_id' => $itemId,
        ]))['data']['id'];

        $response = $this->dispatchJson('GET', '/api/v1/products/' . $productId . '/price-suggestion?margin=-10');

        self::assertSame(400, $response['status']);
    }

    public function testPriceSuggestionIsRejectedForProductOfAnotherTenant(): void
    {
        $this->loginAsAdminTwo();

        $response = $this->dispatchJson('GET', '/api/v1/products/999999/price-suggestion?margin=30');

        self::assertSame(404, $response['status']);
    }
    // ------------------------------------------------------ listagem e filtros

    public function testProductListIsFilteredByCategoryBrandStatusAndSearch(): void
    {
        $this->loginAsAdminOne();

        $categoryId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Calcados']))['data']['id'];
        $brandId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Nike']))['data']['id'];

        $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'TENIS-1',
            'name' => 'Tênis Esportivo',
            'category_id' => $categoryId,
            'brand_id' => $brandId,
        ]);
        $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'CAMISA-1', 'name' => 'Camisa Polo']);
        $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'RETI-1', 'name' => 'Retirado', 'status' => 'INACTIVE']);

        $byCategory = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?category_id=' . $categoryId));
        self::assertCount(1, $byCategory['data']);
        self::assertSame('TENIS-1', $byCategory['data'][0]['sku']);

        $byBrand = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?brand_id=' . $brandId));
        self::assertCount(1, $byBrand['data']);

        $byStatus = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?status=INACTIVE'));
        self::assertCount(1, $byStatus['data']);
        self::assertSame('RETI-1', $byStatus['data'][0]['sku']);

        $byName = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?search=Tênis'));
        self::assertCount(1, $byName['data']);
        self::assertSame('TENIS-1', $byName['data'][0]['sku']);

        $bySku = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?search=CAMISA'));
        self::assertCount(1, $bySku['data']);

        $combined = $this->responseJson($this->dispatchJson(
            'GET',
            '/api/v1/products?category_id=' . $categoryId . '&brand_id=' . $brandId . '&search=Esportivo'
        ));
        self::assertCount(1, $combined['data']);
    }

    public function testProductListIsPaginated(): void
    {
        $this->loginAsAdminOne();

        for ($index = 1; $index <= 5; $index++) {
            $this->dispatchJson('POST', '/api/v1/products', [
                'sku' => 'PAG-' . $index,
                'name' => 'Produto ' . $index,
            ]);
        }

        $first = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?per_page=2&page=1'));
        self::assertCount(2, $first['data']);
        self::assertSame(5, $first['meta']['total']);
        self::assertSame(1, $first['meta']['page']);
        self::assertSame(2, $first['meta']['per_page']);

        $last = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?per_page=2&page=3'));
        self::assertCount(1, $last['data']);
        self::assertSame(5, $last['meta']['total']);

        $ids = [];
        foreach ([1, 2, 3] as $page) {
            foreach ($this->responseJson($this->dispatchJson('GET', '/api/v1/products?per_page=2&page=' . $page))['data'] as $row) {
                $ids[] = $row['id'];
            }
        }

        self::assertCount(5, $ids, 'as paginas devem cobrir todos os registros sem repetir');
        self::assertSame($ids, array_values(array_unique($ids)));
    }

    public function testProductListIsIsolatedByTenant(): void
    {
        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'T1-1', 'name' => 'Produto tenant 1']);

        $this->switchToTenantTwo();
        $payload = $this->responseJson($this->dispatchJson('GET', '/api/v1/products'));

        self::assertSame([], $payload['data']);
        self::assertSame(0, $payload['meta']['total']);
    }

    public function testPerPageIsCapped(): void
    {
        $this->loginAsAdminOne();

        $payload = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?per_page=5000'));

        self::assertSame(100, $payload['meta']['per_page']);
    }

    /**
     * Regressao: a busca repetia o mesmo placeholder em nome e SKU, o que quebra
     * com ATTR_EMULATE_PREPARES = false (HY093). No SQLite o SQLite reutiliza o
     * placeholder e o bug passava; so o MySQL com prepares nativos acusa.
     */
    public function testSearchFilterDoesNotRepeatTheSameSqlPlaceholder(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/app/Repositories/ProductRepository.php');

        self::assertMatchesRegularExpression(
            '/LIKE :search_name.*LIKE :search_sku/s',
            $source,
            'nome e sku devem usar placeholders distintos'
        );
        self::assertDoesNotMatchRegularExpression(
            '/LIKE :search\s+OR\s+p\.sku LIKE :search\b/',
            $source,
            'repetir :search quebra com prepares nativos'
        );
    }

    // ------------------------------------------------- exclusao x desativacao

    public function testDeleteSucceedsForProductWithoutStockOrHistory(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-1',
            'name' => 'Produto descartavel',
        ]))['data']['id'];

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame('INACTIVE', $payload['data']['status'], 'sem historico, a exclusao desativa');
    }

    public function testDeleteIsBlockedWhenProductHasStock(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-2',
            'name' => 'Produto com estoque',
        ]))['data']['id'];

        $this->execSql('UPDATE stock SET quantity = 5 WHERE tenant_id = 1 AND product_id = ?', [$productId]);

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);
        $payload = $this->responseJson($response);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('estoque', $payload['message']);
    }

    public function testDeleteIsBlockedWhenProductHasStockMovements(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-3',
            'name' => 'Produto movimentado',
        ]))['data']['id'];

        $this->execSql(
            'INSERT INTO stock_movements (tenant_id, product_id, type, quantity, balance_after, reference_type, reference_id, created_at)
             VALUES (1, ?, \'IN\', 3, 3, \'IMPORT\', 1, \'2026-08-01 10:00:00\')',
            [$productId],
        );

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);
        $payload = $this->responseJson($response);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('movimentacao', $payload['message']);
    }

    public function testDeleteIsBlockedWhenProductHasSales(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-4',
            'name' => 'Produto vendido',
        ]))['data']['id'];

        $this->execSql(
            "INSERT INTO sales (id, tenant_id, user_id, sale_number, status, subtotal, total, sale_date, created_at, updated_at) VALUES (70, 1, 1, 'VND-70', 'COMPLETED', 100.00, 100.00, '2026-08-01', '2026-08-01', '2026-08-01')"
        );
        $this->execSql(
            'INSERT INTO sale_items (sale_id, product_id, quantity, cost_price, sale_price, subtotal, created_at)
             VALUES (70, ?, 1, 60.00, 100.00, 100.00, \'2026-08-01\')',
            [$productId],
        );

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);
        $payload = $this->responseJson($response);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('vendas', $payload['message']);
    }

    /**
     * Regressao: sale_items nao possui tenant_id (o escopo vem de sales). Uma
     * verificacao que usasse si.tenant_id quebrava no MySQL real e era aceita
     * pelo SQLite quando o schema de teste divergia do schema real.
     */
    public function testDeleteGuardWorksWithoutTenantColumnOnSaleItems(): void
    {
        $columns = $this->fetchColumnList('sale_items');

        self::assertNotContains('tenant_id', $columns, 'sale_items nao deve ter tenant_id no schema');

        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-SCHEMA',
            'name' => 'Produto com venda via sales',
        ]))['data']['id'];

        $this->execSql(
            "INSERT INTO sales (id, tenant_id, user_id, sale_number, status, subtotal, total, sale_date, created_at, updated_at) VALUES (71, 1, 1, 'VND-71', 'COMPLETED', 50.00, 50.00, '2026-08-02', '2026-08-02', '2026-08-02')"
        );
        $this->execSql(
            'INSERT INTO sale_items (sale_id, product_id, quantity, cost_price, sale_price, subtotal, created_at)
             VALUES (71, ?, 1, 30.00, 50.00, 50.00, \'2026-08-02\')',
            [$productId],
        );

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);

        self::assertSame(400, $response['status'], 'a venda deve bloquear a exclusao pelo caminho de sales');
    }

    public function testDeleteIsBlockedWhenSaleBelongsToAnotherTenantOnly(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-CROSS',
            'name' => 'Produto sem venda propria',
        ]))['data']['id'];

        // Venda de outro tenant para o MESMO id de produto: nao deve bloquear,
        // provando que o escopo desce por sales.tenant_id.
        $this->execSql(
            "INSERT INTO sales (id, tenant_id, user_id, sale_number, status, subtotal, total, sale_date, created_at, updated_at) VALUES (72, 2, 3, 'VND-72', 'COMPLETED', 10.00, 10.00, '2026-08-03', '2026-08-03', '2026-08-03')"
        );

        $response = $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);

        self::assertSame(200, $response['status'], 'venda de outro tenant nao bloqueia o produto');
    }

    public function testBlockedDeleteKeepsProductActive(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'DEL-5',
            'name' => 'Produto preservado',
        ]))['data']['id'];

        $this->execSql('UPDATE stock SET quantity = 2 WHERE tenant_id = 1 AND product_id = ?', [$productId]);
        $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);

        $row = $this->fetchOne('SELECT status FROM products WHERE id = ?', [$productId]);

        self::assertSame('ACTIVE', $row['status'], 'exclusao bloqueada nao pode desativar o produto');
    }

    public function testDeactivationKeepsProductVisibleWithStatusFilter(): void
    {
        $this->loginAsAdminOne();

        $productId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'OFF-1',
            'name' => 'Produto desativado',
        ]))['data']['id'];

        $this->execSql('UPDATE stock SET quantity = 1 WHERE tenant_id = 1 AND product_id = ?', [$productId]);
        $this->dispatchJson('DELETE', '/api/v1/products/' . $productId);

        // Desativacao explicita pelo update.
        $this->dispatchJson('PUT', '/api/v1/products/' . $productId, ['status' => 'INACTIVE']);

        $active = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?status=ACTIVE'));
        $inactive = $this->responseJson($this->dispatchJson('GET', '/api/v1/products?status=INACTIVE'));

        self::assertSame([], $active['data']);
        self::assertCount(1, $inactive['data']);
        self::assertSame('OFF-1', $inactive['data'][0]['sku']);
    }

    public function testRejectsInvalidProductStatus(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'BAD-1',
            'name' => 'Status invalido',
            'status' => 'QUEBRADO',
        ]);

        self::assertSame(400, $response['status']);
    }

    // ------------------------------------------------------------------ permissoes

    public function testViewerCannotCreateProduct(): void
    {
        // Cria um usuario com o perfil "viewer" (role 2), que tem apenas
        // products.view, e login direto como ele.
        $this->execSql(
            "INSERT INTO users (id, tenant_id, role_id, name, email, password, status, created_at, updated_at)
             VALUES (9, 1, 2, 'Sem Permissao', 'noperm@example.com', :password, 'ACTIVE', '', '')",
            ['password' => password_hash('secret123', PASSWORD_DEFAULT)],
        );

        $login = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'noperm@example.com',
            'password' => 'secret123',
        ]);
        self::assertSame(200, $login['status']);

        $response = $this->dispatchJson('POST', '/api/v1/products', ['sku' => 'NOPERM', 'name' => 'Nao deveria']);

        self::assertSame(403, $response['status']);
    }

    // ----------------------------------------------------------------- tela web

    public function testProductsScreenRendersWithFiltersAndForm(): void
    {
        $this->loginAsAdminOne();

        $categoryId = (int) $this->responseJson($this->dispatchJson('POST', '/api/v1/categories', ['name' => 'Vestuario']))['data']['id'];
        $this->dispatchJson('POST', '/api/v1/brands', ['name' => 'Alfa']);
        $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'WEB-1',
            'name' => 'Produto da tela',
            'category_id' => $categoryId,
        ]);

        $response = $this->dispatchPage('GET', '/produtos');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Produtos', $response['body']);
        self::assertStringContainsString('WEB-1', $response['body']);
        self::assertStringContainsString('Vestuario', $response['body'], 'categoria aparece no select');
        self::assertStringContainsString('Alfa', $response['body'], 'marca aparece no select');
    }

    public function testProductsScreenCreatesProductThroughForm(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchNativeForm('POST', '/produtos', [
            'sku' => 'FORM-1',
            'name' => 'Produto via formulario',
            'unit' => 'UN',
        ]);

        self::assertSame(302, $response['status']);
        self::assertSame('/produtos', $response['headers']['Location']);

        $row = $this->fetchOne('SELECT * FROM products WHERE sku = ?', ['FORM-1']);

        self::assertNotNull($row, 'form nativo deve criar o produto');
        self::assertSame('Produto via formulario', $row['name']);
    }

    public function testProductsScreenRejectsDuplicateSkuWithoutCrashing(): void
    {
        $this->loginAsAdminOne();

        $this->dispatchNativeForm('POST', '/produtos', ['sku' => 'FORM-2', 'name' => 'Primeiro']);
        $response = $this->dispatchNativeForm('POST', '/produtos', ['sku' => 'FORM-2', 'name' => 'Segundo']);

        self::assertSame(302, $response['status']);

        $count = $this->fetchOne('SELECT COUNT(*) AS total FROM products WHERE sku = ?', ['FORM-2']);

        self::assertSame(1, (int) $count['total']);
    }

    public function testProductsScreenRequiresAuthentication(): void
    {
        $response = $this->dispatchPage('GET', '/produtos');

        self::assertSame(302, $response['status']);
        self::assertStringContainsString('/login', (string) $response['headers']['Location']);
    }

    // ------------------------------------------- persistencia do preco calculado

    public function testMarginOnCreateIsPersistedIntoProductPrices(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 100.00, 'real_unit_cost' => 100.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'MARG-1',
            'name' => 'Com margem',
            'default_import_item_id' => $itemId,
            'margin' => 30,
        ]);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertEquals(100.00, (float) $payload['data']['cost_price']);
        self::assertEquals(130.00, (float) $payload['data']['sale_price'], 'mark-up de 30% sobre 100');
        self::assertEquals(130.00, (float) $payload['data']['minimum_price']);
        self::assertEquals(30.00, (float) $payload['data']['margin']);

        // A suggestao precisa estar persistida, e nao apenas devolvida na resposta.
        $stored = $this->fetchOne('SELECT * FROM product_prices WHERE product_id = ?', [(int) $payload['data']['id']]);

        self::assertNotNull($stored);
        self::assertEquals(130.00, (float) $stored['sale_price']);
        self::assertEquals(30.00, (float) $stored['margin']);
    }

    public function testExplicitSalePriceRecalculatesStoredMargin(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 80.00, 'real_unit_cost' => 80.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'MARG-2',
            'name' => 'Preco manual',
            'default_import_item_id' => $itemId,
            'sale_price' => 100.00,
        ]);
        $payload = $this->responseJson($create);

        // 100 sobre custo 80 = 25% de mark-up efetivo, e nao a margem enviada.
        self::assertSame(201, $create['status']);
        self::assertEquals(100.00, (float) $payload['data']['sale_price']);
        self::assertEquals(25.00, (float) $payload['data']['margin']);

        $stored = $this->fetchOne('SELECT * FROM product_prices WHERE product_id = ?', [(int) $payload['data']['id']]);

        self::assertEquals(25.00, (float) $stored['margin'], 'a margem gravada descreve o preco gravado');
    }

    public function testMinimumPriceIsHonouredWhenProvided(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 50.00, 'real_unit_cost' => 50.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'MARG-3',
            'name' => 'Com minimo',
            'default_import_item_id' => $itemId,
            'margin' => 20,
            'minimum_price' => 90.00,
        ]);
        $payload = $this->responseJson($create);

        self::assertEquals(60.00, (float) $payload['data']['sale_price']);
        self::assertEquals(90.00, (float) $payload['data']['minimum_price'], 'o minimo informado nao e sobrescrito');
    }

    public function testNegativeMarginIsRejectedOnCreate(): void
    {
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(1, [
            ['product_name' => 'Item', 'unit_cost_local' => 10.00, 'real_unit_cost' => 10.00],
        ]);
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'MARG-NEG',
            'name' => 'Margem negativa',
            'default_import_item_id' => $itemId,
            'margin' => -10,
        ]);

        self::assertSame(400, $create['status']);

        $count = $this->fetchOne('SELECT COUNT(*) AS total FROM products WHERE sku = ?', ['MARG-NEG']);

        self::assertSame(0, (int) $count['total'], 'nenhum produto deve sobrar apos recusa');
    }

    public function testMarginWithoutRealCostDoesNotInventASalePrice(): void
    {
        $this->loginAsAdminOne();

        // Sem item de importacao nao ha custo real: a margem nao pode virar preco.
        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'MARG-SEM-CUSTO',
            'name' => 'Sem custo',
            'margin' => 50,
        ]);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertEquals(0.0, (float) $payload['data']['cost_price']);
        self::assertEquals(0.0, (float) $payload['data']['sale_price'], 'sem custo nao há base para a margem');
    }

    public function testChangingLinkedImportItemRefreshesCost(): void
    {
        $this->loginAsAdminOne();

        $firstImport = $this->seedCompletedImport(1, [
            ['product_name' => 'A', 'unit_cost_local' => 20.00, 'real_unit_cost' => 20.00],
        ]);
        $secondImport = $this->seedCompletedImport(1, [
            ['product_name' => 'B', 'unit_cost_local' => 55.00, 'real_unit_cost' => 55.00],
        ]);

        $firstItem = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$firstImport])['id'];
        $secondItem = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$secondImport])['id'];

        $create = $this->responseJson($this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'TROCA-1',
            'name' => 'Troca de item',
            'default_import_item_id' => $firstItem,
        ]));

        self::assertEquals(20.00, (float) $create['data']['cost_price']);

        $update = $this->dispatchJson('PUT', '/api/v1/products/' . (int) $create['data']['id'], [
            'default_import_item_id' => $secondItem,
        ]);
        $payload = $this->responseJson($update);

        self::assertSame(200, $update['status']);
        self::assertEquals(55.00, (float) $payload['data']['cost_price'], 'o custo precisa acompanhar o novo item');

        $stored = $this->fetchOne('SELECT * FROM product_prices WHERE product_id = ?', [(int) $create['data']['id']]);

        self::assertEquals(55.00, (float) $stored['cost_price'], 'o custo antigo ficaria gravado em product_prices');
    }

    // ----------------------------------------------------- normalizacao de status

    public function testProductStatusIsStoredUppercase(): void
    {
        $this->loginAsAdminOne();

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'STATUS-1',
            'name' => 'Status minusculo',
            'status' => 'active',
        ]);
        $payload = $this->responseJson($create);

        self::assertSame(201, $create['status']);
        self::assertSame('ACTIVE', (string) $payload['data']['status']);

        $stored = $this->fetchOne('SELECT status FROM products WHERE sku = ?', ['STATUS-1']);

        self::assertSame('ACTIVE', (string) $stored['status']);
    }

    public function testInvalidProductStatusIsRejected(): void
    {
        $this->loginAsAdminOne();

        $create = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'STATUS-2',
            'name' => 'Status invalido',
            'status' => 'ARQUIVADO',
        ]);

        self::assertSame(400, $create['status']);
    }

    public function testCategoryCreateValidatesAndNormalizesStatus(): void
    {
        $this->loginAsAdminOne();

        $lower = $this->dispatchJson('POST', '/api/v1/categories', [
            'name' => 'Categoria minuscula',
            'status' => 'active',
        ]);
        $payload = $this->responseJson($lower);

        self::assertSame(201, $lower['status']);
        self::assertSame('ACTIVE', (string) $payload['data']['status']);

        $invalid = $this->dispatchJson('POST', '/api/v1/categories', [
            'name' => 'Categoria invalida',
            'status' => 'ARQUIVADA',
        ]);

        self::assertSame(400, $invalid['status'], 'create precisa validar status como o update');

        $count = $this->fetchOne('SELECT COUNT(*) AS total FROM categories WHERE name = ?', ['Categoria invalida']);

        self::assertSame(0, (int) $count['total']);
    }
}
