<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée une variante pour un produit existant (ex. couleur + pointure). Les valeurs d\'attributs sont créées si besoin et un SKU unique est généré automatiquement.')]
class CreateProductVariantTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'attributes' => 'required|array|min:1',
            'attributes.*.attribute_type_id' => 'required|integer|exists:attribute_types,id',
            'attributes.*.value' => 'required|string|max:255',
            'price_adjustment' => 'nullable|numeric',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'image_path' => 'nullable|string',
        ], [
            'product_id.required' => 'Le produit est requis (product_id).',
            'product_id.exists' => "Le produit sélectionné n'existe pas.",
            'attributes.required' => 'Au moins un attribut est requis (ex. couleur, pointure).',
            'attributes.*.attribute_type_id.exists' => "Un type d'attribut sélectionné n'existe pas.",
            'attributes.*.value.required' => 'La valeur de chaque attribut est requise.',
        ]);

        $product = Product::find($input['product_id']);

        if (! $product) {
            return Response::error("Le produit #{$input['product_id']} n'existe pas.");
        }

        $sku = $this->generateSku($product, $input['attributes']);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'price_adjustment' => $input['price_adjustment'] ?? 0,
            'low_stock_threshold' => $input['low_stock_threshold'] ?? 5,
            'image_path' => $input['image_path'] ?? null,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $attributesMap = [];

        foreach ($input['attributes'] as $attr) {
            $attributeValue = AttributeValue::firstOrCreate(
                [
                    'attribute_type_id' => (int) $attr['attribute_type_id'],
                    'value' => $attr['value'],
                ],
                ['sort_order' => 999]
            );

            VariantAttributeValue::create([
                'variant_id' => $variant->id,
                'attribute_value_id' => $attributeValue->id,
            ]);

            $typeName = $attributeValue->attributeType?->display_name
                ?? $attributeValue->attributeType?->name
                ?? "attribut #{$attr['attribute_type_id']}";

            $attributesMap[$typeName] = $attributeValue->value;
        }

        ActivityLogger::success(
            ActivityAction::PRODUCT_VARIANT_CREATED,
            " a créé une nouvelle variante (SKU: {$variant->sku}) pour le produit {$product->name} via MCP",
            [
                'model_type' => ProductVariant::class,
                'model_id' => $variant->id,
                'metadata' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $variant->sku,
                ],
            ],
            "produits/{$product->id}"
        );

        return Response::json([
            'message' => 'Variante créée avec succès.',
            'variant' => [
                'id' => $variant->id,
                'product_id' => (int) $variant->product_id,
                'sku' => $variant->sku,
                'price_adjustment' => (float) $variant->price_adjustment,
                'stock_quantity' => (int) $variant->stock_quantity,
                'is_active' => (bool) $variant->is_active,
                'attributes' => $attributesMap,
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
            'product_id' => $schema->integer()
                ->description('Identifiant du produit parent.')
                ->required()
                ->min(1),
            'attributes' => $schema->array()
                ->description('Attributs de la variante : objets {attribute_type_id, value}. Ex. couleur (id 16) = "Noir", pointure (id 17) = "34".')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'attribute_type_id' => $schema->integer()
                            ->description("Identifiant du type d'attribut (ex. 16 = couleur, 17 = pointure).")
                            ->required()
                            ->min(1),
                        'value' => $schema->string()
                            ->description("Valeur de l'attribut (ex. \"Noir\", \"34\").")
                            ->required()
                            ->max(255),
                    ])
                ),
            'price_adjustment' => $schema->number()
                ->description('Ajustement de prix par rapport au prix de base du produit (0 par défaut).'),
            'low_stock_threshold' => $schema->integer()
                ->description("Seuil d'alerte de stock bas (5 par défaut).")
                ->min(0),
            'image_path' => $schema->string()
                ->description("URL de l'image spécifique à cette variante (optionnel)."),
        ];
    }

    /**
     * Générer un SKU unique basé sur le produit et ses attributs.
     */
    private function generateSku(Product $product, array $attributes): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 3));

        $attributeParts = [];

        foreach ($attributes as $attr) {
            $value = preg_replace('/[^A-Za-z0-9]/', '', $attr['value']);

            if (str_starts_with($attr['value'], '#')) {
                $attributeParts[] = strtoupper(substr($value, 0, 4));
            } else {
                $attributeParts[] = strtoupper(substr($value, 0, 3));
            }
        }

        $attributeString = implode('-', $attributeParts);
        $attempt = 0;

        do {
            $sku = "{$prefix}-{$attributeString}-".strtoupper(Str::random(4));
            $attempt++;
        } while (ProductVariant::where('sku', $sku)->exists() && $attempt < 10);

        return $sku;
    }
}
