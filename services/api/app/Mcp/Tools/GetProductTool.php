<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Retourne le détail complet d\'un produit : prix, catégorie, fournisseur et toutes ses variantes avec stock, prix ajusté et attributs.')]
class GetProductTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'id' => 'required|integer|min:1',
        ], [
            'id.required' => 'Vous devez préciser l\'identifiant du produit (id).',
            'id.integer' => 'L\'identifiant du produit doit être un entier.',
        ]);

        $product = Product::with([
            'category',
            'supplier',
            'variants' => fn ($query) => $query->with(['product']),
        ])->find($input['id']);

        if (! $product) {
            return Response::error("Aucun produit ne correspond à l'identifiant fourni.");
        }

        $variants = $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'price' => (float) $variant->getFinalPriceAttribute(),
            'stock_quantity' => (int) $variant->stock_quantity,
            'reserved_quantity' => (int) $variant->reserved_quantity,
            'available_quantity' => (int) $variant->getAvailableQuantityAttribute(),
            'is_low_stock' => (bool) $variant->getIsLowStockAttribute(),
            'is_active' => (bool) $variant->is_active,
            'attributes' => $variant->getAttributeValuesString(),
            'image_url' => $variant->image_path,
        ])->toArray();

        return Response::json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => (float) $product->base_price,
            'is_active' => (bool) $product->is_active,
            'image_url' => $product->image_url,
            'category' => $product->category?->name,
            'supplier' => $product->supplier?->name,
            'variant_count' => count($variants),
            'variants' => $variants,
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
            'id' => $schema->integer()
                ->description('Identifiant du produit à consulter.')
                ->required()
                ->min(1),
        ];
    }
}
