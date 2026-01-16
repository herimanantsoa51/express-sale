<?php

namespace App\Http\Controllers;

use App\Models\FreightForwarder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\FreightForwarderResource;

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
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:freight_forwarders,name',
            'logo_url' => 'nullable|string|max:500',
            'contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'coordinate_id' => 'nullable|exists:coordinates,id',
            'is_active' => 'boolean',
        ]);

        $forwarder = FreightForwarder::create($data);

        return new FreightForwarderResource(
            $forwarder->load('coordinate')
        );
    }

    /**
     * GET /api/freight-forwarders/{id}
     */
    public function show($id)
    {
        $forwarder = FreightForwarder::with('coordinate')->findOrFail($id);

        return new FreightForwarderResource($forwarder);
    }

    /**
     * PUT /api/freight-forwarders/{id}
     */
    public function update(Request $request, $id)
    {
        $forwarder = FreightForwarder::findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('freight_forwarders', 'name')->ignore($forwarder->id),
            ],
            'logo_url' => 'nullable|string|max:500',
            'contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'coordinate_id' => 'nullable|exists:coordinates,id',
        ]);

        $forwarder->update($data);

        return new FreightForwarderResource(
            $forwarder->load('coordinate')
        );
    }

    /**
     * DELETE /api/freight-forwarders/{id}
     */
    public function destroy($id)
    {
        $forwarder = FreightForwarder::findOrFail($id);

        if ($forwarder->stockReceipts()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : transitaire lié à des réceptions de stock'
            ], 409);
        }

        $forwarder->delete();

        return response()->json([
            'message' => 'Transitaire supprimé'
        ]);
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
