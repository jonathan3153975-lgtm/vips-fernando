<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StockRepository;
use RuntimeException;

final class StockService
{
    /**
     * Tolerancia usada nas comparacoes de saldo. DECIMAL(12,3) chega ao PHP
     * como float, e 0.1 + 0.2 !== 0.3: sem isso, uma reserva de exatamente o
     * disponivel seria recusada por ruido de ponto flutuante.
     */
    private const EPSILON = 0.0005;

    public function __construct(private readonly StockRepository $stock)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function balances(array $filters = [], ?int $limit = null, int $offset = 0): array
    {
        return $this->stock->balances($filters, $limit, $offset);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countBalances(array $filters = []): int
    {
        return $this->stock->countBalances($filters);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function movements(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        return $this->stock->movements($filters, $limit, $offset);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countMovements(array $filters = []): int
    {
        return $this->stock->countMovements($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function balance(int $productId): array
    {
        return $this->stock->ensureRow($productId);
    }

    /**
     * Entrada de estoque por importacao. E o caminho P0 da etapa: quando o
     * usuario vincula um item de importacao a um produto, concluir a importacao
     * precisa transformar a quantidade comprada em saldo.
     *
     * Sem lote: a rastreabilidade fica em stock_movements.import_item_id, que
     * aponta de qual item veio cada entrada. O roteiro registra essa decisao e
     * o que ela adia.
     *
     * @return array<string, mixed>
     */
    public function receiveFromImport(
        int $productId,
        float $quantity,
        ?int $importItemId = null,
        ?string $notes = null,
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('A quantidade de entrada deve ser maior que zero.');
        }

        return $this->stock->transactional(
            fn (): array => $this->stock->apply([
                'product_id' => $productId,
                'type' => StockRepository::TYPE_IMPORT_ENTRY,
                'quantity' => $quantity,
                'import_item_id' => $importItemId,
                'reference_type' => 'import',
                'reference_id' => $importItemId,
                'notes' => $notes ?? 'Entrada por importacao.',
            ]),
        );
    }

    /**
     * Ajuste manual. Sempre exige justificativa: manual/42 s13 exige que todo
     * ajuste registre o motivo, para o inventario ser auditavel.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function adjust(array $data): array
    {
        $productId = $this->productId($data);
        $quantity = $this->positiveDecimal($data['quantity'] ?? null, 'quantity');
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($notes === '') {
            throw new RuntimeException('Informe a justificativa do ajuste: todo ajuste precisa ser rastreavel.');
        }

        // Ajuste e correcao, nao entrada com sinal: o usuario escolhe o tipo.
        $type = $this->assertAdjustmentType((string) ($data['type'] ?? ''));

        return $this->stock->transactional(
            fn (): array => $this->stock->apply([
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => 'manual_adjustment',
                'notes' => mb_substr($notes, 0, 255),
            ]),
        );
    }

    /**
     * Reserva quantidade sem mexer no saldo fisico, so em `reserved_quantity`.
     * Exige que o disponivel cubra a reserva: e a diferenca entre reservar e
     * prometer mercadoria que nao existe.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function reserve(array $data): array
    {
        $productId = $this->productId($data);
        $quantity = $this->positiveDecimal($data['quantity'] ?? null, 'quantity');

        return $this->stock->transactional(
            fn (): array => $this->stock->apply([
                'product_id' => $productId,
                'type' => StockRepository::TYPE_RESERVE,
                'quantity' => $quantity,
                'reference_type' => $data['reference_type'] ?? 'manual_reservation',
                'reference_id' => $this->referenceId($data),
                'notes' => $this->notes($data),
            ]),
        );
    }

    /**
     * Libera reserva: pedido cancelado ou prazo vencido. Nao mexe no saldo
     * fisico, devolve a reserva ao disponivel.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function release(array $data): array
    {
        $productId = $this->productId($data);
        $quantity = $this->positiveDecimal($data['quantity'] ?? null, 'quantity');

        return $this->stock->transactional(
            fn (): array => $this->stock->apply([
                'product_id' => $productId,
                'type' => StockRepository::TYPE_RELEASE,
                'quantity' => $quantity,
                'reference_type' => $data['reference_type'] ?? 'manual_reservation',
                'reference_id' => $this->referenceId($data),
                'notes' => $this->notes($data),
            ]),
        );
    }

    /**
     * Baixa a mercadoria que saiu. O `consume_reserved` faz o repositorio debitar
     * saldo fisico e reserva na mesma movimentacao, que e o que mantem o
     * disponivel estavel quando a mercadoria comprometida sai.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function consume(array $data): array
    {
        $productId = $this->productId($data);
        $quantity = $this->positiveDecimal($data['quantity'] ?? null, 'quantity');

        return $this->stock->transactional(
            fn (): array => $this->stock->apply([
                'product_id' => $productId,
                'type' => StockRepository::TYPE_CONSUME,
                'quantity' => $quantity,
                'consume_reserved' => true,
                'sale_item_id' => isset($data['sale_item_id']) ? (int) $data['sale_item_id'] : null,
                'reference_type' => $data['reference_type'] ?? 'manual_consumption',
                'reference_id' => $this->referenceId($data),
                'notes' => $this->notes($data),
            ]),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function setMinimumQuantity(array $data): array
    {
        $productId = $this->productId($data);
        $minimum = $this->decimal($data['minimum_quantity'] ?? null, 'minimum_quantity');

        if ($minimum < 0) {
            throw new RuntimeException('O estoque minimo nao pode ser negativo.');
        }

        $this->stock->transactional(function () use ($minimum, $productId): void {
            $this->stock->setMinimumQuantity($productId, $minimum);
        });

        return $this->stock->ensureRow($productId);
    }

    /**
     * @return list<string>
     */
    public function movementTypes(): array
    {
        return StockRepository::ALL_TYPES;
    }

    /**
     * @return array<string, string> tipo => rotulo
     */
    public function movementTypeLabels(): array
    {
        return [
            StockRepository::TYPE_IMPORT_ENTRY => 'Entrada por importacao',
            StockRepository::TYPE_MANUAL_IN => 'Entrada manual',
            StockRepository::TYPE_MANUAL_OUT => 'Saida manual',
            StockRepository::TYPE_RESERVE => 'Reserva',
            StockRepository::TYPE_RELEASE => 'Liberacao de reserva',
            StockRepository::TYPE_CONSUME => 'Consumo',
            StockRepository::TYPE_SALE_OUT => 'Saida por venda',
        ];
    }

    /**
     * Tipos que aumentam o saldo. A tela usa para marcar a seta da movimentacao.
     *
     * @return list<string>
     */
    public function inflowTypes(): array
    {
        return StockRepository::INFLOW_TYPES;
    }

    /**
     * Tipos que reduzem o saldo.
     *
     * @return list<string>
     */
    public function outflowTypes(): array
    {
        return StockRepository::OUTFLOW_TYPES;
    }

    /**
     * Tipos que so mexem na reserva.
     *
     * @return list<string>
     */
    public function reservationTypes(): array
    {
        return StockRepository::RESERVATION_TYPES;
    }

    private function assertAdjustmentType(string $type): string
    {
        $type = strtoupper(trim($type));

        $allowed = [StockRepository::TYPE_MANUAL_IN, StockRepository::TYPE_MANUAL_OUT];

        if (!in_array($type, $allowed, true)) {
            throw new RuntimeException('Informe se o ajuste e de entrada (IN) ou de saida (OUT).');
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function productId(array $data): int
    {
        $productId = (int) ($data['product_id'] ?? 0);

        if ($productId <= 0) {
            throw new RuntimeException('Selecione o produto.');
        }

        return $productId;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function referenceId(array $data): ?int
    {
        $referenceId = $data['reference_id'] ?? null;

        return $referenceId === null || $referenceId === '' ? null : (int) $referenceId;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function notes(array $data): ?string
    {
        $notes = trim((string) ($data['notes'] ?? ''));

        return $notes === '' ? null : mb_substr($notes, 0, 255);
    }

    private function positiveDecimal(mixed $value, string $field): float
    {
        $number = $this->decimal($value, $field);

        if ($number <= 0) {
            throw new RuntimeException('Informe um valor maior que zero.');
        }

        return $number;
    }

    private function decimal(mixed $value, string $field): float
    {
        if ($value === null || $value === '') {
            throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
        }

        if (!is_numeric($value)) {
            throw new RuntimeException('O valor deve ser numerico: ' . $field);
        }

        return round((float) $value, 3);
    }
}
