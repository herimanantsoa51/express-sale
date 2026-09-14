<?php

namespace App\Http\Controllers;

use App\Models\AttributeType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeTypeController extends Controller
{
    /**
     * GET /api/attribute-types
     * Liste des types d'attributs
     */
    public function index()
    {
        return response()->json(
            AttributeType::with('values')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * POST /api/attribute-types
     * Création d'un type d'attribut
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:attribute_types,name',
            'display_name' => 'required|string|max:255',
            'input_type' => [
                'required',
                Rule::in(['select', 'multiselect', 'text', 'number']),
            ],

            // valeurs optionnelles
            'attribute_values' => 'nullable|array',
            'attribute_values.*.value' => 'required|string|max:100',
            'attribute_values.*.sort_order' => 'nullable|integer',
        ]);

        // Création du type
        $attributeType = AttributeType::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'input_type' => $data['input_type'],
        ]);

        // Création des valeurs associées
        if (! empty($data['attribute_values'])) {
            foreach ($data['attribute_values'] as $index => $valueData) {
                $attributeType->values()->create([
                    'value' => $valueData['value'],
                    'sort_order' => $valueData['sort_order'] ?? $index + 1,
                ]);
            }
        }

        return response()->json(
            $attributeType->load('values'),
            201
        );
    }

    /**
     * GET /api/attribute-types/{id}
     * Détail d'un type d'attribut
     */
    public function show($id)
    {
        $attributeType = AttributeType::with('values')->findOrFail($id);

        return response()->json($attributeType);
    }

    /**
     * PUT /api/attribute-types/{id}
     * Mise à jour
     */
    public function update(Request $request, $id)
    {
        $attributeType = AttributeType::findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attribute_types', 'name')->ignore($attributeType->id),
            ],
            'display_name' => 'required|string|max:255',
            'input_type' => [
                'required',
                Rule::in(['select', 'multiselect', 'text', 'number']),
            ],
        ]);

        $attributeType->update($data);

        return response()->json($attributeType);
    }

    /**
     * DELETE /api/attribute-types/{id}
     */
    public function destroy($id)
    {
        $attributeType = AttributeType::findOrFail($id);

        // Sécurité : empêche la suppression si utilisé
        if ($attributeType->productAttributes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : attribut utilisé par des produits',
            ], 409);
        }

        $attributeType->delete();

        return response()->json([
            'message' => 'Attribute type supprimé',
        ]);
    }
}
