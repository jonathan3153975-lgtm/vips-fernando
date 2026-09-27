<?php
/** @var array<string, mixed> $role */
/** @var list<array<string, mixed>> $permissions */
/** @var list<array<string, mixed>> $available */
/** @var string $csrfField */
/** @var bool $canManageUsers */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$activeNav = 'roles';

$granted = [];
foreach ($permissions as $permission) {
    $granted[(int) $permission['id']] = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($role['name']) ?> | ImportControl</title>
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
                <div class="eyebrow"><a href="/perfis">Perfis</a> / detalhe</div>
                <h1><?= $e($role['name']) ?></h1>
                <p class="muted"><?= $role['description'] ? $e($role['description']) : 'Sem descricao.' ?></p>
                <p>
                    <span class="badge"><?= (int) $role['user_count'] ?> usuario(s)</span>
                    <?php if ((int) $role['is_system'] === 1): ?>
                        <span class="badge">perfil do sistema</span>
                    <?php endif; ?>
                </p>
            </section>

            <section class="card">
                <h2>Permissoes concedidas</h2>
                <?php if ($permissions === []): ?>
                    <p class="muted">Este perfil ainda nao concede nenhuma permissao.</p>
                <?php else: ?>
                    <table>
                        <thead>
                        <tr>
                            <th>Permissao</th>
                            <th>Descricao</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($permissions as $permission): ?>
                            <tr>
                                <td><code><?= $e($permission['name']) ?></code></td>
                                <td class="muted"><?= $permission['description'] ? $e($permission['description']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <?php if ($canManageUsers): ?>
                <section class="card">
                    <h2>Conceder permissoes</h2>
                    <form method="post" action="/perfis/<?= (int) $role['id'] ?>/permissoes">
                        <?= $csrfField ?>
                        <?php /* Sem input hidden: quando nenhuma caixa esta marcada o campo
                            simply nao chega, e o controller usa o default []. Um hidden com
                            o mesmo nome sobrescreveria os valores marcados conforme a ordem
                            de parsing do PHP. */ ?>
                        <table>
                            <thead>
                            <tr>
                                <th></th>
                                <th>Permissao</th>
                                <th>Descricao</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($available as $permission): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               name="permission_ids[]"
                                               value="<?= (int) $permission['id'] ?>"
                                               <?= isset($granted[(int) $permission['id']]) ? 'checked' : '' ?>>
                                    </td>
                                    <td><code><?= $e($permission['name']) ?></code></td>
                                    <td class="muted"><?= $permission['description'] ? $e($permission['description']) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="row" style="margin-top: 14px;">
                            <button type="submit">Salvar permissoes</button>
                            <span class="muted">O perfil que concede gestao de usuarios nao pode ficar sem ela se for o unico.</span>
                        </p>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
