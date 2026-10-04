<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;
use RuntimeException;
use Throwable;

/**
 * Vendas e checkout (EPIC 08).
 *
 * O escopo e por `tenant_id` em `sales`. `sale_items`, `payments`,
 * `sale_discounts`, `sale_returns` e `sale_return_items` NAO tem `tenant_id`, e
 * isso muda a forma de ESCREVER nelas: nao ha coluna onde colocar o
 * `:tenant_id` que o `TenantScopedRepository` exige, e um `INSERT ... VALUES`
 * seria recusado pelo guard de escopo.
 *
 * Por isso toda escrita nestas tabelas e um `INSERT ... SELECT` com o id do
 * pai vindo de uma subquery ja filtrada por `sales.tenant_id`. Isso resolve as
 * duas coisas de uma vez: satisfaz o guard e passa a IMPEDIR de verdade gravar
 * linha em venda de outro tenant — o `sale_id` cru vindo de quem chama deixa de
 * ser confiavel, porque o `WHERE` da subquery o confronta com o escopo. Com
 * `INSERT ... VALUES` o `sale_id` alheio entraria sem resistencia; a FK
 * `sale_items.sale_id` existe e nao protege nada nesse sentido, ja que vendas de
 * outro tenant tem id valido.
 *
 * O efeito colateral obrigatorio: `rowCount()` precisa ser conferido. Uma
 * subquery que nao casa nao da erro, apenas nao grava — e sem o teste o
 * chamador receberia um `lastInsertId()` obsoleto e seguiria como se a venda
 * tivesse sido criada.
 */
final class SaleRepository extends TenantScopedRepository
{
    /**
     * Venda aberta: itens reservados, mercadoria ainda no estoque, nada
     * baixado nem lançado no financeiro.
     */
    public const STATUS_OPEN = 'OPEN';

    /**
     * Concluida: estoque baixado, contas a receber geradas. Estado final, do
     * ponto de vista do estoque — depois dela so devolucao mexe no saldo.
     */
    public const STATUS_COMPLETED = 'COMPLETED';

    /**
     * Cancelada: reservas devolvidas ao disponivel, sem baixa de estoque nem
     * lancamento financeiro. Estado final.
     */
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    /**
     * Estados em que a venda ainda pode receber item. Depois de concluida ou
     * cancelada a venda e imutavel no que diz respeito a estoque e total.
     */
    public const MUTABLE_STATUSES = [self::STATUS_OPEN];

    /**
     * Desconto por item, em dinheiro. `PRICE_CHANGE` e o preco de venda
     * liberado abaixo do tabela: o `value` guarda o preco ORIGINAL, que e o que
     * o roteiro exige registrar para auditoria.
     */
    public const DISCOUNT_ITEM = 'ITEM';

    public const DISCOUNT_PRICE_CHANGE = 'PRICE_CHANGE';

    public const DISCOUNT_TYPES = [self::DISCOUNT_ITEM, self::DISCOUNT_PRICE_CHANGE];

    public const PAYMENT_PENDING = 'PENDING';

    public const PAYMENT_PAID = 'PAID';

    public const PAYMENT_PARTIAL = 'PARTIAL';

