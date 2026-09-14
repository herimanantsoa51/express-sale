<?php

namespace App\Mcp\Tools;

use App\Models\AttributeType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les types d\'attributs (couleur, taille, pointure...) et leurs valeurs possibles. Indispensable pour créer ou mettre à jour une variante de produit avec ses attributs.')]
class ListAttributeTypesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $types = AttributeType::query()
            ->with(['values:id,attribute_type_id,value,sort_order'])
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'input_type']);

        return Response::json([
            'total' => $types->count(),
            'attribute_types' => $types->map(fn (AttributeType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'display_name' => $type->display_name,
                'input_type' => $type->input_type,
                'values' => $type->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->toArray(),
            ])->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
