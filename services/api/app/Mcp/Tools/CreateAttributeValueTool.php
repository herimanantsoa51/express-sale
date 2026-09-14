<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Ajoute une valeur à un type d\'attribut existant (ex. "Rouge" au type "Couleur"). La valeur doit être unique pour ce type. Les valeurs sont utilisées pour définir les variantes de produits.')]
class CreateAttributeValueTool extends Tool
{
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'attribute_type_id' => 'required|integer|min:1|exists:attribute_types,id',
            'value' => 'required|string|max:100',
        ], [
            'attribute_type_id.required' => "L'identifiant du type d'attribut est requis (attribute_type_id) — voir list-attribute-types-tool.",
            'value.required' => 'La valeur est requise (value).',
        ]);

        $exists = AttributeValue::where('attribute_type_id', $input['attribute_type_id'])
            ->where('value', $input['value'])
            ->exists();
        if ($exists) {
            return Response::error("La valeur « {$input['value']} » existe déjà pour ce type d'attribut.");
        }

        try {
            $lastOrder = AttributeValue::where('attribute_type_id', $input['attribute_type_id'])->max('sort_order') ?? 0;
            $value = AttributeValue::create([
                'attribute_type_id' => (int) $input['attribute_type_id'],
                'value' => $input['value'],
                'sort_order' => $lastOrder + 1,
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::PRODUCT_UPDATED,
                " la création de la valeur d'attribut via MCP a échoué",
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création : '.$e->getMessage());
        }

        return Response::json([
            'message' => 'Valeur d\'attribut créée avec succès.',
            'attribute_value' => [
                'id' => $value->id,
                'attribute_type_id' => (int) $value->attribute_type_id,
                'value' => $value->value,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute_type_id' => $schema->integer()
                ->description("Identifiant du type d'attribut (ex. Couleur).")
                ->required()
                ->min(1),
            'value' => $schema->string()
                ->description("Valeur à ajouter (ex. 'Rouge'). Unique pour ce type.")
                ->required()
                ->max(100),
        ];
    }
}
