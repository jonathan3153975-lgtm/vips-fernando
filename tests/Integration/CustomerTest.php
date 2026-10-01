<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Clientes e CRM (EPIC 07).
 *
 * O foco e no que o usuario mais sente errado quando quebra: documento
 * duplicado passando, cliente de outra empresa vazando na listagem, e cliente com
 * historico sendo apagado — o que faria a venda perder para quem comprou.
 */
final class CustomerTest extends ApiIntegrationTestCase
{
    /**
     * O login e idempotente porque o GuestMiddleware responde 409 quando a
     * sessao ja esta autenticada, e varios testes criam mais de um cliente.
     */
    private bool $adminLoggedIn = false;

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

    private function loginAsAdminTwo(): void
    {
        // `$_SESSION = []` antes do login: o GuestMiddleware responde 409 quando a
        // sessao ja esta autenticada, e estes testes trocam de usuario no meio do
        // caminho (o mesmo padrao de CrossTenantTest).
        $_SESSION = [];

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'admin2@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'login do admin do tenant 2 falhou');
    }

    private function loginAsViewerOne(): void
    {
        $_SESSION = [];

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => 'viewer1@example.com',
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'login do viewer do tenant 1 falhou');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCustomer(array $overrides = []): int
    {
        $this->loginAsAdminOne();

        $payload = array_merge(['name' => 'Cliente de teste'], $overrides);

        $response = $this->dispatchJson('POST', '/api/v1/customers', $payload);

        self::assertSame(201, $response['status'], 'criacao do cliente falhou: ' . $response['body']);

        return (int) $this->responseJson($response)['data']['id'];
    }

