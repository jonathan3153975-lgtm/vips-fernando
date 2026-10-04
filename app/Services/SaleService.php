<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\NotFoundException;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use RuntimeException;

/**
 * Vendas e checkout (EPIC 08).
 *
 * A regra que atravessa o servico inteiro: **enquanto a venda esta `OPEN`, o
 * estoque esta RESERVADO; quando ela e concluida, o estoque e BAIXADO.** A
 * reserva acontece na criacao do item e a baixa na conclusao, e as duas correm
 * dentro da mesma transacao da venda — se qualquer item falhar, nada fica
 * reservado pela metade.
 *
 * Calculo em centavos inteiros. `DECIMAL(14,2)` chega ao PHP como float e
 * `0.1 + 0.2 !== 0.3`: somar direto em float faz `sales.total` divergir da soma
 * das linhas, que e exatamente o tipo de bug que so aparece quando alguem
 * confere o fechamento. O mesmo cuidado do rateio da Etapa 3.
 *
 * Desconto acima do limite do tenant exige `sales.discount`, e preco diferente
 * do de tabela exige `sales.change_price`. O limite vem de
 * `tenant_settings.max_discount_percent`, que tem zero como padrao: nenhum
 * desconto passa sem permissao ate o tenant configurar o proprio teto.
 */
