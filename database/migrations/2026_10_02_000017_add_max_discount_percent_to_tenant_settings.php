<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Desconto controlado na venda (EPIC 08). O roteiro exige `sales.discount`
        // "acima de um limite percentual definido no tenant" — sem uma coluna para
        // o limite, a regra nao tem onde morar e o desconto viraria livre.
        //
        // O padrao e 0.00: nenhum desconto passa sem permissao especifica. E o
        // limite seguro, porque quem libera desconto e uma decisao conscious, nao
        // um efeito colateral de migrar o banco.
        $pdo->exec(
            'ALTER TABLE tenant_settings
                ADD COLUMN max_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE tenant_settings DROP COLUMN max_discount_percent');
    }
};
