<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Devolucao por item (EPIC 08).
        //
        // `sale_returns` (migration 000011) tem valor e motivo, mas nao tem
        // COMO foram devolvidos: uma venda de 3 linhas devolvida "parcialmente"
        // nao deixaria registro de qual linha voltou. A quantidade devolvida
        // por linha e o que impede a segunda devolucao da mesma mercadoria — sem
        // isso, devolver 5 unidades duas vezes colocaria 10 no estoque e as duas
        // vezes passariam.
        //
        // `amount` e o valor proporcional estornado daquela linha, para o
        // estorno bater com a soma da devolucao.
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sale_return_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_return_id BIGINT UNSIGNED NOT NULL,
                sale_item_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity DECIMAL(12,3) NOT NULL,
                amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_sale_return_items_return FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_return_items_sale_item FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_return_items_product FOREIGN KEY (product_id) REFERENCES products(id),
                KEY idx_sale_return_items_return (sale_return_id),
                KEY idx_sale_return_items_sale_item (sale_item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS sale_return_items');
    }
};
