<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Estoque transacional (EPIC 06). Duas garantias no banco, alem da
        // regra aplicada no StockService:
        //
        //  1. CHECK impede saldo negativo e reserva maior que o saldo. A regra
        //     vem de manual/42 s13, manual/53 s16 e manual/62: "Produto nao pode
        //     possuir estoque negativo". Aplicar so na applicacao deixaria a
        //     garantia depender de todo caminho de escrita lembrar dela.
        //  2. Indice para o historico, que agora e consultado por tenant + tipo +
        //     periodo (rota P1) e nao so por produto.
        //
        // product_lots e stock_movements.lot_id ficaram fora desta migration:
        // o usuario decidiu rastrear pelo item de importacao (ja gravado em
        // stock_movements.import_item_id) em vez de criar lotes com saldo
        // proprio. O roteiro registra a decisao e o que ela adia.
        $pdo->exec(
            'ALTER TABLE stock
                ADD CONSTRAINT chk_stock_quantity_non_negative CHECK (quantity >= 0),
                ADD CONSTRAINT chk_stock_reserved_non_negative CHECK (reserved_quantity >= 0),
                ADD CONSTRAINT chk_stock_reserved_within_quantity CHECK (reserved_quantity <= quantity)'
        );

        $pdo->exec(
            'ALTER TABLE stock_movements
                ADD KEY idx_stock_movements_tenant_created (tenant_id, created_at),
                ADD KEY idx_stock_movements_tenant_type (tenant_id, type)'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE stock_movements
                DROP INDEX idx_stock_movements_tenant_created,
                DROP INDEX idx_stock_movements_tenant_type'
        );

        $pdo->exec(
            'ALTER TABLE stock
                DROP CONSTRAINT chk_stock_reserved_within_quantity,
                DROP CONSTRAINT chk_stock_reserved_non_negative,
                DROP CONSTRAINT chk_stock_quantity_non_negative'
        );
    }
};
