<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --panel: #ffffff;
            --text: #13304a;
            --muted: #60758a;
            --brand: #0d47a1;
            --accent: #16a085;
            --border: #d8e2ec;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #dfeeff 0, var(--bg) 42%, #eef3f8 100%);
            color: var(--text);
            min-height: 100vh;
        }

        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 56px 24px;
        }

        .hero {
            background: linear-gradient(135deg, rgba(13, 71, 161, 0.97), rgba(16, 124, 195, 0.9));
            color: #fff;
            padding: 32px;
            border-radius: 24px;
            box-shadow: 0 24px 64px rgba(19, 48, 74, 0.16);
        }

        .hero p { color: rgba(255, 255, 255, 0.88); }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .card {
            background: var(--panel);
            padding: 20px;
            border-radius: 18px;
            border: 1px solid var(--border);
        }

        .card strong {
            display: block;
            margin-bottom: 8px;
        }

        .status {
            margin-top: 24px;
            padding: 16px 20px;
            border-left: 4px solid var(--accent);
            background: rgba(22, 160, 133, 0.08);
            border-radius: 12px;
        }
    </style>
</head>
<body>
<main>
    <section class="hero">
        <p>Baseline inicial do MVP consolidada e fundacao tecnica pronta para evolucao.</p>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Estrutura inicial em PHP 8.3+, MVC com Service Layer e Repository Pattern, preparada para multi-tenant e evolucao modular.</p>
    </section>

    <section class="grid">
        <article class="card">
            <strong>MVP priorizado</strong>
            <span>Autenticacao, empresas, importacoes, produtos, estoque, clientes, vendas e financeiro base.</span>
        </article>
        <article class="card">
            <strong>Banco inicial</strong>
            <span>Migrations preparadas para tenants, usuarios, papeis, permissoes e configuracoes.</span>
        </article>
        <article class="card">
            <strong>Proxima etapa</strong>
            <span>Implementar modulos do backlog tecnico na ordem definida nos manuais consolidados.</span>
        </article>
    </section>

    <div class="status">
        Endpoint de saude disponivel em <code>/health</code>.
    </div>
</main>
</body>
</html>
