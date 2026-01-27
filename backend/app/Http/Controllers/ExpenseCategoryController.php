<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Http\Resources\ExpenseCategoryCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ActivityLogger;
use App\Enums\ActivityAction;


/**
 * Controller pour les catégories de dépenses
 */
class ExpenseCategoryController extends Controller
{
    /**
     * Liste toutes les catégories de dépenses
     * GET /api/expense-categories
     * 
     * Query params:
     * - is_active: Filtrer par statut (true/false)
     * - include_count: Inclure le nombre de transactions (true/false)
     */
    public function index(Request $request): ExpenseCategoryCollection
    {
        $query = ExpenseCategory::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('name')->get();
        return new ExpenseCategoryCollection($categories);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {   

        try {
            $category = ExpenseCategory::create($request->validated());
                ActivityLogger::success(
                    ActivityAction::EXPENSE_CATEGORY_CREATED,
                    "a créé la catégorie de dépense {$category->name}",
                    [
                        'model_type' => 'App\Models\ExpenseCategory',
                        'model_id' => $category->id,
                        'metadata' => [
                            'name' => $category->name,
                            'description' => $category->description,
                            'is_active' => $category->is_active,
                        ]
                    ],                    "/depenses",
                );
            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie de dépense créée avec succès',
                'data' => new ExpenseCategoryResource($category)
            ], 201);
        } catch (\Exception $th) {
            ActivityLogger::error(
                ActivityAction::EXPENSE_CATEGORY_CREATED,
                "a tenté de créer une catégorie de dépense et a échoué",
                $th
            );
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la catégorie de dépense',
                'error' => $th->getMessage()
            ], 500);
        }
        
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new ExpenseCategoryResource($expenseCategory)
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie de dépense mise à jour avec succès',
            'data' => new ExpenseCategoryResource($expenseCategory)
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        // Vérifier s'il y a des transactions associées
        if ($expenseCategory->transactions()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer cette catégorie car des transactions l\'utilisent'
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie de dépense supprimée avec succès'
        ]);
    }

    /**
     * Activer/Désactiver une catégorie
     * PATCH /api/expense-categories/{id}/toggle-status
     */
    public function toggleStatus(ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->update(['is_active' => !$expenseCategory->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => $expenseCategory->is_active 
                ? 'Catégorie activée avec succès' 
                : 'Catégorie désactivée avec succès',
            'data' => new ExpenseCategoryResource($expenseCategory)
        ]);
    }
}