    /**
     * Venda direta no banco: o modulo de vendas ainda nao existe, e o historico
     * precisa ser testado com linha realista (status, total, lucro, data).
     */
    private function insertSale(int $customerId, array $overrides = []): int
    {
        $this->loginAsAdminOne();

        $sale = array_merge([
            'customer_id' => $customerId,
            'sale_number' => 'S-' . bin2hex(random_bytes(3)),
            'status' => 'COMPLETED',
            'subtotal' => 100.0,
            'discount' => 0.0,
            'total' => 100.0,
            'cost_total' => 60.0,
            'profit' => 40.0,
            'sale_date' => '2026-08-15 10:00:00',
            'completed_at' => '2026-08-15 10:05:00',
        ], $overrides);

        return $this->execSql(
            'INSERT INTO sales (tenant_id, user_id, customer_id, sale_number, status, subtotal, discount, total, cost_total, profit, sale_date, completed_at, created_at, updated_at)
             VALUES (1, 1, :customer_id, :sale_number, :status, :subtotal, :discount, :total, :cost_total, :profit, :sale_date, :completed_at, :created_at, :updated_at)',
            [
                'customer_id' => $sale['customer_id'],
                'sale_number' => $sale['sale_number'],
                'status' => $sale['status'],
                'subtotal' => $sale['subtotal'],
                'discount' => $sale['discount'],
                'total' => $sale['total'],
                'cost_total' => $sale['cost_total'],
                'profit' => $sale['profit'],
                'sale_date' => $sale['sale_date'],
                'completed_at' => $sale['completed_at'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Quantos clientes existem. `fetchColumnList()` devolve nomes de coluna, nao
     * linhas — por isso um COUNT direto.
     */
    private function customerCount(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM customers');

        return (int) ($row['total'] ?? 0);
    }

    // ---------------------------------------------------------------- cadastro

    public function test_cadastra_cliente_com_documento_normalizado_para_digitos(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/customers', [
            'name' => 'Maria Souza',
            'document' => '123.456.789-01',
            'phone' => '(11) 98888-0000',
            'email' => 'maria@example.com',
        ]);

        self::assertSame(201, $response['status'], $response['body']);

        $data = $this->responseJson($response)['data'];

        self::assertSame('Maria Souza', $data['name']);
        // Guardar como digitado faria o UNIQUE(tenant_id, document) deixar de
        // pegar "123.456.789-01" vs "12345678901" como o mesmo documento.
        self::assertSame('12345678901', $data['document']);
        self::assertSame('ACTIVE', $data['status']);

        $stored = $this->fetchOne('SELECT document, email FROM customers WHERE id = :id', ['id' => $data['id']]);

        self::assertNotNull($stored, 'cliente nao foi gravado no banco');
        self::assertSame('12345678901', $stored['document']);
    }

    public function test_documento_vazio_fica_nulo_e_permite_varios_clientes_sem_documento(): void
    {
        $this->loginAsAdminOne();

        $first = $this->dispatchJson('POST', '/api/v1/customers', ['name' => 'Sem documento A']);
        $second = $this->dispatchJson('POST', '/api/v1/customers', ['name' => 'Sem documento B']);

        self::assertSame(201, $first['status'], $first['body']);
        self::assertSame(201, $second['status'], $second['body']);

        // Sem documento e caso legitimo. Se guardassemos string vazia, o segundo
        // cliente bateria no UNIQUE e a API devolveria "documento duplicado".
        self::assertNull($this->responseJson($first)['data']['document']);
        self::assertNull($this->responseJson($second)['data']['document']);
        self::assertSame(2, $this->customerCount());
    }

    /**
     * O navegador sempre manda o campo vazio quando o usuario deixa o input em
     * branco: `document=` chega como string vazia (e com espacos, se sobrou
     * espaco). Esse caminho e diferente de "campo ausente", e e justamente o
     * que rebateria no UNIQUE se guardassemos '' em vez de NULL.
     */
    public function test_documento_em_branco_via_formulario_vira_nulo_e_nao_colide(): void
    {
        $this->loginAsAdminOne();

        $vazio = $this->dispatchUserForm('POST', '/clientes', [
            'name' => 'Campo em branco',
            'document' => '',
        ]);

        self::assertSame(302, $vazio['status'], $vazio['body']);

        $comEspacos = $this->dispatchUserForm('POST', '/clientes', [
            'name' => 'Com espacos',
            'document' => '   ',
        ]);

        self::assertSame(302, $comEspacos['status'], $comEspacos['body']);
        self::assertSame(2, $this->customerCount(), 'documento em branco nao pode virar conflito de unicidade');

        $documents = array_column($this->fetchAllRows('SELECT document FROM customers ORDER BY id'), 'document');

        self::assertSame([null, null], $documents, 'documento em branco tem de ser guardado como NULL');
    }

    /**
     * Documento só com pontuação tambem e "sem documento": ".-/ ." nao tem
     * digito nenhum.
     */
    public function test_documento_so_com_pontuacao_vira_nulo(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchJson('POST', '/api/v1/customers', [
            'name' => 'Documento simbolico',
            'document' => '.-/.',
        ]);

        self::assertSame(201, $response['status'], $response['body']);
        self::assertNull($this->responseJson($response)['data']['document']);
    }

    public function test_rejeita_documento_duplicado_no_mesmo_tenant(): void
    {
        $this->createCustomer(['name' => 'Primeiro', 'document' => '12345678901']);

        $response = $this->dispatchJson('POST', '/api/v1/customers', [
            'name' => 'Segundo',
            // Mesma pessoa com mascara diferente.
            'document' => '123.456.789-01',
        ]);

        self::assertSame(400, $response['status'], $response['body']);
        self::assertStringContainsString('documento', $this->responseJson($response)['message']);
        self::assertSame(1, $this->customerCount(), 'nenhum cliente duplicado deve ter sido criado');
    }

    public function test_documento_igual_em_outro_tenant_e_permitido(): void
    {
        $this->createCustomer(['name' => 'Cliente do tenant 1', 'document' => '12345678901']);

        $this->loginAsAdminTwo();

        $response = $this->dispatchJson('POST', '/api/v1/customers', [
            'name' => 'Cliente do tenant 2',
            'document' => '12345678901',
        ]);

        self::assertSame(201, $response['status'], $response['body']);
        self::assertSame('12345678901', $this->responseJson($response)['data']['document']);
    }

    public function test_nome_obrigatorio_e_email_invalido_sao_rejeitados(): void
    {
        $this->loginAsAdminOne();

        $blankName = $this->dispatchJson('POST', '/api/v1/customers', ['name' => '   ']);
        $badEmail = $this->dispatchJson('POST', '/api/v1/customers', ['name' => 'Com email ruim', 'email' => 'nao-e-email']);

        self::assertSame(400, $blankName['status'], $blankName['body']);
        self::assertSame(400, $badEmail['status'], $badEmail['body']);
        self::assertSame(0, $this->customerCount());
    }

    public function test_atualiza_cliente_e_rejeita_documento_de_outro_cliente(): void
    {
        $first = $this->createCustomer(['name' => 'Primeiro', 'document' => '11111111111']);
        $second = $this->createCustomer(['name' => 'Segundo', 'document' => '22222222222']);

        $ok = $this->dispatchJson('PUT', '/api/v1/customers/' . $second, ['name' => 'Segundo editado', 'document' => '33333333333']);

        self::assertSame(200, $ok['status'], $ok['body']);
        self::assertSame('Segundo editado', $this->responseJson($ok)['data']['name']);

        $clash = $this->dispatchJson('PUT', '/api/v1/customers/' . $second, ['name' => 'Segundo', 'document' => '11111111111']);

        self::assertSame(400, $clash['status'], $clash['body']);

        // Editar mantendo o proprio documento tem de funcionar: senao o segundo
        // PUT do mesmo registro rebateria sempre.
        $sameDocument = $this->dispatchJson('PUT', '/api/v1/customers/' . $second, ['name' => 'Segundo de novo', 'document' => '33333333333']);

        self::assertSame(200, $sameDocument['status'], $sameDocument['body']);
        self::assertSame('Segundo de novo', $this->responseJson($sameDocument)['data']['name']);

        // O primeiro cliente nao pode ter mudado.
        $firstRow = $this->fetchOne('SELECT name FROM customers WHERE id = :id', ['id' => $first]);

        self::assertSame('Primeiro', $firstRow['name']);
    }

    // -------------------------------------------------------------- isolamento

    public function test_cliente_de_outro_tenant_responde_404_em_todas_as_rotas(): void
    {
        $customerId = $this->createCustomer(['name' => 'Segredo do tenant 1']);

        $this->loginAsAdminTwo();

        $show = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId);
        $history = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history');
        $update = $this->dispatchJson('PUT', '/api/v1/customers/' . $customerId, ['name' => 'Invadido']);
        $destroy = $this->dispatchJson('DELETE', '/api/v1/customers/' . $customerId);
        $listing = $this->dispatchJson('GET', '/api/v1/customers');

        // 404 e nao 403: para quem perguntou o cliente nao existe, e dizer que
        // ele existe em outra empresa ja entregaria informacao.
        self::assertSame(404, $show['status'], $show['body']);
        self::assertSame(404, $history['status'], $history['body']);
        self::assertSame(404, $update['status'], $update['body']);
        self::assertSame(404, $destroy['status'], $destroy['body']);
        self::assertSame(200, $listing['status']);
        self::assertSame(0, $this->responseJson($listing)['meta']['total'], 'cliente alheio na listagem');
    }

    // ------------------------------------------------------------------- busca

    public function test_busca_encontra_por_nome_documento_e_contato(): void
    {
        $this->createCustomer([
            'name' => 'Joao Pereira',
            'document' => '55566677788',
            'phone' => '11988887777',
            'email' => 'joao@example.com',
        ]);

        $byName = $this->dispatchJson('GET', '/api/v1/customers?search=Pereira');
        $byDocument = $this->dispatchJson('GET', '/api/v1/customers?search=55566677788');
        $byPhone = $this->dispatchJson('GET', '/api/v1/customers?search=88887777');
        $byEmail = $this->dispatchJson('GET', '/api/v1/customers?search=joao@example.com');
        $miss = $this->dispatchJson('GET', '/api/v1/customers?search=nao-existe');

        self::assertSame(1, $this->responseJson($byName)['meta']['total'], $byName['body']);
        self::assertSame(1, $this->responseJson($byDocument)['meta']['total']);
        self::assertSame(1, $this->responseJson($byPhone)['meta']['total']);
        self::assertSame(1, $this->responseJson($byEmail)['meta']['total']);
        self::assertSame(0, $this->responseJson($miss)['meta']['total']);
    }

    public function test_filtro_de_situacao_separa_ativo_de_inativo(): void
    {
        $active = $this->createCustomer(['name' => 'Ativo', 'document' => '90000000001']);
        $inactive = $this->createCustomer(['name' => 'Inativo', 'document' => '90000000002']);

        $this->dispatchJson('PUT', '/api/v1/customers/' . $inactive, ['name' => 'Inativo', 'status' => 'INACTIVE']);

        $onlyActive = $this->dispatchJson('GET', '/api/v1/customers?status=ACTIVE');
        $onlyInactive = $this->dispatchJson('GET', '/api/v1/customers?status=INACTIVE');

        self::assertSame(1, $this->responseJson($onlyActive)['meta']['total'], $onlyActive['body']);
        self::assertSame((int) $active, (int) $this->responseJson($onlyActive)['data'][0]['id']);
        self::assertSame(1, $this->responseJson($onlyInactive)['meta']['total']);
        self::assertSame((int) $inactive, (int) $this->responseJson($onlyInactive)['data'][0]['id']);
    }

    // ------------------------------------------------------------- paginacao

    public function test_paginacao_limita_a_pagina_e_total_confere(): void
    {
        $this->loginAsAdminOne();

        for ($index = 1; $index <= 7; $index++) {
            $created = $this->dispatchJson('POST', '/api/v1/customers', [
                'name' => sprintf('Cliente %02d', $index),
                'document' => sprintf('%011d', $index),
            ]);

            self::assertSame(201, $created['status'], $created['body']);
        }

        $firstPage = $this->dispatchJson('GET', '/api/v1/customers?per_page=3&page=1');
        $secondPage = $this->dispatchJson('GET', '/api/v1/customers?per_page=3&page=2');
        $lastPage = $this->dispatchJson('GET', '/api/v1/customers?per_page=3&page=3');

        // O LIMIT precisa estar no SQL: sem ele a pagina 1 devolveria os 7.
        self::assertCount(3, $this->responseJson($firstPage)['data'], $firstPage['body']);
        self::assertCount(3, $this->responseJson($secondPage)['data'], $secondPage['body']);
        self::assertCount(1, $this->responseJson($lastPage)['data'], $lastPage['body']);

        // `total` precisa contar o filtro inteiro, nao a pagina.
        self::assertSame(7, $this->responseJson($firstPage)['meta']['total']);
        self::assertSame(7, $this->responseJson($lastPage)['meta']['total']);
        self::assertSame(3, $this->responseJson($firstPage)['meta']['per_page']);
        self::assertSame(2, $this->responseJson($secondPage)['meta']['page']);

        // Paginas diferentes, sem repetir nem perder cliente.
        $ids = [];

        foreach ([$firstPage, $secondPage, $lastPage] as $page) {
            foreach ($this->responseJson($page)['data'] as $customer) {
                $ids[] = (int) $customer['id'];
            }
        }

        self::assertCount(7, $ids);
        self::assertSame($ids, array_values(array_unique($ids)), 'paginas repetiram cliente');
    }

    public function test_total_conferindo_com_busca_e_nao_com_a_base_inteira(): void
    {
        $this->createCustomer(['name' => 'Ana Souza', 'document' => '70000000001']);
        $this->createCustomer(['name' => 'Bruno Lima', 'document' => '70000000002']);

        $filtered = $this->dispatchJson('GET', '/api/v1/customers?search=Ana');

        self::assertSame(1, $this->responseJson($filtered)['meta']['total'], $filtered['body']);
        self::assertCount(1, $this->responseJson($filtered)['data']);
    }

    // ------------------------------------------------------- historico comercial

    public function test_resumo_comercial_soma_somente_vendas_concluidas(): void
    {
        $customerId = $this->createCustomer(['name' => 'Comprador', 'document' => '80000000001']);

        $this->insertSale($customerId, ['sale_number' => 'V-1', 'status' => 'COMPLETED', 'total' => 100.0, 'profit' => 40.0, 'sale_date' => '2026-08-01 10:00:00']);
        $this->insertSale($customerId, ['sale_number' => 'V-2', 'status' => 'COMPLETED', 'total' => 300.0, 'profit' => 90.0, 'sale_date' => '2026-08-10 10:00:00']);
        // Estas duas nao podem entrar em "total gasto": uma ainda nao fechou, a
        // outra foi desfeita. SomarCanceled inventaria faturamento.
        $this->insertSale($customerId, ['sale_number' => 'V-3', 'status' => 'OPEN', 'total' => 500.0, 'profit' => 200.0]);
        $this->insertSale($customerId, ['sale_number' => 'V-4', 'status' => 'CANCELLED', 'total' => 900.0, 'profit' => 400.0]);

        $response = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId);

        self::assertSame(200, $response['status'], $response['body']);

        $payload = $this->responseJson($response)['data'];

        self::assertSame(2, $payload['summary']['purchase_count'], 'so as duas concluidas contam');
        self::assertEqualsWithDelta(400.0, (float) $payload['summary']['total_spent'], 0.001, 'total gasto');
        self::assertEqualsWithDelta(130.0, (float) $payload['summary']['total_profit'], 0.001, 'lucro');
        self::assertEqualsWithDelta(200.0, (float) $payload['summary']['average_ticket'], 0.001, 'ticket medio');
        self::assertSame('2026-08-10 10:00:00', $payload['summary']['last_purchase_at']);
    }