    public const PAYMENT_REFUNDED = 'REFUNDED';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PAID,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_REFUNDED,
    ];

    public const RETURN_OPEN = 'OPEN';

    public const RETURN_COMPLETED = 'COMPLETED';

    public const RETURN_CANCELLED = 'CANCELLED';

    public const RETURN_STATUSES = [self::RETURN_OPEN, self::RETURN_COMPLETED, self::RETURN_CANCELLED];

    /**
     * Situacoes de `accounts_receivable`. `CANCELLED` e o titulo que deixou de
     * existir sem baixa — no caso das vendas, porque o cliente devolveu a
     * mercadoria. E distinto de `PAID` de proposito: "quitado" e "cancelado"
     * respondem a perguntas diferentes no financeiro da Etapa 8.
     */
    public const RECEIVABLE_PENDING = 'PENDING';

    public const RECEIVABLE_PAID = 'PAID';

    public const RECEIVABLE_PARTIAL = 'PARTIAL';

    public const RECEIVABLE_CANCELLED = 'CANCELLED';

    public const RECEIVABLE_STATUSES = [
        self::RECEIVABLE_PENDING,
        self::RECEIVABLE_PAID,
        self::RECEIVABLE_PARTIAL,
        self::RECEIVABLE_CANCELLED,
    ];

    /**
     * Metodos de pagamento aceitos no MVP. Livre para o tenant cadastrar outros
     * depois, mas a lista precisa ser fechada agora: e ela que impede
     * `method = "<script>"` de virar um valor solto no historico.
     */
    public const PAYMENT_METHODS = ['CASH', 'CARD_CREDIT', 'CARD_DEBIT', 'BOLETO', 'TRANSFER', 'OTHER'];

    /**
     * Listagem de vendas. Filtros por situacao, cliente e periodo.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function paginate(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->filter($filters);

        return $this->selectAll(
            'SELECT s.id, s.customer_id, s.user_id, s.sale_number, s.status, s.subtotal, s.discount,
                    s.total, s.cost_total, s.profit, s.sale_date, s.completed_at, s.cancelled_at,
                    s.created_at, s.updated_at,
                    c.name AS customer_name, c.document AS customer_document,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = :tenant_id' . $where . '
             ORDER BY s.sale_date DESC, s.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countFiltered(array $filters = []): int
    {
        [$where, $params] = $this->filter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = :tenant_id' . $where,
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $saleId): ?array
    {
        return $this->selectOne(
            'SELECT s.*, c.name AS customer_name, c.document AS customer_document
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = :tenant_id AND s.id = :sale_id
             LIMIT 1',
            ['sale_id' => $saleId],
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundException
     */
    public function findOrFail(int $saleId): array
    {
        $sale = $this->find($saleId);

        if ($sale === null) {
            throw NotFoundException::entity('Venda', 'nao encontrada.');
        }

        return $sale;
    }

    /**
     * Itens da venda com produto e preco de tabela juntos. O `sale_price` gravado
     * em `sale_items` e o preco CONCEDIDO (o que foi cobrado), e nao o de
     * tabela: e a diferenca entre os dois que a permissao `sales.change_price`
     * controla. Por isso o de tabela vem separado, como `list_price`.
     *
     * @return list<array<string, mixed>>
     */
    public function items(int $saleId): array
    {
        $this->findOrFail($saleId);

        return $this->selectAll(
            'SELECT si.id, si.sale_id, si.product_id, si.quantity, si.cost_price, si.sale_price,
                    si.discount, si.subtotal,
                    p.sku, p.name AS product_name, p.unit, pp.sale_price AS list_price,
                    (si.sale_price - pp.minimum_price) AS below_minimum_gap
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id AND s.tenant_id = :tenant_id
             INNER JOIN products p ON p.id = si.product_id AND p.tenant_id = s.tenant_id
             LEFT JOIN product_prices pp ON pp.product_id = p.id
             WHERE si.sale_id = :sale_id
             ORDER BY si.id',
            ['sale_id' => $saleId],
        );
    }

