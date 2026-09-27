<?php
/** @var array<string, mixed> $user */
/** @var string $csrfField */
/** @var bool $canManageUsers */
/** @var \App\Support\TenantFormatter $formatter */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($user['name']) ?> | ImportControl</title>
    <?php require __DIR__ . '/../partials/styles.php'; ?>
</head>
<body>
    <header>
        <div>
            <div class="eyebrow">Tenant</div>
            <strong><?= $e($currentUser['tenant_name'] ?? '') ?></strong>
        </div>
        <form method="post" action="/logout">
            <?= $csrfField ?>
            <button type="submit">Sair</button>
        </form>
    </header>

    <div class="shell">
        <?php require __DIR__ . '/../partials/notice.php'; ?>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <main>
            <section class="card">
                <div class="eyebrow"><a href="/usuarios">Usuarios</a> / detalhe</div>
                <h1><?= $e($user['name']) ?></h1>
                <p class="muted">#<?= (int) $user['id'] ?></p>
            </section>

            <section class="grid">
                <article class="card">
                    <div class="eyebrow">E-mail</div>
                    <strong><?= $e($user['email']) ?></strong>
                </article>
                <article class="card">
                    <div class="eyebrow">Perfil</div>
                    <strong><?= $e($user['role_name']) ?></strong>
                    <?php if ((int) $user['role_is_system'] === 1): ?>
                        <p class="muted">Perfil do sistema</p>
                    <?php endif; ?>
                </article>
                <article class="card">
                    <div class="eyebrow">Situacao</div>
                    <strong>
                        <span class="badge <?= $user['status'] === 'ACTIVE' ? 'on' : 'off' ?>">
                            <?= $user['status'] === 'ACTIVE' ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </strong>
                </article>
                <article class="card">
                    <div class="eyebrow">Ultimo acesso</div>
                    <strong><?= $user['last_login'] ? $e($formatter->date($user['last_login'], true)) : 'nunca' ?></strong>
                </article>
            </section>

            <section class="card">
                <h2>Telefone</h2>
                <p><?= $user['phone'] ? $e($user['phone']) : '<span class="muted">nao informado</span>' ?></p>
            </section>

            <?php if ($canManageUsers): ?>
                <section class="card">
                    <h2>Acoes</h2>
                    <form method="post" action="/usuarios/<?= (int) $user['id'] ?>/<?= $user['status'] === 'ACTIVE' ? 'bloquear' : 'ativar' ?>">
                        <?= $csrfField ?>
                        <button type="submit" class="<?= $user['status'] === 'ACTIVE' ? 'danger' : 'secondary' ?>">
                            <?= $user['status'] === 'ACTIVE' ? 'Bloquear acesso' : 'Reativar acesso' ?>
                        </button>
                    </form>
                    <p class="muted">O tenant sempre precisa conservar ao menos um administrador ativo.</p>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
