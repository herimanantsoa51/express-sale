<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductAttribute;

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
                
                // Alertes
                'alerts' => [
                    'is_low_stock' => $totalStock <= $variant->low_stock_threshold,
                    'has_pending_reservations' => $reservedQuantity > 0,
                    'has_active_credits' => $creditQuantity > 0,
                ]
            ];
        });
        
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
                ->sum('sale_items.subtotal')
        ];
        
        return response()->json($productArray);
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
        $hasStock = $product->variants()->where('stock_quantity', '>', 0)->exists();

        if ($hasStock) {
            return response()->json(['message' => 'Impossible de supprimer un produit avec du stock'], 400);
        }

        $product->delete();
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
                DB::raw('COALESCE(SUM(product_variants.available_quantity), 0) as total_stock'),
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
                            'attributeValues.attributeType',
                            'attributeValues.attributeValue',
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
            // CORRECTION: Utiliser l'expression complète au lieu de l'alias
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

        // Filtre par attributs
        if ($request->has('attributes')) {
            $attributes = $request->attributes;
            if (is_array($attributes)) {
                foreach ($attributes as $attributeTypeId => $value) {
                    $query->whereHas('variants.attributeValues', function ($q) use ($attributeTypeId, $value) {
                        $q->where('attribute_type_id', $attributeTypeId)
                            ->whereHas('attributeValue', function ($q) use ($value) {
                                $q->where('value', $value);
                            });
                    });
                }
            }
        }

        // Filtre par plage de prix
        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $query->where('products.base_price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && is_numeric($request->max_price)) {
            $query->where('products.base_price', '<=', $request->max_price);
        }

        // Filtre par stock minimum - CORRECTION: utiliser DB::raw
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
        
        // Gestion spéciale pour le tri par stock
        if ($sortBy === 'total_stock') {
            $query->orderBy(DB::raw('COALESCE(SUM(product_variants.stock_quantity), 0)'), $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 24), 100);
        $products = $query->paginate($perPage);

        // Formatage de la réponse
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
                                'type_id' => $attributeValue->attributeType->id ?? null,
                                'type_name' => $attributeValue->attributeType->display_name ?? null,
                                'value_id' => $attributeValue->attributeValue->id ?? null,
                                'value' => $attributeValue->attributeValue->value ?? null
                            ];
                        })->toArray(),
                        'locations' => $variant->locations->map(function ($location) {
                            return [
                                'location_id' => $location->location->id ?? null,
                                'location_name' => $location->location->name ?? null,
                                'code'=>$location->location->code ?? null,
                                'quantity' => (int) $location->quantity -$location->reserved_quantity,
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
            foreach ($request->products as $prodData) {
                $product = Product::findOrFail($prodData['id']);
                $product->base_price = $prodData['base_price'];
                $product->save();
            }

            DB::commit();
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
}
