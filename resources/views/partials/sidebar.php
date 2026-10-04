<?php
/**
 * Menu lateral. Recebe do controller:
 *   $currentUser      array|null  sessao do usuario
 *   $canManageUsers   bool        possui users.manage
 *   $activeNav        string      identificador da pagina atual
 *
 * Itens aparecem conforme as permissoes do usuario da sessao.
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$activeNav = $activeNav ?? '';
$currentUser = $currentUser ?? null;
$canManageUsers = $canManageUsers ?? false;
$canManageSettings = $canManageSettings ?? false;

$has = static function (string $permission) use ($currentUser): bool {
    if (!is_array($currentUser)) {
        return false;
    }

    return in_array($permission, $currentUser['permissions'] ?? [], true);
};
?>
<aside>
    <div class="group">
        <div class="group-label">Operacao</div>
        <?php if ($has('dashboard.view')): ?>
            <a class="item<?= $activeNav === 'dashboard' ? ' active' : '' ?>" href="/dashboard">Dashboard</a>
        <?php endif; ?>
        <?php if ($has('products.view')): ?>
            <a class="item<?= $activeNav === 'products' ? ' active' : '' ?>" href="/produtos">Produtos</a>
        <?php endif; ?>
        <?php if ($has('stock.view')): ?>
            <a class="item<?= $activeNav === 'stock' ? ' active' : '' ?>" href="/estoque">Estoque</a>
            <a class="item<?= $activeNav === 'stock-movements' ? ' active' : '' ?>" href="/estoque/movimentacoes">Movimentações</a>
        <?php endif; ?>
        <?php if ($has('customers.view')): ?>
            <a class="item<?= $activeNav === 'customers' ? ' active' : '' ?>" href="/clientes">Clientes</a>
        <?php endif; ?>
    </div>

    <div class="group">
        <div class="group-label">Administracao</div>
        <?php if ($has('users.view')): ?>
            <a class="item<?= $activeNav === 'users' ? ' active' : '' ?>" href="/usuarios">Usuarios</a>
            <a class="item<?= $activeNav === 'roles' ? ' active' : '' ?>" href="/perfis">Perfis</a>
        <?php endif; ?>
        <?php if ($has('settings.manage')): ?>
            <a class="item<?= $activeNav === 'settings' ? ' active' : '' ?>" href="/configuracoes">Configuracoes</a>
        <?php endif; ?>
    </div>
</aside>
