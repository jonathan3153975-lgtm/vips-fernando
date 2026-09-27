<?php
/** @var string|null $error */
/** @var string|null $notice */
/** @var string|null $devLink */
/** @var string $oldEmail */
/** @var string $csrfField */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a senha | ImportControl</title>
    <style>
        :root { --bg: #eef4fb; --panel: #ffffff; --brand: #0d47a1; --brand-dark: #0a336f; --danger: #b42318; --ok: #1b7f4b; --border: #d0dbe8; --text: #15314a; --muted: #5d7083; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(160deg, #d9e9ff 0%, var(--bg) 45%, #f8fbff 100%); font-family: "Segoe UI", sans-serif; color: var(--text); }
        .card { width: min(460px, calc(100% - 32px)); background: var(--panel); border-radius: 24px; padding: 40px 32px; box-shadow: 0 28px 80px rgba(21, 49, 74, 0.16); }
        h1 { margin-top: 0; font-size: 24px; }
        p { line-height: 1.5; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input { width: 100%; border: 1px solid var(--border); border-radius: 14px; padding: 14px 16px; font-size: 15px; margin-bottom: 16px; }
        button { width: 100%; border: 0; border-radius: 14px; padding: 14px 16px; font-size: 15px; font-weight: 700; cursor: pointer; background: var(--brand); color: #fff; }
        button:hover { background: var(--brand-dark); }
        .error { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #fef3f2; color: var(--danger); }
        .notice { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #e9f6ef; color: var(--ok); }
        .dev { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #fff8e6; color: #8a5a00; font-size: 13px; word-break: break-all; }
        .meta { margin-top: 18px; color: var(--muted); font-size: 14px; }
        .meta a { color: var(--brand); }
    </style>
</head>
<body>
    <div class="card">
        <h1>Esqueci minha senha</h1>
        <p class="meta">Informe o email cadastrado para receber as instrucoes de redefinicao.</p>

        <?php if ($notice): ?>
            <div class="notice"><?= $e($notice) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= $e($error) ?></div>
        <?php endif; ?>

        <?php if ($devLink): ?>
            <div class="dev">
                Ambiente de desenvolvimento (sem envio de email):
                <a href="<?= $e($devLink) ?>"><?= $e($devLink) ?></a>
            </div>
        <?php endif; ?>

        <form method="post" action="/esqueci-senha">
            <?= $csrfField ?>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= $e($oldEmail) ?>" required>
            <button type="submit">Enviar instrucoes</button>
        </form>

        <p class="meta"><a href="/login">Voltar para o login</a></p>
    </div>
</body>
</html>
