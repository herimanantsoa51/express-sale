<?php

// ============================================
// app/Http/Controllers/ProductVariantController.php
// ============================================

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\VariantAttributeValue;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    /**
     * Liste variantes d'un produit
     */
    public function index(Request $request, $productId)
    {
        $query = ProductVariant::with(['attributeValues.attributeValue.attributeType'])
            ->where('product_id', $productId);

        // Filtres
        if ($request->has('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        if ($request->has('available')) {
            $query->whereRaw('(stock_quantity - reserved_quantity - credit_quantity) > 0');
        }

        $variants = $query->get();

        // Transformer les données pour inclure les valeurs d'attributs
        $variants = $variants->map(function ($variant) {
            $variantArray = $variant->toArray();
            
            // Restructurer attribute_values pour inclure la valeur directement
            $variantArray['attribute_values'] = $variant->attributeValues
                ->filter(fn ($vav) => $vav->attributeValue)
                ->map(function ($vav) {
                    $attrValue = $vav->attributeValue;
                    $attrType = $attrValue->attributeType;
                    
                    return [
                        'attribute_type_id' => $attrType->id ?? null,
                        'value' => $attrValue->value,
                        'attribute_value_id' => $attrValue->id,
                        'attribute_type' => $attrType ? [
                            'id' => $attrType->id,
                            'name' => $attrType->name,
                            'display_name' => $attrType->display_name,
                            'input_type' => $attrType->input_type,
                        ] : null,
                    ];
                })
                ->values();
            
            return $variantArray;
        });

        return response()->json($variants);
    }

    /**
     * Créer une variante
     */
    public function store(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'price_adjustment' => 'nullable|numeric',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'image_path' => 'nullable|string',
            'attributes' => 'required|array',
            'attributes.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'attributes.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✅ Récupérer le produit pour générer le SKU
        $product = Product::findOrFail($productId);

        // ✅ Générer le SKU
        $sku = $this->generateSKU($product, $request->attributes);

        // Créer la variante avec SKU
        $variant = ProductVariant::create([
            'product_id' => $productId,
            'sku' => $sku, // ✅ Ajouté
            'price_adjustment' => $request->price_adjustment ?? 0,
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
            'image_path' => $request->image_path,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $attributes = $request->input('attributes');

        foreach ($attributes as $attr) {
            $attributeTypeId = $attr['attribute_type_id'];
            $value = $attr['value'];

            $attributeValue = AttributeValue::firstOrCreate(
                [
                    'attribute_type_id' => $attributeTypeId,
                    'value' => $value
                ],
                [
                    'sort_order' => 999
                ]
            );

            VariantAttributeValue::create([
                'variant_id' => $variant->id,
                'attribute_value_id' => $attributeValue->id,
            ]);
        }

        return response()->json($variant->load('attributeValues.attributeType'), 201);
    }

    /**
     * Mettre à jour une variante
     */
    public function update(Request $request, $productId, $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'price_adjustment' => 'nullable|numeric',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'image_path' => 'nullable|string',
            'attributes' => 'required|array',
            'attributes.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'attributes.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✅ Vérifier si l'image est supprimée (image_path est null)
        $oldImagePath = $variant->image_path;
        $newImagePath = $request->image_path;

        // ✅ Logique de suppression d'image
        if ($oldImagePath && ($newImagePath === null || $newImagePath === '')) {
            // Supprimer l'ancienne image du storage
            $this->deleteImageFromStorage($oldImagePath);
            Log::info("Image supprimée pour la variante {$variant->id}: {$oldImagePath}");
        }

        // ✅ Récupérer le produit
        $product = Product::findOrFail($productId);

        // ✅ Régénérer le SKU si les attributs ont changé
        $newSku = $this->generateSKU($product, $request->attributes);

        // Mettre à jour les champs de base
        $variant->update([
            'sku' => $newSku, // ✅ Mettre à jour le SKU
            'price_adjustment' => $request->price_adjustment ?? 0,
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
            'is_active' => $request->is_active ?? $variant->is_active,
            'image_path' => $newImagePath,
        ]);

        $attributes = $request->input('attributes');

        // Supprimer les anciennes valeurs d'attributs
        VariantAttributeValue::where('variant_id', $variant->id)->delete();

        foreach ($attributes as $attr) {
            $attributeValue = AttributeValue::firstOrCreate(
                [
                    'attribute_type_id' => $attr['attribute_type_id'],
                    'value' => $attr['value']
                ],
                ['sort_order' => 999]
            );

            VariantAttributeValue::create([
                'variant_id' => $variant->id,
                'attribute_value_id' => $attributeValue->id
            ]);
        }

        return response()->json($variant->load('attributeValues.attributeType'));
    }

    /**
     * Supprimer une variante
     */
    public function destroy($productId, $id)
    {
        $variant = ProductVariant::where('product_id', $productId)->findOrFail($id);

        // Supprimer l'image associée si elle existe
        if ($variant->image_path) {
            $this->deleteImageFromStorage($variant->image_path);
        }

        if ($variant->stock_quantity > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une variante avec du stock'
            ], 400);
        }

        $variant->delete();

        return response()->json(['message' => 'Variante supprimée'], 200);
    }

    public function show($productId, $id)
    {
        $variant = ProductVariant::with([
            'attributeValues.attributeValue.attributeType.values'
        ])
        ->where('product_id', $productId)
        ->findOrFail($id);

        $productAttributes = ProductAttribute::where('product_id', $productId)
            ->get()
            ->keyBy('attribute_type_id');

        $response = $variant->toArray();

        $response['attribute_values'] = $variant->attributeValues
            ->filter(fn ($vav) => $vav->attributeValue) // ✅ sécurité
            ->map(function ($vav) use ($productAttributes) {

                $attrValue = $vav->attributeValue;
                $attrType  = $attrValue->attributeType;

                return [
                    'attribute_type_id' => $attrType->id,

                    // ✅ VALEUR SIMPLE UTILISABLE PAR LE FRONT
                    'value' => $attrValue->value,

                    // utile si plus tard tu veux afficher le label
                    'attribute_value_id' => $attrValue->id,

                    'attribute_type' => [
                        'id' => $attrType->id,
                        'name' => $attrType->name,
                        'display_name' => $attrType->display_name,
                        'input_type' => $attrType->input_type,
                        'is_required' => $productAttributes[$attrType->id]->is_required ?? true,
                        'values' => $attrType->values,
                    ],
                ];
            })
            ->values();

        return response()->json($response);
    }

    /**
     * Générer SKU unique basé sur le produit et ses attributs
     */
    private function generateSKU($product, $attributes)
    {
        // Préfixe basé sur le nom du produit (3 premières lettres)
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 3));
        
        // ✅ Construire la partie attributs (prendre les 2-3 premiers caractères de chaque valeur)
        $attributeParts = [];
        foreach ($attributes as $attr) {
            $value = preg_replace('/[^A-Za-z0-9]/', '', $attr['value']); // Nettoyer
            
            // Pour les couleurs hex, prendre les 4 premiers caractères
            if (strpos($attr['value'], '#') === 0) {
                $attributeParts[] = strtoupper(substr($value, 0, 4));
            } else {
                $attributeParts[] = strtoupper(substr($value, 0, 3));
            }
        }
        
        $attributeString = implode('-', $attributeParts);
        
        // ✅ Générer un identifiant court unique
        $random = strtoupper(Str::random(4));
        
        $sku = "{$prefix}-{$attributeString}-{$random}";
        
        // ✅ Vérifier l'unicité et régénérer si nécessaire
        $attempt = 0;
        while (ProductVariant::where('sku', $sku)->exists() && $attempt < 10) {
            $random = strtoupper(Str::random(4));
            $sku = "{$prefix}-{$attributeString}-{$random}";
            $attempt++;
        }
        
        return $sku;
    }

    /**
     * Supprimer une image du storage
     */
    private function deleteImageFromStorage($imageUrl)
    {
        try {
            // Extraire le chemin du fichier depuis l'URL complète
            // Exemple: http://example.com/storage/uploads/images/2024/01/abc123.jpg
            // On veut: uploads/images/2024/01/abc123.jpg
            
            $parsedUrl = parse_url($imageUrl);
            $path = $parsedUrl['path'] ?? '';
            
            // Supprimer "/storage/" du début si présent
            if (strpos($path, '/storage/') === 0) {
                $path = substr($path, 9); // Supprime "/storage/"
            }
            
            // Supprimer "storage/" du début si présent
            if (strpos($path, 'storage/') === 0) {
                $path = substr($path, 8); // Supprime "storage/"
            }
            
            // Vérifier si le fichier existe et le supprimer
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info("Image supprimée du storage: {$path}");
                return true;
            }
            
            // Essayer aussi avec le chemin complet
            $fullPath = str_replace(asset('storage/'), '', $imageUrl);
            if (Storage::disk('public')->exists($fullPath)) {
                Storage::disk('public')->delete($fullPath);
                Log::info("Image supprimée du storage (full path): {$fullPath}");
                return true;
            }
            
            Log::warning("Image non trouvée dans le storage: {$imageUrl}");
            return false;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression de l'image {$imageUrl}: " . $e->getMessage());
            return false;
        }
    }
}