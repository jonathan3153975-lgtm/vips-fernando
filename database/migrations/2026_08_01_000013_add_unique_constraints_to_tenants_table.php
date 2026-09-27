<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Sem unique, o ON DUPLICATE KEY do seed nao dispara e cada execucao
        // cria outro tenant, alem de permitir duas empresas com o mesmo CNPJ.
        // Nao removemos duplicados automaticamente: apagar empresa e dados
        // vinculados exige decisao humana. Falhamos com a lista para o operador
        // reconciliar.
        $duplicates = [];

        foreach (['document', 'email'] as $column) {
            $rows = $pdo->query(
                "SELECT {$column} AS value, COUNT(*) AS total
                 FROM tenants
                 WHERE {$column} IS NOT NULL
                 GROUP BY {$column}
                 HAVING COUNT(*) > 1"
            )->fetchAll();

            foreach ($rows as $row) {
                $duplicates[] = $column . '=' . var_export($row['value'], true) . ' (' . $row['total'] . 'x)';
            }
        }

        if ($duplicates !== []) {
            throw new RuntimeException(
                'Existem tenants duplicados em document/email: ' . implode(', ', $duplicates)
                . '. Reconcilie os registros antes de aplicar o indice unico.'
            );
        }

        $pdo->exec(
            'ALTER TABLE tenants
                ADD UNIQUE KEY uq_tenants_document (document),
                ADD UNIQUE KEY uq_tenants_email (email)'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE tenants
                DROP INDEX uq_tenants_document,
                DROP INDEX uq_tenants_email'
        );
    }
};
