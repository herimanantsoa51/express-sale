<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Resources\FreightForwarderResource;
use App\Models\AccountTransaction;
use App\Models\FreightForwarder;
use App\Models\StockReceipt;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FreightForwarderController extends Controller
{
    /**
     * GET /api/freight-forwarders
     */
    public function index()
    {
        $forwarders = FreightForwarder::with('coordinate')
            ->orderBy('name')
            ->get();

        return FreightForwarderResource::collection($forwarders);
    }

    /**
     * POST /api/freight-forwarders
     */
    public function store(Request $request)
    {

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255|unique:freight_forwarders,name',
                'type' => 'required|in:aerien,maritime',
                'logo_url' => 'nullable|string|max:500',
                'contact' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'coordinate_id' => 'nullable|exists:coordinates,id',
                'is_active' => 'boolean',
            ]);

            $forwarder = FreightForwarder::create($data);
            ActivityLogger::success(
                ActivityAction::FREIGHT_FORWARDER_CREATED,
                "Transitaire créé : {$forwarder->name}",
                [
                    'model_type' => FreightForwarder::class,
                    'model_id' => $forwarder->id,
                    'metadata' => $forwarder->toArray(),
                ],
                "/transitaires/{$forwarder->id}"
            );

            return new FreightForwarderResource(
                $forwarder->load('coordinate')
            );
        } catch (\Exception $e) {
            // throw $th;
            ActivityLogger::error(
                ActivityAction::FREIGHT_FORWARDER_CREATED,
                'Erreur lors de la création du transitaire ',
                $e,
                [
                    'model_type' => FreightForwarder::class,
                    'metadata' => $request->all(),
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'message' => 'Erreur lors de la création du transitaire',
                'error' => 'creation_failed',
            ], 500);
        }

    }

    /**
     * GET /api/freight-forwarders/{id}
     */
    public function show($id)
    {
        $forwarder = FreightForwarder::with('coordinate')->findOrFail($id);

        // Calculer les statistiques
        $statistics = $this->calculateStatistics($forwarder);

        return response()->json([
            'status' => 'success',
            'data' => new FreightForwarderResource($forwarder),
            'statistics' => $statistics,
        ]);
    }

    /**
     * Calculer les statistiques pour un transitaire
     */
    private function calculateStatistics(FreightForwarder $forwarder)
    {
        // Total dépensé (transactions non annulées)
        $totalSpent = AccountTransaction::where('freight_forwarder_id', $forwarder->id)
            ->whereNull('reversed_transaction_id')
            ->sum('amount');

        // Nombre total de réceptions
        $totalReceipts = $forwarder->stockReceipts()->count();

        // Réceptions validées
        $validatedReceipts = $forwarder->stockReceipts()
            ->where('status', 'validated')
            ->count();

        // Dernière réception
        $lastReceipt = $forwarder->stockReceipts()
            ->latest('created_at')
            ->first();

        // Valeur totale des réceptions
        $totalReceiptsValue = $forwarder->stockReceipts()
            ->where('status', 'validated')
            ->sum('total_cost_ariary');

        return [
            'total_spent' => (float) $totalSpent,
            'total_receipts' => $totalReceipts,
            'validated_receipts' => $validatedReceipts,
            'pending_receipts' => $totalReceipts - $validatedReceipts,
            'total_receipts_value' => (float) $totalReceiptsValue,
            'last_receipt_date' => $lastReceipt ? $lastReceipt->created_at : null,
            'last_receipt_number' => $lastReceipt ? $lastReceipt->receipt_number : null,
            'average_per_receipt' => $totalReceipts > 0 ? (float) ($totalSpent / $totalReceipts) : 0,
        ];
    }

    /**
     * Obtenir les réceptions de stock d'un transitaire avec pagination
     * GET /api/freight-forwarders/{id}/stock-receipts
     */
    public function getStockReceipts($id, Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $receipts = StockReceipt::where('freight_forwarder_id', $id)
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
            ],
        ]);
    }

    /**
     * PUT /api/freight-forwarders/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $forwarder = FreightForwarder::findOrFail($id);

            $data = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('freight_forwarders', 'name')->ignore($forwarder->id),
                ],
                'type' => 'required|in:aerien,maritime',
                'logo_url' => 'nullable|string|max:500',
                'contact' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'is_active' => 'boolean',
                'coordinate_id' => 'nullable|exists:coordinates,id',
            ]);

            $forwarder->update($data);
            $forwarder->load('coordinate');

            ActivityLogger::success(
                ActivityAction::FREIGHT_FORWARDER_UPDATED,
                "Transitaire modifié : {$forwarder->name}",
                [
                    'model_type' => FreightForwarder::class,
                    'model_id' => $forwarder->id,
                    'metadata' => $forwarder->toArray(),
                ],
                "/transitaires/{$forwarder->id}"
            );

            return new FreightForwarderResource(
                $forwarder
            );
        } catch (\Exception $e) {
            // throw $th;
            ActivityLogger::error(
                ActivityAction::FREIGHT_FORWARDER_UPDATED,
                "Erreur lors de la modification du transitaire ID : {$id}",
                $e,
                [
                    'model_type' => FreightForwarder::class,
                    'model_id' => $id,
                    'metadata' => $request->all(),
                ]
            );

            return response()->json([
                'message' => 'Erreur lors de la modification du transitaire',
                'error' => 'update_failed',
            ], 500);
        }
    }

    /**
     * DELETE /api/freight-forwarders/{id}
     */
    public function destroy($id)
    {
        try {
            $forwarder = FreightForwarder::findOrFail($id);

            if ($forwarder->stockReceipts()->exists()) {
                return response()->json([
                    'message' => 'Impossible de supprimer : transitaire lié à des réceptions de stock',
                ], 409);
            }

            $forwarder->delete();

            ActivityLogger::success(
                ActivityAction::FREIGHT_FORWARDER_DELETED,
                "Transitaire supprimé : {$forwarder->name}",
                [
                    'model_type' => FreightForwarder::class,
                    'model_id' => $forwarder->id,
                    'metadata' => $forwarder->toArray(),
                ]
            );

            return response()->json([
                'message' => 'Transitaire supprimé',
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::FREIGHT_FORWARDER_DELETED,
                "Erreur lors de la suppression du transitaire ID : {$id}",
                $e,
                [
                    'model_type' => FreightForwarder::class,
                    'model_id' => $id,
                ]
            );

            return response()->json([
                'message' => 'Erreur lors de la suppression du transitaire',
                'error' => 'deletion_failed',
            ], 500);
        }
    }

    /**
     * GET /api/freight-forwarders/{id}/statistics
     */
    public function statistics($id)
    {
        $forwarder = FreightForwarder::findOrFail($id);

        return response()->json([
            'total_receipts' => $forwarder->stockReceipts()->count(),
            'total_value' => $forwarder->stockReceipts()->sum('total_cost_ariary'),
            'last_receipt_date' => $forwarder->stockReceipts()
                ->max('receipt_date'),
            'active_receipts' => $forwarder->stockReceipts()
                ->where('status', 'validated')
                ->count(),
        ]);
    }
}
