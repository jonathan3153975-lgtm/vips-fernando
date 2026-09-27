<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ImportControl</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --brand: #0d47a1;
            --border: #d8e2ec;
            --text: #16324a;
            --muted: #65798d;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
        }
        main { max-width: 1120px; margin: 0 auto; padding: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
        }
        .eyebrow { color: var(--muted); font-size: 14px; }
        button {
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
        }
        form { margin: 0; }
        code { background: #eef3fb; padding: 2px 6px; border-radius: 6px; }
    </style>
</head>
<body>
    <header>
        <div>
            <div class="eyebrow">Tenant</div>
            <strong><?= htmlspecialchars((string) ($user['tenant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <form method="post" action="/logout">
            <?= $csrfField ?>
            <button type="submit">Sair</button>
        </form>
    </header>
    <main>
        <section class="card" style="margin-bottom: 16px;">
            <div class="eyebrow">Usuario autenticado</div>
            <h1><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Perfil: <strong><?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p>Email: <?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <section class="grid">
            <article class="card">
                <div class="eyebrow">RBAC</div>
                <strong><?= count($user['permissions'] ?? []) ?> permissoes carregadas</strong>
                <p>Controle por middleware ja habilitado para as rotas do MVP.</p>
            </article>
            <article class="card">
                <div class="eyebrow">Sprint 02</div>
                <strong>Sessao web ativa</strong>
                <p>Fluxo de login, logout e area protegida funcionando.</p>
            </article>
            <article class="card">
                <div class="eyebrow">Proxima fase</div>
                <strong>Modulos operacionais</strong>
                <p>Importacoes, estoque, vendas e financeiro seguem a modelagem do MVP em migrations e OpenAPI.</p>
            </article>
        </section>
        <section class="card" style="margin-top: 16px;">
            <div class="eyebrow">Permissoes</div>
            <p><code><?= htmlspecialchars(implode('</code>, <code>', $user['permissions'] ?? []), ENT_QUOTES, 'UTF-8') ?></code></p>
        </section>
    </main>
</body>
</html>
