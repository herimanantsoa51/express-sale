<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\AttributeType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    /**
     * GET /api/attribute-types/{attributeTypeId}/values
     * Liste des valeurs d'un type d'attribut
     */
    public function index($attributeTypeId)
    {
        $attributeType = AttributeType::findOrFail($attributeTypeId);
        
        $values = $attributeType->values()
            ->orderBy('sort_order')
            ->orderBy('value')
            ->get();

        return response()->json($values);
    }

    /**
     * POST /api/attribute-types/{attributeTypeId}/values
     * Création d'une valeur d'attribut
     */
    public function store(Request $request, $attributeTypeId)
    {
        $attributeType = AttributeType::findOrFail($attributeTypeId);

        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attribute_values')->where(function ($query) use ($attributeTypeId) {
                    return $query->where('attribute_type_id', $attributeTypeId);
                })
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Détermine l'ordre si non fourni
        if (empty($data['sort_order'])) {
            $lastOrder = AttributeValue::where('attribute_type_id', $attributeTypeId)
                ->max('sort_order') ?? 0;
            $data['sort_order'] = $lastOrder + 1;
        }

        $attributeValue = $attributeType->values()->create($data);

        return response()->json($attributeValue, 201);
    }

    /**
     * GET /api/attribute-types/{attributeTypeId}/values/{id}
     * Détail d'une valeur d'attribut
     */
    public function show($attributeTypeId, $id)
    {
        $attributeValue = AttributeValue::where('attribute_type_id', $attributeTypeId)
            ->findOrFail($id);

        return response()->json($attributeValue);
    }

    /**
     * PUT /api/attribute-types/{attributeTypeId}/values/{id}
     * Mise à jour d'une valeur d'attribut
     */
    public function update(Request $request, $attributeTypeId, $id)
    {
        $attributeValue = AttributeValue::where('attribute_type_id', $attributeTypeId)
            ->findOrFail($id);

        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attribute_values')
                    ->where(function ($query) use ($attributeTypeId) {
                        return $query->where('attribute_type_id', $attributeTypeId);
                    })
                    ->ignore($attributeValue->id)
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $attributeValue->update($data);

        return response()->json($attributeValue);
    }

    /**
     * DELETE /api/attribute-types/{attributeTypeId}/values/{id}
     * Suppression d'une valeur d'attribut
     */
    public function destroy($attributeTypeId, $id)
    {
        $attributeValue = AttributeValue::where('attribute_type_id', $attributeTypeId)
            ->findOrFail($id);

        // Vérifie si la valeur est utilisée par des variantes
        if ($attributeValue->hasVariants()) {
            return response()->json([
                'message' => 'Cannot delete: value is used by product variants'
            ], 409);
        }

        $attributeValue->delete();

        return response()->json([
            'message' => 'Attribute value deleted successfully'
        ]);
    }

    /**
     * POST /api/attribute-types/{attributeTypeId}/values/reorder
     * Réordonner les valeurs
     */
    public function reorder(Request $request, $attributeTypeId)
    {
        $attributeType = AttributeType::findOrFail($attributeTypeId);

        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:attribute_values,id'
        ]);

        foreach ($data['order'] as $index => $valueId) {
            AttributeValue::where('id', $valueId)
                ->where('attribute_type_id', $attributeTypeId)
                ->update(['sort_order' => $index + 1]);
        }

        // Retourne les valeurs dans le nouvel ordre
        $values = $attributeType->values()
            ->orderBy('sort_order')
            ->get();

        return response()->json($values);
    }
}