<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        // Incrementada a cada troca de senha. A sessao guarda o valor vigente
        // no login e o AuthService compara a cada requisicao: versao diferente
        // significa que a senha mudou em outro lugar e a sessao deve cair.
        // Sem isso nao ha como "invalidar todas as sessoes", porque a sessao
        // fica em arquivo no servidor e nao ha indice por usuario.
        $pdo->exec(
            'ALTER TABLE users
                ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER password'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE users DROP COLUMN auth_version');
    }
};
