<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Helpers\ActivityLogger;
use App\Models\AttributeType;
use App\Enums\ActivityAction;

class ProductController extends Controller
{
    /**
 * Liste des produits avec filtres, tri et pagination (version minimale)
 */

public function index(Request $request)
{
    // Chargement minimum - uniquement ce qui est essentiel pour l'affichage en liste
        $query = Product::with([
            'category:id,name',  // Uniquement id et nom de la catégorie
            'subcategory:id,name', // Uniquement id et nom de la sous-catégorie
        ]);

        // Filtre par recherche textuelle (nom, description)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par catégorie
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filtre par sous-catégorie
        if ($request->has('subcategory_id') && !empty($request->subcategory_id)) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        

        // Filtre par plage de prix
        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && is_numeric($request->max_price)) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Filtre pour produits avec variantes uniquement
        if ($request->has('has_variants') && $request->has_variants) {
            $query->whereHas('variants');
        }

        if ($request->boolean('low_stock')) {
            $query->whereHas('variants', function ($q) {
                $q->whereRaw('stock_quantity <= low_stock_threshold');
            });
        }
        

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validation des colonnes de tri autorisées
        $allowedSortColumns = ['created_at', 'updated_at', 'name', 'base_price'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 20), 100);
        
        // Sélection uniquement des colonnes nécessaires pour l'affichage en liste
        $products = $query->select([
            'id',
            'name',
            'base_price',
            'image_url',
            'is_active',
            'category_id',
            'subcategory_id',
            'created_at',
            'updated_at'
        ])->paginate($perPage);

        return response()->json($products);
}
    /**
     * Créer un produit
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'attributes.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            Log::error('❌ Validation failed (store):', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $subcategoryId = $request->subcategory_id ?: null;
            $imageUrl = $request->image_url ?: null;

            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => (int) $request->category_id,
                'subcategory_id' => $subcategoryId,
                'base_price' => $request->base_price,
                'image_url' => $imageUrl,
                'is_active' => $request->get('is_active', true),
            ]);

            // Gestion des attributs (belongsToMany)
            $attributes = $request->input('attributes', []);
            if (!empty($attributes)) {
                $syncData = [];
                foreach ($attributes as $attr) {
                    $syncData[(int)$attr['attribute_type_id']] = [
                        'is_required' => $attr['is_required'] ?? true
                    ];
                }
                $product->attributeTypes()->sync($syncData);
            }

            DB::commit();
                        
            ActivityLogger::success(
                ActivityAction::PRODUCT_CREATED,
                "a créé le produit '{$product->name}' - Prix de base: " . number_format($product->base_price, 2) . " AR",
                [
                    'model_type' => 'App\Models\Product',
                    'model_id' => $product->id,
                    'metadata' => [
                        'name' => $product->name,
                        'category_id' => $product->category_id,
                        'subcategory_id' => $product->subcategory_id,
                        'base_price' => $product->base_price,
                        'has_attributes' => !empty($attributes),
                        'attributes_count' => count($attributes),
                    ]
                ],
                "produits/{$product->id}"
            );
            $product->load('category', 'subcategory', 'attributeTypes.values');
            return response()->json([
                'message' => 'Produit créé avec succès',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('🔥 Erreur création produit', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la création', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $product = Product::with([
            'category',
            'subcategory',
            'attributeTypes.values',
            'variants.variantAttributeValues.attributeValue.attributeType',
            'variants.locations'
        ])->findOrFail($id);
        
        $productArray = $product->toArray();
        
        // Transformer attributeTypes → attributes
        $productArray['attributes'] = $product->attributeTypes->map(function ($type) {
            return [
                'attribute_type_id' => $type->id,
                'is_required' => $type->pivot->is_required ?? true,
                'attribute_type' => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'display_name' => $type->display_name,
                    'input_type' => $type->input_type,
                    'values' => $type->values,
                ],
            ];
        });
        
        // Enrichir chaque variant avec ses statistiques détaillées
        $productArray['variants'] = $product->variants->map(function ($variant) {
            // 1. Calculer les quantités vendues (sales finalisées uniquement)
            $soldQuantity = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.variant_id', $variant->id)
                ->where('sales.payment_status', 'paid')
                ->whereIn('sales.sale_type', ['immediate'])
                ->sum('sale_items.quantity');
            
            // 2. Quantités en réservation (réservations non finalisées)
            $reservedQuantity = $variant->reserved_quantity;
            
            // 3. Quantités en crédit actif (crédits non payés complètement)
            $creditQuantity = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('credits', 'sales.id', '=', 'credits.sale_id')
                ->where('sale_items.variant_id', $variant->id)
                ->where('sales.sale_type', 'credit')
                ->whereIn('credits.status', ['active', 'partial_paid', 'overdue'])
                ->sum('sale_items.quantity');
            
            // 4. Quantités vendues via réservations finalisées
            $soldViaReservation = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('reservations', 'sales.id', '=', 'reservations.sale_id')
                ->where('sale_items.variant_id', $variant->id)
                ->where('sales.sale_type', 'reservation')
                ->where('sales.payment_status', 'paid')
                ->where('reservations.status', 'completed')
                ->sum('sale_items.quantity');
            
            // 5. Quantités vendues via crédits finalisés
            $soldViaCredit = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('credits', 'sales.id', '=', 'credits.sale_id')
                ->where('sale_items.variant_id', $variant->id)
                ->where('sales.sale_type', 'credit')
                ->where('credits.status', 'completed')
                ->sum('sale_items.quantity');
            
            // ✅ NOUVEAU: Calcul des bénéfices par variant
            $profitData = DB::table('sale_item_batches')
                ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
                ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
                ->where('sale_items.variant_id', $variant->id)
                ->where('sale_item_batches.status', 'sold')
                ->selectRaw('
                    SUM(sale_item_batches.quantity) as total_sold_qty,
                    SUM(sale_item_batches.quantity * sale_item_batches.unit_price_at_sale) as total_revenue,
                    SUM(sale_item_batches.quantity * sale_item_batches.discount_at_sale) as total_discount,
                    SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as total_cost
                ')
                ->first();
            
            $totalRevenue = (float) ($profitData->total_revenue ?? 0);
            $totalDiscount = (float) ($profitData->total_discount ?? 0);
            $totalCost = (float) ($profitData->total_cost ?? 0);
            $netRevenue = $totalRevenue - $totalDiscount;
            $totalProfit = $netRevenue - $totalCost;
            $profitMargin = $netRevenue > 0 ? ($totalProfit / $netRevenue) * 100 : 0;
            
            // Stock total par location
            $stockByLocation = $variant->locations->map(function($loc) {
                return [
                    'location_id' => $loc->location_id,
                    'location_name' => $loc->location->name ?? 'N/A',
                    'quantity' => $loc->quantity,
                    'notes' => $loc->notes
                ];
            });
            
            $totalStock = $variant->stock_quantity;
            $availableStock = $variant->available_quantity;
            
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price_adjustment' => $variant->price_adjustment,
                'final_price' => floatval($variant->product->base_price) + floatval($variant->price_adjustment),
                'is_active' => $variant->is_active,
                'image_path' => $variant->image_path,
                'low_stock_threshold' => $variant->low_stock_threshold,
                
                // Attributs du variant
                'attributes' => $variant->variantAttributeValues->map(function($vav) {
                    return [
                        'attribute_type_id' => $vav->attributeValue->attributeType->id,
                        'attribute_type_name' => $vav->attributeValue->attributeType->name,
                        'attribute_type_display' => $vav->attributeValue->attributeType->display_name,
                        'value_id' => $vav->attributeValue->id,
                        'value' => $vav->attributeValue->value,
                    ];
                }),
                
                // Statistiques de stock
                'stock' => [
                    'total' => $totalStock,
                    'available' => max(0, $availableStock),
                    'reserved_in_reservations' => (int) $reservedQuantity,
                    'locked_in_active_credits' => (int) $creditQuantity,
                    'by_location' => $stockByLocation
                ],
                
                // Statistiques de ventes
                'sales' => [
                    'immediate_sales' => (int) $soldQuantity,
                    'completed_reservations' => (int) $soldViaReservation,
                    'completed_credits' => (int) $soldViaCredit,
                    'total_sold' => (int) ($soldQuantity + $soldViaReservation + $soldViaCredit),
                ],
                
                // ✅ NOUVEAU: Statistiques financières
                'financials' => [
                    'total_revenue' => round($totalRevenue, 2),
                    'total_discount' => round($totalDiscount, 2),
                    'net_revenue' => round($netRevenue, 2),
                    'total_cost' => round($totalCost, 2),
                    'total_profit' => round($totalProfit, 2),
                    'profit_margin_percent' => round($profitMargin, 2),
                    'units_sold' => (int) ($profitData->total_sold_qty ?? 0),
                ],
                
                // Alertes
                'alerts' => [
                    'is_low_stock' => $totalStock <= $variant->low_stock_threshold,
                    'has_pending_reservations' => $reservedQuantity > 0,
                    'has_active_credits' => $creditQuantity > 0,
                ]
            ];
        });
        
        // ✅ NOUVEAU: Stats globales financières du produit
        $globalProfitData = DB::table('sale_item_batches')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $product->id)
            ->where('sale_item_batches.status', 'sold')
            ->selectRaw('
                SUM(sale_item_batches.quantity * sale_item_batches.unit_price_at_sale) as total_revenue,
                SUM(sale_item_batches.quantity * sale_item_batches.discount_at_sale) as total_discount,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as total_cost
            ')
            ->first();
        
        $productTotalRevenue = (float) ($globalProfitData->total_revenue ?? 0);
        $productTotalDiscount = (float) ($globalProfitData->total_discount ?? 0);
        $productTotalCost = (float) ($globalProfitData->total_cost ?? 0);
        $productNetRevenue = $productTotalRevenue - $productTotalDiscount;
        $productTotalProfit = $productNetRevenue - $productTotalCost;
        $productProfitMargin = $productNetRevenue > 0 ? ($productTotalProfit / $productNetRevenue) * 100 : 0;
        
        // Stats globales du produit
        $productArray['product_stats'] = [
            'total_variants' => $product->variants->count(),
            'active_variants' => $product->variants->where('is_active', true)->count(),
            'total_stock_all_variants' => $product->variants->sum(function($v) {
                return $v->locations->sum('quantity');
            }),
            'total_sold' => DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
                ->where('product_variants.product_id', $product->id)
                ->where('sales.payment_status', 'paid')
                ->sum('sale_items.quantity'),
            'revenue_generated' => DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
                ->where('product_variants.product_id', $product->id)
                ->where('sales.payment_status', 'paid')
                ->sum('sale_items.subtotal'),
            // ✅ NOUVEAU: Statistiques financières globales
            'total_revenue' => round($productTotalRevenue, 2),
            'total_discount' => round($productTotalDiscount, 2),
            'net_revenue' => round($productNetRevenue, 2),
            'total_cost' => round($productTotalCost, 2),
            'total_profit' => round($productTotalProfit, 2),
            'profit_margin_percent' => round($productProfitMargin, 2),
        ];
        
        return response()->json($productArray);
    }

    // ✅ NOUVEAU: Endpoint pour obtenir les batches d'un variant avec pagination
    public function getVariantBatches(Request $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('id', $variantId)
            ->where('product_id', $productId)
            ->firstOrFail();
        
        // Paramètres de pagination
        $perPage = $request->input('per_page', 10); // 10 par défaut
        $page = $request->input('page', 1);
        
        // Query de base
        $query = DB::table('stock_batches')
            ->join('stock_receipt_items', 'stock_batches.stock_receipt_item_id', '=', 'stock_receipt_items.id')
            ->join('stock_receipts', 'stock_receipt_items.stock_receipt_id', '=', 'stock_receipts.id')
            ->leftJoin('suppliers', 'stock_receipts.supplier_id', '=', 'suppliers.id')
            ->where('stock_batches.variant_id', $variantId)
            ->select([
                'stock_batches.id',
                'stock_batches.batch_number',
                'stock_batches.initial_quantity',
                'stock_batches.remaining_quantity',
                'stock_batches.reserved_quantity',
                'stock_batches.supplier_unit_cost',
                'stock_batches.freight_cost_per_unit',
                'stock_batches.other_costs_per_unit',
                'stock_batches.total_unit_cost',
                'stock_batches.received_date',
                'stock_batches.cost_status',
                'stock_batches.cost_validated_at',
                'stock_receipts.id as stock_receipt_id',
                'stock_receipts.receipt_number',
                'suppliers.name as supplier_name',
            ])
            ->orderBy('stock_batches.received_date', 'desc');
        
        // Calculer les statistiques globales AVANT la pagination
        $allBatches = $query->get();
        $totalInventoryValue = 0;
        $totalRemainingStock = 0;
        $totalAvailableStock = 0;
        
        foreach ($allBatches as $batch) {
            $availableQuantity = $batch->remaining_quantity - $batch->reserved_quantity;
            $totalRemainingStock += $batch->remaining_quantity;
            $totalAvailableStock += max(0, $availableQuantity);
            $totalInventoryValue += ($batch->remaining_quantity * $batch->total_unit_cost);
        }
        
        // Pagination
        $total = $allBatches->count();
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $paginatedBatches = $allBatches->slice($offset, $perPage)->values();
        
        // Mapper les batches paginés
        $batches = $paginatedBatches->map(function($batch) {
            $soldQuantity = $batch->initial_quantity - $batch->remaining_quantity;
            $availableQuantity = $batch->remaining_quantity - $batch->reserved_quantity;
            
            return [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'stock_receipt_id' => $batch->stock_receipt_id,
                'receipt_number' => $batch->receipt_number,
                'supplier_name' => $batch->supplier_name,
                'quantities' => [
                    'initial' => (int) $batch->initial_quantity,
                    'remaining' => (int) $batch->remaining_quantity,
                    'reserved' => (int) $batch->reserved_quantity,
                    'available' => max(0, (int) $availableQuantity),
                    'sold' => (int) $soldQuantity,
                ],
                'costs' => [
                    'supplier_unit_cost' => (float) $batch->supplier_unit_cost,
                    'freight_cost_per_unit' => (float) $batch->freight_cost_per_unit,
                    'other_costs_per_unit' => (float) $batch->other_costs_per_unit,
                    'total_unit_cost' => (float) $batch->total_unit_cost,
                    'total_batch_value' => (float) ($batch->remaining_quantity * $batch->total_unit_cost),
                ],
                'dates' => [
                    'received_date' => $batch->received_date,
                    'cost_validated_at' => $batch->cost_validated_at,
                ],
                'cost_status' => $batch->cost_status,
            ];
        });
        
        return response()->json([
            'variant_id' => $variantId,
            'variant_sku' => $variant->sku,
            'batches' => $batches,
            'summary' => [
                'total_batches' => $total,
                'total_remaining_stock' => (int) $totalRemainingStock,
                'total_available_stock' => (int) $totalAvailableStock,
                'total_inventory_value' => round($totalInventoryValue, 2),
            ],
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) $lastPage,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ]
        ]);
    }

    public function showWithVariants($id)
    {
        

        $product = Product::with([
            'category',
            'subcategory',
            'supplier',
            'variants.attributeValues.attributeValue.attributeType',
        ])->findOrFail($id);

        // Formatter les variantes pour le frontend
        $variants = $product->variants->map(function ($variant) {

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price_adjustment' => $variant->price_adjustment,
                'stock_quantity' => $variant->stock_quantity,
                'reserved_quantity' => $variant->reserved_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'image_path' => $variant->image_path,
                'is_active' => $variant->is_active,

                // 🔥 ATTRIBUTS NORMALISÉS
                'attribute_values' => $variant->attributeValues->map(function ($vav) {
                    $attrValue = $vav->attributeValue;
                    $attrType = $attrValue->attributeType;

                    return [
                        'id' => $vav->id,
                        'value' => $attrValue->value,
                        'attribute_type' => [
                            'id' => $attrType->id,
                            'name' => $attrType->name,
                            'display_name' => $attrType->display_name,
                            'input_type' => $attrType->input_type,
                        ]
                    ];
                })->values()
            ];
        });

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => $product->base_price,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
            'created_at' => $product->created_at,

            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'supplier' => $product->supplier,

            // ⭐️ IMPORTANT
            'variants' => $variants,
        ]);


    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'base_price' => 'numeric|min:0',
            'is_active' => 'boolean',
            'image_url' => 'nullable|string',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'attributes.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Avant $product->update($data), sauvegarder les anciennes valeurs
        $oldValues = [
            'name' => $product->name,
            'base_price' => $product->base_price,
            'category_id' => $product->category_id,
            'subcategory_id' => $product->subcategory_id,
            'is_active' => $product->is_active,
        ];
        DB::beginTransaction();
        try {
            $data = $request->only(['name','description','base_price','is_active']);

            if ($request->has('category_id')) $data['category_id'] = (int)$request->category_id;
            if ($request->has('subcategory_id')) $data['subcategory_id'] = $request->subcategory_id ?: null;
            if ($request->has('image_url')) $data['image_url'] = $request->image_url ?: null;

            $product->update($data);

            if ($request->has('attributes')) {
                $attributes = $request->input('attributes', []);
                $syncData = [];
                foreach ($attributes as $attr) {
                    $syncData[(int)$attr['attribute_type_id']] = [
                        'is_required' => $attr['is_required'] ?? true
                    ];
                }
                $product->attributeTypes()->sync($syncData);
            }

            DB::commit();

            $changes = [];
            if (isset($data['name']) && $data['name'] !== $oldValues['name']) {
                $changes[] = "nom: '{$oldValues['name']}' → '{$data['name']}'";
            }
            if (isset($data['base_price']) && $data['base_price'] !== $oldValues['base_price']) {
                $changes[] = "prix: " . number_format($oldValues['base_price'], 2) . " → " . number_format($data['base_price'], 2) . " AR";
            }
            if (isset($data['category_id']) && $data['category_id'] !== $oldValues['category_id']) {
                $changes[] = "catégorie modifiée";
            }
            if (isset($data['is_active']) && $data['is_active'] !== $oldValues['is_active']) {
                $status = $data['is_active'] ? 'activé' : 'désactivé';
                $changes[] = "statut: {$status}";
            }

            $description = count($changes) > 0 
                ? "a modifié le produit '{$product->name}' - " . implode(', ', $changes)
                : "a modifié le produit '{$product->name}'";

            ActivityLogger::success(
                ActivityAction::PRODUCT_UPDATED,
                $description,
                [
                    'model_type' => 'App\Models\Product',
                    'model_id' => $product->id,
                    'metadata' => [
                        'old_values' => $oldValues,
                        'new_values' => $product->only(['name', 'base_price', 'category_id', 'subcategory_id', 'is_active']),
                        'changes' => $changes,
                        'attributes_updated' => $request->has('attributes'),
                    ]
                ],
                "produits/{$product->id}"
            );
            $product->load('category', 'subcategory', 'attributeTypes.values');
            return response()->json([
                'message' => 'Produit mis à jour avec succès',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('🔥 Erreur mise à jour produit', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un produit
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        // Avant $product->delete(), vérifier le stock
            $hasStock = $product->variants()->where('stock_quantity', '>', 0)->exists();

            if ($hasStock) {
                // ⚠️ Log tentative échouée
                ActivityLogger::failed(
                    ActivityAction::PRODUCT_DELETED,
                    "a tenté de supprimer le produit '{$product->name}' ayant du stock",
                    [
                        'model_type' => 'App\Models\Product',
                        'model_id' => $product->id,
                        'metadata' => [
                            'reason' => 'has_stock',
                            'variants_count' => $product->variants()->count(),
                        ]
                    ]
                );
                
                return response()->json(['message' => 'Impossible de supprimer un produit avec du stock'], 400);
            }

            // Sauvegarder les données avant suppression
            $productData = [
                'id' => $product->id,
                'name' => $product->name,
                'base_price' => $product->base_price,
                'category_id' => $product->category_id,
                'variants_count' => $product->variants()->count(),
            ];

        $product->delete();
        ActivityLogger::success(
            ActivityAction::PRODUCT_DELETED,
            "a supprimé le produit '{$productData['name']}'",
            [
                'model_type' => 'App\Models\Product',
                'model_id' => $productData['id'],
                'metadata' => [
                    'deleted_data' => $productData,
                ]
            ],
            'produits'
        );
        return response()->json(['message' => 'Produit supprimé'], 200);
    }

    /**
     * Récupérer les types d'attributs pour un produit
     */
    public function getProductAttributeTypes($productId)
    {
        $attributes = ProductAttribute::where('product_id', $productId)
            ->with('attributeType.values')
            ->get()
            ->map(function($attr) {
                $type = $attr->attributeType;
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'display_name' => $type->display_name,
                    'input_type' => $type->input_type,
                    'is_required' => $attr->is_required, // ✅ Inclure is_required
                    'values' => $type->values
                ];
            });

        return response()->json($attributes);
    }

    /**
     * Statistiques produit
     */
    public function statistics($id)
    {
        $product = Product::findOrFail($id);
        $stats = [
            'total_variants' => $product->variants()->count(),
            'total_stock' => $product->variants()->sum('stock_quantity'),
            'available_stock' => $product->variants()
                ->selectRaw('SUM(stock_quantity - reserved_quantity) as available')
                ->value('available'),
            'low_stock_variants' => $product->variants()
                ->whereRaw('stock_quantity <= low_stock_threshold')
                ->count(),
        ];
        return response()->json($stats);
    }


    public function getProductsForSale(Request $request)
{
    $query = Product::query()
        ->select([
            'products.id',
            'products.name',
            'products.description',
            'products.base_price',
            'products.image_url',
            'products.category_id',
            'products.subcategory_id',
            'products.is_active',
            'categories.name as category_name',
            'subcategories.name as subcategory_name',
            DB::raw('COALESCE(SUM(product_variants.stock_quantity), 0) as total_stock'),
            DB::raw('COUNT(DISTINCT product_variants.id) as variants_count')
        ])
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->leftJoin('categories as subcategories', 'products.subcategory_id', '=', 'subcategories.id')
        ->leftJoin('product_variants', function ($join) {
            $join->on('products.id', '=', 'product_variants.product_id')
                ->where('product_variants.is_active', true)
                ->where('product_variants.stock_quantity', '>', 0);
        })
        ->with([
            'variants' => function ($query) {
                $query->where('is_active', true)
                    ->where('stock_quantity', '>', 0)
                    ->with([
                        'attributeValues.attributeValue.attributeType',
                        'locations.location'
                    ])
                    ->select([
                        'id',
                        'product_id',
                        'sku',
                        'stock_quantity',
                        'image_path',
                        'is_active'
                    ]);
            },
            'category:id,name',
            'subcategory:id,name',
            'attributeTypes.values'
        ])
        ->where('products.is_active', true)
        ->groupBy(
            'products.id',
            'products.name',
            'products.description',
            'products.base_price',
            'products.image_url',
            'products.category_id',
            'products.subcategory_id',
            'products.is_active',
            'categories.name',
            'subcategories.name'
        )
        ->having(DB::raw('COALESCE(SUM(product_variants.stock_quantity), 0)'), '>', 0);

    // Recherche textuelle
    if ($request->has('search') && !empty($request->search)) {
        $searchTerm = '%' . $request->search . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where('products.name', 'ILIKE', $searchTerm)
                ->orWhere('products.description', 'ILIKE', $searchTerm)
                ->orWhereHas('variants', function ($q) use ($searchTerm) {
                    $q->where('sku', 'ILIKE', $searchTerm);
                });
        });
    }

    // Filtre par catégorie
    if ($request->has('category_id') && !empty($request->category_id)) {
        $query->where('products.category_id', $request->category_id);
    }

    // Filtre par sous-catégorie
    if ($request->has('subcategory_id') && !empty($request->subcategory_id)) {
        $query->where('products.subcategory_id', $request->subcategory_id);
    }

    // ✅ FILTRE PAR ATTRIBUTS - VERSION OPTIMISÉE AVEC JOIN
    if ($request->has('attributes')) {
        try {
            $attributesInput = $request->input('attributes');
            $attributes = null;

            if (is_string($attributesInput)) {
                $decoded = json_decode($attributesInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $attributes = $decoded;
                }
            } elseif (is_array($attributesInput)) {
                $attributes = $attributesInput;
            }

            if (is_array($attributes) && !empty($attributes)) {
                foreach ($attributes as $attributeTypeId => $value) {
                    // ✅ OPTIMISATION : Utiliser JOIN au lieu de whereHas
                    $query->whereExists(function ($subQuery) use ($attributeTypeId, $value) {
                        $subQuery->select(DB::raw(1))
                            ->from('product_variants as pv')
                            ->join('variant_attribute_values as vav', 'vav.variant_id', '=', 'pv.id')
                            ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                            ->whereColumn('pv.product_id', 'products.id')
                            ->where('pv.is_active', true)
                            ->where('pv.stock_quantity', '>', 0)
                            ->where('av.attribute_type_id', $attributeTypeId)
                            ->whereRaw('LOWER(av.value) = LOWER(?)', [$value]);
                    });
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Erreur traitement attributs:', [
                'error' => $e->getMessage(),
                'input' => $request->input('attributes')
            ]);
        }
    }

    // Reste de la requête...
    if ($request->has('min_price') && is_numeric($request->min_price)) {
        $query->where('products.base_price', '>=', $request->min_price);
    }
    if ($request->has('max_price') && is_numeric($request->max_price)) {
        $query->where('products.base_price', '<=', $request->max_price);
    }

    if ($request->has('min_stock') && is_numeric($request->min_stock)) {
        $query->having(DB::raw('COALESCE(SUM(product_variants.stock_quantity), 0)'), '>=', $request->min_stock);
    }

    // Tri
    $sortBy = $request->get('sort_by', 'name');
    $sortOrder = $request->get('sort_order', 'asc');
    
    $allowedSortColumns = ['name', 'base_price', 'created_at'];
    
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'name';
    }
    
    if ($sortBy === 'total_stock') {
        $query->orderBy(DB::raw('COALESCE(SUM(product_variants.stock_quantity), 0)'), $sortOrder);
    } else {
        $query->orderBy($sortBy, $sortOrder);
    }
    
    $perPage = min($request->get('per_page', 24), 100);
    $products = $query->paginate($perPage);

    // Formatage identique...
    $formattedProducts = $products->getCollection()->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => (float) $product->base_price,
            'image_url' => $product->image_url,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name
            ] : null,
            'subcategory' => $product->subcategory ? [
                'id' => $product->subcategory->id,
                'name' => $product->subcategory->name
            ] : null,
            'total_stock' => (int) $product->total_stock,
            'variants_count' => (int) $product->variants_count,
            'variants' => $product->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'stock_quantity' => (int) $variant->stock_quantity,
                    'image_path' => $variant->image_path,
                    'attributes' => $variant->attributeValues->map(function ($attributeValue) {
                        return [
                            'type_id' => $attributeValue->attributeValue->attributeType->id ?? null,
                            'type_name' => $attributeValue->attributeValue->attributeType->display_name ?? null,
                            'value_id' => $attributeValue->attributeValue->id ?? null,
                            'value' => $attributeValue->attributeValue->value ?? null
                        ];
                    })->toArray(),
                    'locations' => $variant->locations->map(function ($location) {
                        return [
                            'location_id' => $location->location->id ?? null,
                            'location_name' => $location->location->name ?? null,
                            'code' => $location->location->code ?? null,
                            'quantity' => (int) $location->quantity - $location->reserved_quantity,
                            'full_path' => $location->location->getFullPath() ?? null
                        ];
                    })->toArray()
                ];
            })->toArray(),
            'attribute_types' => $product->attributeTypes->map(function ($attributeType) {
                return [
                    'id' => $attributeType->id,
                    'name' => $attributeType->name,
                    'display_name' => $attributeType->display_name,
                    'values' => $attributeType->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value
                        ];
                    })->toArray()
                ];
            })->toArray()
        ];
    });

    return response()->json([
        'data' => $formattedProducts,
        'pagination' => [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'from' => $products->firstItem(),
            'to' => $products->lastItem()
        ]
    ]);
}

    public function updateBasePrices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.base_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $priceChanges = [];

            foreach ($request->products as $prodData) {
                $product = Product::findOrFail($prodData['id']);
                $oldPrice = $product->base_price;
                $newPrice = $prodData['base_price'];
                
                if ($oldPrice != $newPrice) {
                    $priceChanges[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                    ];
                }
                
                $product->base_price = $newPrice;
                $product->save();
            }


            DB::commit();
            ActivityLogger::success(
                ActivityAction::PRODUCT_PRICES_UPDATED,
                "a mis à jour les prix de base de". count($priceChanges) ." produit(s)",
                [
                    'metadata' => [
                        'products_count' => count($request->products),
                        'changes_count' => count($priceChanges),
                        'price_changes' => $priceChanges,
                    ]
                ],
                'products'
            );
            return response()->json(['message' => 'Prix de base mis à jour avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('🔥 Erreur mise à jour prix de base produit', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la mise à jour des prix', 'error' => $e->getMessage()], 500);
        }
    }
    public function getAvailableAttributes(Request $request)
    {
        // Récupérer tous les attributs utilisés par les produits actifs
        $attributes = AttributeType::with(['values' => function($query) {
            $query->whereHas('variants', function($q) {
                $q->where('is_active', true)
                    ->where('stock_quantity', '>', 0)
                    ->whereHas('product', function($productQuery) {
                        $productQuery->where('is_active', true);
                    });
            });
        }])
        ->whereHas('values.variants', function($query) {
            $query->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->whereHas('product', function($productQuery) {
                    $productQuery->where('is_active', true);
                });
        })
        ->get()
        ->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'display_name' => $attribute->display_name,
                'values' => $attribute->values->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'product_count' => $value->variants()
                            ->where('is_active', true)
                            ->where('stock_quantity', '>', 0)
                            ->whereHas('product', function($q) {
                                $q->where('is_active', true);
                            })
                            ->distinct('product_id')
                            ->count('product_id')
                    ];
                })->toArray()
            ];
        });

        return response()->json($attributes);
    }
}
