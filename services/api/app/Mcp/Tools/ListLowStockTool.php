<?php

namespace App\Mcp\Tools;

use App\Models\ProductVariant;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les produits et variantes à commander : stock sous le seuil d\'alerte, avec le déficit et la quantité déjà en commande (réapprovisionnements non encore validés). À utiliser pour préparer une commande fournisseur via create-stock-receipt-tool.')]
class ListLowStockTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = ProductVariant::query()
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('product_variants.is_active', true)
            ->where('products.is_active', true)
            ->whereColumn('product_variants.stock_quantity', '<=', 'product_variants.low_stock_threshold')
            ->select([
                'product_variants.id',
                'product_variants.sku',
                'product_variants.stock_quantity',
                'product_variants.low_stock_threshold',
                'products.id AS product_id',
                'products.name AS product_name',
            ])
            ->orderBy('product_variants.stock_quantity')
            ->limit(100);

        if (! empty($input['search'])) {
            $query->where(function ($q) use ($input) {
                $q->where('products.name', 'ILIKE', "%{$input['search']}%")
                    ->orWhere('product_variants.sku', 'ILIKE', "%{$input['search']}%");
            });
        }

        $variants = $query->get();

        // Quantité déjà en commande : réapprovisionnements non validés, en une requête groupée
        $orderedByVariant = StockReceipt::query()
            ->join('stock_receipt_items', 'stock_receipt_items.stock_receipt_id', '=', 'stock_receipts.id')
            ->whereIn('stock_receipts.status', ['pending', 'sent', 'in_transit', 'arrived', 'cost_allocated'])
            ->whereIn('stock_receipt_items.variant_id', $variants->pluck('id')->isEmpty() ? [0] : $variants->pluck('id'))
            ->groupBy('stock_receipt_items.variant_id')
            ->selectRaw('stock_receipt_items.variant_id, COALESCE(SUM(stock_receipt_items.quantity_ordered - stock_receipt_items.quantity_received), 0) AS pending_qty')
            ->get()
            ->pluck('pending_qty', 'variant_id')
            ->all();

        return Response::json([
            'total' => $variants->count(),
            'variants' => $variants->map(fn (ProductVariant $variant) => [
                'variant_id' => $variant->id,
                'product_id' => (int) $variant->product_id,
                'product_name' => $variant->product_name,
                'sku' => $variant->sku,
                'stock_quantity' => (int) $variant->stock_quantity,
                'low_stock_threshold' => (int) $variant->low_stock_threshold,
                'deficit' => max(0, (int) $variant->low_stock_threshold - (int) $variant->stock_quantity),
                'pending_order_quantity' => (int) ($orderedByVariant[$variant->id] ?? 0),
            ])->toArray(),
        ]);
    }
}
