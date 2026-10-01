<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use RuntimeException;

/**
 * Formacao de preco a partir do custo real.
 *
 * O custo real do produto vem do rateio da importacao: custo de compra
 * convertido + rateio das despesas, gravado em `import_items.real_unit_cost`
 * no fechamento (manual/69:82). O preco sugerido e o custo acrescido da margem
 * desejada (mark-up sobre custo), que e a regra coerente com o schema de
 * `product_prices` (manual/62) e com os dois exemplos de "preco sugerido" dos
 * manuais (62 e 31).
 *
 *   preco_sugerido = custo_real * (1 + margem / 100)
 *   margem         = (preco_venda - custo_real) / custo_real * 100
 */
final class PricingService
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    /**
     * Sugere precos a partir do custo real (em centavos, para nao acumular erro
     * de ponto flutuante) e da margem desejada.
     *
     * @return array{
     *     cost_price: float,
     *     sale_price: float,
     *     minimum_price: float,
     *     margin: float,
     *     effective_margin: float
     * }
     */
    public function suggest(float $realUnitCost, float $marginPercent, ?float $minimumPrice = null): array
    {
        if ($marginPercent < 0) {
            throw new RuntimeException('A margem nao pode ser negativa.');
        }

        $costCents = (int) round($realUnitCost * 100);
        $saleCents = (int) round($costCents * (1 + $marginPercent / 100));

        $minimumCents = $minimumPrice === null
            ? $saleCents
            : (int) round($minimumPrice * 100);

        return [
            'cost_price' => $costCents / 100,
            'sale_price' => $saleCents / 100,
            'minimum_price' => $minimumCents / 100,
            'margin' => round($this->effectiveMargin($costCents, $saleCents), 2),
            'effective_margin' => round($this->effectiveMargin($costCents, $saleCents), 2),
        ];
    }

    /**
     * Mesma montagem de `suggest`, mas a partir de um preco de venda explicito:
     * a margem passa a ser a do preco que sera gravado, e nao a margem sugerida.
     * Sem este metodo, gravar um `sale_price` enviado junto de uma `margin`
     * divergiria — a linha em `product_prices` ficaria com dois numeros que nao
     * descrevem a mesma operacao.
     *
     * @return array{
     *     cost_price: float,
     *     sale_price: float,
     *     minimum_price: float,
     *     margin: float,
     *     effective_margin: float
     * }
     */
    public function forSalePrice(float $realUnitCost, float $salePrice, ?float $minimumPrice = null): array
    {
        $costCents = (int) round($realUnitCost * 100);
        $saleCents = (int) round($salePrice * 100);

        $minimumCents = $minimumPrice === null
            ? $saleCents
            : (int) round($minimumPrice * 100);

        return [
            'cost_price' => $costCents / 100,
            'sale_price' => $saleCents / 100,
            'minimum_price' => $minimumCents / 100,
            'margin' => round($this->effectiveMargin($costCents, $saleCents), 2),
            'effective_margin' => round($this->effectiveMargin($costCents, $saleCents), 2),
        ];
    }

    /**
     * Sugere o preco de um produto vinculado a um item de importacao, usando o
     * custo real congelado no rateio. Retorna null quando o item ainda nao tem
     * custo real (importacao nao concluida).
     *
     * @return array<string, float>|null
     */
    public function suggestForProduct(int $productId, float $marginPercent, ?float $minimumPrice = null): ?array
    {
        $product = $this->products->find($productId);

        if ($product === null) {
            throw \App\Core\NotFoundException::entity('Produto', 'nao encontrado.');
        }

        $importItemId = $product['default_import_item_id'] === null
            ? null
            : (int) $product['default_import_item_id'];

        if ($importItemId === null) {
            return null;
        }

        $realUnitCost = $this->products->realUnitCostForItem($importItemId);

        if ($realUnitCost === null) {
            return null;
        }

        return $this->suggest($realUnitCost, $marginPercent, $minimumPrice);
    }

    /**
     * Margem efetiva (mark-up sobre custo) em %, ja em centavos.
     */
    private function effectiveMargin(int $costCents, int $saleCents): float
    {
        if ($costCents <= 0) {
            return 0.0;
        }

        return (($saleCents - $costCents) / $costCents) * 100;
    }
}
