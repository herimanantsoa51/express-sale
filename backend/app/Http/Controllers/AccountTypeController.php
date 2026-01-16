<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Http\Requests\StoreAccountTypeRequest;
use App\Http\Requests\UpdateAccountTypeRequest;
use App\Http\Resources\AccountTypeResource;
use App\Http\Resources\AccountTypeCollection;
use Illuminate\Http\JsonResponse;

/**
 * Controller pour les types de comptes
 */
class AccountTypeController extends Controller
{
    public function index(): AccountTypeCollection
    {
        $types = AccountType::all();
        return new AccountTypeCollection($types);
    }

    public function store(StoreAccountTypeRequest $request): JsonResponse
    {
        $type = AccountType::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Type de compte créé avec succès',
            'data' => new AccountTypeResource($type)
        ], 201);
    }

    public function show(AccountType $accountType): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new AccountTypeResource($accountType)
        ]);
    }

    public function update(UpdateAccountTypeRequest $request, AccountType $accountType): JsonResponse
    {
        $accountType->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Type de compte mis à jour avec succès',
            'data' => new AccountTypeResource($accountType)
        ]);
    }

    public function destroy(AccountType $accountType): JsonResponse
    {
        // Vérifier s'il y a des comptes associés
        if ($accountType->accounts()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer ce type car des comptes l\'utilisent'
            ], 422);
        }

        $accountType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Type de compte supprimé avec succès'
        ]);
    }
}

