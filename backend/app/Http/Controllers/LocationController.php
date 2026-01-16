<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\StockMovement;
use App\Models\ProductVariantLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Liste toutes les locations avec statistiques
     * GET /api/locations
     */
    public function index(Request $request)
    {
        $query = Location::query();

        // Filtre par statut actif
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filtre par entrepôt
        if ($request->has('warehouse')) {
            $query->where('warehouse', $request->warehouse);
        }

        // Recherche textuelle
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('warehouse', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $locations = $query->orderBy('name')->get();

        // Ajouter les statistiques pour chaque location
        $locations->each(function ($location) {
            $location->statistics = $this->getLocationStats($location);
        });

        return response()->json($locations);
    }

    /**
     * Afficher une location avec tous ses détails
     * GET /api/locations/{id}
     */
    public function show($id)
    {
        $location = Location::findOrFail($id);

        // Charger les variantes avec leurs produits
        $location->load(['variantLocations.variant.product']);

        // Ajouter les statistiques détaillées
        $location->statistics = $this->getLocationStats($location);

        return response()->json($location);
    }

    /**
     * Obtenir les variantes d'une location avec pagination
     * GET /api/locations/{id}/variants-detail
     */
    public function getVariantsDetail($id, Request $request)
    {
        $location = Location::findOrFail($id);

        $query = ProductVariantLocation::with([
            'variant.product', 
            'variant.attributeValues.attributeValue.attributeType'
        ])
            ->where('location_id', $id);

        // Filtre par stock
        if ($request->has('has_stock')) {
            if ($request->boolean('has_stock')) {
                $query->where('quantity', '>', 0);
            } else {
                $query->where('quantity', '=', 0);
            }
        }

        // Recherche par produit
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('variant.product', function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $variants = $query->orderBy('quantity', 'desc')->paginate($perPage);

        return response()->json($variants);
    }

    /**
     * Obtenir les mouvements de stock d'une location
     * GET /api/locations/{id}/stock-movements
     */
    public function getStockMovements($id, Request $request)
    {
        $location = Location::findOrFail($id);

        $query = StockMovement::with([
            'variant.product',
            'fromLocation',
            'toLocation',
            'performedBy',
            'sale',
            'stockReceipt'
        ])
        ->where(function ($q) use ($id) {
            $q->where('from_location_id', $id)
              ->orWhere('to_location_id', $id);
        })
        ->orderBy('created_at', 'desc');

        // Filtre par type de mouvement
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filtre par période
        if ($request->has('days')) {
            $query->where('created_at', '>=', now()->subDays($request->days));
        }

        // Filtre par date spécifique
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 20);
        $movements = $query->paginate($perPage);

        return response()->json($movements);
    }

    /**
     * Obtenir les statistiques détaillées d'une location
     * GET /api/locations/{id}/statistics
     */
    public function statistics($id)
    {
        $location = Location::findOrFail($id);

        $stats = $this->getLocationStats($location);

        // Ajouter des statistiques supplémentaires
        $stats['movements_by_type'] = StockMovement::where(function ($q) use ($id) {
            $q->where('from_location_id', $id)
              ->orWhere('to_location_id', $id);
        })
        ->select('movement_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_quantity'))
        ->groupBy('movement_type')
        ->get();

        // Mouvements des 30 derniers jours
        $stats['recent_movements_count'] = StockMovement::where(function ($q) use ($id) {
            $q->where('from_location_id', $id)
              ->orWhere('to_location_id', $id);
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->count();

        // Top 5 produits par quantité
        $stats['top_products'] = ProductVariantLocation::with(['variant.product'])
            ->where('location_id', $id)
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($pvl) {
                return [
                    'product_name' => $pvl->variant->product->name ?? 'N/A',
                    'variant_sku' => $pvl->variant->sku ?? 'N/A',
                    'quantity' => $pvl->quantity
                ];
            });

        return response()->json($stats);
    }

    /**
     * Créer une nouvelle location
     * POST /api/locations
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:locations,code',
            'warehouse' => 'required|string|max:255',
            'aisle' => 'nullable|string|max:100',
            'shelf' => 'nullable|string|max:100',
            'bin' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $location = Location::create($data);
        $location->statistics = $this->getLocationStats($location);

        return response()->json($location, 201);
    }

    /**
     * Mettre à jour une location
     * PUT /api/locations/{id}
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:100|unique:locations,code,' . $location->id,
            'warehouse' => 'sometimes|required|string|max:255',
            'aisle' => 'nullable|string|max:100',
            'shelf' => 'nullable|string|max:100',
            'bin' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $location->update($data);
        $location->statistics = $this->getLocationStats($location);

        return response()->json($location);
    }

    /**
     * Vérifier si une location peut être supprimée
     * GET /api/locations/{id}/can-delete
     */
    public function canDelete($id)
    {
        $location = Location::findOrFail($id);

        // Vérifier s'il y a du stock
        $hasStock = ProductVariantLocation::where('location_id', $id)
            ->where('quantity', '>', 0)
            ->exists();

        if ($hasStock) {
            return response()->json([
                'can_delete' => false,
                'reason' => 'Cette location contient encore du stock. Transférez ou ajustez le stock avant de supprimer.'
            ]);
        }

        // Vérifier s'il y a des mouvements
        $hasMovements = StockMovement::where('from_location_id', $id)
            ->orWhere('to_location_id', $id)
            ->exists();

        if ($hasMovements) {
            return response()->json([
                'can_delete' => false,
                'reason' => 'Cette location a un historique de mouvements de stock. Elle ne peut pas être supprimée mais peut être désactivée.'
            ]);
        }

        return response()->json([
            'can_delete' => true,
            'reason' => null
        ]);
    }

    /**
     * Supprimer une location
     * DELETE /api/locations/{id}
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        // Vérifier si on peut supprimer
        $canDeleteCheck = $this->canDelete($id)->getData();

        if (!$canDeleteCheck->can_delete) {
            return response()->json([
                'message' => $canDeleteCheck->reason,
                'error' => 'cannot_delete'
            ], 422);
        }

        $location->delete();

        return response()->json(null, 204);
    }

    /**
     * Obtenir les locations actives (pour les sélecteurs)
     * GET /api/locations/active
     */
    public function activeLocations()
    {
        $locations = Location::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    /**
     * Obtenir la liste des entrepôts distincts
     * GET /api/locations/warehouses
     */
    public function warehouses()
    {
        $warehouses = Location::select('warehouse')
            ->distinct()
            ->orderBy('warehouse')
            ->pluck('warehouse');

        return response()->json($warehouses);
    }

    /**
     * Obtenir les réservations actives pour une location
     * GET /api/locations/{id}/reservations
     */
    public function getActiveReservations($id, Request $request)
    {
        $location = Location::findOrFail($id);

        // Récupérer UNIQUEMENT les variantes réservées dans CETTE location
        $query = DB::table('stock_movements as sm')
            ->join('product_variants as pv', 'sm.variant_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->join('sales', 'sm.sale_id', '=', 'sales.id')
            ->join('reservations as r', 'sales.id', '=', 'r.sale_id')
            ->join('customers as c', 'r.customer_id', '=', 'c.id')
            ->where('sm.from_location_id', $location->id)
            ->where('sm.movement_type', 'reservation')
            ->whereIn('r.status', ['pending', 'confirmed', 'partial_paid'])
            ->where('r.expiry_date', '>', now())
            ->select(
                'sm.id as movement_id',
                'sm.variant_id',
                'p.name as product_name',
                'p.image_url as product_image',
                'pv.sku',
                'sm.quantity as reserved_quantity',
                'r.id as reservation_id',
                'sales.sale_number',
                'c.name as customer_name',
                'c.phone as customer_phone',
                'r.reservation_date',
                'r.expiry_date',
                'r.status',
                DB::raw('EXTRACT(DAY FROM (r.expiry_date - NOW())) as days_remaining')
            )
            ->orderBy('r.expiry_date');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $items = $query->offset($offset)->limit($perPage)->get();

        // Formater les résultats
        $reservedVariants = $items->map(function ($item) {
            return [
                'variant_id' => $item->variant_id,
                'product_name' => $item->product_name,
                'product_image' => $item->product_image,
                'sku' => $item->sku,
                'reserved_quantity' => (int) $item->reserved_quantity,
                'reservation' => [
                    'id' => $item->reservation_id,
                    'sale_number' => $item->sale_number,
                    'status' => $item->status,
                    'reservation_date' => $item->reservation_date,
                    'expiry_date' => $item->expiry_date,
                    'days_remaining' => (int) $item->days_remaining,
                ],
                'customer' => [
                    'name' => $item->customer_name,
                    'phone' => $item->customer_phone,
                ],
            ];
        });

        // Calculer le total réservé dans cette location
        $totalReservedInLocation = DB::table('stock_movements as sm')
            ->join('reservations as r', 'sm.sale_id', '=', DB::raw('(SELECT id FROM sales WHERE id = r.sale_id)'))
            ->where('sm.from_location_id', $location->id)
            ->where('sm.movement_type', 'reservation')
            ->whereIn('r.status', ['pending', 'confirmed', 'partial_paid'])
            ->where('r.expiry_date', '>', now())
            ->sum('sm.quantity');

        return response()->json([
            'data' => $reservedVariants,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
            ],
            'total_reserved_quantity' => (int) $totalReservedInLocation,
        ]);
    }

    /**
     * Calculer les statistiques d'une location
     */
    private function getLocationStats(Location $location): array
    {
        $variantLocations = ProductVariantLocation::where('location_id', $location->id);

        $totalQuantity = (clone $variantLocations)->sum('quantity');
        $variantCount = (clone $variantLocations)->count();
        $variantsWithStock = (clone $variantLocations)->where('quantity', '>', 0)->count();

        $movementCount = StockMovement::where('from_location_id', $location->id)
            ->orWhere('to_location_id', $location->id)
            ->count();

        // Calculer le stock réservé (réservations actives)
        $reservedStock = DB::table('stock_movements as sm')
            ->join('sales', 'sm.sale_id', '=', 'sales.id')
            ->join('reservations', 'sales.id', '=', 'reservations.sale_id')
            ->where('sm.from_location_id', $location->id)
            ->where('sm.movement_type', 'reservation')
            ->whereIn('reservations.status', ['pending', 'confirmed', 'partial_paid'])
            ->where('reservations.expiry_date', '>', now())
            ->sum('sm.quantity');

        $occupancyPercentage = null;
        if ($location->capacity && $location->capacity > 0) {
            $occupancyPercentage = round(($totalQuantity / $location->capacity) * 100, 2);
        }

        return [
            'total_quantity' => $totalQuantity,
            'variant_count' => $variantCount,
            'variants_with_stock' => $variantsWithStock,
            'reserved_quantity' => (int) $reservedStock,
            'available_quantity' => $totalQuantity, // Le stock disponible est déjà diminué lors de la réservation
            'capacity' => $location->capacity,
            'occupancy_percentage' => $occupancyPercentage,
            'is_full' => $location->capacity ? $totalQuantity >= $location->capacity : false,
            'movement_count' => $movementCount
        ];
    }
}