final class SaleService
{
    public function __construct(
        private readonly SaleRepository $sales,
        private readonly ProductRepository $products,
        private readonly StockService $stock,
        private readonly AuditLogRepository $auditLogs,
        private readonly AuthService $auth,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, int $limit, int $offset): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'data' => $this->sales->paginate($filters, $limit, $offset),
            'total' => $this->sales->countFiltered($filters),
        ];
    }

    /**
     * Detalhe da venda: cabecalho, itens, descontos, pagamentos, devolucoes e a
     * conta a receber. Tudo em uma chamada, porque a tela de detalhe mostra
     * esses blocos juntos e cinco idas ao banco para montar uma pagina seria
     * desperdicio.
     *
     * @return array<string, mixed>
     */
    public function show(int $saleId): array
    {
        $sale = $this->sales->findOrFail($saleId);
        $items = $this->sales->items($saleId);

        $returns = [];

        foreach ($this->sales->returns($saleId) as $return) {
            $returns[] = $return + ['items' => $this->sales->returnItems((int) $return['id'])];
        }

        return [
            'sale' => $sale,
            'items' => $items,
            'discounts' => $this->sales->discounts($saleId),
            'payments' => $this->sales->payments($saleId),
            'paid_total' => $this->sales->paidTotal($saleId),
            'returns' => $returns,
            'returned_total' => $this->sales->returnedTotal($saleId),
            'receivable' => $this->sales->receivableForSale($saleId),
        ];
    }

    /**
     * Cria a venda com os itens e reserva o estoque de cada um.
     *
     * A reserva e feita aqui, e nao na conclusao, porque entre abrir o pedido e
     * fechar a venda a mercadoria continua no estoque e outro vendedor poderia
     * le-va. Quem nao reservar na criacao descobre que vendeu o que nao tinha
     * no momento de baixar.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $customerId = $this->optionalCustomerId($data);
        $prepared = $this->prepareItems($data['items'] ?? null);
        $saleDiscount = $this->saleLevelDiscount($data, $prepared);

        // Rateia o desconto de cabecalho entre os itens ANTES de gravar: e o que
        // faz cada linha ter um liquido proprio, e sem liquido proprio a
        // devolucao parcial nao saberia o que estornar.
        $prepared = $this->distributeSaleDiscount($prepared, $saleDiscount);

        $saleId = $this->sales->transactional(function () use ($customerId, $prepared): int {
            $saleId = $this->sales->insertSale([
                'customer_id' => $customerId,
                'sale_number' => $this->sales->nextSaleNumber(),
                'status' => SaleRepository::STATUS_OPEN,
                'sale_date' => date('Y-m-d H:i:s'),
            ]);

            $this->writeItems($saleId, $prepared);

            $this->sales->updateTotals($saleId);

            return $saleId;
        });

        $this->audit('sale.create', $saleId, ['customer_id' => $customerId]);

        return $this->show($saleId);
    }

    /**
     * Acrescenta item a uma venda aberta. Reserva so a diferenca, e nao a
     * quantidade inteira: os itens anteriores continuam reservados, e reservar
     * tudo de novo dobraria a reserva do produto.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function addItem(int $saleId, array $data): array
    {
        $sale = $this->sales->findOrFail($saleId);
        $this->assertStatus($sale, SaleRepository::MUTABLE_STATUSES, 'Adicionar item');

        $prepared = $this->prepareItems([$data]);

        $this->sales->transactional(function () use ($saleId, $prepared): void {
            $this->writeItems($saleId, $prepared);
            $this->sales->updateTotals($saleId);
        });

        $this->audit('sale.item.add', $saleId, ['product_id' => $prepared[0]['product_id']]);

        return $this->show($saleId);
    }

    /**
     * Conclui a venda: baixa o estoque de cada item, registra os pagamentos e
     * gera a conta a receber do saldo.
     *
     * A ordem importa. Cada item e baixado e a reserva e consumida na MESMA
     * movimentacao; se a venda inteira e concluida em uma transacao so, uma
     * falha no ultimo item desfaz tambem as baixas anteriores. Fazer item a item
     * com transacao propria deixaria a venda concluida pela metade se a terceira
     * linha falhasse.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function complete(int $saleId, array $data = []): array
    {
        $sale = $this->sales->findOrFail($saleId);
        $this->assertStatus($sale, SaleRepository::MUTABLE_STATUSES, 'Concluir');

        $items = $this->sales->items($saleId);

        if ($items === []) {
            throw new RuntimeException('Adicione ao menos um item antes de concluir a venda.');
        }

        $payments = $this->preparePayments($data['payments'] ?? null);

        $this->sales->transactional(function () use ($saleId, $sale, $items, $payments): void {
            foreach ($items as $item) {
                $this->stock->deliverForSale([
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'sale_item_id' => (int) $item['id'],
                    'reference_id' => $saleId,
                    'notes' => 'Baixa pela venda ' . $sale['sale_number'] . '.',
                ]);
            }

            foreach ($payments as $payment) {
                $this->sales->insertPayment($payment + ['sale_id' => $saleId]);
            }

            $this->sales->complete($saleId, date('Y-m-d H:i:s'));

            $this->openReceivable($sale, $payments);
        });

        $this->audit('sale.complete', $saleId, ['total' => (float) $sale['total']]);

        return $this->show($saleId);
    }

    /**
     * Cancela a venda e devolve ao disponivel tudo o que estava reservado.
     *
     * So `OPEN` e cancelavel. Uma venda `COMPLETED` ja baixou estoque e gerou
     * conta a receber: desfaze-la e uma devolucao (com reentrada de mercadoria e
     * estorno financeiro), nao um cancelamento. Misturar os dois caminhos
     * deixaria a pergunta "o que aconteceu com essa venda?" sem resposta, porque
     * o status nao distingue.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function cancel(int $saleId, array $data = []): array
    {
        $sale = $this->sales->findOrFail($saleId);
        $this->assertStatus($sale, SaleRepository::MUTABLE_STATUSES, 'Cancelar');

        $items = $this->sales->items($saleId);

        $this->sales->transactional(function () use ($saleId, $sale, $items): void {
            foreach ($items as $item) {
                $this->stock->release([
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'sale_item_id' => (int) $item['id'],
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'notes' => 'Liberacao pelo cancelamento da venda ' . $sale['sale_number'] . '.',
                ]);
            }

            $this->sales->cancel($saleId, date('Y-m-d H:i:s'));
        });

        $this->audit('sale.cancel', $saleId, ['reason' => $this->reason($data)]);

        return $this->show($saleId);
    }

    /**
     * Devolucao parcial ou total: a mercadoria volta ao estoque e o valor e
     * estornado na proporcao do que voltou.
     *
     * A venda NAO volta a `OPEN` e seus totais NAO sao reescritos. A venda
     * concluida e um documento historico; a devolucao e outro documento que se
     * refere a ela. Reescrever o total da venda apagaria do historico o fato de
     * que houve uma venda de 1.000 e uma devolucao de 400 — sobraria so um
     * numero de 600 que nunca foi vendido.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function returnItems(int $saleId, array $data): array
    {
        $sale = $this->sales->findOrFail($saleId);

        if ((string) $sale['status'] !== SaleRepository::STATUS_COMPLETED) {
            throw new RuntimeException('Somente venda concluida aceita devolucao. Venda aberta se cancela.');
        }

        $lines = $this->prepareReturnLines($saleId, $data['items'] ?? null);
        $reason = $this->reason($data);

        $totalCents = 0;

        foreach ($lines as $line) {
            $totalCents += $line['amount_cents'];
        }

        if ($totalCents <= 0) {
            throw new RuntimeException('A devolucao precisa ter ao menos um item com quantidade.');
        }

        $returnId = $this->sales->transactional(function () use ($saleId, $sale, $lines, $totalCents, $reason): int {
            $returnId = $this->sales->insertReturn([
                'sale_id' => $saleId,
                'customer_id' => $sale['customer_id'],
                'reason' => $reason,
                'amount' => $totalCents / 100,
                'status' => SaleRepository::RETURN_COMPLETED,
            ]);

            foreach ($lines as $line) {
                $this->sales->insertReturnItem([
                    'sale_return_id' => $returnId,
                    'sale_item_id' => $line['sale_item_id'],
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'amount' => $line['amount_cents'] / 100,
                ]);

                $this->stock->receiveReturn([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'sale_item_id' => $line['sale_item_id'],
                    'reference_id' => $returnId,
                    'notes' => 'Devolucao da venda ' . $sale['sale_number'] . '.',
                ]);
            }

            $this->reduceReceivable($saleId, $totalCents);

            return $returnId;
        });

        $this->audit('sale.return', $saleId, ['return_id' => $returnId, 'amount' => $totalCents / 100]);

        return $this->show($saleId);
    }

    /**
     * @return array<string, mixed>
     */
    public function statuses(): array
    {
        return [
            'sale_statuses' => SaleRepository::STATUSES,
            'payment_statuses' => SaleRepository::PAYMENT_STATUSES,
            'payment_methods' => SaleRepository::PAYMENT_METHODS,
            'return_statuses' => SaleRepository::RETURN_STATUSES,
            'receivable_statuses' => SaleRepository::RECEIVABLE_STATUSES,
            'max_discount_percent' => $this->sales->maxDiscountPercent(),
        ];
    }

    /**
     * Grava os itens ja preparados e reserva o estoque de cada um.
     *
     * @param list<array<string, mixed>> $prepared
     */
    private function writeItems(int $saleId, array $prepared): void
    {
        foreach ($prepared as $item) {
            $saleItemId = $this->sales->insertItem([
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'sale_price' => $item['sale_price'],
                'discount' => $item['discount'],
                'subtotal' => $item['subtotal'],
            ]);

            // A reserva e por item e leva o sale_item_id: e o que permite
            // responder "o que estava reservado por este pedido" sem varrer o
            // estoque inteiro procurando movimento sem dono.
            $this->stock->reserve([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'sale_item_id' => $saleItemId,
                'reference_type' => 'sale',
                'reference_id' => $saleId,
                'notes' => 'Reserva pela venda.',
            ]);

            if ($item['price_change_cents'] !== null) {
                $this->sales->insertDiscount([
                    'sale_id' => $saleId,
                    'type' => SaleRepository::DISCOUNT_PRICE_CHANGE,
                    'value' => $item['list_price_cents'] / 100,
                    'reason' => $item['price_change_reason'],
                ]);
            }

            if ($item['discount'] > 0.0) {
                $this->sales->insertDiscount([
                    'sale_id' => $saleId,
                    'type' => SaleRepository::DISCOUNT_ITEM,
                    'value' => $item['discount'],
                    'reason' => $item['discount_reason'],
                ]);
            }
        }
    }

    /**
     * Prepara os itens de entrada: valida, resolve preco e custo, e calcula
     * subtotal e desconto em centavos.
     *
     * O preco vem do `product_prices.sale_price` quando o payload nao traz um.
     * Quando traz e ele difere do de tabela, exige `sales.change_price` e
     * registra o preco original — o criterio do roteiro e do EPIC 08 ("usuario
     * sem permissao nao altera preco") e a segunda parte ("registrar o preco
     * original") e o que torna a primeira auditavel.
     *
     * @return list<array<string, mixed>>
     */
    private function prepareItems(mixed $items): array
    {
        if (!is_array($items) || $items === []) {
            throw new RuntimeException('Informe ao menos um item para a venda.');
        }

        $prepared = [];

        foreach ($items as $raw) {
            if (!is_array($raw)) {
                throw new RuntimeException('Item de venda invalido.');
            }

            $productId = (int) ($raw['product_id'] ?? 0);

            if ($productId <= 0) {
                throw new RuntimeException('Informe o produto do item.');
            }

            $product = $this->products->findOrFail($productId);

            if ((string) $product['status'] === 'INACTIVE') {
                throw new RuntimeException(sprintf(
                    'O produto %s esta inativo e nao pode ser vendido.',
                    (string) $product['name'],
                ));
            }

            $quantity = $this->positiveDecimal($raw['quantity'] ?? null, 'quantity');

            $listPriceCents = (int) round((float) ($product['sale_price'] ?? 0) * 100);

            if ($listPriceCents <= 0) {
                throw new RuntimeException(sprintf(
                    'O produto %s nao tem preco de venda cadastrado.',
                    (string) $product['name'],
                ));
            }

            [$salePriceCents, $priceChangeCents, $priceChangeReason] =
                $this->resolvePrice($raw, $product, $listPriceCents);

            $grossCents = (int) round($quantity * $salePriceCents);
            $discountCents = $this->resolveItemDiscount($raw, $grossCents);

            $prepared[] = [
                'product_id' => $productId,
                'sku' => (string) ($product['sku'] ?? $productId),
                'quantity' => $quantity,
                'cost_price' => round((float) ($product['cost_price'] ?? 0), 2),
                'sale_price' => $salePriceCents / 100,
                'list_price_cents' => $listPriceCents,
                'price_change_cents' => $priceChangeCents,
                'price_change_reason' => $priceChangeReason,
                'discount_cents' => $discountCents,
                'discount' => $discountCents / 100,
                'discount_reason' => $this->reason($raw),
                'subtotal' => $grossCents / 100,
            ];
        }

        return $prepared;
    }

    /**
     * Resolve o preco de venda do item e a eventual mudanca de preco.
     *
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $product
     *
     * @return array{0: int, 1: int|null, 2: string|null} centavos concedidos, centavos do preco original, justificativa
     */
    private function resolvePrice(array $raw, array $product, int $listPriceCents): array
    {
        $requested = $raw['sale_price'] ?? null;

        if ($requested === null || $requested === '') {
            return [$listPriceCents, null, null];
        }

        $requestedCents = (int) round($this->positiveDecimal($requested, 'sale_price') * 100);

        if ($requestedCents === $listPriceCents) {
            return [$listPriceCents, null, null];
        }

        if (!$this->auth->hasPermission('sales.change_price')) {
            throw new RuntimeException(
                'Alterar o preco de venda exige a permissao sales.change_price.',
            );
        }

        if ($requestedCents <= 0) {
            throw new RuntimeException('O preco de venda deve ser maior que zero.');
        }

        $reason = $this->reason($raw);

        if ($reason === null) {
            // Sem justificativa, o preco original gravado em sale_discounts nao
            // diz POR QUE o preco mudou — e um registro que nao responde a
            // pergunta que ele existe para responder.
            throw new RuntimeException('Informe a justificativa da alteracao de preco de venda.');
        }

        return [$requestedCents, $listPriceCents, $reason];
    }

    /**
     * Desconto do item, em centavos. Aceita valor absoluto ou percentual.
     *
     * Acima do limite do tenant exige `sales.discount`. O limite e lido do banco
     * a cada venda, e nao da sessao (ver `SaleRepository::maxDiscountPercent()`).
     *
     * @param array<string, mixed> $raw
     */
    private function resolveItemDiscount(array $raw, int $grossCents): int
    {
        $absolute = $raw['discount'] ?? null;
        $percent = $raw['discount_percent'] ?? null;

        if (($absolute === null || $absolute === '') && ($percent === null || $percent === '')) {
            return 0;
        }

        $discountCents = $absolute !== null && $absolute !== ''
            ? (int) round($this->positiveDecimal($absolute, 'discount') * 100)
            : (int) round($grossCents * $this->percent($percent) / 100);

        if ($discountCents <= 0) {
            return 0;
        }

        if ($discountCents > $grossCents) {
            throw new RuntimeException('O desconto do item nao pode ser maior que o proprio item.');
        }

        $limitPercent = $this->sales->maxDiscountPercent();
        $appliedPercent = ($discountCents / $grossCents) * 100;

        $this->assertDiscountWithinLimit($appliedPercent, $limitPercent);

        return $discountCents;
    }

    /**
     * Desconto de cabecalho, em centavos. Nao e gravado em `sales.discount`
     * direto: ele e rateado entre os itens antes da gravacao.
     *
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $prepared
     */
    private function saleLevelDiscount(array $data, array $prepared): int
    {
        $absolute = $data['discount'] ?? null;
        $percent = $data['discount_percent'] ?? null;

        if (($absolute === null || $absolute === '') && ($percent === null || $percent === '')) {
            return 0;
        }

        $grossCents = 0;

        foreach ($prepared as $item) {
            $grossCents += (int) round($item['quantity'] * $item['sale_price'] * 100);
        }

        $discountCents = $absolute !== null && $absolute !== ''
            ? (int) round($this->positiveDecimal($absolute, 'discount') * 100)
            : (int) round($grossCents * $this->percent($percent) / 100);

        if ($discountCents <= 0) {
            return 0;
        }

        if ($discountCents > $grossCents) {
            throw new RuntimeException('O desconto da venda nao pode ser maior que a venda.');
        }

        $limitPercent = $this->sales->maxDiscountPercent();
        $appliedPercent = ($discountCents / $grossCents) * 100;

        $this->assertDiscountWithinLimit($appliedPercent, $limitPercent);

        return $discountCents;
    }

    /**
     * Desconto acima do limite do tenant so passa com `sales.discount`.
     *
     * O limite e lido do banco a cada desconto aplicado, e nao da sessao: a
     * sessao carrega o tenant no login, e um limite alterado na tela de
     * configuracoes valeria para o log-in seguinte — o que daria a primeira venda
     * com o teto antigo.
     */
    private function assertDiscountWithinLimit(float $appliedPercent, float $limitPercent): void
    {
        // 0.005 de folga: o percentual calculado em cima de centavos inteiros
        // pode dar 10,0000000001% quando o limite e exatamente 10%.
        if ($appliedPercent <= $limitPercent + 0.005) {
            return;
        }

        if (!$this->auth->hasPermission('sales.discount')) {
            throw new RuntimeException(sprintf(
                'Desconto de %s%% excede o limite de %s%% do tenant e exige a permissao sales.discount.',
                number_format($appliedPercent, 2, ',', '.'),
                number_format($limitPercent, 2, ',', '.'),
            ));
        }
    }

    /**
     * Reparte o desconto de cabecalho entre os itens, em centavos inteiros.
     *
     * A sobra dos centavos vai para o item de maior valor: e o mesmo truque do
     * rateio da Etapa 3. Sem ele, a soma dos descontos por item seria alguns
     * centavos menor que o desconto concedido, e `sales.total` nao fecharia
     * com o que o usuario leu na tela.
     *
     * @param list<array<string, mixed>> $prepared
     *
     * @return list<array<string, mixed>>
     */
    private function distributeSaleDiscount(array $prepared, int $saleDiscountCents): array
    {
        if ($saleDiscountCents <= 0) {
            return $prepared;
        }

        $grossCents = [];

        foreach ($prepared as $index => $item) {
            $grossCents[$index] = (int) round($item['quantity'] * $item['sale_price'] * 100);
        }

        $total = array_sum($grossCents);

        if ($total <= 0) {
            return $prepared;
        }

        $distributed = 0;
        $shares = [];

        foreach ($grossCents as $index => $cents) {
            $share = (int) floor(($cents * $saleDiscountCents) / $total);
            $shares[$index] = $share;
            $distributed += $share;
        }

        $largest = array_keys($grossCents, max($grossCents), true);
        $shares[$largest[0]] += $saleDiscountCents - $distributed;

        foreach ($prepared as $index => $item) {
            $discountCents = $item['discount_cents'] + $shares[$index];

            // Desconto por item e desconto de cabecalho sao independentes, e a
            // soma deles pode passar do valor do proprio item: 100 reais de
            // mercadoria com 60 de desconto no item e mais 60 rateados na venda
            // daria -20. A regra e do item, nao da venda.
            if ($discountCents > $grossCents[$index]) {
                throw new RuntimeException(sprintf(
                    'Os descontos somam %s e o item %s vale %s. O desconto nao pode zerar o item.',
                    number_format($discountCents / 100, 2, ',', '.'),
                    (string) ($item['sku'] ?? $item['product_id']),
                    number_format($grossCents[$index] / 100, 2, ',', '.'),
                ));
            }

            $prepared[$index]['discount_cents'] = $discountCents;
            $prepared[$index]['discount'] = $discountCents / 100;
        }

        return $prepared;
    }

    /**
     * @param mixed $raw
     *
     * @return list<array<string, mixed>>
     */
    private function prepareReturnLines(int $saleId, mixed $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            throw new RuntimeException('Informe ao menos um item para devolver.');
        }

        $itemsById = [];

        foreach ($this->sales->items($saleId) as $item) {
            $itemsById[(int) $item['id']] = $item;
        }

        $alreadyReturned = $this->sales->returnedQuantityPerItem($saleId);
        $lines = [];

        foreach ($raw as $line) {
            if (!is_array($line)) {
                throw new RuntimeException('Item de devolucao invalido.');
            }

            $saleItemId = (int) ($line['sale_item_id'] ?? 0);

            // Sem este teste, um sale_item_id de outra venda (ou de outro tenant)
            // entraria na devolucao: o `returnedQuantityPerItem` do tenant errado
            // viria vazio, e a linha passaria sem jamais ter sido vendida aqui.
            if ($saleItemId <= 0 || !isset($itemsById[$saleItemId])) {
                throw NotFoundException::entity('Item da venda', 'nao encontrado nesta venda.');
            }

            $item = $itemsById[$saleItemId];
            $sold = (float) $item['quantity'];
            $quantity = $this->positiveDecimal($line['quantity'] ?? null, 'quantity');
            $returned = (float) ($alreadyReturned[$saleItemId] ?? 0.0);

            if ($quantity > $sold - $returned + 0.0005) {
                throw new RuntimeException(sprintf(
                    'Devolucao de %s excede o que ainda pode ser devolvido do item (vendido %s, ja devolvido %s).',
                    rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ','),
                    rtrim(rtrim(number_format($sold, 3, ',', '.'), '0'), ','),
                    rtrim(rtrim(number_format($returned, 3, ',', '.'), '0'), ','),
                ));
            }

            $netCents = (int) round($item['subtotal'] * 100) - (int) round($item['discount'] * 100);
            $amountCents = (int) round($netCents * $quantity / $sold);

            $lines[] = [
                'sale_item_id' => $saleItemId,
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'amount_cents' => $amountCents,
            ];
        }

        return $lines;
    }

    /**
     * @param mixed $raw
     *
     * @return list<array<string, mixed>>
     */
    private function preparePayments(mixed $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        if (!is_array($raw)) {
            throw new RuntimeException('Pagamento invalido.');
        }

        $prepared = [];

        foreach ($raw as $payment) {
            if (!is_array($payment)) {
                throw new RuntimeException('Pagamento invalido.');
            }

            $method = strtoupper(trim((string) ($payment['method'] ?? '')));

            if (!in_array($method, SaleRepository::PAYMENT_METHODS, true)) {
                throw new RuntimeException('Forma de pagamento invalida: ' . $method);
            }

            $status = strtoupper(trim((string) ($payment['status'] ?? SaleRepository::PAYMENT_PAID)));

            if (!in_array($status, SaleRepository::PAYMENT_STATUSES, true)) {
                throw new RuntimeException('Situacao de pagamento invalida: ' . $status);
            }

            $amount = $this->positiveDecimal($payment['amount'] ?? null, 'amount');
            $installments = (int) ($payment['installments'] ?? 1);

            if ($installments < 1) {
                throw new RuntimeException('A quantidade de parcelas deve ser no minimo 1.');
            }

            $prepared[] = [
                'method' => $method,
                'amount' => round($amount, 2),
                'installments' => $installments,
                'status' => $status,
                'payment_date' => $status === SaleRepository::PAYMENT_PENDING
                    ? null
                    : ($payment['payment_date'] ?? date('Y-m-d')),
            ];
        }

        return $prepared;
    }

    /**
     * Gera a conta a receber do que ficou em aberto.
     *
     * So existe conta a receber quando existe saldo. Uma venda totalmente paga
     * nao gera nada a receber, e criar uma linha `PAID` com valor 0 poluiria o
     * financeiro da Etapa 8 com titulo que ninguem precisa baixar.
     *
     * `sales.total` ja esta confiavel aqui: o servico so chega a `complete()`
     * em venda `OPEN`, e nenhuma escrita de item deixa de passar por
     * `updateTotals()`. O que este metodo precisa garantir e outro — que o
     * pagamento informado nao exceda o total, porque esse dado vem do cliente e
     * o total nao.
     *
     * @param array<string, mixed> $sale
     * @param list<array<string, mixed>> $payments
     */
    private function openReceivable(array $sale, array $payments): void
    {
        $totalCents = (int) round((float) $sale['total'] * 100);

        $paidCents = 0;

        foreach ($payments as $payment) {
            if ($payment['status'] === SaleRepository::PAYMENT_PAID
                || $payment['status'] === SaleRepository::PAYMENT_PARTIAL) {
                $paidCents += (int) round($payment['amount'] * 100);
            }
        }

        // O teto vem do total gravado, nao do que o cliente informou: e ele que impede
        // o pagamento de ser maior que a venda.
        if ($paidCents > $totalCents) {
            throw new RuntimeException(sprintf(
                'O pagamento informado (%s) nao pode ser maior que o total da venda (%s).',
                number_format($paidCents / 100, 2, ',', '.'),
                number_format($totalCents / 100, 2, ',', '.'),
            ));
        }

        $balanceCents = $totalCents - $paidCents;

        if ($balanceCents <= 0) {
            return;
        }

        $dueDate = strtotime('+30 days');

        $this->sales->insertReceivable([
            'customer_id' => $sale['customer_id'],
            'sale_id' => $sale['id'],
            'description' => 'Venda ' . $sale['sale_number'],
            'amount' => $balanceCents / 100,
            'due_date' => date('Y-m-d', $dueDate === false ? time() : $dueDate),
            'status' => $paidCents > 0 ? SaleRepository::PAYMENT_PARTIAL : SaleRepository::PAYMENT_PENDING,
        ]);
    }

    /**
     * Reduz o saldo em aberto da venda pelo valor devolvido.
     *
     * A devolucao pode ser maior do que o titulo em aberto: o cliente pagou e
     * depois devolveu, e nesse caso sobra a devolver dinheiro a ele. Isso e
     * trabalho da Etapa 8 (baixa e conciliacao de contas a receber); aqui o
     * titulo apenas zera e a diferenca continua registrada na venda.
     *
     * Com saldo zerado o status vai para `CANCELLED`, e nao `PAID`: ninguem
     * quitou esse titulo, ele deixou de existir porque a venda foi devolvida.
     * Marcar como pago diria que o cliente entregou o dinheiro.
     */
    private function reduceReceivable(int $saleId, int $amountCents): void
    {
        $receivable = $this->sales->receivableForSale($saleId);

        if ($receivable === null) {
            return;
        }

        $balanceCents = max(0, (int) round((float) $receivable['amount'] * 100) - $amountCents);

        $this->sales->settleReceivable([
            'id' => (int) $receivable['id'],
            'amount' => $balanceCents / 100,
            'status' => $balanceCents > 0 ? SaleRepository::PAYMENT_PARTIAL : SaleRepository::RECEIVABLE_CANCELLED,
            'payment_date' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $sale
     * @param list<string> $allowed
     */
    private function assertStatus(array $sale, array $allowed, string $action): void
    {
        $status = (string) $sale['status'];

        if (in_array($status, $allowed, true)) {
            return;
        }

        $accepted = implode(', ', array_map($this->statusLabel(...), $allowed));

        throw new RuntimeException(sprintf(
            'Nao e possivel %s uma venda %s. Situacoes aceitas: %s.',
            mb_strtolower($action),
            $this->statusLabel($status),
            $accepted,
        ));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            SaleRepository::STATUS_OPEN,
            SaleRepository::RETURN_OPEN,
            SaleRepository::RECEIVABLE_PENDING => 'aberta',
            SaleRepository::STATUS_COMPLETED,
            SaleRepository::RETURN_COMPLETED,
            SaleRepository::RECEIVABLE_PAID => 'concluida',
            SaleRepository::STATUS_CANCELLED,
            SaleRepository::RETURN_CANCELLED,
            SaleRepository::RECEIVABLE_CANCELLED => 'cancelada',
            SaleRepository::PAYMENT_PENDING => 'pendente',
            SaleRepository::PAYMENT_PARTIAL,
            SaleRepository::RECEIVABLE_PARTIAL => 'parcial',
            SaleRepository::PAYMENT_PAID => 'pago',
            SaleRepository::PAYMENT_REFUNDED => 'estornado',
            default => $status,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalCustomerId(array $data): ?int
    {
        $customerId = $data['customer_id'] ?? null;

        if ($customerId === null || $customerId === '' || (int) $customerId <= 0) {
            return null;
        }

        $customerId = (int) $customerId;
        $this->sales->assertCustomerInTenant($customerId);

        return $customerId;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function reason(array $raw): ?string
    {
        $reason = trim((string) ($raw['reason'] ?? ''));

        return $reason === '' ? null : mb_substr($reason, 0, 255);
    }

    private function percent(mixed $value): float
    {
        $percent = $this->decimal($value, 'discount_percent');

        if ($percent < 0 || $percent > 100) {
            throw new RuntimeException('O percentual de desconto deve estar entre 0 e 100.');
        }

        return $percent;
    }

    private function positiveDecimal(mixed $value, string $field): float
    {
        $number = $this->decimal($value, $field);

        if ($number <= 0) {
            throw new RuntimeException(sprintf('O campo %s deve ser maior que zero.', $field));
        }

        return $number;
    }

    private function decimal(mixed $value, string $field): float
    {
        if ($value === null || $value === '') {
            throw new RuntimeException(sprintf('Campo obrigatorio ausente: %s', $field));
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(sprintf('O valor deve ser numerico: %s', $field));
        }

        return round((float) $value, 3);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $normalized['search'] = $search;
        }

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));

        if ($status !== '') {
            if (!in_array($status, SaleRepository::STATUSES, true)) {
                throw new RuntimeException('Situacao de venda invalida: ' . $status);
            }

            $normalized['status'] = $status;
        }

        if (!empty($filters['customer_id'])) {
            $normalized['customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['from'])) {
            $normalized['from'] = date('Y-m-d', (int) strtotime((string) $filters['from'])) ?: null;
        }

        if (!empty($filters['to'])) {
            $normalized['to'] = date('Y-m-d', (int) strtotime((string) $filters['to'])) ?: null;
        }

        return array_filter($normalized, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function audit(string $action, int $entityId, array $context): void
    {
        $this->auditLogs->create($action, 'sale', $entityId, $context);
    }
}
