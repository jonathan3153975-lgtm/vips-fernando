<?php
/** @var list<array<string, mixed>> $roles */
/** @var string $csrfField */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$activeNav = 'roles';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfis | ImportControl</title>
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
                <h1>Perfis</h1>
                <p class="muted">Cada perfil concede um conjunto de permissoes. As permissoes sao as mesmas para todos os tenants.</p>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Descricao</th>
                        <th>Permissoes</th>
                        <th>Usuarios</th>
                        <th>Tipo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><a href="/perfis/<?= (int) $role['id'] ?>"><?= $e($role['name']) ?></a></td>
                            <td class="muted"><?= $role['description'] ? $e($role['description']) : '—' ?></td>
                            <td><?= (int) $role['permission_count'] ?></td>
                            <td><?= (int) $role['user_count'] ?></td>
                            <td>
                                <?php if ((int) $role['is_system'] === 1): ?>
                                    <span class="badge">sistema</span>
                                <?php else: ?>
                                    <span class="badge on">proprio</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($roles === []): ?>
                        <tr><td colspan="6" class="muted">Nenhum perfil cadastrado neste tenant.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