    public function test_listagem_mostra_resumo_comercial_sem_n_mais_um(): void
    {
        $comCompras = $this->createCustomer(['name' => 'Comprador', 'document' => '81000000001']);
        $this->createCustomer(['name' => 'Nunca comprou', 'document' => '81000000002']);

        $this->insertSale($comCompras, ['sale_number' => 'V-10', 'total' => 250.0, 'profit' => 100.0]);

        $listing = $this->dispatchJson('GET', '/api/v1/customers');

        self::assertSame(200, $listing['status'], $listing['body']);

        $byId = [];

        foreach ($this->responseJson($listing)['data'] as $customer) {
            $byId[(int) $customer['id']] = $customer;
        }

        self::assertSame(1, (int) $byId[$comCompras]['purchase_count']);
        self::assertEqualsWithDelta(250.0, (float) $byId[$comCompras]['total_spent'], 0.001);
        self::assertSame('2026-08-15 10:00:00', $byId[$comCompras]['last_purchase_at']);

        // Cliente sem venda vem com zero, e nao com null nem com linha faltando.
        $semCompras = array_values(array_filter($byId, static fn (array $c): bool => (int) $c['id'] !== $comCompras));

        self::assertCount(1, $semCompras);
        self::assertSame(0, (int) $semCompras[0]['purchase_count']);
        self::assertEqualsWithDelta(0.0, (float) $semCompras[0]['total_spent'], 0.001);
        self::assertNull($semCompras[0]['last_purchase_at']);
    }

