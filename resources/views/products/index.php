<?php
/** @var list<array<string, mixed>> $products */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var list<array<string, mixed>> $categories */
/** @var list<array<string, mixed>> $brands */
/** @var list<array<string, mixed>> $suppliers */
/** @var string $csrfField */
/** @var bool $canCreateProducts */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$activeNav = 'products';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos | ImportControl</title>
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
                <div class="eyebrow">Catálogo</div>
                <h1>Produtos</h1>
                <p class="muted">Controle de catálogo, custo real (via item de importação concluída) e precificação.</p>
            </section>

            <section class="card">
                <form method="get" action="/produtos" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="search">Busca</label>
                        <input id="search" name="search" type="text" value="<?= $e($filters['search'] ?? '') ?>" placeholder="Nome ou SKU">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
                        <label class="muted" for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="ACTIVE"<?= (($filters['status'] ?? '') === 'ACTIVE') ? ' selected' : '' ?>>Ativos</option>
                            <option value="INACTIVE"<?= (($filters['status'] ?? '') === 'INACTIVE') ? ' selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
<label class="muted" for="filter_category_id">Categoria</label>
<select id="filter_category_id" name="category_id">
                            <option value="">Todas</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"<?= ((string) ($filters['category_id'] ?? '') === (string) $c['id']) ? ' selected' : '' ?>><?= $e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.4rem;">
<label class="muted" for="filter_brand_id">Marca</label>
<select id="filter_brand_id" name="brand_id">
                            <option value="">Todas</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"<?= ((string) ($filters['brand_id'] ?? '') === (string) $b['id']) ? ' selected' : '' ?>><?= $e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Filtrar</button>
                    </div>
                    <div>
                        <a href="/produtos" class="muted">Limpar</a>
                    </div>
                </form>
            </section>

            <?php if ($canCreateProducts): ?>
                <section class="card">
                    <h2>Novo produto</h2>
                    <form method="post" action="/produtos" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; align-items:end;">
                        <?= $csrfField ?>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="sku">SKU *</label>
                            <input id="sku" name="sku" required type="text" placeholder="SKU">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="name">Nome *</label>
                            <input id="name" name="name" required type="text" placeholder="Nome do produto">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="unit">Unidade</label>
                            <input id="unit" name="unit" type="text" value="UN" placeholder="UN">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="category_id">Categoria</label>
                            <select id="category_id" name="category_id">
                                <option value="">Selecione</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= $e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="brand_id">Marca</label>
                            <select id="brand_id" name="brand_id">
                                <option value="">Selecione</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>"><?= $e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="supplier_id">Fornecedor</label>
                            <select id="supplier_id" name="supplier_id">
                                <option value="">Selecione</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= $e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="default_import_item_id">ID do item de importação</label>
                            <input id="default_import_item_id" name="default_import_item_id" type="number" min="1" step="1" placeholder="(opcional)">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="margin">Margem %</label>
                            <input id="margin" name="margin" type="number" step="0.01" min="0" placeholder="ex.: 30.00">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="cost_price">Custo (R$)</label>
                            <input id="cost_price" name="cost_price" type="number" step="0.01" min="0" placeholder="Preenchido auto se vinculado">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="sale_price">Preço venda (R$)</label>
                            <input id="sale_price" name="sale_price" type="number" step="0.01" min="0">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="minimum_price">Preço mínimo (R$)</label>
                            <input id="minimum_price" name="minimum_price" type="number" step="0.01" min="0">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:.4rem;">
                            <label class="muted" for="description">Descrição</label>
                            <input id="description" name="description" type="text" placeholder="Opcional">
                        </div>
                        <div>
                            <button type="submit">Criar produto</button>
                        </div>
                    </form>
                    <p class="muted" style="margin-top:.5rem;">Ao vincular um item de importação concluída, o <strong>custo real rateado</strong> é preenchido automaticamente.</p>
                </section>
            <?php endif; ?>

            <section class="card">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Custo (R$)</th>
                        <th>Venda (R$)</th>
                        <th>Mínimo (R$)</th>
                        <th>Margem %</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= (int) $product['id'] ?></td>
                            <td><?= $e($product['sku']) ?></td>
                            <td><?= $e($product['name']) ?></td>
                            <td><?= $e($product['status']) ?></td>
                            <td><?= $e(number_format((float) ($product['cost_price'] ?? 0), 2, ',', '.')) ?></td>
                            <td><?= $e(number_format((float) ($product['sale_price'] ?? 0), 2, ',', '.')) ?></td>
                            <td><?= $e(number_format((float) ($product['minimum_price'] ?? 0), 2, ',', '.')) ?></td>
                            <td><?= $e(number_format((float) ($product['margin'] ?? 0), 2, ',', '.')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($products === []): ?>
                        <tr><td colspan="8" class="muted">Nenhum produto encontrado com os filtros aplicados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="muted" style="margin-top:.75rem;">Total: <?= $total ?> · Página <?= $page ?> · Por página <?= $perPage ?></div>
            </section>
        </main>
    </div>
</body>
</html>
