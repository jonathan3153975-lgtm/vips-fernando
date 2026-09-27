<?php
/** @var string $token */
/** @var string|null $error */
/** @var string $csrfField */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir nova senha | ImportControl</title>
    <style>
        :root { --bg: #eef4fb; --panel: #ffffff; --brand: #0d47a1; --brand-dark: #0a336f; --danger: #b42318; --border: #d0dbe8; --text: #15314a; --muted: #5d7083; }
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
        .meta { margin-top: 18px; color: var(--muted); font-size: 14px; }
        .meta a { color: var(--brand); }
    </style>
</head>
<body>
    <div class="card">
        <h1>Definir nova senha</h1>

        <?php if ($error): ?>
            <div class="error"><?= $e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/redefinir-senha">
            <?= $csrfField ?>
            <input type="hidden" name="token" value="<?= $e($token) ?>">

            <label for="password">Nova senha</label>
            <input id="password" name="password" type="password" minlength="8" required>

            <label for="password_confirmation">Confirmar nova senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>

            <button type="submit">Salvar nova senha</button>
        </form>

        <p class="meta">A senha deve ter ao menos 8 caracteres. <a href="/login">Voltar para o login</a></p>
    </div>
</body>
</html>
