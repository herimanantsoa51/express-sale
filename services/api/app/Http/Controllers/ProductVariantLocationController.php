<?php

namespace App\Http\Controllers;

use App\Models\ProductVariantLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVariantLocationController extends Controller
{
    /**
     * Afficher toutes les localisations de variantes
     * GET /api/product-variant-locations
     */
    public function index()
    {
        $variantLocations = ProductVariantLocation::with([
            'variant.product',
            'location',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json($variantLocations);
    }

    /**
     * Afficher une localisation spécifique
     * GET /api/product-variant-locations/{id}
     */
    public function show($id)
    {
        $variantLocation = ProductVariantLocation::with([
            'variant.product',
            'location',
        ])->findOrFail($id);

        return response()->json($variantLocation);
    }

    /**
     * Obtenir toutes les variantes dans une location (avec pagination et optimisation)
     * GET /api/locations/{locationId}/variants
     */
    public function getByLocation(Request $request, $locationId)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search', '');
        $availableOnly = $request->boolean('available_only', false);

        $query = ProductVariantLocation::with([
            'variant' => function ($q) {
                // NB : plus de reserved_quantity (colonne fantôme jamais alimentée) —
                // le stock résiduel est déjà net des réservations/crédits.
                $q->select('id', 'product_id', 'sku', 'stock_quantity')
                    ->with('product:id,name,base_price,image_url');
            },
        ])
            ->where('location_id', $locationId)
            ->select('id', 'variant_id', 'location_id', 'quantity');

        // Filtrer uniquement les variants avec stock disponible
        if ($availableOnly) {
            $query->where('quantity', '>', 0);
        }

        // Recherche par nom de produit ou SKU
        if ($search) {
            $query->whereHas('variant', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $result = $query->orderBy('quantity', 'desc')
            ->paginate($perPage);

        // Quantité réellement réservée par variante dans cette location,
        // via les batches de réservations actives (source de vérité unique).
        $variantIds = $result->getCollection()->pluck('variant_id')->unique();

        $reservedByVariant = DB::table('reservation_item_batches as rib')
            ->join('reservation_items as ri', 'rib.reservation_item_id', '=', 'ri.id')
            ->join('reservations as r', 'ri.reservation_id', '=', 'r.id')
            ->where('ri.location_id', $locationId)
            ->where('rib.status', 'reserved')
            ->whereIn('r.status', ['pending', 'confirmed', 'partial_paid'])
            ->where('r.expiry_date', '>', now())
            ->whereIn('ri.variant_id', $variantIds)
            ->selectRaw('ri.variant_id, SUM(rib.quantity) as reserved')
            ->pluck('reserved', 'ri.variant_id');

        // Ajouter available_quantity calculée
        $result->getCollection()->transform(function ($item) use ($reservedByVariant) {
            $reserved = (int) ($reservedByVariant[$item->variant_id] ?? 0);
            $item->available_quantity = max(0, $item->quantity - $reserved);

            return $item;
        });

        return response()->json($result);
    }

    /**
     * Obtenir toutes les locations d'une variante spécifique
     * GET /api/variants/{variantId}/locations
     */
    public function getByVariant($variantId)
    {
        $variantLocations = ProductVariantLocation::with([
            'variant.product',
            'location',
        ])
            ->where('variant_id', $variantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($variantLocations);
    }

    /**
     * Créer une nouvelle localisation
     * POST /api/product-variant-locations
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'location_id' => 'required|integer|exists:locations,id',
            'quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Vérifier qu'il n'existe pas déjà une combinaison variant/location
        $exists = ProductVariantLocation::where('variant_id', $data['variant_id'])
            ->where('location_id', $data['location_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette variante existe déjà dans cette location',
                'error' => 'duplicate_entry',
            ], 422);
        }

        $variantLocation = ProductVariantLocation::create($data);

        $variantLocation->load(['variant.product', 'location']);

        return response()->json($variantLocation, 201);
    }

    /**
     * Mettre à jour une localisation
     * PUT /api/product-variant-locations/{id}
     */
    public function update(Request $request, $id)
    {
        $variantLocation = ProductVariantLocation::findOrFail($id);

        $data = $request->validate([
            'location_id' => 'sometimes|required|integer|exists:locations,id',
            'quantity' => 'sometimes|required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Si on change de location, vérifier qu'il n'existe pas déjà
        if (isset($data['location_id']) && $data['location_id'] != $variantLocation->location_id) {
            $exists = ProductVariantLocation::where('variant_id', $variantLocation->variant_id)
                ->where('location_id', $data['location_id'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Cette variante existe déjà dans cette location',
                    'error' => 'duplicate_entry',
                ], 422);
            }
        }

        $variantLocation->update($data);
        $variantLocation->load(['variant.product', 'location']);

        return response()->json($variantLocation);
    }

    /**
     * Vérifier si une localisation peut être supprimée
     * GET /api/product-variant-locations/{id}/can-delete
     */
    public function canDelete($id)
    {
        $variantLocation = ProductVariantLocation::findOrFail($id);

        // Vérifier les relations avec d'autres tables
        // Vous pouvez ajouter d'autres vérifications selon votre schéma de base de données

        // Exemple : vérifier s'il y a des mouvements de stock liés
        // $hasStockMovements = StockMovement::where('variant_location_id', $id)->exists();

        // Pour l'instant, on vérifie simplement si la quantité est à 0
        // Une localisation avec une quantité > 0 ne devrait pas être supprimée
        if ($variantLocation->quantity > 0) {
            return response()->json([
                'canDelete' => false,
                'reason' => 'Impossible de supprimer : il reste '.$variantLocation->quantity.' unité(s) en stock dans cette location.',
            ]);
        }

        // Ajoutez ici d'autres vérifications de relations si nécessaire
        // Exemple :
        // if ($hasStockMovements) {
        //     return response()->json([
        //         'canDelete' => false,
        //         'reason' => 'Impossible de supprimer : cette localisation a un historique de mouvements de stock.'
        //     ]);
        // }

        return response()->json([
            'canDelete' => true,
            'reason' => null,
        ]);
    }

    /**
     * Supprimer une localisation
     * DELETE /api/product-variant-locations/{id}
     */
    public function destroy($id)
    {
        $variantLocation = ProductVariantLocation::findOrFail($id);

        // Vérifier si on peut supprimer
        $canDeleteResponse = $this->canDelete($id);
        $canDeleteData = $canDeleteResponse->getData();

        if (! $canDeleteData->canDelete) {
            return response()->json([
                'message' => $canDeleteData->reason,
                'error' => 'cannot_delete',
            ], 422);
        }

        $variantLocation->delete();

        return response()->json(null, 204);
    }

    /**
     * Obtenir les statistiques d'une location
     * GET /api/locations/{locationId}/statistics
     */
    public function locationStatistics($locationId)
    {
        $stats = DB::table('product_variant_locations')
            ->where('location_id', $locationId)
            ->select(
                DB::raw('COUNT(DISTINCT variant_id) as total_variants'),
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->first();

        $location = DB::table('locations')
            ->where('id', $locationId)
            ->first();

        return response()->json([
            'location' => $location,
            'statistics' => [
                'total_variants' => $stats->total_variants ?? 0,
                'total_quantity' => $stats->total_quantity ?? 0,
                'capacity' => $location->capacity ?? null,
                'capacity_used_percentage' => $location->capacity && $stats->total_quantity
                    ? round(($stats->total_quantity / $location->capacity) * 100, 2)
                    : null,
            ],
        ]);
    }
}
