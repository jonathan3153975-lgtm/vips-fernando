<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // allocation_method: metodo de rateio aplicado (VALUE | QUANTITY).
        // completed_at: instante do congelamento. Reabrir zera, para a edicao
        // posterior ser explicita e auditavel (baseline 69 §5).
        $pdo->exec(
            "ALTER TABLE imports
                ADD COLUMN allocation_method VARCHAR(20) NOT NULL DEFAULT 'VALUE' AFTER status,
                ADD COLUMN completed_at DATETIME NULL AFTER total_items"
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE imports
                DROP COLUMN allocation_method,
                DROP COLUMN completed_at'
        );
    }
};
