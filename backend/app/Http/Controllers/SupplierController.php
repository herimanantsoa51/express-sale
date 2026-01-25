<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\StockBatch;
use App\Models\SaleItemBatch;
use App\Models\StockReceiptItemRating;
use App\Models\StockReceipt;
use Illuminate\Http\JsonResponse;

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
    // Dans SupplierController.php
        /**
         * Obtenir les réceptions de stock d'un fournisseur avec pagination
         * GET /api/suppliers/{id}/stock-receipts
         */
        public function getStockReceipts($id, Request $request): JsonResponse
        {
            $perPage = $request->input('per_page', 15);
            
            $receipts = StockReceipt::where('supplier_id', $id)
                ->select(['id', 'receipt_number', 'status', 'total_cost_ariary', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $receipts->items(),
                'meta' => [
                    'current_page' => $receipts->currentPage(),
                    'last_page' => $receipts->lastPage(),
                    'per_page' => $receipts->perPage(),
                    'total' => $receipts->total(),
                ]
            ]);
        }

    /**
     * GET /api/suppliers/{id}
     */
            public function show($id)
        {
            $supplier = Supplier::with('coordinate')->findOrFail($id);
            
            // Calculer les statistiques
            $statistics = $this->calculateSupplierStatistics($supplier);
            
            return response()->json([
                'status' => 'success',
                'data' => $supplier,
                'statistics' => $statistics
            ]);
        }

        /**
         * Calculer les statistiques complètes du fournisseur
         */
        private function calculateSupplierStatistics(Supplier $supplier): array
        {
            // 1. Total dépensé (basé sur total_cost_ariary des réceptions validées)
            $totalSpent = $supplier->stockReceipts()
                ->whereIn('status', ['validated', 'cost_allocated'])
                ->sum('total_cost_ariary');
            
            // 2. Performance des produits (bénéfices réalisés)
            $profitData = $this->calculateSupplierProfits($supplier);
            
            // 3. Conformité par attribut
            $conformityByAttribute = $this->calculateConformityByAttribute($supplier);
            
            // 4. Statistiques générales
            $totalReceipts = $supplier->stockReceipts()->count();
            $totalProducts = $supplier->stockReceiptItems()
                ->distinct('variant_id')
                ->count('variant_id');
            
            return [
                'total_spent' => round($totalSpent, 2),
                'total_receipts' => $totalReceipts,
                'total_products' => $totalProducts,
                'profit_data' => $profitData,
                'conformity_by_attribute' => $conformityByAttribute,
            ];
        }

        /**
         * Calculer les bénéfices générés par les produits du fournisseur
         */
        private function calculateSupplierProfits(Supplier $supplier): array
        {
            // Récupérer tous les batches liés à ce fournisseur
            $batches = StockBatch::whereHas('stockReceiptItem.stockReceipt', function($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })->get();
            
            $totalProfit = 0;
            $totalRevenue = 0;
            $totalUnitsSold = 0;
            
            foreach ($batches as $batch) {
                // Récupérer tous les sale_item_batches vendus (status = 'sold')
                $soldBatches = SaleItemBatch::where('batch_id', $batch->id)
                    ->where('status', 'sold')
                    ->get();
                
                foreach ($soldBatches as $soldBatch) {
                    $quantity = $soldBatch->quantity;
                    $totalUnitsSold += $quantity;
                    
                    // Prix de vente par unité (après réduction)
                    $unitPriceAfterDiscount = $soldBatch->unit_price_at_sale - $soldBatch->discount_at_sale;
                    
                    // Coût total par unité (fournisseur + fret + autres)
                    $unitCost = $batch->supplier_unit_cost + 
                            $batch->freight_cost_per_unit + 
                            $batch->other_costs_per_unit;
                    
                    // Profit par unité
                    $unitProfit = $unitPriceAfterDiscount - $unitCost;
                    
                    // Totaux
                    $totalRevenue += $unitPriceAfterDiscount * $quantity;
                    $totalProfit += $unitProfit * $quantity;
                }
            }
            
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
            
            return [
                'total_profit' => round($totalProfit, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_units_sold' => $totalUnitsSold,
                'profit_margin_percent' => round($profitMargin, 2),
            ];
        }

        /**
         * Calculer la conformité par type d'attribut
         * (conformity_rate = moyenne des notes /10 → %)
         */
        private function calculateConformityByAttribute(Supplier $supplier): array
        {
            $ratings = StockReceiptItemRating::whereHas(
                'stockReceiptItem.stockReceipt',
                function ($q) use ($supplier) {
                    $q->where('supplier_id', $supplier->id);
                }
            )
            ->with('attributeType')
            ->get();

            $conformityByAttr = [];

            foreach ($ratings as $rating) {
                $attrId   = $rating->attribute_type_id;
                $attrName = $rating->attributeType->display_name;

                if (!isset($conformityByAttr[$attrId])) {
                    $conformityByAttr[$attrId] = [
                        'attribute_id'     => $attrId,
                        'attribute_name'   => $attrName,
                        'total_ratings'    => 0,
                        'conforming_count' => 0, // conservé
                        'conformity_rate'  => 0, // pourcentage
                        '_total_score'     => 0, // interne
                    ];
                }

                $conformityByAttr[$attrId]['total_ratings']++;
                $conformityByAttr[$attrId]['_total_score'] += $rating->conformity_rating;

                if ($rating->isConforming()) {
                    $conformityByAttr[$attrId]['conforming_count']++;
                }
            }

            // Calcul du pourcentage à partir de la moyenne (/10)
            foreach ($conformityByAttr as &$attr) {
                if ($attr['total_ratings'] > 0) {
                    $average = $attr['_total_score'] / $attr['total_ratings'];

                    $attr['conformity_rate'] = round(
                        $average * 10, // note sur 10 → %
                        2
                    );
                }

                unset($attr['_total_score']);
            }

            return array_values($conformityByAttr);
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
