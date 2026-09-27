<?php
/** @var list<array<string, mixed>> $users */
/** @var list<array<string, mixed>> $roles */
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
    <title>Usuarios | ImportControl</title>
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
                <div class="eyebrow">Administracao</div>
                <h1>Usuarios</h1>
                <p class="muted"><?= count($users) ?> usuario(s) neste tenant. Usuarios e perfis de outras empresas nao sao visiveis.</p>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situacao</th>
                        <th>Ultimo acesso</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $row): ?>
                        <tr>
                            <td><a href="/usuarios/<?= (int) $row['id'] ?>"><?= $e($row['name']) ?></a></td>
                            <td><?= $e($row['email']) ?></td>
                            <td><?= $e($row['role_name']) ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'ACTIVE' ? 'on' : 'off' ?>">
                                    <?= $row['status'] === 'ACTIVE' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="muted"><?= $row['last_login'] ? $e($formatter->date($row['last_login'], true)) : 'nunca' ?></td>
                            <td class="row">
                                <?php if ($canManageUsers): ?>
                                    <form method="post" action="/usuarios/<?= (int) $row['id'] ?>/<?= $row['status'] === 'ACTIVE' ? 'bloquear' : 'ativar' ?>">
                                        <?= $csrfField ?>
                                        <button type="submit" class="<?= $row['status'] === 'ACTIVE' ? 'danger' : 'secondary' ?>">
                                            <?= $row['status'] === 'ACTIVE' ? 'Bloquear' : 'Reativar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($users === []): ?>
                        <tr><td colspan="6" class="muted">Nenhum usuario neste tenant.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="card">
                <h2>Permissoes em uso neste tenant</h2>
                <p class="muted"><?= count($roles) ?> perfil(is) cadastrado(s).</p>
                <p><a href="/perfis">Ver perfis e permissoes</a></p>
            </section>
        </main>
    </div>
</body>
</html>
