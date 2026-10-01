<?php
/**
 * @var list<array<string, mixed>> $balances
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var array<string, mixed> $filters
 * @var int $lowStockCount
 * @var array<string, string> $movementTypes
 * @var string $csrfField
 * @var bool $canAdjustStock
 * @var array<string, mixed>|null $currentUser
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

/**
 * DECIMAL(12,3) chega do MySQL como string. Sem converter, (float) na frente
 * daria 0 para "10.000".
 */
$quantity = static function (mixed $value): float {
    return round((float) $value, 3);
};

$format = static fn (float $value): string => rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');

$onlyLowStock = ($filters['below_minimum'] ?? '') === '1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque | ImportControl</title>
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
                <div class="eyebrow">Operação</div>
                <h1>Estoque</h1>
                <p class="muted">
                    Saldo físico, reservado e disponível por produto.
                    <?= $lowStockCount > 0
                        ? $e($lowStockCount) . ' produto(s) abaixo do estoque mínimo.'
                        : 'Nenhum produto abaixo do estoque mínimo.' ?>
                </p>
            </section>

            <section class="card">
                <form method="get" action="/estoque" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="search">Busca</label>
                        <input id="search" name="search" type="text" value="<?= $e($filters['search'] ?? '') ?>" placeholder="Nome ou SKU">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="below_minimum">Reposição</label>
                        <select id="below_minimum" name="below_minimum">
                            <option value="">Todos os produtos</option>
                            <option value="1"<?= $onlyLowStock ? ' selected' : '' ?>>Somente abaixo do mínimo</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Filtrar</button>
                    </div>
                    <div>
                        <a href="/estoque" class="muted">Limpar</a>
                    </div>
                    <div>
                        <a href="/estoque/movimentacoes">Ver histórico</a>
                    </div>
                </form>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Produto</th>
                        <th>Saldo</th>
                        <th>Reservado</th>
                        <th>Disponível</th>
                        <th>Mínimo</th>
                        <th>Situação</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($balances as $balance): ?>
                        <?php
                        $available = $quantity($balance['available_quantity']);
                        $minimum = $quantity($balance['minimum_quantity']);
                        $belowMinimum = $minimum > 0 && $available < $minimum;
                        ?>
                        <tr>
                            <td><?= (int) $balance['product_id'] ?></td>
                            <td><?= $e($balance['sku'] ?? '') ?></td>
                            <td><?= $e($balance['product_name'] ?? '') ?></td>
                            <td><?= $e($format($quantity($balance['quantity']))) ?></td>
                            <td><?= $e($format($quantity($balance['reserved_quantity']))) ?></td>
                            <td><?= $e($format($available)) ?></td>
                            <td><?= $e($format($minimum)) ?></td>
                            <td>
                                <?php if ($belowMinimum): ?>
                                    <strong>Abaixo do mínimo</strong>
                                <?php elseif ((string) ($balance['product_status'] ?? '') === 'INACTIVE'): ?>
                                    <span class="muted">Inativo</span>
                                <?php else: ?>
                                    <span class="muted">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($balances === []): ?>
                        <tr><td colspan="8" class="muted">Nenhum produto encontrado com os filtros aplicados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="muted" style="margin-top:.75rem;">
                    Total: <?= $total ?> · Página <?= $page ?> · Por página <?= $perPage ?>
                </div>
            </section>

            <?php if ($canAdjustStock): ?>
                <section class="card">
                    <h2>Ajustes e reservas</h2>
                    <p class="muted">
                        Nesta etapa o ajuste manual e a reserva são feitos pela API, com a permissão
                        <strong>stock.adjust</strong>. Ajuste exige justificativa; reserva exige saldo
                        disponível e não mexe no saldo físico.
                    </p>
                    <p class="muted" style="margin-top:.5rem;">
                        Saldo não pode ficar negativo — a regra é validada no serviço e também por
                        constraint no banco.
                    </p>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
