<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée un nouveau produit dans le catalogue (nom, catégorie, prix de base, description, image).')]
class CreateProductTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom du produit est requis.',
            'category_id.required' => 'La catégorie est requise (category_id).',
            'category_id.exists' => "La catégorie sélectionnée n'existe pas.",
            'base_price.required' => 'Le prix de base est requis (base_price).',
            'base_price.min' => 'Le prix de base ne peut pas être négatif.',
        ]);

        if (! Category::find($input['category_id'])) {
            return Response::error("La catégorie #{$input['category_id']} n'existe pas.");
        }

        $product = Product::create([
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'category_id' => (int) $input['category_id'],
            'subcategory_id' => $input['subcategory_id'] ?? null,
            'base_price' => $input['base_price'],
            'image_url' => $input['image_url'] ?? null,
            'is_active' => $input['is_active'] ?? true,
        ]);

        ActivityLogger::success(
            ActivityAction::PRODUCT_CREATED,
            " a créé le produit {$product->name} via MCP",
            [
                'model_type' => Product::class,
                'model_id' => $product->id,
                'metadata' => $product->toArray(),
            ],
            "produits/{$product->id}"
        );

        return Response::json([
            'message' => 'Produit créé avec succès.',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category_id' => (int) $product->category_id,
                'subcategory_id' => $product->subcategory_id !== null ? (int) $product->subcategory_id : null,
                'base_price' => (float) $product->base_price,
                'image_url' => $product->image_url,
                'is_active' => (bool) $product->is_active,
            ],
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
            'name' => $schema->string()
                ->description('Nom du produit.')
                ->required()
                ->max(255),
            'category_id' => $schema->integer()
                ->description('Identifiant de la catégorie du produit.')
                ->required()
                ->min(1),
            'base_price' => $schema->number()
                ->description('Prix de base du produit en Ariary.')
                ->required()
                ->min(0),
            'description' => $schema->string()
                ->description('Description détaillée du produit.'),
            'subcategory_id' => $schema->integer()
                ->description('Identifiant de la sous-catégorie (optionnel).')
                ->min(1),
            'image_url' => $schema->string()
                ->description("URL de l'image du produit (optionnel)."),
            'is_active' => $schema->boolean()
                ->description('Produit actif dès la création (true par défaut).'),
        ];
    }
}
