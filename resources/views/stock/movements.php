<?php
/**
 * @var list<array<string, mixed>> $movements
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var array<string, mixed> $filters
 * @var array<string, string> $movementTypes
 * @var string $csrfField
 * @var array<string, mixed>|null $currentUser
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$quantity = static fn (mixed $value): float => round((float) $value, 3);
$format = static fn (float $value): string => rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');

// Seta visual por familia de movimentacao: entrada, saida ou so reserva.
$inflows = array_keys(\App\Repositories\StockRepository::INFLOW_TYPES);
$reservationTypes = array_keys(\App\Repositories\StockRepository::RESERVATION_TYPES);

$signFor = static function (string $type) use ($inflows, $reservationTypes): string {
    if (in_array($type, $inflows, true)) {
        return '+';
    }

    if (in_array($type, $reservationTypes, true)) {
        return '=';
    }

    return '-';
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações de estoque | ImportControl</title>
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
                <div class="eyebrow">Estoque</div>
                <h1>Movimentações</h1>
                <p class="muted">
                    Rastreabilidade por item de importação: cada entrada registra de qual item veio,
                    o que substitui o lote individual.
                </p>
            </section>

            <section class="card">
                <form method="get" action="/estoque/movimentacoes" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="product_id">Produto (ID)</label>
                        <input id="product_id" name="product_id" type="number" min="1" step="1" value="<?= $e($filters['product_id'] ?? '') ?>">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="type">Tipo</label>
                        <select id="type" name="type">
                            <option value="">Todos</option>
                            <?php foreach ($movementTypes as $value => $label): ?>
                                <option value="<?= $e($value) ?>"<?= ((string) ($filters['type'] ?? '') === $value) ? ' selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="from">De</label>
                        <input id="from" name="from" type="date" value="<?= $e($filters['from'] ?? '') ?>">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="to">Até</label>
                        <input id="to" name="to" type="date" value="<?= $e($filters['to'] ?? '') ?>">
                    </div>
                    <div>
                        <button type="submit">Filtrar</button>
                    </div>
                    <div>
                        <a href="/estoque/movimentacoes" class="muted">Limpar</a>
                    </div>
                    <div>
                        <a href="/estoque">Ver saldos</a>
                    </div>
                </form>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Saldo após</th>
                        <th>Item de importação</th>
                        <th>Notas</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <?php $type = (string) $movement['type']; ?>
                        <tr>
                            <td><?= (int) $movement['id'] ?></td>
                            <td>
                                <?= $e($movement['sku'] ?? '') ?>
                                <span class="muted"><?= $e($movement['product_name'] ?? '') ?></span>
                            </td>
                            <td><?= $e($movementTypes[$type] ?? $type) ?></td>
                            <td><?= $signFor($type) . $e($format($quantity($movement['quantity']))) ?></td>
                            <td><?= $e($format($quantity($movement['balance_after']))) ?></td>
                            <td><?= $movement['import_item_id'] === null ? '—' : (int) $movement['import_item_id'] ?></td>
                            <td><?= $e($movement['notes'] ?? '') ?></td>
                            <td><?= $e($movement['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($movements === []): ?>
                        <tr><td colspan="8" class="muted">Nenhuma movimentação encontrada com os filtros aplicados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="muted" style="margin-top:.75rem;">
                    Total: <?= $total ?> · Página <?= $page ?> · Por página <?= $perPage ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