    public function test_filtro_de_compras_separa_quem_ja_comprou(): void
    {
        $comCompras = $this->createCustomer(['name' => 'Comprador', 'document' => '82000000001']);
        $this->createCustomer(['name' => 'Nunca comprou', 'document' => '82000000002']);

        $this->insertSale($comCompras, ['sale_number' => 'V-20']);

        $comprou = $this->dispatchJson('GET', '/api/v1/customers?has_sales=1');
        $nuncaComprou = $this->dispatchJson('GET', '/api/v1/customers?has_sales=0');

        self::assertSame(1, $this->responseJson($comprou)['meta']['total'], $comprou['body']);
        self::assertSame($comCompras, (int) $this->responseJson($comprou)['data'][0]['id']);
        self::assertSame(1, $this->responseJson($nuncaComprou)['meta']['total'], $nuncaComprou['body']);
        self::assertNotSame($comCompras, (int) $this->responseJson($nuncaComprou)['data'][0]['id']);
    }

    public function test_historico_lista_vendas_do_cliente_com_item_count(): void
    {
        $customerId = $this->createCustomer(['name' => 'Comprador', 'document' => '83000000001']);
        $outro = $this->createCustomer(['name' => 'Outro cliente', 'document' => '83000000002']);

        $saleId = $this->insertSale($customerId, ['sale_number' => 'V-30', 'total' => 42.0]);
        $this->insertSale($customerId, ['sale_number' => 'V-31', 'total' => 8.0]);
        $this->insertSale($outro, ['sale_number' => 'V-32', 'total' => 999.0]);

        $this->execSql(
            'INSERT INTO sale_items (sale_id, product_id, quantity, cost_price, sale_price, discount, subtotal, created_at, updated_at)
             VALUES (:sale_id, 1, 2, 10.0, 21.0, 0, 42.0, :created_at, :updated_at)',
            ['sale_id' => $saleId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        );

        $response = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history');

        self::assertSame(200, $response['status'], $response['body']);

        $payload = $this->responseJson($response);

        // A venda do outro cliente nao pode aparecer no historico deste.
        self::assertSame(2, $payload['meta']['total'], $response['body']);
        self::assertCount(2, $payload['data']);

        $numbers = array_column($payload['data'], 'sale_number');

        self::assertContains('V-30', $numbers);
        self::assertContains('V-31', $numbers);
        self::assertNotContains('V-32', $numbers);

        // Ordenacao e `sale_date DESC, id DESC`; as duas vendas usam a data
        // padrao, entao V-31 (id maior) vem primeiro. O item foi para V-30.
        self::assertSame('V-31', $payload['data'][0]['sale_number'], 'ordem deve ser pela data mais recente');
        self::assertSame(0, (int) $payload['data'][0]['item_count'], 'V-31 nao tem item');

        $comItem = $payload['data'][1];

        self::assertSame('V-30', $comItem['sale_number']);
        self::assertSame(1, (int) $comItem['item_count'], 'V-30 tem um item e o item_count tem de contar');
    }

    public function test_historico_filtra_por_situacao_da_venda(): void
    {
        $customerId = $this->createCustomer(['name' => 'Comprador', 'document' => '84000000001']);

        $this->insertSale($customerId, ['sale_number' => 'V-40', 'status' => 'COMPLETED']);
        $this->insertSale($customerId, ['sale_number' => 'V-41', 'status' => 'CANCELLED']);

        $cancelled = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history?status=cancelled');
        $all = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history');

        self::assertSame(1, $this->responseJson($cancelled)['meta']['total'], $cancelled['body']);
        self::assertSame('V-41', $this->responseJson($cancelled)['data'][0]['sale_number']);
        self::assertSame(2, $this->responseJson($all)['meta']['total']);
    }

    public function test_historico_pagina(): void
    {
        $customerId = $this->createCustomer(['name' => 'Comprador', 'document' => '85000000001']);

        for ($index = 1; $index <= 5; $index++) {
            $this->insertSale($customerId, ['sale_number' => 'V-5' . $index]);
        }

        $page = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history?per_page=2&page=2');

        self::assertCount(2, $this->responseJson($page)['data'], $page['body']);
        self::assertSame(5, $this->responseJson($page)['meta']['total']);
    }

    // ------------------------------------------------- bloqueio x exclusao

    public function test_cliente_sem_historico_e_apagado(): void
    {
        $customerId = $this->createCustomer(['name' => 'Cadastro fantasma', 'document' => '86000000001']);

        $response = $this->dispatchJson('DELETE', '/api/v1/customers/' . $customerId);

        self::assertSame(200, $response['status'], $response['body']);

        $payload = $this->responseJson($response)['data'];

        self::assertFalse($payload['blocked'], 'cliente sem historico deve ser apagado');
        self::assertSame(0, $payload['purchases']);
        self::assertNull($this->fetchOne('SELECT id FROM customers WHERE id = :id', ['id' => $customerId]));
    }

    public function test_cliente_com_historico_e_bloqueado_e_nao_apagado(): void
    {
        $customerId = $this->createCustomer(['name' => 'Cliente fiel', 'document' => '87000000001']);
        $this->insertSale($customerId, ['sale_number' => 'V-50']);

        $response = $this->dispatchJson('DELETE', '/api/v1/customers/' . $customerId);

        self::assertSame(200, $response['status'], $response['body']);

        $payload = $this->responseJson($response)['data'];

        self::assertTrue($payload['blocked'], 'cliente com historico tem de ser bloqueado, nao apagado');
        self::assertSame(1, $payload['purchases']);

        // A linha continua: e a venda que aponta para ela.
        $row = $this->fetchOne('SELECT status FROM customers WHERE id = :id', ['id' => $customerId]);

        self::assertNotNull($row, 'cliente com historico nao pode sumir do banco');
        self::assertSame('INACTIVE', $row['status']);

        // E o historico continua legivel.
        $history = $this->dispatchJson('GET', '/api/v1/customers/' . $customerId . '/history');

        self::assertSame(200, $history['status'], $history['body']);
        self::assertSame(1, $this->responseJson($history)['meta']['total']);
    }

    public function test_cliente_so_com_venda_cancelada_e_apagado(): void
    {
        $customerId = $this->createCustomer(['name' => 'Compra desfeita', 'document' => '88000000001']);
        $this->insertSale($customerId, ['sale_number' => 'V-60', 'status' => 'CANCELLED']);

        $response = $this->dispatchJson('DELETE', '/api/v1/customers/' . $customerId);

        self::assertSame(200, $response['status'], $response['body']);
        self::assertFalse(
            $this->responseJson($response)['data']['blocked'],
            'venda cancelada nao e historico comercial que precise ser preservado',
        );
        self::assertNull($this->fetchOne('SELECT id FROM customers WHERE id = :id', ['id' => $customerId]));
    }

    public function test_cliente_inexistente_responde_404(): void
    {
        $this->loginAsAdminOne();

        $show = $this->dispatchJson('GET', '/api/v1/customers/999999');
        $history = $this->dispatchJson('GET', '/api/v1/customers/999999/history');
        $destroy = $this->dispatchJson('DELETE', '/api/v1/customers/999999');

        self::assertSame(404, $show['status'], $show['body']);
        self::assertSame(404, $history['status'], $history['body']);
        self::assertSame(404, $destroy['status'], $destroy['body']);
    }

    // ------------------------------------------------------------------- RBAC

    public function test_viewer_le_o_cadastro_mas_nao_cria_nem_edita_nem_apaga(): void
    {
        $this->createCustomer(['name' => 'Cliente visivel', 'document' => '89000000001']);

        $this->loginAsViewerOne();

        // O viewer so tem imports.view, nem customers.view. Confere que cada
        // rota esta amarrada a sua permissao.
        $listing = $this->dispatchJson('GET', '/api/v1/customers');
        $store = $this->dispatchJson('POST', '/api/v1/customers', ['name' => 'Nao autorizado']);
        $update = $this->dispatchJson('PUT', '/api/v1/customers/1', ['name' => 'Nao autorizado']);
        $destroy = $this->dispatchJson('DELETE', '/api/v1/customers/1');

        self::assertSame(403, $listing['status'], $listing['body']);
        self::assertSame(403, $store['status'], $store['body']);
        self::assertSame(403, $update['status'], $update['body']);
        self::assertSame(403, $destroy['status'], $destroy['body']);
        self::assertSame(1, $this->customerCount(), 'nada pode ter sido gravado');
    }

    public function test_admin_do_tenant_2_nao_ve_cliente_do_tenant_1_nem_na_tela(): void
    {
        $this->createCustomer(['name' => 'Cliente privado', 'document' => '90000000001']);

        $this->loginAsAdminTwo();

        $page = $this->dispatchPage('GET', '/clientes');

        self::assertSame(200, $page['status'], $page['body']);
        self::assertStringNotContainsString('Cliente privado', $page['body']);
    }

    // ------------------------------------------------------------------ telas

    public function test_tela_de_clientes_lista_o_cadastro_com_resumo(): void
    {
        $customerId = $this->createCustomer([
            'name' => 'Cliente visivel na tela',
            'document' => '91000000001',
            'phone' => '11977778888',
        ]);

        $this->insertSale($customerId, ['sale_number' => 'V-70', 'total' => 150.0]);

        $this->loginAsAdminOne();

        $page = $this->dispatchPage('GET', '/clientes');

        self::assertSame(200, $page['status'], $page['body']);
        self::assertStringContainsString('Cliente visivel na tela', $page['body']);
        self::assertStringContainsString('91000000001', $page['body']);
        // Resumo comercial visivel na listagem, sem clicar em nada.
        self::assertStringContainsString('150,00', $page['body']);
        // Link para o historico.
        self::assertStringContainsString('/clientes/' . $customerId, $page['body']);
    }

    public function test_tela_de_historico_mostra_resumo_e_vendas(): void
    {
        $customerId = $this->createCustomer(['name' => 'Cliente com historico', 'document' => '92000000001']);

        $this->insertSale($customerId, ['sale_number' => 'V-80', 'total' => 75.5, 'profit' => 25.5]);

        $this->loginAsAdminOne();

        $page = $this->dispatchPage('GET', '/clientes/' . $customerId);

        self::assertSame(200, $page['status'], $page['body']);
        self::assertStringContainsString('Cliente com historico', $page['body']);
        self::assertStringContainsString('V-80', $page['body']);
        self::assertStringContainsString('75,50', $page['body']);
        self::assertStringContainsString('Resumo comercial', $page['body']);
    }

    public function test_tela_de_clientes_bloqueada_para_quem_nao_tem_permissao(): void
    {
        $this->loginAsViewerOne();

        $page = $this->dispatchPage('GET', '/clientes');

        self::assertSame(403, $page['status'], $page['body']);
    }

    public function test_cadastro_pelo_formulario_nativo_da_tela(): void
    {
        $this->loginAsAdminOne();

        $response = $this->dispatchUserForm('POST', '/clientes', [
            'name' => 'Cadastrado pela tela',
            'document' => '93000000001',
            'email' => 'tela@example.com',
        ]);

        self::assertSame(302, $response['status'], $response['body']);

        $row = $this->fetchOne('SELECT name, document FROM customers WHERE name = :name', ['name' => 'Cadastrado pela tela']);

        self::assertNotNull($row, 'o form nativo nao gravou o cliente');
        self::assertSame('93000000001', $row['document']);
    }

    public function test_formulario_com_documento_duplicado_responde_com_erro_amigavel(): void
    {
        $this->createCustomer(['name' => 'Ja existe', 'document' => '94000000001']);

        $this->loginAsAdminOne();

        $response = $this->dispatchUserForm('POST', '/clientes', [
            'name' => 'Tentativa',
            'document' => '94000000001',
        ]);

        self::assertSame(302, $response['status'], $response['body']);
        self::assertSame(1, $this->customerCount(), 'o duplicado nao pode ter sido criado');
    }

    public function test_escrita_de_cliente_gera_auditoria(): void
    {
        $customerId = $this->createCustomer(['name' => 'Auditado', 'document' => '95000000001']);

        $this->insertSale($customerId, ['sale_number' => 'V-90']);

        $this->dispatchJson('DELETE', '/api/v1/customers/' . $customerId);

        $actions = array_column(
            $this->fetchAllRows('SELECT action FROM audit_logs WHERE entity_type = :type ORDER BY id', ['type' => 'customer']),
            'action',
        );

        self::assertContains('customer.create', $actions, 'cadastro nao foi auditado: ' . implode(',', $actions));
        self::assertContains('customer.block', $actions, 'bloqueio nao foi auditado: ' . implode(',', $actions));
    }
}