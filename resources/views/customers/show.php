<?php
/**
 * @var array<string, mixed> $customer
 * @var array<string, mixed> $summary
 * @var list<array<string, mixed>> $purchases
 * @var int $purchaseTotal
 * @var int $page
 * @var int $perPage
 * @var array<string, string> $filters
 * @var string $csrfField
 * @var bool $canEditCustomers
 * @var bool $canDeleteCustomers
 * @var array<string, mixed>|null $currentUser
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$money = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');

$date = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime((string) $value);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
};

$statusLabels = [
    'OPEN' => 'Aberta',
    'COMPLETED' => 'Concluída',
    'CANCELLED' => 'Cancelada',
    'RETURNED' => 'Devolvida',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($customer['name']) ?> | ImportControl</title>
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
                <div class="eyebrow">Cliente #<?= (int) $customer['id'] ?></div>
                <h1><?= $e($customer['name']) ?></h1>
                <p class="muted">
                    <?= (string) ($customer['status'] ?? '') === 'INACTIVE' ? 'Cliente inativo (bloqueado).' : 'Cliente ativo.' ?>
                    <?php if (($customer['document'] ?? '') !== ''): ?>
                        Documento <?= $e($customer['document']) ?>.
                    <?php endif; ?>
                </p>
                <p class="muted">
                    <?php if (($customer['phone'] ?? '') !== ''): ?>
                        <?= $e($customer['phone']) ?><br>
                    <?php endif; ?>
                    <?php if (($customer['whatsapp'] ?? '') !== ''): ?>
                        <?= $e($customer['whatsapp']) ?><br>
                    <?php endif; ?>
                    <?php if (($customer['email'] ?? '') !== ''): ?>
                        <?= $e($customer['email']) ?><br>
                    <?php endif; ?>
                    <?php if (($customer['address'] ?? '') !== ''): ?>
                        <?= $e($customer['address']) ?><br>
                    <?php endif; ?>
                    <?php if (($customer['notes'] ?? '') !== ''): ?>
                        <?= $e($customer['notes']) ?>
                    <?php endif; ?>
                </p>
            </section>

            <section class="card">
                <h2>Resumo comercial</h2>
                <table>
                    <tbody>
                    <tr><td>Compras concluídas</td><td><?= (int) ($summary['purchase_count'] ?? 0) ?></td></tr>
                    <tr><td>Total gasto</td><td><?= $e($money($summary['total_spent'] ?? 0)) ?></td></tr>
                    <tr><td>Ticket médio</td><td><?= $e($money($summary['average_ticket'] ?? 0)) ?></td></tr>
                    <tr><td>Lucro gerado</td><td><?= $e($money($summary['total_profit'] ?? 0)) ?></td></tr>
                    <tr><td>Última compra</td><td><?= $e($date($summary['last_purchase_at'] ?? null)) ?></td></tr>
                    </tbody>
                </table>
                <p class="muted" style="margin-top:.75rem;">
                    O resumo conta apenas vendas com situação <strong>CONCLUÍDA</strong>.
                    Venda aberta ou cancelada não entra em total gasto nem em ticket médio.
                </p>
            </section>

            <section class="card">
                <form method="get" action="/clientes/<?= (int) $customer['id'] ?>" style="display:flex; gap:.75rem; align-items:end; flex-wrap:wrap;">
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="status">Situação da venda</label>
                        <select id="status" name="status">
                            <option value="">Todas</option>
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?= $e($value) ?>"<?= ($filters['status'] ?? '') === $value ? ' selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Filtrar</button>
                    </div>
                    <div>
                        <a href="/clientes/<?= (int) $customer['id'] ?>" class="muted">Limpar</a>
                    </div>
                    <div>
                        <a href="/clientes">Voltar para a lista</a>
                    </div>
                </form>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>Venda</th>
                        <th>Situação</th>
                        <th>Itens</th>
                        <th>Total</th>
                        <th>Lucro</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?= $e($purchase['sale_number'] ?? '#' . (int) $purchase['id']) ?></td>
                            <td>
                                <?php $saleStatus = strtoupper((string) ($purchase['status'] ?? '')); ?>
                                <?= $e($statusLabels[$saleStatus] ?? $saleStatus) ?>
                            </td>
                            <td><?= (int) ($purchase['item_count'] ?? 0) ?></td>
                            <td><?= $e($money($purchase['total'] ?? 0)) ?></td>
                            <td><?= $e($money($purchase['profit'] ?? 0)) ?></td>
                            <td class="muted"><?= $e($date($purchase['sale_date'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($purchases === []): ?>
                        <tr><td colspan="6" class="muted">Nenhuma venda registrada para este cliente.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="muted" style="margin-top:.75rem;">
                    Total: <?= (int) $purchaseTotal ?> · Página <?= (int) $page ?> · Por página <?= (int) $perPage ?>
                </div>
            </section>

            <?php if ($canEditCustomers || $canDeleteCustomers): ?>
                <section class="card">
                    <h2>Alterações</h2>
                    <p class="muted">
                        Edição e bloqueio ficam na API
                        (<strong>customers.edit</strong> e <strong>customers.delete</strong>).
                        Cliente com histórico é bloqueado, nunca apagado — a venda precisa
                        continuar apontando para quem comprou.
                    </p>
                    <?php if ($canDeleteCustomers): ?>
                        <p class="muted" style="margin-top:.5rem;">
                            <code>DELETE /api/v1/customers/<?= (int) $customer['id'] ?></code>
                        </p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>