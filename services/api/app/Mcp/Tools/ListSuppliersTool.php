<?php

namespace App\Mcp\Tools;

use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les fournisseurs avec leur score de fiabilité et leur nombre de réapprovisionnements. À utiliser pour trouver le supplier_id avant de créer une commande (réception de stock). Filtres : recherche, actifs uniquement.')]
class ListSuppliersTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'active_only' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Supplier::query()
            ->select(['id', 'name', 'wechat', 'profile', 'contact', 'reliability_score', 'is_active'])
            ->withCount('stockReceipts')
            ->orderBy('name');

        if ($input['active_only'] ?? true) {
            $query->where('is_active', true);
        }

        if (! empty($input['search'])) {
            $search = $input['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('wechat', 'ILIKE', "%{$search}%")
                    ->orWhere('contact', 'ILIKE', "%{$search}%");
            });
        }

        $suppliers = $query->get();

        return Response::json([
            'total' => $suppliers->count(),
            'suppliers' => $suppliers->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'wechat' => $supplier->wechat,
                'contact' => $supplier->contact,
                'profile' => $supplier->profile,
                'reliability_score' => $supplier->reliability_score !== null ? (float) $supplier->reliability_score : null,
                'is_active' => $supplier->is_active,
                'receipt_count' => (int) $supplier->stock_receipts_count,
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
            'active_only' => $schema->boolean()
                ->description('Ne retourner que les fournisseurs actifs (true par défaut).'),
            'search' => $schema->string()
                ->description('Recherche textuelle sur le nom, le wechat ou le contact.'),
        ];
    }
}
