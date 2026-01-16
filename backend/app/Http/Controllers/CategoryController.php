<?php

namespace App\Http\Controllers;


// ============================================
// app/Http/Controllers/CategoryController.php
// ============================================


use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $category = Category::create($request->all());

        return response()->json($category, 201);
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
        $category = Category::findOrFail($id);

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

        $category->update($request->all());

        return response()->json($category);
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Vérifier si a des produits
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie contenant des produits'
            ], 400);
        }

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée'], 200);
    }
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
}

