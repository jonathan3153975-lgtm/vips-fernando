<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;

/**
 * Clientes (EPIC 07). O escopo e sempre por tenant: `customers` tem
 * `tenant_id` proprio, e `sales.customer_id` so aponta para clientes do mesmo
 * tenant por causa da FK — mas o filtro continua obrigatorio na consulta, porque
 * a FK impede o id errado na escrita e nao protege a leitura.
 */
final class CustomerRepository extends TenantScopedRepository
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    /**
     * Venda que conta como compra do cliente. Uma venda `OPEN` ainda nao comprou
     * nada e uma `CANCELLED` nao comprou nunca; somar as duas no "total gasto"
     * inventaria faturamento.
     */
    public const SALE_STATUS_COMPLETED = 'COMPLETED';

    /**
     * Listagem com busca e filtros. A busca cobre os tres criterios do roteiro
     * (nome, documento e contato) em um campo so, porque e assim que o usuario
     * procura: digita qualquer coisa que ele lembra.
     *
     * O resumo comercial vem por LEFT JOIN de uma subquery agregada, e nao por
     * N+1: a listagem mostra "total gasto" e "ultima compra" de cada cliente.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function paginate(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->filter($filters);

        return $this->selectAll(
            $this->selectColumns() . '
             WHERE c.tenant_id = :tenant_id' . $where . '
             ORDER BY c.name ASC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            // `:summary_status` pertence a subquery de `selectColumns()`; esta
            // query tem a subquery, entao o parametro e usado.
            $params + $this->summaryBindings(),
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
             FROM customers c
             WHERE c.tenant_id = :tenant_id' . $where,
            $params,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $customerId): ?array
    {
        return $this->selectOne(
            $this->selectColumns() . '
             WHERE c.tenant_id = :tenant_id AND c.id = :id
             LIMIT 1',
            // `:summary_status` vem da subquery de `selectColumns()` e precisa
            // ser informado aqui tambem. Sem isso o MySQL responde HY093
            // ("number of bound variables does not match number of tokens") em
            // toda leitura de um unico cliente — e o SQLite aceita a consulta,
            // entao so o banco real acusa.
            ['id' => $customerId] + $this->summaryBindings(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findOrFail(int $customerId): array
    {
        $customer = $this->find($customerId);

        if ($customer === null) {
            // 404 e nao 400: para quem perguntou o cliente nao existe, e dizer
            // "pertence a outra empresa" ja entregaria informacao indevida.
            throw NotFoundException::entity('Cliente', 'nao encontrado.');
        }

        return $customer;
    }

    /**
     * O mesmo documento nao pode existir duas vezes no mesmo tenant. O
     * `UNIQUE (tenant_id, document)` do banco e a garantia real; esta consulta
     * existe para devolver uma mensagem de dominio em vez do erro do driver.
     *
     * Documento vazio e guardado como NULL, e NULL nao colide no indice unico
     * (nem no MySQL nem no SQLite). Se vazio virasse string vazia, o segundo
     * cliente sem documento cairia em "duplicate key" — e sem documento e caso
     * legitimo, nao erro.
     */
    public function documentExists(string $document, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM customers WHERE tenant_id = :tenant_id AND document = :document';
        $params = ['document' => $document];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        return $this->selectValue($sql . ' LIMIT 1', $params) !== null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = date('Y-m-d H:i:s');

        $customerId = $this->insert(
            'INSERT INTO customers (
                tenant_id, name, document, phone, whatsapp, email, address, notes, status, created_at, updated_at
            ) VALUES (
                :tenant_id, :name, :document, :phone, :whatsapp, :email, :address, :notes, :status, :created_at, :updated_at
            )',
            [
                'name' => $data['name'],
                'document' => $data['document'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? self::STATUS_ACTIVE,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        );

        return $this->findOrFail($customerId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $customerId, array $data): array
    {
        // findOrFail antes do UPDATE: sem isso, um id de outro tenant cairia num
        // UPDATE sem linhas afetadas e a API responderia 200 sem ter gravado nada.
        $this->findOrFail($customerId);

        $this->run(
            'UPDATE customers SET
                name = :name,
                document = :document,
                phone = :phone,
                whatsapp = :whatsapp,
                email = :email,
                address = :address,
                notes = :notes,
                status = :status,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'name' => $data['name'],
                'document' => $data['document'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? self::STATUS_ACTIVE,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $customerId,
            ],
        );

        return $this->findOrFail($customerId);
    }

    /**
     * Quantas vendas concluidas o cliente tem. E o que decide entre bloquear e
     * apagar: com historico, o cliente e desativado (regra 1 do modulo, "cliente
     * utilizado em venda nunca deve ser apagado").
     */
    public function countCompletedSales(int $customerId): int
    {
        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM sales s
             WHERE s.tenant_id = :tenant_id
               AND s.customer_id = :customer_id
               AND s.status = :status',
            ['customer_id' => $customerId, 'status' => self::SALE_STATUS_COMPLETED],
        );
    }

    /**
     * Resumo comercial: total gasto, numero de compras, ultima compra e ticket
     * medio. Ticket medio sai do proprio banco para nao divergir do total.
     *
     * @return array<string, mixed>
     */
    public function summary(int $customerId): array
    {
        $this->findOrFail($customerId);

        $row = $this->selectOne(
            'SELECT COUNT(s.id) AS purchase_count,
                    COALESCE(SUM(s.total), 0) AS total_spent,
                    COALESCE(SUM(s.profit), 0) AS total_profit,
                    MAX(s.sale_date) AS last_purchase_at,
                    COALESCE(AVG(NULLIF(s.total, 0)), 0) AS average_ticket
             FROM sales s
             WHERE s.tenant_id = :tenant_id
               AND s.customer_id = :customer_id
               AND s.status = :status',
            ['customer_id' => $customerId, 'status' => self::SALE_STATUS_COMPLETED],
        ) ?? [];

        // `AVG` ignora o NULL de total 0, que e venda de brinde/cortesia e nao
        // deve inflar nem afundar o ticket medio.
        return [
            'purchase_count' => (int) ($row['purchase_count'] ?? 0),
            'total_spent' => (float) ($row['total_spent'] ?? 0),
            'total_profit' => (float) ($row['total_profit'] ?? 0),
            'last_purchase_at' => $row['last_purchase_at'] ?? null,
            'average_ticket' => (float) ($row['average_ticket'] ?? 0),
        ];
    }

    /**
     * Vendas do cliente, mais recentes primeiro. E a tela de historico comercial.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function purchases(int $customerId, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        $this->findOrFail($customerId);

        $where = ' AND s.customer_id = :customer_id';

        if (($filters['status'] ?? '') !== '') {
            $where .= ' AND s.status = :status';
        }

        $params = ['customer_id' => $customerId];

        if (($filters['status'] ?? '') !== '') {
            $params['status'] = strtoupper((string) $filters['status']);
        }

        return $this->selectAll(
            'SELECT s.id, s.sale_number, s.status, s.subtotal, s.discount, s.total,
                    s.cost_total, s.profit, s.sale_date, s.completed_at,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
             FROM sales s
             WHERE s.tenant_id = :tenant_id' . $where . '
             ORDER BY s.sale_date DESC, s.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countPurchases(int $customerId, array $filters = []): int
    {
        $where = ' AND s.customer_id = :customer_id';
        $params = ['customer_id' => $customerId];

        if (($filters['status'] ?? '') !== '') {
            $where .= ' AND s.status = :status';
            $params['status'] = strtoupper((string) $filters['status']);
        }

        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM sales s
             WHERE s.tenant_id = :tenant_id' . $where,
            $params,
        );
    }

    /**
     * Desativa o cliente, preservando o registro e o historico.
     *
     * @return array<string, mixed>
     */
    public function deactivate(int $customerId): array
    {
        $this->run(
            'UPDATE customers SET status = :status, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id',
            [
                'status' => self::STATUS_INACTIVE,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $customerId,
            ],
        );

        return $this->findOrFail($customerId);
    }

    /**
     * Remove de verdade. So e chamado quando o cliente nao tem venda nenhuma —
     * `sales.customer_id` tem ON DELETE SET NULL, entao uma remocao fisica
     * apagaria silenciosamente o vinculo de um historico que por algum motivo
     * nao tivesse sido detectado. O servico e quem garante a pre-condicao.
     *
     * @return array<string, mixed>
     */
    public function delete(int $customerId): array
    {
        $customer = $this->findOrFail($customerId);

        $this->run(
            'DELETE FROM customers WHERE tenant_id = :tenant_id AND id = :id',
            ['id' => $customerId],
        );

        return $customer;
    }

    /**
     * Bindings exigidos pela subquery de `selectColumns()`.
     *
     * Ficam em um metodo so porque essa query e usada por `paginate()` e por
     * `find()`, e esquecer o parametro em uma das duas so aparece no MySQL
     * (HY093) — o SQLite aceita binding a mais sem reclamar.
     *
     * @return array<string, mixed>
     */
    private function summaryBindings(): array
    {
        return ['summary_status' => self::SALE_STATUS_COMPLETED];
    }

    /**
     * Colunas da listagem, com o resumo comercial agregado por LEFT JOIN.
     *
     * A subquery repete o filtro de tenant e o de `COMPLETED` de proposito: e o
     * mesmo criterio de `summary()`, senao a lista e o detalhe contariam coisas
     * diferentes.
     */
    private function selectColumns(): string
    {
        return 'SELECT c.id, c.name, c.document, c.phone, c.whatsapp, c.email, c.address,
                       c.notes, c.status, c.created_at, c.updated_at,
                       COALESCE(h.purchase_count, 0) AS purchase_count,
                       COALESCE(h.total_spent, 0) AS total_spent,
                       COALESCE(h.total_profit, 0) AS total_profit,
                       h.last_purchase_at,
                       COALESCE(h.average_ticket, 0) AS average_ticket
                FROM customers c
                LEFT JOIN (
                    SELECT s.customer_id,
                           COUNT(*) AS purchase_count,
                           COALESCE(SUM(s.total), 0) AS total_spent,
                           COALESCE(SUM(s.profit), 0) AS total_profit,
                           MAX(s.sale_date) AS last_purchase_at,
                           COALESCE(AVG(NULLIF(s.total, 0)), 0) AS average_ticket
                    FROM sales s
                    WHERE s.tenant_id = :tenant_id
                      AND s.status = :summary_status
                    GROUP BY s.customer_id
                ) h ON h.customer_id = c.id';
    }

    /**
     * Filtros de listagem. Mesma montagem para `paginate()` e `countFiltered()`:
     * duplicar as condicoes nos dois e o jeito classico de `total` e `data`
     * começarem a discordar.
     *
     * Por isso o filtro de "com compras" e um EXISTS e nao uma referencia ao
     * alias `h` da subquery: o `COUNT` de `countFiltered()` nao tem esse alias,
     * e um filtro escrito contra `h` quebraria ali com um erro de coluna.
     * O EXISTS nao depende de join nenhum e nao pode divergir entre as duas.
     *
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function filter(array $filters): array
    {
        $where = '';
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            // Placeholders distintos em cada coluna: repetir :search quebra com
            // prepares nativos (HY093). O SQLite aceita o marcador repetido e nao
            // avisa — foi assim que um bug de busca ja passou aqui.
            $where .= ' AND (c.name LIKE :search_name
                        OR c.document LIKE :search_document
                        OR c.phone LIKE :search_phone
                        OR c.whatsapp LIKE :search_whatsapp
                        OR c.email LIKE :search_email)';

            $params['search_name'] = '%' . $search . '%';
            $params['search_document'] = '%' . $search . '%';
            $params['search_phone'] = '%' . $search . '%';
            $params['search_whatsapp'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where .= ' AND c.status = :customer_status';
            $params['customer_status'] = strtoupper((string) $filters['status']);
        }

        $hasSales = (string) ($filters['has_sales'] ?? '');

        if ($hasSales === '1' || $hasSales === '0') {
            $where .= $hasSales === '1'
                ? ' AND EXISTS (SELECT 1 FROM sales s_filter
                                WHERE s_filter.tenant_id = c.tenant_id
                                  AND s_filter.customer_id = c.id
                                  AND s_filter.status = :sale_status)'
                : ' AND NOT EXISTS (SELECT 1 FROM sales s_filter
                                    WHERE s_filter.tenant_id = c.tenant_id
                                      AND s_filter.customer_id = c.id
                                      AND s_filter.status = :sale_status)';

            $params['sale_status'] = self::SALE_STATUS_COMPLETED;
        }

        return [$where, $params];
    }
}