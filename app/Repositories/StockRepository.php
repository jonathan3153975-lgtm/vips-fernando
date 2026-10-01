<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;
use RuntimeException;
use Throwable;

final class StockRepository extends TenantScopedRepository
{
    /**
     * Tipos de movimentacao. O vocabulario importa: e o que o historico filtra
     * e o que o usuario le na tela.
     *
     * - IN  = entra no estoque (compra, ajuste positivo)
     * - OUT = sai do estoque (ajuste negativo, perda, consumo avulso)
     */
    public const TYPE_IMPORT_ENTRY = 'IMPORT_ENTRY';

    public const TYPE_MANUAL_IN = 'MANUAL_IN';

    public const TYPE_MANUAL_OUT = 'MANUAL_OUT';

    public const TYPE_RESERVE = 'RESERVE';

    public const TYPE_RELEASE = 'RELEASE';

    public const TYPE_CONSUME = 'CONSUME';

    public const TYPE_SALE_OUT = 'SALE_OUT';

    /**
     * Tipos que aumentam o saldo fisico.
     */
    public const INFLOW_TYPES = [self::TYPE_IMPORT_ENTRY, self::TYPE_MANUAL_IN];

    /**
     * Tipos que reduzem o saldo fisico.
     */
    public const OUTFLOW_TYPES = [self::TYPE_MANUAL_OUT, self::TYPE_CONSUME, self::TYPE_SALE_OUT];

    /**
     * Tipos que mexem apenas em `reserved_quantity`, sem alterar `quantity`.
     * Manter a lista explicita evita que um novo tipo passe a mover saldo por
     * engano: reserva e liberacao sao logicas opostas, e uma baixa de venda
     * precisa consumir a reserva antes de mexer no saldo fisico.
     */
    public const RESERVATION_TYPES = [self::TYPE_RESERVE, self::TYPE_RELEASE];

    public const ALL_TYPES = [
        self::TYPE_IMPORT_ENTRY,
        self::TYPE_MANUAL_IN,
        self::TYPE_MANUAL_OUT,
        self::TYPE_RESERVE,
        self::TYPE_RELEASE,
        self::TYPE_CONSUME,
        self::TYPE_SALE_OUT,
    ];

    /**
     * Garante que o produto pertence ao tenant ativo.
     *
     * Sem isso, `ensureRow()` responderia a um `product_id` de outra empresa com
     * um INSERT de estoque no tenant de quem perguntou: o saldo alheio viraria
     * uma linha visivel na lista de quem perguntou. O filtro por `tenant_id` na
     * tabela `stock` nao segura esse caso, porque a linha criado ainda pertence
     * ao tenant que perguntou — o produto e que e de outro.
     *
     * @throws NotFoundException
     */
    public function assertProductInTenant(int $productId): void
    {
        $product = $this->selectOne(
            'SELECT id FROM products WHERE tenant_id = :tenant_id AND id = :product_id LIMIT 1',
            ['product_id' => $productId],
        );

        if ($product === null) {
            // 404, e nao 400: para quem perguntou o produto nao existe, e dizer
            // "de outra empresa" ja entregaria informacao que nao lhe cabe.
            throw NotFoundException::entity('Produto', 'nao encontrado.');
        }
    }

    /**
     * Saldo do produto, criando a linha zerada se ainda nao existir.
     *
     * SELECT e depois INSERT, como ProductRepository::ensureStock() ja faz: o
     * `ON DUPLICATE KEY UPDATE` e sintaxe do MySQL e o schema de teste roda em
     * SQLite, onde ela quebra. A constraint unica (tenant_id, product_id) e o
     * que protege a corrida entre duas escritas do mesmo produto.
     *
     * @return array<string, mixed>
     */
    public function ensureRow(int $productId): array
    {
        $this->assertProductInTenant($productId);

        $exists = $this->selectOne(
            'SELECT id FROM stock WHERE tenant_id = :tenant_id AND product_id = :product_id LIMIT 1',
            ['product_id' => $productId],
        );

        if ($exists === null) {
            $this->run(
                'INSERT INTO stock (tenant_id, product_id, quantity, reserved_quantity, minimum_quantity, updated_at)
                 VALUES (:tenant_id, :product_id, 0.000, 0.000, 0.000, :updated_at)',
                ['product_id' => $productId, 'updated_at' => date('Y-m-d H:i:s')],
            );
        }

        $row = $this->selectOne(
            'SELECT s.id, s.product_id, s.quantity, s.reserved_quantity, s.minimum_quantity,
                    (s.quantity - s.reserved_quantity) AS available_quantity, s.updated_at
             FROM stock s
             WHERE s.tenant_id = :tenant_id AND s.product_id = :product_id
             LIMIT 1',
            ['product_id' => $productId],
        );

        if ($row === null) {
            throw NotFoundException::entity('Estoque', 'nao encontrado para o produto.');
        }

        return $row;
    }

