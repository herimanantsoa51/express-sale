<?php

namespace App\Mcp\Tools;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fournit des statistiques de ventes sur une période : chiffre d\'affaires total, nombre de ventes, panier moyen et top produits.')]
class SalesStatisticsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'start_date.date' => 'La date de début doit être au format Y-m-d (ex: 2026-09-01).',
            'end_date.date' => 'La date de fin doit être au format Y-m-d (ex: 2026-09-08).',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $startDate = $input['start_date'] ?? now()->copy()->subDays(30)->format('Y-m-d');
        $endDate = $input['end_date'] ?? now()->format('Y-m-d');

        $start = Carbon::parse((string) $startDate)->startOfDay();
        $end = Carbon::parse((string) $endDate)->endOfDay();

        $period = [$start, $end];

        $totalRevenue = (float) Sale::whereBetween('sale_date', $period)->sum('total_amount');
        $totalSales = Sale::whereBetween('sale_date', $period)->count();
        $averageValue = $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0.0;

        $topProducts = SaleItem::selectRaw('
                product_variants.product_id,
                products.name as product_name,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.subtotal) as total_revenue
            ')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', $period)
            ->groupBy('product_variants.product_id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return Response::json([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_revenue' => $totalRevenue,
            'total_sales' => $totalSales,
            'average_sale_value' => $averageValue,
            'top_products' => $topProducts->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'product_name' => $item->product_name,
                'total_quantity' => (int) $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue,
            ])->toArray(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('Début de la période au format Y-m-d (ex: 2026-09-01). Par défaut il y a 30 jours.'),
            'end_date' => $schema->string()
                ->description('Fin de la période au format Y-m-d (ex: 2026-09-08). Par défaut aujourd\'hui.'),
        ];
    }
}
