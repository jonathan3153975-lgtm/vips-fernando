<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Vendas e checkout (EPIC 08).
 *
 * O foco nao e o caminho feliz. E o que o usuario nao perceberia por semanas se
 * quebrasse:
 *
 * 1. Dinheiro e estoque. Reserva na criacao, baixa na conclusao, liberacao no
 *    cancelamento, reentrada na devolucao — cada passo com o saldo esperado
 *    conferido no banco, porque o erro de inventario aparece no fechamento do
 *    mes, nao no dia da venda.
 * 2. A transacao e inteira. Uma venda de dois itens que falha no segundo nao
 *    pode deixar reserva no primeiro nem cabecalho gravado.
 * 3. As permissoes sao portas separadas. Quem registra venda nao concede
 *    desconto de 40% nem reescreve o preco de tabela: o teste usa um perfil
 *    propositalmente sem `sales.discount` e sem `sales.change_price`.
 * 4. Devolver duas vezes nao dobra o estoque. E a unica trava contra mercadoria
 *    que entra no armazem sem nunca ter saido de la.
 */
final class SaleTest extends ApiIntegrationTestCase
{
    private bool $adminLoggedIn = false;

    // ------------------------------------------------------------- helpers

    private function loginAsAdminOne(): void
    {
        if ($this->adminLoggedIn) {
            return;
        }

        $this->assertLogin('admin1@example.com');
        $this->adminLoggedIn = true;
    }

    private function loginAsAdminTwo(): void
    {
        $this->logout();
        $this->assertLogin('admin2@example.com');
    }

    /**
     * O `GuestMiddleware` responde 409 quando ja existe sessao autenticada, e
     * cada `setUp()` cria uma sessao nova. Trocar de usuario exige sair antes.
     */
    private function logout(): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/logout', []);

