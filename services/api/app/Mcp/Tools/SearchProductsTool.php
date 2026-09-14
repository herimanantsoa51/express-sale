<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Recherche des produits dans le catalogue (par nom, catégorie ou statut actif). Retourne une liste de produits avec prix et catégorie.')]
class SearchProductsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'query' => 'nullable|string|max:120',
            'category_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:50',
        ], [
            'query.string' => 'Le champ query doit être une chaîne de caractères.',
        ]);

        $limit = $input['limit'] ?? 10;

        $query = Product::with(['category']);

        $queryText = trim((string) ($input['query'] ?? ''));
        if ($queryText !== '') {
            $query->where('name', 'like', "%{$queryText}%");
        }
        if ($input['category_id'] ?? null) {
            $query->where('category_id', $input['category_id']);
        }
        if (isset($input['is_active'])) {
            $query->where('is_active', (bool) $input['is_active']);
        }

        $products = $query->orderByDesc('id')->limit($limit)->get();

        $data = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => (float) $product->base_price,
            'is_active' => (bool) $product->is_active,
            'category' => $product->category?->name,
            'image_url' => $product->image_url,
        ])->toArray();

        return Response::json([
            'total' => count($data),
            'products' => $data,
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
            'query' => $schema->string()
                ->description('Terme de recherche sur le nom du produit.')
                ->max(120),
            'category_id' => $schema->integer()
                ->description('Filtrer par identifiant de catégorie.')
                ->min(1),
            'is_active' => $schema->boolean()
                ->description('Filtrer les produits actifs uniquement.'),
            'limit' => $schema->integer()
                ->description('Nombre maximal de produits à retourner.')
                ->default(10)
                ->min(1)
                ->max(50),
        ];
    }
}
