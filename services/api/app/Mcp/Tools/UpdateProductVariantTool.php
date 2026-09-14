<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProductVariantController;
use App\Models\ProductVariant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Met à jour une variante de produit : seuil d\'alerte de stock, activation/désactivation et attributs (le SKU est régénéré si les attributs changent). Les attributs doivent être fournis en entier — valeurs actuelles disponibles via get-product-tool.')]
class UpdateProductVariantTool extends Tool
{
    /**
     * La méthode du contrôleur est réutilisée telle quelle (régénération du
     * SKU, gestion d'image) : une seule source de vérité.
     */
    public function handle(Request $request, ProductVariantController $controller): Response
    {
        $input = $request->validate([
            'variant_id' => 'required|integer|min:1',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'attributes' => 'required|array|min:1',
            'attributes.*.attribute_type_id' => 'required|integer|min:1|exists:attribute_types,id',
            'attributes.*.value' => 'required|string|max:100',
        ], [
            'variant_id.required' => "L'identifiant de la variante est requis (variant_id) — voir get-product-tool.",
            'attributes.required' => "Les attributs sont requis en entier (attributes : attribute_type_id + value) — valeurs actuelles disponibles via get-product-tool.",
        ]);

        $variant = ProductVariant::find($input['variant_id']);
        if (! $variant) {
            return Response::error("Aucune variante ne correspond à l'identifiant {$input['variant_id']}.");
        }

        $httpRequest = HttpRequest::create('/', 'PUT', array_filter([
            'low_stock_threshold' => $input['low_stock_threshold'] ?? null,
            'is_active' => $input['is_active'] ?? null,
            'attributes' => $input['attributes'],
        ], fn ($v) => $v !== null));

        try {
            $response = $controller->update($httpRequest, $variant->product_id, $variant->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        $data = $response->getData(true);

        if (isset($data['errors'])) {
            return Response::error('Paramètres invalides : '.implode(' ', array_merge(...array_values($data['errors']))));
        }
        if (isset($data['error'])) {
            return Response::error(($data['message'] ?? 'Erreur').' : '.$data['error']);
        }

        $variant->refresh();

        return Response::json([
            'message' => 'Variante mise à jour avec succès.',
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'low_stock_threshold' => (int) $variant->low_stock_threshold,
                'is_active' => $variant->is_active,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'variant_id' => $schema->integer()
                ->description('Identifiant de la variante à mettre à jour.')
                ->required()
                ->min(1),
            'low_stock_threshold' => $schema->integer()
                ->description("Nouveau seuil d'alerte de stock bas.")
                ->min(0),
            'is_active' => $schema->boolean()
                ->description('Activer ou désactiver la variante.'),
            'attributes' => $schema->array()
                ->description('Attributs complets de la variante (remplace les existants, régénère le SKU). Ex. [{attribute_type_id: 1, value: "Rouge"}, {attribute_type_id: 2, value: "M"}].')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'attribute_type_id' => $schema->integer()
                            ->description("Identifiant du type d'attribut.")
                            ->required()
                            ->min(1),
                        'value' => $schema->string()
                            ->description("Valeur de l'attribut.")
                            ->required()
                            ->max(100),
                    ])
                ),
        ];
    }
}
