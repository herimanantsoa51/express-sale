<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Met à jour un produit existant (nom, prix de base, description, catégorie, image, statut actif). Au moins un champ à modifier doit être fourni.')]
class UpdateProductTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:categories,id',
            'base_price' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'id.required' => "Vous devez préciser l'identifiant du produit (id).",
            'base_price.min' => 'Le prix de base ne peut pas être négatif.',
        ]);

        $product = Product::find($input['id']);
        if (! $product) {
            return Response::error("Aucun produit ne correspond à l'identifiant fourni.");
        }

        $data = [];
        if (array_key_exists('name', $input)) {
            $data['name'] = $input['name'];
        }
        if (array_key_exists('description', $input)) {
            $data['description'] = $input['description'];
        }
        if (array_key_exists('category_id', $input)) {
            $data['category_id'] = (int) $input['category_id'];
        }
        if (array_key_exists('subcategory_id', $input)) {
            $data['subcategory_id'] = $input['subcategory_id'];
        }
        if (array_key_exists('base_price', $input)) {
            $data['base_price'] = $input['base_price'];
        }
        if (array_key_exists('image_url', $input)) {
            $data['image_url'] = $input['image_url'];
        }
        if (array_key_exists('is_active', $input)) {
            $data['is_active'] = (bool) $input['is_active'];
        }

        if ($data === []) {
            return Response::error('Aucun champ à mettre à jour. Fournissez au moins un champ (name, base_price, description, category_id, image_url, is_active).');
        }

        $product->update($data);

        ActivityLogger::success(
            ActivityAction::PRODUCT_UPDATED,
            " a modifié le produit {$product->name} via MCP",
            [
                'model_type' => Product::class,
                'model_id' => $product->id,
                'metadata' => $product->toArray(),
            ],
            "produits/{$product->id}"
        );

        return Response::json([
            'message' => 'Produit mis à jour avec succès.',
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
            'id' => $schema->integer()
                ->description('Identifiant du produit à modifier.')
                ->required()
                ->min(1),
            'name' => $schema->string()
                ->description('Nouveau nom du produit.')
                ->max(255),
            'description' => $schema->string()
                ->description('Nouvelle description du produit.'),
            'category_id' => $schema->integer()
                ->description('Nouvelle catégorie du produit.')
                ->min(1),
            'subcategory_id' => $schema->integer()
                ->description('Nouvelle sous-catégorie du produit.')
                ->min(1),
            'base_price' => $schema->number()
                ->description('Nouveau prix de base du produit en Ariary.')
                ->min(0),
            'image_url' => $schema->string()
                ->description("Nouvelle URL de l'image du produit."),
            'is_active' => $schema->boolean()
                ->description('Activer ou désactiver le produit.'),
        ];
    }
}