        self::assertContains(
            $response['status'],
            [200, 204],
            'logout falhou: ' . $response['body'],
        );
    }

    private function assertLogin(string $email): void
    {
        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'login de ' . $email . ' falhou: ' . $response['body']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createProduct(array $overrides = []): int
    {
        $this->loginAsAdminOne();

        $payload = array_merge([
            'sku' => 'SALE-1',
            'name' => 'Produto de venda',
            'unit' => 'UN',
            'price' => [
                'cost_price' => 60.00,
                'sale_price' => 100.00,
                'minimum_price' => 80.00,
                'margin' => 40.00,
            ],
        ], $overrides);

        $response = $this->dispatchJson('POST', '/api/v1/products', $payload);

        self::assertSame(201, $response['status'], 'criacao do produto falhou: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    /**
     * @return array{quantity: float, reserved_quantity: float}
     */
    private function stockRow(int $productId): array
    {
        $row = $this->fetchOne(
            'SELECT quantity, reserved_quantity FROM stock WHERE product_id = ?',
            [$productId],
        );

        self::assertNotNull($row, 'a linha de estoque deveria existir para o produto ' . $productId);

        return [
            'quantity' => (float) $row['quantity'],
            'reserved_quantity' => (float) $row['reserved_quantity'],
        ];
    }

    private function setStock(int $productId, float $quantity): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/stock/adjustments', [
            'product_id' => $productId,
            'type' => 'MANUAL_IN',
            'quantity' => $quantity,
            'notes' => 'Carga inicial para o teste de venda.',
        ]);

        self::assertSame(201, $response['status'], 'carga de estoque falhou: ' . $response['body']);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $extra
     *
     * @return array{0: int, 1: array<string, mixed>} status e corpo
     */
    private function createSale(array $items, array $extra = []): array
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/sales', ['items' => $items] + $extra);

        return [$response['status'], $this->safeJson($response)];
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function safeJson(array $response): array
    {
        $decoded = json_decode((string) $response['body'], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string> tipos de movimentacao, na ordem
     */
    private function movementTypes(int $productId): array
    {
        $rows = $this->fetchAllRows(
            'SELECT type FROM stock_movements WHERE product_id = ? ORDER BY id',
            [$productId],
        );

        return array_map(static fn (array $row): string => (string) $row['type'], $rows);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed> detalhe da venda criada
     */
    private function openedSale(array $items, array $extra = []): array
    {
        [$status, $body] = $this->createSale($items, $extra);

        self::assertSame(201, $status, 'criacao da venda falhou: ' . json_encode($body));

        return $body['data'];
    }

    /**
     * @return int id de uma venda ja concluida
     */
    private function completedSale(float $quantity = 1.0): int
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => $quantity]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(200, $complete['status'], 'conclusao falhou: ' . $complete['body']);

        return $saleId;
    }

    // ------------------------------------------------ criacao e reserva

    public function testCreatingSaleReservesStockAndComputesTotals(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 3]]);
        $sale = $data['sale'];

        self::assertSame('OPEN', $sale['status']);
        self::assertSame(300.00, (float) $sale['subtotal'], 'subtotal e a soma bruta das linhas');
        self::assertSame(0.00, (float) $sale['discount']);
        self::assertSame(300.00, (float) $sale['total']);
        self::assertSame(180.00, (float) $sale['cost_total']);
        self::assertSame(120.00, (float) $sale['profit']);

        // A reserva e o compromisso da venda: o saldo fisico continua 10 e so o
        // disponivel cai. Baixar aqui seria vender mercadoria que nao saiu.
        $stock = $this->stockRow($productId);

        self::assertSame(10.0, $stock['quantity'], 'criar venda nao pode mexer no saldo fisico');
        self::assertSame(3.0, $stock['reserved_quantity']);

        $reserve = $this->fetchOne(
            "SELECT sale_item_id FROM stock_movements WHERE product_id = ? AND type = 'RESERVE'",
            [$productId],
        );

        self::assertNotNull($reserve, 'a venda tem que gerar movimentacao de reserva');
        self::assertSame(
            (int) $data['items'][0]['id'],
            (int) $reserve['sale_item_id'],
            'a reserva precisa apontar para o item da venda',
        );
    }

    public function testSaleWithoutItemsIsRejected(): void
    {
        [$status, $body] = $this->createSale([]);

        self::assertSame(400, $status);
        self::assertStringContainsString('item', (string) ($body['message'] ?? ''));
    }

    public function testSaleBeyondAvailableStockIsRejectedAndLeavesNoTrace(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 5.0);

        [$status] = $this->createSale([['product_id' => $productId, 'quantity' => 9]]);

        self::assertSame(400, $status, 'venda acima do disponivel deveria ser recusada');

        // Nada pode sobrar: nem cabecalho, nem item, nem reserva.
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sales')['total']);
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sale_items')['total']);

        $stock = $this->stockRow($productId);

        self::assertSame(5.0, $stock['quantity']);
        self::assertSame(0.0, $stock['reserved_quantity'], 'a venda recusada nao pode reservar nada');
    }

    public function testFailureOnSecondItemRollsBackFirstReservation(): void
    {
        $first = $this->createProduct(['sku' => 'RB-A', 'name' => 'Produto A']);
        $second = $this->createProduct(['sku' => 'RB-B', 'name' => 'Produto B']);

        $this->setStock($first, 10.0);
        $this->setStock($second, 1.0);

        [$status] = $this->createSale([
            ['product_id' => $first, 'quantity' => 2],
            ['product_id' => $second, 'quantity' => 5],
        ]);

        self::assertSame(400, $status);

        // O primeiro item cabia no estoque. Se a reserva dele sobrevivesse, o
        // disponivel do produto A cairia sem existir venda para justificar.
        self::assertSame(0.0, $this->stockRow($first)['reserved_quantity']);
        self::assertSame(0.0, $this->stockRow($second)['reserved_quantity']);
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sales')['total']);
    }

    public function testInactiveProductCannotBeSold(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        // Status direto no banco: `DELETE /api/v1/products/{id}` recusa produto
        // com movimentacao de estoque (Etapa 4), e o caminho que importa aqui e
        // o campo que a venda le.
        $this->execSql("UPDATE products SET status = 'INACTIVE' WHERE id = ?", [$productId]);

        [$status, $body] = $this->createSale([['product_id' => $productId, 'quantity' => 1]]);

        self::assertSame(400, $status);
        self::assertStringContainsString('inativo', (string) ($body['message'] ?? ''));
    }

    public function testProductFromAnotherTenantCannotBeSold(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminTwo();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        self::assertSame(404, $response['status'], 'produto de outro tenant nao pode ser vendido');
    }

    public function testCustomerFromAnotherTenantIsRejected(): void
    {
        $customerId = $this->customerForTenantOne();
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminTwo();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'customer_id' => $customerId,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        // A FK `sales.customer_id` impediria o id errado na escrita, mas nao na
        // leitura: e o mesmo buraco que a Etapa 3 fechou para `supplier_id`.
        self::assertSame(404, $response['status'], 'cliente de outro tenant nao pode ser vinculado a venda');
    }

    // ------------------------------------------------------------ item extra

    public function testItemCanBeAddedToOpenSale(): void
    {
        $first = $this->createProduct(['sku' => 'ADD-1', 'name' => 'Produto 1']);
        $second = $this->createProduct(['sku' => 'ADD-2', 'name' => 'Produto 2']);

        $this->setStock($first, 10.0);
        $this->setStock($second, 10.0);

        $data = $this->openedSale([['product_id' => $first, 'quantity' => 1]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/items', [
            'product_id' => $second,
            'quantity' => 2,
        ]);

        self::assertSame(201, $response['status'], 'acrescimo de item falhou: ' . $response['body']);

        $sale = $this->responseJson($response)['data']['sale'];

        self::assertSame(300.00, (float) $sale['total']);

        // A reserva do item antigo continua 1, e o novo soma 2: o acrescimo
        // reserva so a diferenca, nao os 3 unidades de novo.
        self::assertSame(1.0, $this->stockRow($first)['reserved_quantity']);
        self::assertSame(2.0, $this->stockRow($second)['reserved_quantity']);
    }

    public function testItemCannotBeAddedToCompletedSale(): void
    {
        $saleId = $this->completedSale();
        $extra = $this->createProduct(['sku' => 'LATE', 'name' => 'Produto tardio']);
        $this->setStock($extra, 10.0);

        $this->loginAsAdminOne();
        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/items', [
            'product_id' => $extra,
            'quantity' => 1,
        ]);

        self::assertSame(400, $response['status']);
        self::assertSame(0.0, $this->stockRow($extra)['reserved_quantity']);
    }

    // ----------------------------------------------------------- conclusao

    public function testCompletingSaleConsumesReservationAndDropsBalance(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 4]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(200, $complete['status'], 'conclusao falhou: ' . $complete['body']);

        $stock = $this->stockRow($productId);

        self::assertSame(6.0, $stock['quantity'], 'concluir a venda tem que baixar o saldo fisico');
        self::assertSame(0.0, $stock['reserved_quantity'], 'concluir a venda tem que consumir a reserva');

        self::assertSame(['MANUAL_IN', 'RESERVE', 'SALE_OUT'], $this->movementTypes($productId));

        $sale = $this->fetchOne('SELECT status, completed_at FROM sales WHERE id = ?', [$saleId]);

        self::assertSame('COMPLETED', $sale['status']);
        self::assertNotNull($sale['completed_at']);
    }

    public function testEmptySaleCannotBeCompleted(): void
    {
        $saleId = $this->insertBareSale();

        $this->loginAsAdminOne();
        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('item', (string) ($this->safeJson($response)['message'] ?? ''));
    }

    public function testCompletedSaleCannotBeCompletedAgain(): void
    {
        $saleId = $this->completedSale();

        $this->loginAsAdminOne();
        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('concluida', (string) ($this->safeJson($response)['message'] ?? ''));
    }

    // ------------------------------------------------------- cancelamento

    public function testCancellingOpenSaleReleasesReservationWithoutTouchingBalance(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 4]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $cancel = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/cancel', [
            'reason' => 'Cliente desistiu.',
        ]);

        self::assertSame(200, $cancel['status'], 'cancelamento falhou: ' . $cancel['body']);

        $stock = $this->stockRow($productId);

        self::assertSame(10.0, $stock['quantity'], 'cancelar nao baixa saldo: a mercadoria nem saiu');
        self::assertSame(0.0, $stock['reserved_quantity'], 'cancelar tem que devolver a reserva ao disponivel');

        $sale = $this->fetchOne('SELECT status, cancelled_at, completed_at FROM sales WHERE id = ?', [$saleId]);

        self::assertSame('CANCELLED', $sale['status']);
        self::assertNotNull($sale['cancelled_at']);
        self::assertNull($sale['completed_at'], 'cancelar nao pode carimbar completed_at');
    }

    public function testCompletedSaleCannotBeCancelled(): void
    {
        $saleId = $this->completedSale(2.0);

        $this->loginAsAdminOne();
        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/cancel', []);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('cancelar', (string) ($this->safeJson($response)['message'] ?? ''));

        // Desfazer venda concluida e devolucao, com reentrada de mercadoria e
        // estorno financeiro. Se o cancelamento aceitasse venda concluida, o
        // estoque voltaria ao disponivel SEM a mercadoria ter entrado de novo.
        self::assertSame('COMPLETED', $this->fetchOne('SELECT status FROM sales WHERE id = ?', [$saleId])['status']);
    }

    public function testCancelledSaleCannotBeCompleted(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/cancel', []);

        $response = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(400, $response['status']);
    }

    // ---------------------------------------------------------- financeiro

    public function testFullyPaidSaleOpensNoReceivable(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 3]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'CASH', 'amount' => 300.00, 'status' => 'PAID']],
        ]);

        self::assertSame(200, $complete['status'], 'conclusao falhou: ' . $complete['body']);

        $receivables = (int) $this->fetchOne(
            'SELECT COUNT(*) AS total FROM accounts_receivable WHERE sale_id = ?',
            [$saleId],
        )['total'];

        self::assertSame(0, $receivables, 'venda totalmente paga nao tem o que receber');
    }

    public function testPartiallyPaidSaleOpensReceivableForTheBalance(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 4]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'CARD_CREDIT', 'amount' => 200.00, 'status' => 'PAID']],
        ]);

        self::assertSame(200, $complete['status'], 'conclusao falhou: ' . $complete['body']);

        $receivable = $this->fetchOne('SELECT amount, status FROM accounts_receivable WHERE sale_id = ?', [$saleId]);

        self::assertNotNull($receivable, 'o saldo em aberto precisa virar conta a receber');
        self::assertSame(200.00, (float) $receivable['amount'], 'a receber e o saldo, nao o total');
        self::assertSame('PARTIAL', $receivable['status']);
    }

    public function testPendingPaymentLeavesWholeTotalReceivable(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'BOLETO', 'amount' => 200.00, 'status' => 'PENDING']],
        ]);

        self::assertSame(200, $complete['status']);

        $receivable = $this->fetchOne('SELECT amount, status FROM accounts_receivable WHERE sale_id = ?', [$saleId]);

        self::assertSame(200.00, (float) $receivable['amount'], 'promessa de pagamento nao abate a receber');
        self::assertSame('PENDING', $receivable['status']);
    }

    public function testPaymentAboveTotalIsRejectedAndStockIsNotDropped(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'CASH', 'amount' => 500.00, 'status' => 'PAID']],
        ]);

        self::assertSame(400, $complete['status']);

        // A recusa tem que desfazer a baixa tambem: a venda nao foi concluida,
        // logo a mercadoria continua reservada e nao baixada.
        self::assertSame('OPEN', $this->fetchOne('SELECT status FROM sales WHERE id = ?', [$saleId])['status']);
        self::assertSame(0, (int) $this->fetchOne(
            'SELECT COUNT(*) AS total FROM payments WHERE sale_id = ?',
            [$saleId],
        )['total']);

        $stock = $this->stockRow($productId);

        self::assertSame(10.0, $stock['quantity']);
        self::assertSame(2.0, $stock['reserved_quantity']);
    }

    public function testInvalidPaymentMethodIsRejected(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'PIX_NAO_CADASTRADO', 'amount' => 100.00, 'status' => 'PAID']],
        ]);

        self::assertSame(400, $complete['status']);
        self::assertStringContainsString('pagamento', (string) ($this->safeJson($complete)['message'] ?? ''));
    }

    // ---------------------------------------------------------- devolucao

    public function testPartialReturnReentersStockAndReversesProportionally(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 4]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        $return = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 1]],
            'reason' => 'Produto com avaria.',
        ]);

        self::assertSame(201, $return['status'], 'devolucao falhou: ' . $return['body']);

        // 4 unidades a 100 sairam de um saldo 10: a conclusao deixou 6 e a
        // devolucao de 1 tem que trazer 1 de volta.
        self::assertSame(7.0, $this->stockRow($productId)['quantity']);
        self::assertSame(0.0, $this->stockRow($productId)['reserved_quantity']);

        $header = $this->fetchOne('SELECT id, amount, status FROM sale_returns WHERE sale_id = ?', [$saleId]);

        self::assertSame(100.00, (float) $header['amount'], 'estorno proporcional de 1 de 4 unidades');
        self::assertSame('COMPLETED', $header['status']);

        $line = $this->fetchOne(
            'SELECT sale_item_id, product_id, quantity FROM sale_return_items WHERE sale_return_id = ?',
            [(int) $header['id']],
        );

        self::assertSame($saleItemId, (int) $line['sale_item_id']);
        self::assertSame($productId, (int) $line['product_id']);
        self::assertSame(1.0, (float) $line['quantity']);

        self::assertSame(['MANUAL_IN', 'RESERVE', 'SALE_OUT', 'SALE_RETURN_IN'], $this->movementTypes($productId));

        // A venda concluida e documento historico: seus totais nao sao reescritos
        // pela devolucao. Reescrever trocaria "venda de 400 com devolucao de 100"
        // por "venda de 300", que nunca existiu.
        $sale = $this->fetchOne('SELECT status, total FROM sales WHERE id = ?', [$saleId]);

        self::assertSame('COMPLETED', $sale['status']);
        self::assertSame(400.00, (float) $sale['total']);
    }

    public function testReturnLargerThanSoldIsRejected(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        $return = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 3]],
        ]);

        self::assertSame(400, $return['status']);
        self::assertStringContainsString('excede', (string) ($this->safeJson($return)['message'] ?? ''));

        self::assertSame(8.0, $this->stockRow($productId)['quantity'], 'devolucao recusada nao entra em estoque');
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sale_returns')['total']);
    }

    public function testReturningTwiceDoesNotDoubleTheStock(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 5]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 2]],
        ]);

        self::assertSame(7.0, $this->stockRow($productId)['quantity']);

        // Segunda devolucao de 4: 5 foram vendidas e 2 ja voltaram, entao 3
        // ainda cabem. Pedir 4 tem que falhar — sem esta trava o estoque
        // ganharia 4 unidades que nunca sairam do armazem.
        $again = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 4]],
        ]);

        self::assertSame(400, $again['status']);
        self::assertSame(7.0, $this->stockRow($productId)['quantity']);

        $allowed = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 3]],
        ]);

        self::assertSame(201, $allowed['status']);
        self::assertSame(10.0, $this->stockRow($productId)['quantity']);
    }

    public function testItemFromAnotherSaleCannotBeReturned(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 20.0);

        $first = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $second = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);

        $secondSaleId = (int) $second['sale']['id'];
        $firstSaleItemId = (int) $first['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $secondSaleId . '/complete', []);

        // O item pertence a outra venda. Sem a checagem, o acumulado de devolvido
        // viria vazio e a linha passaria: o estoque cresceria com mercadoria
        // vendida no pedido errado.
        $return = $this->dispatchJson('POST', '/api/v1/sales/' . $secondSaleId . '/returns', [
            'items' => [['sale_item_id' => $firstSaleItemId, 'quantity' => 1]],
        ]);

        self::assertSame(404, $return['status']);
        self::assertStringContainsString('nesta venda', (string) ($this->safeJson($return)['message'] ?? ''));

        // Das 4 unidades reservadas, so as 2 da segunda venda foram concluidas
        // (20 -> 18). A devolucao recusada nao entra no estoque.
        self::assertSame(18.0, $this->stockRow($productId)['quantity']);
    }

    public function testOpenSaleCannotBeReturned(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $return = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 1]],
        ]);

        self::assertSame(400, $return['status']);

        // Venda aberta que nao entregou nada: o caminho dela e o cancelamento,
        // que devolve a reserva. Devolver aqui criaria estoque fantasma.
        self::assertStringContainsString('concluida', (string) ($this->safeJson($return)['message'] ?? ''));
        self::assertSame(10.0, $this->stockRow($productId)['quantity']);
        self::assertSame(2.0, $this->stockRow($productId)['reserved_quantity']);
    }

    public function testReturnReducesReceivableBalance(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 4]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        self::assertSame(400.00, (float) $this->fetchOne(
            'SELECT amount FROM accounts_receivable WHERE sale_id = ?',
            [$saleId],
        )['amount']);

        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 1]],
        ]);

        $receivable = $this->fetchOne('SELECT amount, status FROM accounts_receivable WHERE sale_id = ?', [$saleId]);

        self::assertSame(300.00, (float) $receivable['amount'], 'a devolucao tem que abater o titulo');
        self::assertSame('PARTIAL', $receivable['status']);
    }

    // -------------------------------------------------- desconto e preco

    public function testDiscountWithinTenantLimitDoesNotNeedSpecialPermission(): void
    {
        $this->setMaxDiscountPercent(1, 10.0);

        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsSeller();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 2, 'discount' => 20.00]],
        ]);

        // 20 de desconto em 200 sao 10%, exatamente o limite.
        self::assertSame(201, $response['status'], 'desconto dentro do limite: ' . $response['body']);
        self::assertSame(180.00, (float) $this->responseJson($response)['data']['sale']['total']);
    }

    public function testDiscountAboveTenantLimitIsRefusedWithoutPermission(): void
    {
        $this->setMaxDiscountPercent(1, 10.0);

        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsSeller();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 2, 'discount' => 60.00]],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString(
            'sales.discount',
            (string) ($this->safeJson($response)['message'] ?? ''),
        );

        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sales')['total']);
    }

    public function testDiscountAboveTenantLimitIsAllowedWithPermission(): void
    {
        $this->setMaxDiscountPercent(1, 10.0);

        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 2, 'discount' => 60.00]],
            'reason' => 'Campanha de fidelidade.',
        ]);

        self::assertSame(201, $response['status'], 'admin tem sales.discount: ' . $response['body']);
        self::assertSame(140.00, (float) $this->responseJson($response)['data']['sale']['total']);
    }

    public function testTenantWithoutConfiguredLimitRefusesAnyDiscountWithoutPermission(): void
    {
        // Teto zero e o padrao de `tenant_settings.max_discount_percent`. Um
        // tenant que nunca foi ate a configuracoes nao pode ter desconto livre:
        // o padrao generoso aqui viraria desconto sem permissao em qualquer
        // empresa recem-criada.
        $this->setMaxDiscountPercent(1, 0.0);

        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsSeller();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 2, 'discount' => 1.00]],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('0,00%', (string) ($this->safeJson($response)['message'] ?? ''));
    }

    public function testSaleLevelDiscountIsSpreadAcrossItemsAndTotalsStillAddUp(): void
    {
        $this->setMaxDiscountPercent(1, 50.0);

        $first = $this->createProduct(['sku' => 'DST-1', 'name' => 'Produto 1']);
        $second = $this->createProduct(['sku' => 'DST-2', 'name' => 'Produto 2']);

        $this->setStock($first, 10.0);
        $this->setStock($second, 10.0);

        $this->loginAsAdminOne();

        // 10 unidades e 3 unidades, ambos a 100. Com 45 de desconto sobre 1300, o
        // rateio em centavos inteiros da 3462 e 1038 centavos, com a sobra de 1
        // indo para a maior linha. Se a sobra ficasse fora, a soma dos itens nao
        // fecharia com o desconto concedido.
        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [
                ['product_id' => $first, 'quantity' => 10],
                ['product_id' => $second, 'quantity' => 3],
            ],
            'discount' => 45.00,
            'reason' => 'Desconto de campanha.',
        ]);

        self::assertSame(201, $response['status'], 'venda com desconto de cabecalho: ' . $response['body']);

        $data = $this->responseJson($response)['data'];
        $sale = $data['sale'];

        $itemDiscount = array_sum(array_map(
            static fn (array $item): float => (float) $item['discount'],
            $data['items'],
        ));
        $itemSubtotal = array_sum(array_map(
            static fn (array $item): float => (float) $item['subtotal'],
            $data['items'],
        ));

        self::assertSame(45.00, round($itemDiscount, 2), 'o rateio tem de fechar no desconto concedido');
        self::assertSame(1300.00, round($itemSubtotal, 2));
        self::assertSame(45.00, (float) $sale['discount']);
        self::assertSame(1255.00, round((float) $sale['total'], 2));
    }

    public function testDiscountBiggerThanItemIsRejected(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1, 'discount' => 150.00]],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('desconto', (string) ($this->safeJson($response)['message'] ?? ''));
    }

    public function testPriceBelowListRequiresPermission(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsSeller();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1, 'sale_price' => 50.00]],
        ]);

        self::assertSame(400, $response['status']);
        self::assertStringContainsString(
            'sales.change_price',
            (string) ($this->safeJson($response)['message'] ?? ''),
        );
    }

    public function testPriceChangeRecordsOriginalPrice(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [[
                'product_id' => $productId,
                'quantity' => 1,
                'sale_price' => 90.00,
                'reason' => 'Cliente corporativo.',
            ]],
        ]);

        self::assertSame(201, $response['status'], 'admin tem sales.change_price: ' . $response['body']);

        $data = $this->responseJson($response)['data'];

        self::assertSame(90.00, (float) $data['items'][0]['sale_price'], 'o cobrado e o preco concedido');
        self::assertSame(100.00, (float) $data['items'][0]['list_price'], 'o de tabela vem separado');

        $discount = $this->fetchOne('SELECT type, value, reason FROM sale_discounts WHERE sale_id = ?', [
            (int) $data['sale']['id'],
        ]);

        self::assertNotNull($discount, 'a mudanca de preco tem que ser auditavel');
        self::assertSame('PRICE_CHANGE', $discount['type']);
        self::assertSame(100.00, (float) $discount['value'], 'o registro guarda o preco ORIGINAL');
        self::assertStringContainsString('corporativo', (string) $discount['reason']);
    }

    public function testPriceChangeWithoutReasonIsRejected(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1, 'sale_price' => 90.00]],
        ]);

        self::assertSame(400, $response['status']);

        // Sem justificativa, `sale_discounts` registraria "o preco era 100" sem
        // responder POR QUE virou 90 — um registro que nao serve para auditar.
        self::assertStringContainsString('justificativa', (string) ($this->safeJson($response)['message'] ?? ''));
    }

    public function testPriceEqualToListDoesNotRequirePermission(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->loginAsSeller();

        // Informar o mesmo preco de tabela nao e mudanca de preco: exigir
        // permissao aqui faria a tela de venda pedir justificativa para nada.
        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1, 'sale_price' => 100.00]],
        ]);

        self::assertSame(201, $response['status'], $response['body']);
        self::assertSame(0, (int) $this->fetchOne('SELECT COUNT(*) AS total FROM sale_discounts')['total']);
    }

    // --------------------------------------------------------- permissoes

    public function testViewerHasNoAccessToSales(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);
        $saleId = (int) $data['sale']['id'];

        $this->logout();
        $this->assertLogin('viewer1@example.com');

        // O perfil "viewer" do seed nao tem nenhuma permissao `sales.*`. Nem
        // leitura: quem nao pode registrar venda nao tem por que listar venda
        // alheia.
        self::assertSame(403, $this->dispatchJson('GET', '/api/v1/sales')['status']);

        $store = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $productId, 'quantity' => 1]],
        ]);
        self::assertSame(403, $store['status'], 'o viewer nao tem sales.create');

        $complete = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);
        self::assertSame(403, $complete['status'], 'o viewer nao tem sales.create para concluir');

        $cancel = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/cancel', []);
        self::assertSame(403, $cancel['status'], 'o viewer nao tem sales.cancel');

        $return = $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => (int) $data['items'][0]['id'], 'quantity' => 1]],
        ]);
        self::assertSame(403, $return['status'], 'o viewer nao tem sales.return');

        // Nenhuma das tentativas pode ter mexido no estoque nem no status.
        $stock = $this->stockRow($productId);

        self::assertSame(10.0, $stock['quantity']);
        self::assertSame(1.0, $stock['reserved_quantity']);
        self::assertSame('OPEN', $this->fetchOne('SELECT status FROM sales WHERE id = ?', [$saleId])['status']);
    }

    public function testSaleFromAnotherTenantIsNotVisible(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminTwo();

        // 404, e nao 403: responder "existe mas voce nao pode" ja confirma que o
        // id existe para outra empresa.
        self::assertSame(404, $this->dispatchJson('GET', '/api/v1/sales/' . $saleId)['status']);

        $list = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales'));

        self::assertSame(0, $list['meta']['total'], 'a listagem do tenant 2 nao traz a venda do tenant 1');
    }

    // ----------------------------------------------------------- consultas

    public function testOptionsExposeClosedListsAndTenantDiscountLimit(): void
    {
        $this->setMaxDiscountPercent(1, 7.5);

        $this->loginAsAdminOne();

        $response = $this->dispatchJson('GET', '/api/v1/sales/options');

        self::assertSame(200, $response['status'], 'options nao pode ser engolida pela rota {id}');

        $data = $this->responseJson($response)['data'];

        self::assertSame(['OPEN', 'COMPLETED', 'CANCELLED'], $data['sale_statuses']);
        self::assertSame(['PENDING', 'PAID', 'PARTIAL', 'REFUNDED'], $data['payment_statuses']);
        self::assertContains('CASH', $data['payment_methods']);
        self::assertSame(7.5, (float) $data['max_discount_percent']);
    }

    public function testSaleNumbersAreSequentialPerTenant(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $first = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);
        $second = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);

        self::assertSame('V00001-T001', $first['sale']['sale_number']);
        self::assertSame('V00002-T001', $second['sale']['sale_number']);

        // O tenant 2 recomeca a sequencia: o numero nao pode carregar o id de
        // outra empresa.
        $this->loginAsAdminTwo();

        $other = $this->createProductForTenantTwo();

        $stock = $this->dispatchJson('POST', '/api/v1/stock/adjustments', [
            'product_id' => $other,
            'type' => 'MANUAL_IN',
            'quantity' => 5.0,
            'notes' => 'Carga inicial.',
        ]);
        self::assertSame(201, $stock['status'], 'carga do tenant 2: ' . $stock['body']);

        $response = $this->dispatchJson('POST', '/api/v1/sales', [
            'items' => [['product_id' => $other, 'quantity' => 1]],
        ]);

        self::assertSame(201, $response['status'], 'venda do tenant 2: ' . $response['body']);
        self::assertSame('V00001-T002', $this->responseJson($response)['data']['sale']['sale_number']);
    }

    public function testListCanBeFilteredByStatus(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 20.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);
        $saleId = (int) $data['sale']['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', []);

        $completed = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales?status=COMPLETED'));
        $open = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales?status=OPEN'));

        self::assertSame(1, $completed['meta']['total']);
        self::assertSame('COMPLETED', $completed['data'][0]['status']);
        self::assertSame(0, $open['meta']['total']);
    }

    public function testListCanBeSearchedBySaleNumber(): void
    {
        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $this->openedSale([['product_id' => $productId, 'quantity' => 1]]);

        $this->loginAsAdminOne();

        $found = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales?search=V00001'));
        $missing = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales?search=V99999'));

        self::assertSame(1, $found['meta']['total']);
        self::assertSame(0, $missing['meta']['total']);
    }

    public function testInvalidStatusFilterIsRejected(): void
    {
        $this->loginAsAdminOne();

        self::assertSame(400, $this->dispatchJson('GET', '/api/v1/sales?status=QUASE_LA')['status']);
    }

    public function testDetailExposesPaymentsDiscountsAndReturns(): void
    {
        $this->setMaxDiscountPercent(1, 50.0);

        $productId = $this->createProduct();
        $this->setStock($productId, 10.0);

        $data = $this->openedSale([['product_id' => $productId, 'quantity' => 2, 'discount' => 20.00]]);
        $saleId = (int) $data['sale']['id'];
        $saleItemId = (int) $data['items'][0]['id'];

        $this->loginAsAdminOne();
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/complete', [
            'payments' => [['method' => 'CASH', 'amount' => 100.00, 'status' => 'PAID']],
        ]);
        $this->dispatchJson('POST', '/api/v1/sales/' . $saleId . '/returns', [
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 1]],
        ]);

        $detail = $this->responseJson($this->dispatchJson('GET', '/api/v1/sales/' . $saleId))['data'];

        self::assertSame(1, count($detail['payments']));
        self::assertSame(100.00, (float) $detail['paid_total']);
        self::assertSame(1, count($detail['discounts']));
        self::assertSame(1, count($detail['returns']));
        self::assertSame(1, count($detail['returns'][0]['items']));

        // 2 unidades a 100 com 20 de desconto: liquido de 180, logo 90 por
        // unidade. Devolver 1 estorna 90 dos 100 que ficaram em aberto.
        self::assertSame(90.00, (float) $detail['returned_total']);
        self::assertSame(0.00, (float) $detail['receivable']['amount']);
        self::assertSame(
            'CANCELLED',
            $detail['receivable']['status'],
            'titulo zerado por devolucao e cancelado, nao pago: ninguem quitou',
        );
    }

    // ------------------------------------------------------ infra interna

    /**
     * Perfil de vendas: tem `sales.view` e `sales.create`, e NAO tem
     * `sales.discount` nem `sales.change_price`. E o perfil que o roteiro
     * descreve — quem registra venda, sem poder conceder desconto ou mexer no
     * preco de tabela.
     */
    private function loginAsSeller(): void
    {
        $this->loginAsAdminOne();

        $role = $this->dispatchJson('POST', '/api/v1/roles', [
            'name' => 'vendedor',
            'description' => 'Registra venda sem desconto nem mudanca de preco.',
        ]);

        self::assertSame(201, $role['status'], 'criacao do perfil falhou: ' . $role['body']);

        $roleId = (int) $this->responseJson($role)['data']['id'];

        $permissions = $this->responseJson($this->dispatchJson('GET', '/api/v1/roles/permissions'))['data'];

        $wanted = ['dashboard.view', 'products.view', 'customers.view', 'sales.view', 'sales.create'];
        $ids = [];

        foreach ($permissions as $permission) {
            if (in_array($permission['name'], $wanted, true)) {
                $ids[] = (int) $permission['id'];
            }
        }

        self::assertCount(count($wanted), $ids, 'o vendedor deveria receber as permissoes basicas');

        $update = $this->dispatchJson('PUT', '/api/v1/roles/' . $roleId . '/permissions', [
            'permission_ids' => $ids,
        ]);

        self::assertSame(200, $update['status'], 'atribuicao de permissoes falhou: ' . $update['body']);

        $user = $this->dispatchJson('POST', '/api/v1/users', [
            'name' => 'Vendedor Um',
            'email' => 'vendedor1@example.com',
            'password' => 'secret123',
            'role_id' => $roleId,
        ]);

        self::assertSame(201, $user['status'], 'criacao do vendedor falhou: ' . $user['body']);

        $this->logout();
        $this->assertLogin('vendedor1@example.com');
    }

    private function customerForTenantOne(): int
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/customers', [
            'name' => 'Cliente do tenant 1',
            'document' => '12345678901',
        ]);

        self::assertSame(201, $response['status'], 'criacao do cliente falhou: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    private function createProductForTenantTwo(): int
    {
        $response = $this->dispatchJson('POST', '/api/v1/products', [
            'sku' => 'SALE-T2',
            'name' => 'Produto do tenant 2',
            'unit' => 'UN',
            'price' => [
                'cost_price' => 10.00,
                'sale_price' => 20.00,
                'minimum_price' => 15.00,
                'margin' => 10.00,
            ],
        ]);

        self::assertSame(201, $response['status'], 'produto do tenant 2: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    /**
     * Venda sem item, montada direto porque a API nao produz esse estado:
     * `POST /api/v1/sales` exige pelo menos um item. E exatamente o estado que
     * `complete()` precisa recusar.
     */
    private function insertBareSale(): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->insertSql(
            'INSERT INTO sales (tenant_id, user_id, sale_number, status, subtotal, discount, total,
                                cost_total, profit, sale_date, created_at, updated_at)
             VALUES (1, 1, ?, ?, 0, 0, 0, 0, 0, ?, ?, ?)',
            ['V09999-T001', 'OPEN', $now, $now, $now],
        );
    }
}