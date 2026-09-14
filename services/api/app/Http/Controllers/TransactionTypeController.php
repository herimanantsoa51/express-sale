<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionTypeRequest;
use App\Http\Requests\UpdateTransactionTypeRequest;
use App\Http\Resources\TransactionTypeCollection;
use App\Http\Resources\TransactionTypeResource;
use App\Models\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller pour les types de transactions
 */
class TransactionTypeController extends Controller
{
    /**
     * Liste tous les types de transactions
     * GET /api/transaction-types
     *
     * Query params:
     * - category: Filtrer par catégorie (income/expense/transfer)
     */
    public function index(Request $request): TransactionTypeCollection
    {
        $query = TransactionType::query();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $types = $query->orderBy('category')->orderBy('display_name')->get();

        return new TransactionTypeCollection($types);
    }

    public function store(StoreTransactionTypeRequest $request): JsonResponse
    {
        $type = TransactionType::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Type de transaction créé avec succès',
            'data' => new TransactionTypeResource($type),
        ], 201);
    }

    public function show(TransactionType $transactionType): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new TransactionTypeResource($transactionType),
        ]);
    }

    public function update(UpdateTransactionTypeRequest $request, TransactionType $transactionType): JsonResponse
    {
        $transactionType->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Type de transaction mis à jour avec succès',
            'data' => new TransactionTypeResource($transactionType),
        ]);
    }

    public function destroy(TransactionType $transactionType): JsonResponse
    {
        // Vérifier s'il y a des transactions associées
        if ($transactionType->transactions()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer ce type car des transactions l\'utilisent',
            ], 422);
        }

        $transactionType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Type de transaction supprimé avec succès',
        ]);
    }
}