    /**
     * Saldo com o produto junto, ja filtrando os que estao abaixo do minimo.
     * `available_quantity` vem do proprio banco para nao divergir do que a tela
     * calcula.
     *
     * O `LIMIT` faz parte do metodo, e nao do chamador: quem anuncia `total` e
     * `per_page` na resposta precisa ter recortado a lista, senao a meta mente
     * sobre quantos itens vieram.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function balances(?array $filters = [], ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->balanceFilter($filters);

        $pagination = $limit === null
            ? ''
            : ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return $this->selectAll(
            'SELECT s.id, s.product_id, s.quantity, s.reserved_quantity, s.minimum_quantity,
                    (s.quantity - s.reserved_quantity) AS available_quantity,
                    s.updated_at, p.sku, p.name AS product_name, p.unit, p.status AS product_status
             FROM stock s
             INNER JOIN products p ON p.id = s.product_id
             WHERE ' . $where . '
             ORDER BY p.name ASC' . $pagination,
            $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function balanceFilter(?array $filters): array
    {
        $where = 's.tenant_id = :tenant_id';
        $params = [];

        if (!empty($filters['product_id'])) {
            $where .= ' AND s.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }

        if (!empty($filters['search'])) {
            // Placeholders distintos: repetir :search quebra com prepares nativos.
            $where .= ' AND (p.name LIKE :search_name OR p.sku LIKE :search_sku)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_sku'] = '%' . $filters['search'] . '%';
        }

        if (($filters['below_minimum'] ?? '') === '1' || ($filters['below_minimum'] ?? '') === 1) {
            // Disponivel (nao o fisico) abaixo do minimo: e o que dispara reposicao.
            $where .= ' AND (s.quantity - s.reserved_quantity) < s.minimum_quantity';
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countBalances(array $filters = []): int
    {
        // Mesmo filtro de balances(), de proposito: duplicar as condicoes aqui
        // e o jeito classico de `total` e `data` começarem a discordar.
        [$where, $params] = $this->balanceFilter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM stock s
             INNER JOIN products p ON p.id = s.product_id
             WHERE ' . $where,
            $params,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countMovements(array $filters = []): int
    {
        [$where, $params] = $this->movementFilter($filters);

        return (int) $this->selectValue(
            'SELECT COUNT(*)
             FROM stock_movements m
             INNER JOIN products p ON p.id = m.product_id
             WHERE m.tenant_id = :tenant_id' . $where,
            $params,
        );
    }

    /**
     * Historico de movimentacoes. Filtros por produto, tipo, periodo e usuario,
     * conforme a rota P1 da etapa.
     *
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function movements(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->movementFilter($filters);

        return $this->selectAll(
            'SELECT m.id, m.product_id, m.import_item_id, m.sale_item_id, m.user_id, m.type,
                    m.quantity, m.balance_after, m.reference_type, m.reference_id, m.notes, m.created_at,
                    p.sku, p.name AS product_name
             FROM stock_movements m
             INNER JOIN products p ON p.id = m.product_id
             WHERE m.tenant_id = :tenant_id' . $where . '
             ORDER BY m.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params,
        );
    }

    /**
     * Aplica a movimentacao e devolve o saldo resultante. Todo caminho que muda
     * saldo passa por aqui, dentro da transacao aberta por quem chama.
     *
     * @param array<string, mixed> $movement
     *
     * @return array<string, mixed> saldo depois da movimentacao
     */
    public function apply(array $movement): array
    {
        $productId = (int) $movement['product_id'];
        $quantity = (float) $movement['quantity'];
        $type = (string) $movement['type'];

        if (!in_array($type, self::ALL_TYPES, true)) {
            throw new RuntimeException('Tipo de movimentacao invalido: ' . $type);
        }

        $row = $this->ensureRow($productId);

        $currentQuantity = (float) $row['quantity'];
        $currentReserved = (float) $row['reserved_quantity'];
        $consumeReserved = (bool) ($movement['consume_reserved'] ?? false);

        if ($type === self::TYPE_RESERVE) {
            // Reserva e liberacao mexem so no reservado; o saldo fisico nao se move.
            $reserved = $currentReserved + $quantity;
            $balanceAfter = $currentQuantity;
        } elseif ($type === self::TYPE_RELEASE) {
            $reserved = $currentReserved - $quantity;
            $balanceAfter = $currentQuantity;
        } elseif ($consumeReserved) {
            // Baixa que cumpre reserva: o saldo fisico E a reserva caem juntos, em
            // um unico UPDATE e uma unica movimentacao.
            //
            // Nao da para fazer isso em dois passos (libera e depois baixa). Com
            // quantity=10 e reserved=10, baixar 5 primeiro deixaria
            // reserved(10) > quantity(5) e a constraint do banco derrubaria a
            // operacao no meio. O clamp em zero e porque a baixa pode vir de
            // reserva parcial: o que exceder a reserva sai do saldo livre.
            $reserved = max(0.0, $currentReserved - $quantity);
            $balanceAfter = $currentQuantity - $quantity;
        } else {
            $reserved = $currentReserved;
            $balanceAfter = in_array($type, self::INFLOW_TYPES, true)
                ? $currentQuantity + $quantity
                : $currentQuantity - $quantity;
        }

        $this->guardInvariants($balanceAfter, $reserved, $currentQuantity, $currentReserved, $type, $quantity);

        $this->run(
            'UPDATE stock SET
                quantity = :quantity,
                reserved_quantity = :reserved_quantity,
                updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND product_id = :product_id',
            [
                'quantity' => $balanceAfter,
                'reserved_quantity' => $reserved,
                'updated_at' => date('Y-m-d H:i:s'),
                'product_id' => $productId,
            ],
        );

        $this->run(
            'INSERT INTO stock_movements (
                tenant_id, product_id, import_item_id, sale_item_id, user_id, type,
                quantity, balance_after, reference_type, reference_id, notes, created_at
            ) VALUES (
                :tenant_id, :product_id, :import_item_id, :sale_item_id, :user_id, :type,
                :quantity, :balance_after, :reference_type, :reference_id, :notes, :created_at
            )',
            [
                'product_id' => $productId,
                'import_item_id' => $movement['import_item_id'] ?? null,
                'sale_item_id' => $movement['sale_item_id'] ?? null,
                'user_id' => $movement['user_id'] ?? $this->userId(),
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter,
                'reference_type' => $movement['reference_type'] ?? null,
                'reference_id' => $movement['reference_id'] ?? null,
                'notes' => $movement['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        );

        return [
            'product_id' => $productId,
            'quantity' => $balanceAfter,
            'reserved_quantity' => $reserved,
            'available_quantity' => $balanceAfter - $reserved,
            'minimum_quantity' => (float) $row['minimum_quantity'],
            'type' => $type,
            'balance_after' => $balanceAfter,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    /**
     * Da entrada no estoque dos produtos vinculados a itens de uma importacao.
     *
     * O vinculo e products.default_import_item_id: e o que o usuario escolhe na
     * tela de produto da Etapa 4. Sem esse vinculo o item comprado nao vira
     * mercadoria em estoque, que e o P0 desta etapa.
     *
     * Idempotente por item: se ja existe IMPORT_ENTRY para o import_item_id, a
     * entrada e pulada. Sem isso, reabrir e concluir de novo a mesma importacao
     * (fluxo suportado por ImportService::reopen) somaria a mercadoria duas
     * vezes — a mesma compra entrando no estoque duas vezes.
     *
     * @return list<array<string, mixed>> entradas geradas
     */
    public function receiveImportItems(int $importId): array
    {
        $entries = $this->selectAll(
            'SELECT p.id AS product_id, i.id AS import_item_id, i.quantity, i.product_name, i.sku
             FROM import_items i
             INNER JOIN products p ON p.default_import_item_id = i.id
             WHERE i.tenant_id = :tenant_id
               AND i.import_id = :import_id
               AND p.tenant_id = :tenant_id
               AND p.status <> \'INACTIVE\'
             ORDER BY i.id',
            ['import_id' => $importId],
        );

        if ($entries === []) {
            return [];
        }

        $generated = [];

        foreach ($entries as $entry) {
            $importItemId = (int) $entry['import_item_id'];
            $quantity = (float) $entry['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $alreadyReceived = (int) $this->selectValue(
                'SELECT COUNT(*)
                 FROM stock_movements
                 WHERE tenant_id = :tenant_id
                   AND import_item_id = :import_item_id
                   AND type = :type',
                [
                    'import_item_id' => $importItemId,
                    'type' => self::TYPE_IMPORT_ENTRY,
                ],
            );

            if ($alreadyReceived > 0) {
                continue;
            }

            $generated[] = $this->apply([
                'product_id' => (int) $entry['product_id'],
                'type' => self::TYPE_IMPORT_ENTRY,
                'quantity' => $quantity,
                'import_item_id' => $importItemId,
                'reference_type' => 'import',
                'reference_id' => $importItemId,
                'notes' => 'Entrada pela importacao #' . $importId . '.',
            ]);
        }

        return $generated;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setMinimumQuantity(int $productId, float $minimum): void
    {
        $this->ensureRow($productId);

        $this->run(
            'UPDATE stock SET minimum_quantity = :minimum_quantity, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND product_id = :product_id',
            [
                'minimum_quantity' => $minimum,
                'updated_at' => date('Y-m-d H:i:s'),
                'product_id' => $productId,
            ],
        );
    }

    /**
     * Executa o callback dentro de uma transacao. Concentrar aqui evita que cada
     * metodo do StockService abra a sua e o saldo fique inconsistente se a
     * movimentacao gravar e a auditoria falhar.
     */
    public function transactional(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $alreadyInside = $pdo->inTransaction();
        $ownsTransaction = !$alreadyInside;

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $throwable) {
            if ($ownsTransaction) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * Barra saldo negativo e reserva maior que o saldo, com mensagem de dominio.
     * As constraints CHECK do banco (000016) repetem a regra como ultima linha —
     * aqui o erro e compreensivel, la o erro seria do driver.
     */
    private function guardInvariants(
        float $balanceAfter,
        float $reserved,
        float $currentQuantity,
        float $currentReserved,
        string $type,
        float $quantity,
    ): void {
        // Tolerancia na comparacao: DECIMAL(12,3) chega ao PHP como float e
        // 0.1 + 0.2 !== 0.3. Sem epsilon, um saldo legitimamente zerado seria
        // lido como negativo e uma reserva valida seria recusada.
        $epsilon = 0.0005;

        if ($balanceAfter < -$epsilon) {
            throw new RuntimeException(sprintf(
                'Saldo insuficiente: disponivel %s e a movimentacao pede %s. Estoque negativo nao e permitido.',
                self::format($currentQuantity - $currentReserved),
                self::format($quantity),
            ));
        }

        if ($reserved < -$epsilon) {
            throw new RuntimeException(sprintf(
                'Liberacao maior que a reserva: reservado %s e a movimentacao pede %s.',
                self::format($currentReserved),
                self::format($quantity),
            ));
        }

        if ($reserved - $balanceAfter > $epsilon) {
            // A mensagem muda porque a causa e diferente: na reserva o saldo
            // esta inteiro e nao cabe; na saida foi a saida que comeu a reserva.
            throw new RuntimeException($type === self::TYPE_RESERVE
                ? sprintf(
                    'Reserva maior que o disponivel: disponivel %s e a reserva pedida e %s.',
                    self::format($balanceAfter - $currentReserved),
                    self::format($reserved),
                )
                : sprintf(
                    'A saida deixaria a reserva maior que o saldo: saldo %s, reservado %s.',
                    self::format($balanceAfter),
                    self::format($reserved),
                ));
        }
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function movementFilter(array $filters): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['product_id'])) {
            $where .= ' AND m.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }

        if (!empty($filters['type'])) {
            $type = strtoupper((string) $filters['type']);

            if (!in_array($type, self::ALL_TYPES, true)) {
                throw new RuntimeException('Tipo de movimentacao invalido: ' . $type);
            }

            $where .= ' AND m.type = :type';
            $params['type'] = $type;
        }

        if (!empty($filters['user_id'])) {
            $where .= ' AND m.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['from'])) {
            $where .= ' AND m.created_at >= :date_from';
            $params['date_from'] = (string) $filters['from'] . ' 00:00:00';
        }

        if (!empty($filters['to'])) {
            $where .= ' AND m.created_at <= :date_to';
            $params['date_to'] = (string) $filters['to'] . ' 23:59:59';
        }

        return [$where, $params];
    }

    private static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
