<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * GET /api/suppliers
     * Liste des fournisseurs avec filtres, recherche et tri
     */
    public function index(Request $request)
    {
        $query = Supplier::with('coordinate');

        // Filtrer par statut : active / inactive / all
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filtrer par pays via la relation coordinate
        if ($request->has('country') && $request->country !== '') {
            $query->whereHas('coordinate', function($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        // Recherche globale
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('contact', 'ILIKE', "%{$search}%")
                  ->orWhere('wechat', 'ILIKE', "%{$search}%")
                  ->orWhereHas('coordinate', function($q2) use ($search) {
                      $q2->where('city', 'ILIKE', "%{$search}%")
                         ->orWhere('country', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortBy = $request->get('sortBy', 'name'); // name, reliability, date
        $sortOrder = $request->get('sortOrder', 'asc'); // asc / desc

        switch ($sortBy) {
            case 'reliability':
                $query->orderBy('reliability_score', $sortOrder);
                break;
            case 'date':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }

        $suppliers = $query->get();

        return response()->json($suppliers);
    }

    /**
     * POST /api/suppliers
     * Création fournisseur
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'wechat' => 'nullable|string|max:100',
            'profile' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'accessibility_notes' => 'nullable|string',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'coordinate_id' => 'nullable|exists:coordinates,id',
        ]);

        $supplier = Supplier::create($data);

        return response()->json($supplier->load('coordinate'), 201);
    }

    /**
     * GET /api/suppliers/{id}
     */
    public function show($id)
    {
        $supplier = Supplier::with('coordinate')->findOrFail($id);
        return response()->json($supplier);
    }

    /**
     * PUT /api/suppliers/{id}
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->ignore($supplier->id)
            ],
            'wechat' => 'nullable|string|max:100',
            'profile' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'accessibility_notes' => 'nullable|string',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'coordinate_id' => 'nullable|exists:coordinates,id',
        ]);

        $supplier->update($data);

        return response()->json($supplier->load('coordinate'));
    }

    /**
     * DELETE /api/suppliers/{id}
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        // Sécurité métier : fournisseur utilisé
        if ($supplier->products()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : fournisseur lié à des produits'
            ], 409);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Fournisseur supprimé'
        ]);
    }

    /**
     * GET /api/suppliers/{id}/statistics
     * Statistiques d'un fournisseur
     */
    public function statistics($id)
    {
        $supplier = 0;

        // Total produits
        $totalProducts =  0;
        // Total réceptions
        $totalReceipts = 0;

        // Total dépensé (somme des items en Ariary)
        $totalSpent = 0;

        // Date dernière réception
        $lastReceiptDate = null;

        return response()->json([
            'total_products' => $totalProducts,
            'total_receipts' => $totalReceipts,
            'total_spent' => $totalSpent,
            'last_receipt_date' => $lastReceiptDate ? $lastReceiptDate->format('Y-m-d') : null,
        ]);
    }

}
