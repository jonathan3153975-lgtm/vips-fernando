<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\NotFoundException;
use App\Repositories\AuditLogRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SupplierRepository;
use RuntimeException;

final class ProductService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE'];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly BrandRepository $brands,
        private readonly SupplierRepository $suppliers,
        private readonly AuditLogRepository $auditLogs,
        private readonly ?PricingService $pricing = null,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->products->all();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, int $limit, int $offset): array
    {
        return [
            'data' => $this->products->paginate($filters, $limit, $offset),
            'total' => $this->products->countFiltered($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $productId): array
    {
        return $this->products->findOrFail($productId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        foreach (['sku', 'name'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
            }
        }

        $this->validateRelations($data);
        $this->validateStatus($data);

        $sku = trim((string) $data['sku']);

        if ($this->products->skuExists($sku)) {
            throw new RuntimeException('Ja existe um produto com este SKU.');
        }

        $data['sku'] = $sku;
        $data = $this->withPricedDefaults($data, $data);

        $product = $this->products->create($data);
        $this->auditLogs->create('products.create', 'product', (int) $product['id']);

        return $product;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $productId, array $data): array
    {
        $current = $this->products->findOrFail($productId);

        $this->validateRelations($data);
        $this->validateStatus($data);

        if (array_key_exists('sku', $data)) {
            $sku = trim((string) $data['sku']);

            if ($sku === '') {
                throw new RuntimeException('Campo obrigatorio ausente: sku');
            }

            if ($this->products->skuExists($sku, $productId)) {
                throw new RuntimeException('Ja existe um produto com este SKU.');
            }

            $data['sku'] = $sku;
        }

        $data = $this->withPricedDefaults($data, $current);

        $product = $this->products->update($productId, $data);
        $this->auditLogs->create('products.update', 'product', $productId);

        return $product;
    }

    /**
     * Exclusao bloqueada para produto com estoque ou historico; a saida correta
     * nesses casos e desativar.
     *
     * @return array<string, mixed>
     */
    public function delete(int $productId): array
    {
        $product = $this->products->findOrFail($productId);
        $this->products->assertDeletable($productId);

        $this->products->deactivate($productId);
        $this->auditLogs->create('products.deactivate', 'product', $productId);

        return $this->products->find($productId) ?? $product;
    }

    /**
     * Sugere o preco de venda a partir do custo real do item de importacao
     * vinculado e da margem desejada.
     *
     * @return array<string, float>
     */
    public function suggestPrice(int $productId, float $marginPercent, ?float $minimumPrice = null): array
    {
        if ($this->pricing === null) {
            throw new RuntimeException('Servico de precificacao nao configurado.');
        }

        $suggestion = $this->pricing->suggestForProduct($productId, $marginPercent, $minimumPrice);

        if ($suggestion === null) {
            throw new RuntimeException(
                'Produto sem item de importacao concluido vinculado: o custo real ainda nao existe.'
            );
        }

        return $suggestion;
    }

    /**
     * @return array<string, mixed>
     */
    public function stock(int $productId): array
    {
        return $this->products->stock($productId);
    }

    /**
     * Garante que categoria, marca, fornecedor e item de importacao referenciados
     * existam no mesmo tenant. Sem isso, um id de outro tenant passaria e o
     * registro apontaria para a entity errada.
     *
     * @param array<string, mixed> $data
     */
    private function validateRelations(array $data): void
    {
        if (array_key_exists('category_id', $data) && $data['category_id'] !== null && $data['category_id'] !== '') {
            if (!$this->categories->exists((int) $data['category_id'])) {
                throw new RuntimeException('Categoria nao encontrada.');
            }
        }

        if (array_key_exists('brand_id', $data) && $data['brand_id'] !== null && $data['brand_id'] !== '') {
            if (!$this->brands->exists((int) $data['brand_id'])) {
                throw new RuntimeException('Marca nao encontrada.');
            }
        }

        if (array_key_exists('supplier_id', $data) && $data['supplier_id'] !== null && $data['supplier_id'] !== '') {
            if (!$this->suppliers->exists((int) $data['supplier_id'])) {
                throw new RuntimeException('Fornecedor nao encontrado.');
            }
        }

        if (
            array_key_exists('default_import_item_id', $data)
            && $data['default_import_item_id'] !== null
            && $data['default_import_item_id'] !== ''
            && !$this->products->importItemExists((int) $data['default_import_item_id'])
        ) {
            throw new RuntimeException('Item de importacao nao encontrado.');
        }
    }

    /**
     * Valida o status e o grava normalizado: sem reescrever em maiusculo, um
     * "active" aceito aqui acabaria gravado como "active" e as consultas por
     * status dependeriam da collation do banco.
     *
     * @param array<string, mixed> $data
     */
    private function validateStatus(array &$data): void
    {
        if (!array_key_exists('status', $data)) {
            return;
        }

        $status = strtoupper((string) $data['status']);

        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status invalido. Use ACTIVE ou INACTIVE.');
        }

        $data['status'] = $status;
    }

    /**
     * Completa o preco a partir do custo real e da margem informada.
     *
     * Sem isso a margem chegaria ao `product_prices` como zero e o preco de
     * venda nunca existiria: o suggestPrice e apenas leitura. As regras sao:
     *
     *  - `cost_price` explicito tem precedencia; sem ele, usa-se o custo real
     *    do item de importacao concluido (tratado no repository);
     *  - com `margin` e um custo disponivel, `sale_price` e `minimum_price` sao
     *    calculados e gravados;
     *  - `sale_price` enviado vence sobre a margem calculada, e nesse caso a
     *    margem effective e recalculada para nao divergir do preco gravado.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context produto atual, para reaproveitar o
     *                                        item de importacao ja vinculado
     *
     * @return array<string, mixed>
     */
    private function withPricedDefaults(array $data, array $context): array
    {
        if ($this->pricing === null) {
            return $data;
        }

        $margin = $this->numericInput($data, 'margin');
        $salePrice = $this->numericInput($data, 'sale_price');

        // Só precifica quando ha informacao para precificar: margem ou preco de
        // venda explicito. Sem nenhum dos dois, o price enviado (se houver) segue
        // intacto e o repository apenas completa o custo.
        if ($margin === null && $salePrice === null) {
            return $data;
        }

        $cost = $this->numericInput($data, 'cost_price') ?? $this->currentCost($data, $context);

        // Sem custo real a margem nao tem base: o preco fica em zero em vez de
        // virar um numero inventado.
        $cost ??= 0.0;

        $suggestion = $salePrice === null
            ? $this->pricing->suggest($cost, $margin, $this->numericInput($data, 'minimum_price'))
            : $this->pricing->forSalePrice($cost, $salePrice, $this->numericInput($data, 'minimum_price'));

        $data['price'] = (is_array($data['price'] ?? null) ? $data['price'] : []) + [
            'cost_price' => $cost,
            'sale_price' => $suggestion['sale_price'],
            'minimum_price' => $this->numericInput($data, 'minimum_price') ?? $suggestion['minimum_price'],
            'margin' => $suggestion['margin'],
        ];

        return $data;
    }

    /**
     * Custo disponivel para a margem: o explicito, o do produto ja gravado ou o
     * custo real do item de importacao concluido vinculado.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    private function currentCost(array $data, array $context): ?float
    {
        $itemId = $data['default_import_item_id'] ?? $context['default_import_item_id'] ?? null;

        if ($itemId === null || $itemId === '') {
            return null;
        }

        $realUnitCost = $this->products->realUnitCostForItem((int) $itemId);

        return $realUnitCost === null ? null : $realUnitCost;
    }

    /**
     * Le um campo numerico aceitando as duas notacoes aceitas pelo front
     * (float e string), devolvendo null quando ausente ou vazio.
     *
     * @param array<string, mixed> $data
     */
    private function numericInput(array $data, string $key): ?float
    {
        if (!array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
            return null;
        }

        return (float) $data[$key];
    }
}