/**
     * INSERT que nao pode ser um `VALUES`: a tabela nao tem `tenant_id`, e o id do
     * pai tem de sair de uma subquery ja no escopo. Devolve o id gerado e falha
     * alto quando a subquery nao casa, em vez de devolver um `lastInsertId()`
     * obsoleto.
     */
    private function insertScoped(string $sql, array $bindings, string $parent): int
    {
        $affected = $this->run($sql, $bindings);

        if ($affected !== 1) {
            throw NotFoundException::entity($parent, 'nao encontrado neste tenant.');
        }

        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Insere o cabecalho da venda. Quem calcula os totais e o servico, depois de
     * gravar os itens — aqui vao os zeros, para nao haver numero gravado que
     * `updateTotals()` ainda vai sobrescrever.
     *
     * @param array<string, mixed> $data
     */
    public function insertSale(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->insert(
            'INSERT INTO sales (tenant_id, customer_id, user_id, sale_number, status, subtotal,
                                discount, total, cost_total, profit, sale_date, created_at, updated_at)
             VALUES (:tenant_id, :customer_id, :user_id, :sale_number, :status, 0.00, 0.00, 0.00, 0.00, 0.00,
                     :sale_date, :created_at, :updated_at)',
            [
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $data['user_id'] ?? $this->userId(),
                'sale_number' => $data['sale_number'],
                'status' => $data['status'] ?? self::STATUS_OPEN,
                'sale_date' => $data['sale_date'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertItem(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->insertScoped(
            'INSERT INTO sale_items (sale_id, product_id, quantity, cost_price, sale_price, discount, subtotal, created_at, updated_at)
             SELECT s.id, :product_id, :quantity, :cost_price, :sale_price, :discount, :subtotal, :created_at, :updated_at
             FROM sales s
             WHERE s.tenant_id = :tenant_id AND s.id = :sale_id',
            [
                'sale_id' => $data['sale_id'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                // Custo e preco ficam congelados no item: se a importacao for
                // reprocessada e o custo real mudar depois, o lucro desta venda
                // nao pode mudar junto. Sem isso, o historico reescreve sozinho.
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'discount' => $data['discount'],
                'subtotal' => $data['subtotal'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'Venda',
        );
    }

    /**
     * Recalcula os totais da venda a partir dos itens.
     *
     * A soma e feita no banco e nao no PHP: `SUM()` sobre `sale_items` e o que
     * garante que `sales.total` bate com a soma das linhas, mesmo que um item
     * seja gravado por um caminho que nao passe pelo servico.
     *
     * Nao ha parametro de desconto de cabecalho: um desconto sobre a venda
     * inteira e rateado entre os itens na criacao, e cada item passa a ter o seu
     * desconto liquido. Sem isso, a devolucao de parte da venda nao saberia
     * quanto estornar — o proporcional que ela devolve e sobre o item.
     */
    public function updateTotals(int $saleId): void
    {
        // Colunas qualificadas com `si.`: `sales` tambem tem `subtotal` e
        // `discount`, e sem o prefixo o banco acusa ambiguidade — erro que so
        // apareceria aqui, porque nenhuma outra query soma as duas tabelas.
        $row = $this->selectOne(
            'SELECT COALESCE(SUM(si.subtotal), 0) AS gross,
                    COALESCE(SUM(si.discount), 0) AS discount,
                    COALESCE(SUM(si.quantity * si.cost_price), 0) AS cost
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id AND s.tenant_id = :tenant_id
             WHERE si.sale_id = :sale_id',
            ['sale_id' => $saleId],
        ) ?? [];

        $gross = (float) ($row['gross'] ?? 0);
        $discount = (float) ($row['discount'] ?? 0);
        $cost = (float) ($row['cost'] ?? 0);
        $total = $gross - $discount;

        $this->run(
            'UPDATE sales
             SET subtotal = :subtotal,
                 discount = :discount,
                 total = :total,
                 cost_total = :cost_total,
                 profit = :profit,
                 updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :sale_id',
            [
                'subtotal' => $gross,
                'discount' => $discount,
                'total' => $total,
                'cost_total' => $cost,
                'profit' => $total - $cost,
                'updated_at' => date('Y-m-d H:i:s'),
                'sale_id' => $saleId,
            ],
        );
    }

    /**
     * Proximo numero sequencial do tenant.
     *
     * O sufixo com o id do tenant nao e enfeite: `stock_movements` referencia a
     * venda por `(reference_type, reference_id)` e um `sale_number` repetido
     * entre empresas tornaria "venda 7" ambiguo no historico de estoque. A
     * sequencia e por tenant (`uq_sales_tenant_number`), e o zero-pad deixa
     * ordem lexicografica igual a ordem numerica.
     *
     * Colisao sob concorrencia: dois `MAX + 1` simultaneos podem devolver o
     * mesmo numero e um deles falha no indice unico com erro do driver. Mesma
     * limitacao ja registrada para o SKU do produto na Etapa 4 — a correcao
     * pedida por este repositorio e `SELECT ... FOR UPDATE` sobre a sequencia,
     * que nao existe aqui.
     */
    public function nextSaleNumber(): string
    {
        $last = $this->selectValue(
            'SELECT MAX(sale_number)
             FROM sales
             WHERE tenant_id = :tenant_id',
        );

        $sequence = 1;

        if (is_string($last) && preg_match('/^V(\d+)-T(\d+)$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('V%05d-T%03d', $sequence, $this->tenantId());
    }

    /**
     * Grava um desconto/alteracao de preco. `value` e o valor de referencia
     * (o preco original, no caso de mudanca de preco), nunca o desconto
     * aplicado: o que foi concedido esta em `sale_items.discount`.
     *
     * @param array<string, mixed> $data
     */
    public function insertDiscount(array $data): int
    {
        $type = strtoupper((string) ($data['type'] ?? ''));

        if (!in_array($type, self::DISCOUNT_TYPES, true)) {
            throw new RuntimeException('Tipo de desconto invalido: ' . $type);
        }

        return $this->insertScoped(
            'INSERT INTO sale_discounts (sale_id, type, value, user_id, reason, created_at)
             SELECT s.id, :type, :value, :user_id, :reason, :created_at
             FROM sales s
             WHERE s.tenant_id = :tenant_id AND s.id = :sale_id',
            [
                'sale_id' => $data['sale_id'],
                'type' => $type,
                'value' => $data['value'],
                'user_id' => $data['user_id'] ?? $this->userId(),
                'reason' => $data['reason'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            'Venda',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function discounts(int $saleId): array
    {
        $this->findOrFail($saleId);

        return $this->selectAll(
            'SELECT d.id, d.type, d.value, d.user_id, d.reason, d.created_at
             FROM sale_discounts d
             INNER JOIN sales s ON s.id = d.sale_id AND s.tenant_id = :tenant_id
             WHERE d.sale_id = :sale_id
             ORDER BY d.id',
            ['sale_id' => $saleId],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertPayment(array $data): int
    {
        $method = strtoupper((string) ($data['method'] ?? ''));

        if (!in_array($method, self::PAYMENT_METHODS, true)) {
            throw new RuntimeException('Forma de pagamento invalida: ' . $method);
        }

        $now = date('Y-m-d H:i:s');

        return $this->insertScoped(
            'INSERT INTO payments (sale_id, method, amount, installments, status, payment_date, created_at, updated_at)
             SELECT s.id, :method, :amount, :installments, :status, :payment_date, :created_at, :updated_at
             FROM sales s
             WHERE s.tenant_id = :tenant_id AND s.id = :sale_id',
            [
                'sale_id' => $data['sale_id'],
                'method' => $method,
                'amount' => $data['amount'],
                'installments' => max(1, (int) ($data['installments'] ?? 1)),
                'status' => $data['status'] ?? self::PAYMENT_PENDING,
                'payment_date' => $data['payment_date'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'Venda',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payments(int $saleId): array
    {
        $this->findOrFail($saleId);

        return $this->selectAll(
            'SELECT p.id, p.method, p.amount, p.installments, p.status, p.payment_date, p.created_at
             FROM payments p
             INNER JOIN sales s ON s.id = p.sale_id AND s.tenant_id = :tenant_id
             WHERE p.sale_id = :sale_id
             ORDER BY p.id',
            ['sale_id' => $saleId],
        );
    }

    /**
     * Quanto da venda esta pago. So `PAID` e `PARTIAL` contam: `PENDING` e
     * promessa, nao dinheiro, e `REFUNDED` e o caminho de volta.
     */
    public function paidTotal(int $saleId): float
    {
        return (float) $this->selectValue(
            'SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             INNER JOIN sales s ON s.id = p.sale_id AND s.tenant_id = :tenant_id
             WHERE p.sale_id = :sale_id
               AND p.status IN (:paid, :partial)',
            ['sale_id' => $saleId, 'paid' => self::PAYMENT_PAID, 'partial' => self::PAYMENT_PARTIAL],
        );
    }

    /**
     * Marca a venda como concluida. `cancelled_at` fica NULL: as duas datas nao
     * coexistem, e o que separa as duas situacoes e `status`.
     */
    public function complete(int $saleId, string $completedAt): void
    {
        $this->run(
            'UPDATE sales
             SET status = :status, completed_at = :completed_at, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :sale_id',
            [
                'status' => self::STATUS_COMPLETED,
                'completed_at' => $completedAt,
                'updated_at' => date('Y-m-d H:i:s'),
                'sale_id' => $saleId,
            ],
        );
    }

    public function cancel(int $saleId, string $cancelledAt): void
    {
        $this->run(
            'UPDATE sales
             SET status = :status, cancelled_at = :cancelled_at, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :sale_id',
            [
                'status' => self::STATUS_CANCELLED,
                'cancelled_at' => $cancelledAt,
                'updated_at' => date('Y-m-d H:i:s'),
                'sale_id' => $saleId,
            ],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertReturn(array $data): int
    {
        $now = date('Y-m-d H:i:s');

return $this->insertScoped(
            'INSERT INTO sale_returns (sale_id, customer_id, reason, amount, status, created_at, updated_at)
             SELECT s.id, :customer_id, :reason, :amount, :status, :created_at, :updated_at
             FROM sales s
             WHERE s.tenant_id = :tenant_id AND s.id = :sale_id',
            [
                'sale_id' => $data['sale_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'amount' => $data['amount'],
                'status' => $data['status'] ?? self::RETURN_COMPLETED,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'Venda',
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertReturnItem(array $data): int
    {
        return $this->insertScoped(
            'INSERT INTO sale_return_items (sale_return_id, sale_item_id, product_id, quantity, amount, created_at)
             SELECT r.id, :sale_item_id, :product_id, :quantity, :amount, :created_at
             FROM sale_returns r
             INNER JOIN sales s ON s.id = r.sale_id AND s.tenant_id = :tenant_id
             WHERE r.id = :return_id',
            [
                'return_id' => $data['sale_return_id'],
                'sale_item_id' => $data['sale_item_id'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'amount' => $data['amount'],
                'created_at' => date('Y-m-d H:i:s'),
            ],
            'Devolucao',
        );
    }

    /**
     * Devolucoes da venda com os itens de cada uma.
     *
     * @return list<array<string, mixed>>
     */
    public function returns(int $saleId): array
    {
        $this->findOrFail($saleId);

        return $this->selectAll(
            'SELECT r.id, r.customer_id, r.reason, r.amount, r.status, r.created_at
             FROM sale_returns r
             INNER JOIN sales s ON s.id = r.sale_id AND s.tenant_id = :tenant_id
             WHERE r.sale_id = :sale_id
             ORDER BY r.id',
            ['sale_id' => $saleId],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function returnItems(int $returnId): array
    {
        return $this->selectAll(
            'SELECT ri.id, ri.sale_return_id, ri.sale_item_id, ri.product_id, ri.quantity, ri.amount,
                    p.sku, p.name AS product_name, p.unit
             FROM sale_return_items ri
             INNER JOIN sale_returns r ON r.id = ri.sale_return_id
             INNER JOIN sales s ON s.id = r.sale_id AND s.tenant_id = :tenant_id
             INNER JOIN products p ON p.id = ri.product_id AND p.tenant_id = s.tenant_id
             WHERE ri.sale_return_id = :return_id',
            ['return_id' => $returnId],
        );
    }

    /**
     * Quantidade ja devolvida por item da venda. E o que impede devolver duas
     * vezes a mesma mercadoria: sem esta consulta, a segunda devolucopia de 5
     * unidades seria aceita e o estoque ganharia 5 que nunca sairam.
     *
     * @return array<int, float> sale_item_id => quantidade devolvida
     */
    public function returnedQuantityPerItem(int $saleId): array
    {
        $this->findOrFail($saleId);

        $rows = $this->selectAll(
            'SELECT ri.sale_item_id, COALESCE(SUM(ri.quantity), 0) AS returned
             FROM sale_return_items ri
             INNER JOIN sale_returns r ON r.id = ri.sale_return_id AND r.status <> :cancelled
             INNER JOIN sales s ON s.id = r.sale_id AND s.tenant_id = :tenant_id
             WHERE r.sale_id = :sale_id
             GROUP BY ri.sale_item_id',
            ['sale_id' => $saleId, 'cancelled' => self::RETURN_CANCELLED],
        );

        $returned = [];

        foreach ($rows as $row) {
            $returned[(int) $row['sale_item_id']] = (float) $row['returned'];
        }

        return $returned;
    }

    /**
     * Total ja devolvido na venda, em dinheiro.
     */
    public function returnedTotal(int $saleId): float
    {
        return (float) $this->selectValue(
            'SELECT COALESCE(SUM(r.amount), 0)
             FROM sale_returns r
             INNER JOIN sales s ON s.id = r.sale_id AND s.tenant_id = :tenant_id
             WHERE r.sale_id = :sale_id AND r.status <> :cancelled',
            ['sale_id' => $saleId, 'cancelled' => self::RETURN_CANCELLED],
        );
    }

    /**
     * Gera a conta a receber da venda. Sem isso a receita existe na tela de
     * vendas e nao existe no financeiro — que e exatamente o buraco que o
     * criterio de conclusao do EPIC 08 aponta.
     *
     * @param array<string, mixed> $data
     */
    public function insertReceivable(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->insert(
            'INSERT INTO accounts_receivable (tenant_id, customer_id, sale_id, description, amount, due_date, payment_date, status, created_at, updated_at)
             VALUES (:tenant_id, :customer_id, :sale_id, :description, :amount, :due_date, :payment_date, :status, :created_at, :updated_at)',
            [
                'customer_id' => $data['customer_id'] ?? null,
                'sale_id' => $data['sale_id'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'due_date' => $data['due_date'],
                'payment_date' => $data['payment_date'] ?? null,
                'status' => $data['status'] ?? self::RECEIVABLE_PENDING,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function receivableForSale(int $saleId): ?array
    {
        return $this->selectOne(
            'SELECT ar.id, ar.customer_id, ar.sale_id, ar.description, ar.amount, ar.due_date,
                    ar.payment_date, ar.status
             FROM accounts_receivable ar
             INNER JOIN sales s ON s.id = ar.sale_id AND s.tenant_id = :tenant_id
             WHERE ar.sale_id = :sale_id
             LIMIT 1',
            ['sale_id' => $saleId],
        );
    }

    /**
     * Fecha o recebimento: baixa o que foi pago e marca a conta como quitada ou
     * parcial. `amount` e sempre o saldo em aberto depois do pagamento, nunca o
     * valor do pagamento — e assim que `PENDING`/`PARTIAL`/`PAID` se distinguem.
     *
     * @param array<string, mixed> $data
     */
    public function settleReceivable(array $data): void
    {
        $this->run(
            'UPDATE accounts_receivable
             SET amount = :amount,
                 status = :status,
                 payment_date = :payment_date,
                 updated_at = :updated_at
             WHERE id = :id AND tenant_id = :tenant_id',
            [
                'amount' => $data['amount'],
                'status' => $data['status'],
                'payment_date' => $data['payment_date'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $data['id'],
            ],
        );
    }

    /**
     * Confirma que o cliente existe neste tenant. A FK `sales.customer_id` impede
     * o id errado na escrita, mas nao protege a leitura — e o mesmo buraco que a
     * Etapa 3 fechou para `supplier_id`.
     *
     * O predicado e `customers.tenant_id`, nao um JOIN por `sales`: `customers`
     * tem o proprio `tenant_id`, e um JOIN sem condicao de correlacao aqui
     * seria produto cartesiano.
     *
     * @throws NotFoundException
     */
    public function assertCustomerInTenant(int $customerId): void
    {
        $exists = $this->selectValue(
            'SELECT c.id
             FROM customers c
             WHERE c.tenant_id = :tenant_id AND c.id = :customer_id
             LIMIT 1',
            ['customer_id' => $customerId],
        );

        if ($exists === null) {
            throw NotFoundException::entity('Cliente', 'nao encontrado.');
        }
    }

    /**
     * Limite de desconto do tenant, em %. Vem de `tenant_settings`
     * (migration 000017) e e lido na venda, nao na sessao: a sessao carrega o
     * tenant no login, e um desconto alterado na tela de configuracoes valeria
     * para o log-in seguinte — o que daria a primeira venda com o limite antigo.
     *
     * A leitura e por `tenant_settings.tenant_id`, que tem uma linha por tenant
     * (`uq_tenant_settings_tenant`). O predicado e o proprio filtro de escopo:
     * nao ha artelho para dar o `:tenant_id` que o `TenantScopedRepository` exige.
     */
    public function maxDiscountPercent(): float
    {
        $value = $this->selectValue(
            'SELECT ts.max_discount_percent
             FROM tenant_settings ts
             WHERE ts.tenant_id = :tenant_id
             LIMIT 1',
        );

        return $value === null ? 0.0 : (float) $value;
    }

    /**
     * Executa o callback em uma transacao. Reentrante, como o do
     * StockRepository: quem chama por fora ja estar dentro de uma transacao
     * apenas participa dela, e quem chama de fora a abre.
     */
    public function transactional(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $owns = !$pdo->inTransaction();

        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if ($owns) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $throwable) {
            if ($owns) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function filter(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['status'])) {
            $status = strtoupper((string) $filters['status']);

            if (!in_array($status, self::STATUSES, true)) {
                throw new RuntimeException('Situacao de venda invalida: ' . $status);
            }

            $where .= ' AND s.status = :status';
            $params['status'] = $status;
        }

        if (!empty($filters['customer_id'])) {
            $where .= ' AND s.customer_id = :customer_id';
            $params['customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['search'])) {
            // Placeholders distintos: repetir :search quebra com prepares nativos.
            $where .= ' AND (s.sale_number LIKE :search_number
                            OR c.name LIKE :search_name
                            OR c.document LIKE :search_document)';
            $params['search_number'] = '%' . $filters['search'] . '%';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_document'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['from'])) {
            $where .= ' AND s.sale_date >= :date_from';
            $params['date_from'] = $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $where .= ' AND s.sale_date <= :date_to';
            $params['date_to'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $params];
    }
}
