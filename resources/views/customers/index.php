<?php
/**
 * @var list<array<string, mixed>> $customers
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var array<string, string> $filters
 * @var string $csrfField
 * @var bool $canCreateCustomers
 * @var array<string, mixed>|null $currentUser
 */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$money = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');

$date = static fn (mixed $value): string => $value === null || $value === ''
    ? '—'
    : date('d/m/Y', strtotime((string) $value));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | ImportControl</title>
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
                <div class="eyebrow">CRM</div>
                <h1>Clientes</h1>
                <p class="muted">
                    Cadastro e historico comercial. <?= (int) $total ?> cliente(s) no filtro atual.
                </p>
            </section>

            <section class="card">
                <form method="get" action="/clientes" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:.75rem; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="search">Busca</label>
                        <input id="search" name="search" type="text" value="<?= $e($filters['search'] ?? '') ?>" placeholder="Nome, documento ou contato">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="status">Situação</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="ACTIVE"<?= ($filters['status'] ?? '') === 'ACTIVE' ? ' selected' : '' ?>>Ativos</option>
                            <option value="INACTIVE"<?= ($filters['status'] ?? '') === 'INACTIVE' ? ' selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="has_sales">Compras</label>
                        <select id="has_sales" name="has_sales">
                            <option value="">Todos</option>
                            <option value="1"<?= ($filters['has_sales'] ?? '') === '1' ? ' selected' : '' ?>>Já comprou</option>
                            <option value="0"<?= ($filters['has_sales'] ?? '') === '0' ? ' selected' : '' ?>>Nunca comprou</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Filtrar</button>
                    </div>
                    <div>
                        <a href="/clientes" class="muted">Limpar</a>
                    </div>
                </form>
            </section>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Documento</th>
                        <th>Contato</th>
                        <th>Compras</th>
                        <th>Total gasto</th>
                        <th>Última compra</th>
                        <th>Situação</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><a href="/clientes/<?= (int) $customer['id'] ?>"><?= $e($customer['name']) ?></a></td>
                            <td><?= $e($customer['document'] ?? '—') ?></td>
                            <td class="muted">
                                <?= $e($customer['phone'] ?? '') ?>
                                <?php if (($customer['email'] ?? '') !== ''): ?>
                                    <br><?= $e($customer['email']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) ($customer['purchase_count'] ?? 0) ?></td>
                            <td><?= $e($money($customer['total_spent'] ?? 0)) ?></td>
                            <td class="muted"><?= $e($date($customer['last_purchase_at'] ?? null)) ?></td>
                            <td>
                                <?php if ((string) ($customer['status'] ?? '') === 'INACTIVE'): ?>
                                    <strong>Inativo</strong>
                                <?php else: ?>
                                    <span class="muted">Ativo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($customers === []): ?>
                        <tr><td colspan="7" class="muted">Nenhum cliente encontrado com os filtros aplicados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="muted" style="margin-top:.75rem;">
                    Total: <?= (int) $total ?> · Página <?= (int) $page ?> · Por página <?= (int) $perPage ?>
                </div>
            </section>

            <?php if ($canCreateCustomers): ?>
                <section class="card">
                    <h2>Novo cliente</h2>
                    <p class="muted">
                        O documento é único por tenant e fica guardado só com dígitos —
                        <code>123.456.789-01</code> e <code>12345678901</code> contam como o mesmo.
                        Sem documento é permitido.
                    </p>
                    <form method="post" action="/clientes" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:.75rem; margin-top:.75rem;">
                        <?= $csrfField ?>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="name">Nome</label>
                            <input id="name" name="name" type="text" required maxlength="150">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="document">Documento</label>
                            <input id="document" name="document" type="text" maxlength="30">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="phone">Telefone</label>
                            <input id="phone" name="phone" type="text" maxlength="30">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="whatsapp">WhatsApp</label>
                            <input id="whatsapp" name="whatsapp" type="text" maxlength="30">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="email">E-mail</label>
                            <input id="email" name="email" type="email" maxlength="150">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="address">Endereço</label>
                            <input id="address" name="address" type="text">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="notes">Observações</label>
                            <input id="notes" name="notes" type="text">
                        </div>
                        <div>
                            <button type="submit">Cadastrar</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>