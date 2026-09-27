<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ImportControl</title>
    <style>
        :root {
            --bg: #eef4fb;
            --panel: #ffffff;
            --brand: #0d47a1;
            --brand-dark: #0a336f;
            --danger: #b42318;
            --border: #d0dbe8;
            --text: #15314a;
            --muted: #5d7083;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(160deg, #d9e9ff 0%, var(--bg) 45%, #f8fbff 100%);
            font-family: "Segoe UI", sans-serif;
            color: var(--text);
        }

        .shell {
            width: min(980px, calc(100% - 32px));
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: var(--panel);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(21, 49, 74, 0.16);
        }

        .hero {
            padding: 48px;
            background: linear-gradient(160deg, rgba(13, 71, 161, 0.96), rgba(9, 125, 173, 0.88));
            color: #fff;
        }

        .form {
            padding: 48px 36px;
        }

        h1, h2 { margin-top: 0; }
        p { line-height: 1.5; }

        .field { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 15px;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            background: var(--brand);
            color: #fff;
        }
        button:hover { background: var(--brand-dark); }
        .error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fef3f2;
            color: var(--danger);
        }
        .notice {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #e9f6ef;
            color: #1b7f4b;
        }
        .meta {
            margin-top: 18px;
            color: var(--muted);
            font-size: 14px;
        }

        @media (max-width: 760px) {
            .shell { grid-template-columns: 1fr; }
            .hero, .form { padding: 28px; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <p>ImportControl</p>
            <h1>Controle de importacoes, estoque, vendas e financeiro em uma unica operacao.</h1>
            <p>Esta baseline implementa a Sprint 02 com sessao autenticada e RBAC por tenant, pronta para evolucao dos modulos operacionais.</p>
        </section>
        <section class="form">
            <h2>Entrar</h2>
            <p>Use as credenciais do seu tenant para acessar o sistema.</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($notice ?? null): ?>
                <div class="notice"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="/login">
                <?= $csrfField ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars((string) $oldEmail, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="field">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <button type="submit">Acessar</button>
            </form>

            <p class="meta"><a href="/esqueci-senha">Esqueci minha senha</a></p>
            <p class="meta">Seed padrao de desenvolvimento cria um usuario administrador quando o banco estiver configurado.</p>
        </section>
    </main>
</body>
</html>
