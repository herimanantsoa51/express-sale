<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;
use App\Helpers\FrontendRoutes;
use App\Enums\ActivityAction;

class CategoryController extends Controller
{
    /**
     * Liste toutes les catégories
     */
    public function index(Request $request)
    {
        $query = Category::with('parent', 'children');

        // Filtrer uniquement les catégories racines (sans parent)
        if ($request->has('only_roots') && $request->only_roots) {
            $query->whereNull('parent_id');
        }
        // Filtrer par parent_id spécifique
        elseif ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        $categories = $query->orderBy('sort_order')->get();

        return response()->json($categories);
    }

    /**
     * Créer une catégorie
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $category = Category::create($request->all());

            // Déterminer si c'est une catégorie parent ou enfant
            $parentInfo = '';
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                $parentInfo = " (sous-catégorie de {$parent->name})";
            }

            DB::commit();

            // ✅ Log création catégorie
            ActivityLogger::success(
                ActivityAction::CATEGORY_CREATED,
                "a créé la catégorie '{$category->name}'{$parentInfo}",
                [
                    'model_type' => 'App\Models\Category',
                    'model_id' => $category->id,
                    'metadata' => [
                        'name' => $category->name,
                        'parent_id' => $category->parent_id,
                        'has_parent' => !is_null($category->parent_id),
                        'sort_order' => $category->sort_order,
                    ]
                ],
                
            );

            return response()->json($category, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Afficher une catégorie
     */
    public function show($id)
    {
        $category = Category::with('parent', 'children', 'products')->findOrFail($id);
        return response()->json($category);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $category = Category::findOrFail($id);

            // Sauvegarder les anciennes valeurs
            $oldValues = [
                'name' => $category->name,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'image_url' => $category->image_url,
                'sort_order' => $category->sort_order,
            ];

            $category->update($request->all());

            // Construire le message de description
            $changes = [];
            
            if ($request->filled('name') && $request->name !== $oldValues['name']) {
                $changes[] = "nom: '{$oldValues['name']}' → '{$request->name}'";
            }
            
            if ($request->has('parent_id') && $request->parent_id !== $oldValues['parent_id']) {
                $oldParent = $oldValues['parent_id'] 
                    ? Category::find($oldValues['parent_id'])?->name ?? 'Supprimée'
                    : 'Aucun';
                $newParent = $request->parent_id 
                    ? Category::find($request->parent_id)?->name ?? 'Inconnue'
                    : 'Aucun';
                $changes[] = "parent: '{$oldParent}' → '{$newParent}'";
            }
            
            if ($request->filled('description') && $request->description !== $oldValues['description']) {
                $changes[] = "description modifiée";
            }
            
            if ($request->has('image_url') && $request->image_url !== $oldValues['image_url']) {
                $changes[] = $request->image_url ? "image ajoutée/modifiée" : "image supprimée";
            }
            
            if ($request->filled('sort_order') && $request->sort_order !== $oldValues['sort_order']) {
                $changes[] = "ordre: {$oldValues['sort_order']} → {$request->sort_order}";
            }

            $description = count($changes) > 0 
                ? "a modifié la catégorie '{$category->name}' - " . implode(', ', $changes)
                : "a modifié la catégorie '{$category->name}'";

            DB::commit();

            // ✅ Log modification catégorie
            ActivityLogger::success(
                ActivityAction::CATEGORY_UPDATED,
                $description,
                [
                    'model_type' => 'App\Models\Category',
                    'model_id' => $category->id,
                    'metadata' => [
                        'old_values' => $oldValues,
                        'new_values' => $category->only(['name', 'description', 'parent_id', 'image_url', 'sort_order']),
                        'changes' => $changes,
                    ]
                ],

            );

            return response()->json($category);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $category = Category::findOrFail($id);
            
            // Vérifier si a des produits
            $productsCount = $category->products()->count();
            if ($productsCount > 0) {
                DB::rollBack();
                
                // ⚠️ Log tentative de suppression échouée
                ActivityLogger::failed(
                    ActivityAction::CATEGORY_DELETED,
                    "a tenté de supprimer la catégorie '{$category->name}' contenant {$productsCount} produit(s)",
                    [
                        'model_type' => 'App\Models\Category',
                        'model_id' => $category->id,
                        'metadata' => [
                            'reason' => 'has_products',
                            'products_count' => $productsCount,
                        ]
                    ]
                );
                
                return response()->json([
                    'message' => 'Impossible de supprimer une catégorie contenant des produits'
                ], 400);
            }

            // Sauvegarder les infos avant suppression
            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'children_count' => $category->children()->count(),
            ];

            // Vérifier si a des sous-catégories
            $childrenCount = $categoryData['children_count'];
            if ($childrenCount > 0) {
                DB::rollBack();
                
                // ⚠️ Log tentative de suppression échouée
                ActivityLogger::failed(
                    ActivityAction::CATEGORY_DELETED,
                    "a tenté de supprimer la catégorie '{$category->name}' ayant {$childrenCount} sous-catégorie(s)",
                    [
                        'model_type' => 'App\Models\Category',
                        'model_id' => $category->id,
                        'metadata' => [
                            'reason' => 'has_children',
                            'children_count' => $childrenCount,
                        ]
                    ]
                );
                
                return response()->json([
                    'message' => 'Impossible de supprimer une catégorie ayant des sous-catégories'
                ], 400);
            }

            $category->delete();

            DB::commit();

            // ✅ Log suppression catégorie
            ActivityLogger::success(
                ActivityAction::CATEGORY_DELETED,
                "a supprimé la catégorie '{$categoryData['name']}'",
                [
                    'model_type' => 'App\Models\Category',
                    'model_id' => $categoryData['id'],
                    'metadata' => [
                        'deleted_data' => $categoryData,
                    ]
                ],
            );

            return response()->json(['message' => 'Catégorie supprimée'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Liste des catégories pour la vente
     */
    public function forSale(Request $request)
    {
        $categories = Category::query()
            ->select(['id', 'name', 'image_url', 'parent_id'])
            ->with(['children' => function ($query) {
                $query->select(['id', 'name', 'image_url', 'parent_id'])
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        // Structurer la réponse
        $parents = $categories->whereNull('parent_id');
        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        $formattedCategories = $parents->map(function ($parent) use ($children) {
            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'image' => $parent->image_url,
                'children' => isset($children[$parent->id]) 
                    ? $children[$parent->id]->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'image' => $child->image_url
                        ];
                    })->values()->toArray()
                    : []
            ];
        })->values();

        return response()->json([
            'data' => $formattedCategories
        ]);
    }

    /**
     * Catégories avec leurs produits
     */
    public function withProducts()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->select('id', 'name', 'category_id');
        }])
        ->whereNull('parent_id')
        ->orderBy('name')
        ->get();

        return response()->json($categories);
    }
}