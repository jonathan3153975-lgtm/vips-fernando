<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\NotFoundException;
use App\Repositories\StockRepository;
use App\Services\StockService;

/**
 * Estoque transacional (EPIC 06). O foco e o que o usuario mais sente errado
 * quando quebra: saldo que vira negativo, reserva que promete mercadoria
 * inexistente, e movimentacao que nao fica registrada.
 */
final class StockTest extends ApiIntegrationTestCase
{
    /**
     * O login e idempotente aqui porque o GuestMiddleware responde 409 quando a
     * sessao ja esta autenticada, e varios testes criam mais de um produto.
     */
    private bool $adminLoggedIn = false;

    private function service(): StockService
    {
        return new StockService(new StockRepository());
    }

    private function loginAsAdminOne(): void
    {
        if ($this->adminLoggedIn) {
            return;
        }

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'login do admin do tenant 1 falhou');

        $this->adminLoggedIn = true;
    }

    private function loginAsViewerOne(): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'viewer1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'login do viewer do tenant 1 falhou');
    }

    /**
     * Produto real do tenant 1, criado pela API, para os testes partirem do
     * mesmo estado que o usuario veria na tela. Faz o login porque a criacao de
     * produto e uma rota protegida.
     */
    private function createProduct(array $overrides = []): int
    {
        $this->loginAsAdminOne();

        $payload = array_merge(['sku' => 'STK-1', 'name' => 'Produto de estoque'], $overrides);

        $response = $this->dispatchJson('POST', '/api/v1/products', $payload);

        self::assertSame(201, $response['status'], 'criacao do produto falhou: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    private function stockRow(int $productId): array
    {
        $row = $this->fetchOne(
            'SELECT quantity, reserved_quantity, minimum_quantity FROM stock WHERE product_id = ?',
            [$productId],
        );

        self::assertNotNull($row, 'a linha de estoque deveria existir para o produto ' . $productId);

        return $row;
    }

    private function movementCount(int $productId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total FROM stock_movements WHERE product_id = ?',
            [$productId],
        )['total'];
    }

    // -------------------------------------------------------------- entradas

    public function testImportEntryAddsToBalanceAndRecordsMovement(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $result = $stock->receiveFromImport($productId, 25.0, 4242);

        self::assertEquals(25.0, (float) $result['quantity']);
        self::assertEquals(25.0, (float) $result['balance_after']);
        self::assertEquals(25.0, (float) $result['available_quantity']);

        // A rastreabilidade sem lote mora em import_item_id: e o que substitui
        // o lote, ja que o usuario decidiu nao criar product_lots.
        $movement = $this->fetchOne(
            'SELECT type, quantity, balance_after, import_item_id, reference_type, reference_id
             FROM stock_movements WHERE product_id = ?',
            [$productId],
        );

        self::assertSame(StockRepository::TYPE_IMPORT_ENTRY, $movement['type']);
        self::assertEquals(25.0, (float) $movement['quantity']);
        self::assertEquals(25.0, (float) $movement['balance_after']);
        self::assertEquals(4242, (int) $movement['import_item_id']);
        self::assertSame('import', $movement['reference_type']);
        self::assertEquals(4242, (int) $movement['reference_id']);
    }

    public function testImportEntryAccumulatesAcrossCalls(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->receiveFromImport($productId, 5.5, 2);

        self::assertEquals(15.5, (float) $this->stockRow($productId)['quantity']);
        self::assertSame(2, $this->movementCount($productId));
    }

    public function testImportEntryRejectsNonPositiveQuantity(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('maior que zero');

        $stock->receiveFromImport($productId, 0.0, 1);
    }

    // --------------------------------------------------------------- ajustes

    public function testManualAdjustmentRequiresJustification(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('justificativa');

        $this->service()->adjust([
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_IN,
            'quantity' => 5,
            'notes' => '   ',
        ]);
    }

    public function testManualAdjustmentRejectsUnknownType(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('entrada');

        $this->service()->adjust([
            'product_id' => $productId,
            'type' => 'QUALQUER_COISA',
            'quantity' => 5,
            'notes' => 'Perda avulsa',
        ]);
    }

    public function testManualAdjustmentInAndOutMoveBalanceBothWays(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->adjust([
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_IN,
            'quantity' => 30,
            'notes' => 'Contagem inicial do inventario.',
        ]);
        $stock->adjust([
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_OUT,
            'quantity' => 8,
            'notes' => 'Avaria no deposito.',
        ]);

        self::assertEquals(22.0, (float) $this->stockRow($productId)['quantity']);
        self::assertSame(2, $this->movementCount($productId));
    }

    public function testManualAdjustmentOutBeyondBalanceIsRefused(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $stock->adjust([
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_OUT,
            'quantity' => 11,
            'notes' => 'Tentativa de baixar mais do que existe.',
        ]);
    }

    /**
     * O saldo nao pode ter sido alterado pela tentativa recusada: a guarda roda
     * antes de qualquer UPDATE.
     */
    public function testRefusedAdjustmentLeavesBalanceUntouched(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);

        try {
            $stock->adjust([
                'product_id' => $productId,
                'type' => StockRepository::TYPE_MANUAL_OUT,
                'quantity' => 99,
                'notes' => 'Deve falhar.',
            ]);
            self::fail('a ajuste deveria ter sido recusado');
        } catch (\RuntimeException) {
            // esperado
        }

        self::assertEquals(10.0, (float) $this->stockRow($productId)['quantity']);
        self::assertSame(1, $this->movementCount($productId), 'movimentacao recusada nao pode ser gravada');
    }

    // -------------------------------------------------------------- reservas

    public function testReserveMovesOnlyReservedNotPhysicalBalance(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 4]);

        $row = $this->stockRow($productId);

        self::assertEquals(10.0, (float) $row['quantity'], 'reserva nao pode mexer no saldo fisico');
        self::assertEquals(4.0, (float) $row['reserved_quantity']);
    }

    public function testReserveBeyondAvailabilityIsRefused(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reserva maior que o disponivel');

        $stock->reserve(['product_id' => $productId, 'quantity' => 11]);
    }

    public function testReserveExactlyAvailableIsAllowed(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 10]);

        $row = $this->stockRow($productId);

        self::assertEquals(10.0, (float) $row['reserved_quantity']);

        // Reservar tudo e o limite: mais um pouco tem de falhar.
        $this->expectException(\RuntimeException::class);
        $stock->reserve(['product_id' => $productId, 'quantity' => 0.001]);
    }

    public function testReleaseFreesReservationWithoutTouchingBalance(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 6]);
        $stock->release(['product_id' => $productId, 'quantity' => 6]);

        $row = $this->stockRow($productId);

        self::assertEquals(10.0, (float) $row['quantity']);
        self::assertEquals(0.0, (float) $row['reserved_quantity']);
    }

    public function testReleaseBeyondReservationIsRefused(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Liberacao maior que a reserva');

        $stock->release(['product_id' => $productId, 'quantity' => 4]);
    }

    /**
     * Regressao do bug que motivou o `consume_reserved`: consumir a reserva tem de
     * baixar saldo E reserva numa unica movimentacao. Se so baixasse a reserva,
     * o disponivel cresceria depois da mercadoria sair — o oposto do correto.
     */
    public function testConsumeReservedStockKeepsAvailabilityStable(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 4]);

        self::assertEquals(6.0, (float) $this->balanceValue($productId, 'available_quantity'));

        $stock->consume(['product_id' => $productId, 'quantity' => 4]);

        $row = $this->stockRow($productId);

        self::assertEquals(6.0, (float) $row['quantity'], 'a mercadoria que saiu tem de sair do saldo');
        self::assertEquals(0.0, (float) $row['reserved_quantity']);
        self::assertEquals(
            6.0,
            (float) $this->balanceValue($productId, 'available_quantity'),
            'o disponivel nao pode pular quando a mercadoria reservada sai',
        );
    }

    public function testConsumeBeyondReservationTakesFromFreeBalance(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 2]);

        // Baixa maior que a reserva: 2 da reserva e 3 do saldo livre.
        $stock->consume(['product_id' => $productId, 'quantity' => 5]);

        $row = $this->stockRow($productId);

        self::assertEquals(5.0, (float) $row['quantity']);
        self::assertEquals(0.0, (float) $row['reserved_quantity']);
    }

    public function testConsumeBeyondBalanceIsRefused(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 3.0, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $stock->consume(['product_id' => $productId, 'quantity' => 4]);
    }

    /**
     * A combinacao que quebrava com duas chamadas separadas: com saldo 10 e
     * reserva 10, baixar 5 deixaria reservado(10) > saldo(5) no meio da
     * operacao. Aqui tem de dar certo e sobrar reserva 5.
     */
    public function testConsumeFromFullReservationDoesNotViolateReservedWithinBalance(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 10]);
        $stock->consume(['product_id' => $productId, 'quantity' => 5]);

        $row = $this->stockRow($productId);

        self::assertEquals(5.0, (float) $row['quantity']);
        self::assertEquals(5.0, (float) $row['reserved_quantity']);
    }

    // -------------------------------------------------------------- minimos

    public function testMinimumQuantityCanBeSetAndDrivesBelowMinimumFilter(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->setMinimumQuantity(['product_id' => $productId, 'minimum_quantity' => 20]);

        self::assertEquals(20.0, (float) $this->stockRow($productId)['minimum_quantity']);

        $belowMinimum = $stock->balances(['below_minimum' => '1']);

        self::assertCount(1, $belowMinimum);
        self::assertSame($productId, (int) $belowMinimum[0]['product_id']);

        // Acima do minimo deixa de aparecer no filtro de reposicao.
        $stock->receiveFromImport($productId, 20.0, 2);
        self::assertCount(0, $stock->balances(['below_minimum' => '1']));
    }

    public function testMinimumQuantityRejectsNegativeValue(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('minimo nao pode ser negativo');

        $this->service()->setMinimumQuantity(['product_id' => $productId, 'minimum_quantity' => -1]);
    }

    // ------------------------------------------------------------ historico

    public function testHistoryIsPaginatedAndFilteredByType(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 10.0, 1);
        $stock->reserve(['product_id' => $productId, 'quantity' => 2]);
        $stock->adjust([
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_OUT,
            'quantity' => 1,
            'notes' => 'Avaria.',
        ]);

        self::assertSame(3, $stock->countMovements());

        $reserves = $stock->movements(['type' => StockRepository::TYPE_RESERVE]);
        self::assertCount(1, $reserves);
        self::assertSame(StockRepository::TYPE_RESERVE, $reserves[0]['type']);

        $imports = $stock->movements(['type' => StockRepository::TYPE_IMPORT_ENTRY]);
        self::assertCount(1, $imports);
        self::assertEquals(1, (int) $imports[0]['import_item_id']);
    }

    public function testHistoryFilterByUnknownTypeIsRefused(): void
    {
        $this->bootApplication();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalido');

        $this->service()->movements(['type' => 'TIPO_QUE_NAO_EXISTE']);
    }

    public function testHistoryReturnsNewestFirst(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 1.0, 1);
        $stock->receiveFromImport($productId, 2.0, 2);
        $stock->receiveFromImport($productId, 3.0, 3);

        $movements = $stock->movements([], 10);

        self::assertCount(3, $movements);
        self::assertEquals(3.0, (float) $movements[0]['quantity'], 'o mais recente vem primeiro');
        self::assertEquals(1.0, (float) $movements[2]['quantity']);
    }

    // -------------------------------------------------------- isolamento

    public function testStockOfAnotherTenantIsInvisible(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 10.0, 1);

        // Tenant 2 tem produto e estoque proprios; nenhum saldo deve vazar.
        $this->execSql(
            "INSERT INTO products (id, tenant_id, sku, name, unit, status, created_at, updated_at)
             VALUES (900, 2, 'T2-STK', 'Produto do tenant 2', 'UN', 'ACTIVE', '2026-08-01', '2026-08-01')"
        );
        $this->execSql(
            'INSERT INTO stock (tenant_id, product_id, quantity, reserved_quantity, minimum_quantity, updated_at)
             VALUES (2, 900, 77, 0, 0, \'2026-08-01\')'
        );

        $balances = $this->service()->balances();

        self::assertCount(1, $balances, 'o estoque do tenant 2 nao pode aparecer para o tenant 1');
        self::assertSame($productId, (int) $balances[0]['product_id']);
    }

    public function testAdjustingProductOfAnotherTenantFails(): void
    {
        // Login obrigatorio: sem tenant ativo o repositorio nem chega a checar
        // o produto, e o teste passaria por motivo errado.
        $this->loginAsAdminOne();

        $this->execSql(
            "INSERT INTO products (id, tenant_id, sku, name, unit, status, created_at, updated_at)
             VALUES (901, 2, 'T2-ADJ', 'Produto do tenant 2', 'UN', 'ACTIVE', '2026-08-01', '2026-08-01')"
        );

        try {
            $this->service()->adjust([
                'product_id' => 901,
                'type' => StockRepository::TYPE_MANUAL_IN,
                'quantity' => 10,
                'notes'        => 'Tentativa entre tenants.',
            ]);

            self::fail('o ajuste de um produto de outra empresa nao pode passar');
        } catch (NotFoundException) {
            // 404 e o esperado: para o tenant 1 o produto 901 nao existe.
        }

        self::assertNull(
            $this->fetchOne('SELECT id FROM stock WHERE product_id = 901'),
            'recusar o ajuste tambem precisa impedir a criacao da linha de estoque',
        );

        self::assertSame(
            0,
            (int) $this->fetchOne('SELECT COUNT(*) AS total FROM stock_movements WHERE product_id = 901')['total'],
            'nenhuma movimentacao pode ser gravada para produto de outra empresa',
        );
    }

    public function testReadingStockOfProductOfAnotherTenantFails(): void
    {
        $this->loginAsAdminOne();

        $this->execSql(
            "INSERT INTO products (id, tenant_id, sku, name, unit, status, created_at, updated_at)
             VALUES (902, 2, 'T2-BAL', 'Produto do tenant 2', 'UN', 'ACTIVE', '2026-08-01', '2026-08-01')"
        );

        // ensureRow() cria a linha zerada quando ela nao existe: sem checar o
        // dono do produto, ele criava uma linha de estoque do tenant 1 apontando
        // para o produto 902 e devolvia 200 com saldo 0.
        $this->expectException(NotFoundException::class);

        try {
            $this->service()->balance(902);
        } finally {
            self::assertNull(
                $this->fetchOne('SELECT id FROM stock WHERE product_id = 902'),
                'consultar o saldo nao pode criar linha de estoque para produto alheio',
            );
        }
    }

    public function testSettingMinimumOfProductOfAnotherTenantFails(): void
    {
        $this->loginAsAdminOne();

        $this->execSql(
            "INSERT INTO products (id, tenant_id, sku, name, unit, status, created_at, updated_at)
             VALUES (903, 2, 'T2-MIN', 'Produto do tenant 2', 'UN', 'ACTIVE', '2026-08-01', '2026-08-01')"
        );

        $this->expectException(NotFoundException::class);

        $this->service()->setMinimumQuantity(['product_id' => 903, 'minimum_quantity' => 5]);
    }

    // ------------------------------------------------------------------ API

    public function testStockApiRequiresStockViewToRead(): void
    {
        $this->loginAsViewerOne();

        $response = $this->dispatchJson('GET', '/api/v1/stock');

        self::assertSame(403, $response['status'], 'o viewer nao tem stock.view');
    }

    public function testStockApiRequiresStockAdjustToWrite(): void
    {
        $this->loginAsViewerOne();

        $response = $this->dispatchJson('POST', '/api/v1/stock/adjustments', [
            'product_id' => 1,
            'type' => StockRepository::TYPE_MANUAL_IN,
            'quantity' => 1,
            'notes' => 'Sem permissao.',
        ]);

        self::assertSame(
            403,
            $response['status'],
            'ler saldo e mexer em saldo sao permissoes diferentes',
        );
    }

    public function testStockApiListsBalancesForAdmin(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 12.0, 7);

        $response = $this->dispatchJson('GET', '/api/v1/stock');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame(1, $payload['meta']['total']);
        self::assertSame($productId, (int) $payload['data'][0]['product_id']);
        self::assertEquals(12.0, (float) $payload['data'][0]['available_quantity']);
    }

    public function testStockApiPaginatesBalancesInsteadOfReturningEverything(): void
    {
        // Com 3 produtos e per_page=2, a resposta tem de trazer 2 linhas e
        // anunciar total 3. Sem o LIMIT no repositorio, `meta` dizia uma coisa e
        // `data` trazia a lista inteira.
        foreach (['Alfa', 'Bravo', 'Charlie'] as $indice => $nome) {
            $produtoId = $this->createProduct(['sku' => 'PAG-' . $indice, 'name' => $nome . ' estoque']);
            $this->service()->receiveFromImport($produtoId, 1.0, $indice + 1);
        }

        $primeira = $this->responseJson($this->dispatchJson('GET', '/api/v1/stock?per_page=2&page=1'));

        self::assertSame(3, $primeira['meta']['total']);
        self::assertCount(2, $primeira['data'], 'a primeira pagina nao foi recortada');
        self::assertSame(2, $primeira['meta']['per_page']);

        $segunda = $this->responseJson($this->dispatchJson('GET', '/api/v1/stock?per_page=2&page=2'));

        self::assertCount(1, $segunda['data'], 'a segunda pagina deveria trazer o resto');
        self::assertNotSame(
            $primeira['data'][0]['product_id'],
            $segunda['data'][0]['product_id'],
            'a segunda pagina repetiu item da primeira',
        );
    }

    public function testStockApiAdjustmentRequiresJustification(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();

        $response = $this->dispatchJson('POST', '/api/v1/stock/adjustments', [
            'product_id' => $productId,
            'type' => StockRepository::TYPE_MANUAL_IN,
            'quantity' => 5,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('justificativa', $this->responseJson($response)['message']);
    }

    public function testStockApiRefusesNegativeStockWithReadableMessage(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 2.0, 1);

        $response = $this->dispatchJson('POST', '/api/v1/stock/consumptions', [
            'product_id' => $productId,
            'quantity' => 5,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Saldo insuficiente', $this->responseJson($response)['message']);
    }

    public function testStockApiHistoryShowsMovements(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 4.0, 55);

        $response = $this->dispatchJson('GET', '/api/v1/stock/movements');
        $payload = $this->responseJson($response);

        self::assertSame(200, $response['status']);
        self::assertSame(1, $payload['meta']['total']);
        self::assertSame(StockRepository::TYPE_IMPORT_ENTRY, $payload['data'][0]['type']);
        self::assertSame($productId, (int) $payload['data'][0]['product_id']);
    }

    public function testStockApiReservationRoundTrip(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 10.0, 1);

        $reserve = $this->dispatchJson('POST', '/api/v1/stock/reservations', [
            'product_id' => $productId,
            'quantity' => 3,
        ]);
        self::assertSame(201, $reserve['status']);

        $reserved = (float) $this->responseJson($reserve)['data']['reserved_quantity'];
        self::assertEquals(3.0, $reserved);

        $release = $this->dispatchJson('POST', '/api/v1/stock/reservations/release', [
            'product_id' => $productId,
            'quantity' => 3,
        ]);
        self::assertSame(200, $release['status']);
        self::assertEquals(0.0, (float) $this->responseJson($release)['data']['reserved_quantity']);
    }

    // ---------------------------------------------------------------- telas

    public function testStockPageListsBalancesAndFlagsBelowMinimum(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $stock = $this->service();

        $stock->receiveFromImport($productId, 4.0, 1);
        $stock->setMinimumQuantity(['product_id' => $productId, 'minimum_quantity' => 10]);

        $response = $this->dispatchPage('GET', '/estoque');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Abaixo do mínimo', $response['body']);
        self::assertStringContainsString('1 produto(s) abaixo do estoque m', $response['body']);
    }

    /**
     * Regressao de exibicao: DECIMAL(12,3) chega como string do MySQL. Sem
     * converter, a tela mostraria 0 para um saldo de 10.000.
     */
    public function testStockPageShowsDecimalQuantityWithoutRoundingToZero(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 12.5, 1);

        $body = $this->dispatchPage('GET', '/estoque')['body'];

        self::assertStringContainsString('12,5', $body);
        self::assertStringNotContainsString('>0,000<', $body, 'saldo nao pode virar zero na tela');
    }

    public function testMovementsPageShowsImportTraceability(): void
    {
        $this->bootApplication();

        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 7.0, 321);

        $body = $this->dispatchPage('GET', '/estoque/movimentacoes')['body'];

        self::assertStringContainsString('Entrada por importa', $body);
        self::assertStringContainsString('321', $body, 'o id do item de importacao e a rastreabilidade sem lote');
    }

    public function testStockPagesRequireStockView(): void
    {
        $this->loginAsViewerOne();

        self::assertSame(403, $this->dispatchPage('GET', '/estoque')['status']);
        self::assertSame(403, $this->dispatchPage('GET', '/estoque/movimentacoes')['status']);
    }

    public function testSidebarShowsStockLinksOnlyWithStockView(): void
    {
        $this->bootApplication();

        $this->loginAsViewerOne();
        self::assertStringNotContainsString('href="/estoque"', $this->dispatchPage('GET', '/produtos')['body']);

        // O admin tem stock.view; sai da sessao e entra de novo.
        $_SESSION = [];
        $this->loginAsAdminOne();
        self::assertStringContainsString('href="/estoque"', $this->dispatchPage('GET', '/produtos')['body']);
    }

    public function testStockApiRejectsReservationBeyondAvailability(): void
    {
        // createProduct() ja autentica; logar duas vezes daria 409 do GuestMiddleware.
        $productId = $this->createProduct();
        $this->service()->receiveFromImport($productId, 4.0, 1);

        $response = $this->dispatchJson('POST', '/api/v1/stock/reservations', [
            'product_id' => $productId,
            'quantity' => 9,
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Reserva maior que o disponivel', $this->responseJson($response)['message']);
    }

    // ------------------------------------------------- importacao -> estoque

    /**
     * P0 da etapa: concluir a importacao precisa transformar o item comprado em
     * saldo do produto vinculado. Sem isso a compra existe e o estoque nao.
     */
    public function testCompletingImportGivesStockToLinkedProduct(): void
    {
        $this->bootApplication();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Camiseta', 'quantity' => 40, 'unit_cost_local' => 60.00]],
            'IN_PROGRESS',
        );
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = $this->createProduct(['default_import_item_id' => $itemId]);

        self::assertEquals(0.0, (float) $this->stockRow($productId)['quantity'], 'antes de concluir nao entra nada');

        $response = $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete');

        self::assertSame(200, $response['status'], 'conclusao da importacao falhou: ' . $response['body']);

        self::assertEquals(40.0, (float) $this->stockRow($productId)['quantity']);
        self::assertSame(1, $this->movementCount($productId));

        // A entrada precisa apontar para o item, que e a rastreabilidade no lugar
        // do lote.
        $movement = $this->fetchOne(
            'SELECT type, import_item_id, reference_type FROM stock_movements WHERE product_id = ?',
            [$productId],
        );

        self::assertSame(StockRepository::TYPE_IMPORT_ENTRY, $movement['type']);
        self::assertSame($itemId, (int) $movement['import_item_id']);
        self::assertSame('import', $movement['reference_type']);
    }

    public function testCompletingImportGivesStockToEachLinkedProduct(): void
    {
        $this->bootApplication();

        $importId = $this->seedCompletedImport(
            1,
            [
                ['product_name' => 'Item A', 'quantity' => 10, 'unit_cost_local' => 10.00],
                ['product_name' => 'Item B', 'quantity' => 25, 'unit_cost_local' => 20.00],
            ],
            'IN_PROGRESS',
        );

        $items = $this->fetchAllRows('SELECT id FROM import_items WHERE import_id = ? ORDER BY id', [$importId]);

        $first = $this->createProduct(['sku' => 'STK-A', 'default_import_item_id' => (int) $items[0]['id']]);
        $second = $this->createProduct(['sku' => 'STK-B', 'default_import_item_id' => (int) $items[1]['id']]);

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);

        self::assertEquals(10.0, (float) $this->stockRow($first)['quantity']);
        self::assertEquals(25.0, (float) $this->stockRow($second)['quantity']);
    }

    /**
     * Item sem produto vinculado nao vira estoque: o vinculo e o usuario que
     * decide, nao a importacao.
     */
    public function testImportWithoutLinkedProductGivesNoStock(): void
    {
        $this->bootApplication();
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Sem vinculo', 'quantity' => 99, 'unit_cost_local' => 10.00]],
            'IN_PROGRESS',
        );

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);

        $movements = (int) $this->fetchOne('SELECT COUNT(*) AS total FROM stock_movements')['total'];

        self::assertSame(0, $movements, 'item sem produto vinculado nao gera movimentacao');
    }

    /**
     * Regressao do risco de idempotencia: reabrir e concluir de novo nao pode
     * somar a mesma mercadoria duas vezes.
     */
    public function testReopeningAndCompletingAgainDoesNotDoubleStock(): void
    {
        $this->bootApplication();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Camiseta', 'quantity' => 30, 'unit_cost_local' => 60.00]],
            'IN_PROGRESS',
        );
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = $this->createProduct(['default_import_item_id' => $itemId]);

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);
        self::assertEquals(30.0, (float) $this->stockRow($productId)['quantity']);

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/reopen')['status']);
        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);

        self::assertEquals(
            30.0,
            (float) $this->stockRow($productId)['quantity'],
            'reabrir e concluir de novo nao pode duplicar a mercadoria',
        );
        self::assertSame(1, $this->movementCount($productId));
    }

    public function testProductDeactivatedDoesNotReceiveImportStock(): void
    {
        $this->bootApplication();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Desativado', 'quantity' => 15, 'unit_cost_local' => 10.00]],
            'IN_PROGRESS',
        );
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        $productId = $this->createProduct(['default_import_item_id' => $itemId]);
        $this->execSql("UPDATE products SET status = 'INACTIVE' WHERE id = ?", [$productId]);

        self::assertSame(200, $this->dispatchJson('POST', '/api/v1/imports/' . $importId . '/complete')['status']);

        self::assertEquals(
            0.0,
            (float) $this->stockRow($productId)['quantity'],
            'produto desativado nao deve receber entrada de estoque',
        );
    }

    public function testImportOfAnotherTenantGivesNoStock(): void
    {
        $this->bootApplication();
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(
            2,
            [['product_name' => 'Alheio', 'quantity' => 50, 'unit_cost_local' => 10.00]],
            'IN_PROGRESS',
        );
        $itemId = (int) $this->fetchOne('SELECT id FROM import_items WHERE import_id = ?', [$importId])['id'];

        // Produto do tenant 1 apontando para item do tenant 2: o escopo tem de
        // barrar na criacao.
        $response = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'STK-XTENANT',
            'name' => 'Produto apontando para item alheio',
            'default_import_item_id' => $itemId,
        ]);

        self::assertSame(400, $response['status'], 'o vinculo cross-tenant tem de ser recusado');
    }

    /**
     * O mecanismo da atomicidade: o fechamento participa da transacao de quem
     * chama. Se a parte seguinte do fluxo falhar, a importacao nao fica
     * concluida — sem isso o usuario veria compra concluida e saldo zerado.
     */
    public function testFreezeJoinsTheCallersTransactionSoFailureRollsBack(): void
    {
        $this->bootApplication();
        $this->loginAsAdminOne();

        $importId = $this->seedCompletedImport(
            1,
            [['product_name' => 'Item', 'quantity' => 12, 'unit_cost_local' => 60.00]],
            'IN_PROGRESS',
        );

        $imports = new \App\Repositories\ImportRepository();

        try {
            $imports->transactional(function () use ($imports, $importId): void {
                $imports->freeze($importId, 'VALUE');

                throw new \RuntimeException('falha simulada depois do fechamento');
            });

            self::fail('a transacao deveria ter propagado a falha');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('falha simulada', $exception->getMessage());
        }

        $status = (string) $this->fetchOne('SELECT status FROM imports WHERE id = ?', [$importId])['status'];

        self::assertSame(
            'IN_PROGRESS',
            $status,
            'a importacao nao pode ficar concluida se a operacao falhou depois',
        );
    }

    // ---------------------------------------------------------------- schema

    /**
     * product_lots e lot_id foram descartados por decisao do usuario. Este teste
     * trava essa escolha: se alguem reintroduzir o lote, o teste falha e a
     * decisao precisa ser revista em vez de passar despercebida.
     */
    public function testStockSchemaHasNoLotColumns(): void
    {
        self::assertNotContains('lot_id', $this->fetchColumnList('stock'));
        self::assertNotContains('lot_id', $this->fetchColumnList('stock_movements'));

        $lotColumns = array_filter(
            $this->fetchColumnList('products'),
            static fn (string $column): bool => str_contains($column, 'lot'),
        );

        self::assertSame([], $lotColumns, 'nenhuma coluna de lote deve aparecer');
    }

    public function testEveryMovementTypeIsLabelled(): void
    {
        $this->bootApplication();

        $labels = $this->service()->movementTypeLabels();

        foreach (StockRepository::ALL_TYPES as $type) {
            self::assertArrayHasKey($type, $labels, 'movimentacao sem rotulo: ' . $type);
            self::assertNotSame('', trim($labels[$type]));
        }

        // Os grupos precisam continuar somando o vocabulario completo.
        self::assertSame(
            count(StockRepository::ALL_TYPES),
            count(array_unique(array_merge(
                StockRepository::INFLOW_TYPES,
                StockRepository::OUTFLOW_TYPES,
                StockRepository::RESERVATION_TYPES,
            ))),
            'os grupos de movimentacao nao cobrem todos os tipos, ou tem sobreposicao',
        );
    }

    private function balanceValue(int $productId, string $column): mixed
    {
        $row = $this->fetchOne(
            'SELECT (quantity - reserved_quantity) AS available_quantity FROM stock WHERE product_id = ?',
            [$productId],
        );

        return $row[$column] ?? null;
    }
}
