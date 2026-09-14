<?php

namespace App\Services;

use App\Models\CreditItem;
use App\Models\CreditItemBatch;
use App\Models\ProductVariantLocation;
use App\Models\ReservationItem;
use App\Models\ReservationItemBatch;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Consomme le stock en FIFO pour un item de vente immédiate
     */
    public function consumeFifo(
        int $saleItemId,
        int $variantId,
        int $locationId,
        int $quantityToSell,
        float $unitPriceAtSale,
        float $discount = 0,
        string $status = 'sold'
    ): void {
        $this->doConsumeFifo(
            itemId: $saleItemId,
            itemType: 'sale',
            variantId: $variantId,
            locationId: $locationId,
            quantityToSell: $quantityToSell,
            unitPriceAtSale: $unitPriceAtSale,
            discount: $discount,
            status: $status,
        );
    }

    /**
     * Consomme le stock en FIFO pour un item de réservation
     */
    public function consumeFifoForReservation(
        int $reservationItemId,
        int $variantId,
        int $locationId,
        int $quantityToSell,
        float $unitPriceAtSale,
        float $discount = 0,
    ): void {
        $this->doConsumeFifo(
            itemId: $reservationItemId,
            itemType: 'reservation',
            variantId: $variantId,
            locationId: $locationId,
            quantityToSell: $quantityToSell,
            unitPriceAtSale: $unitPriceAtSale,
            discount: $discount,
            status: 'reserved',
        );
    }

    /**
     * Consomme le stock en FIFO pour un item de crédit
     */
    public function consumeFifoForCredit(
        int $creditItemId,
        int $variantId,
        int $locationId,
        int $quantityToSell,
        float $unitPriceAtSale,
        float $discount = 0,
    ): void {
        $this->doConsumeFifo(
            itemId: $creditItemId,
            itemType: 'credit',
            variantId: $variantId,
            locationId: $locationId,
            quantityToSell: $quantityToSell,
            unitPriceAtSale: $unitPriceAtSale,
            discount: $discount,
            status: 'sold',
        );
    }

    /**
     * Logique FIFO centralisée pour tous les types d'items
     * stock_batches: variant_id, remaining_quantity, total_unit_cost, received_date
     */
    private function doConsumeFifo(
        int $itemId,
        string $itemType,
        int $variantId,
        int $locationId,
        int $quantityToSell,
        float $unitPriceAtSale,
        float $discount,
        string $status,
    ): void {
        // stock_batches n'a PAS de location_id — on filtre par variant_id seulement
        $batches = StockBatch::where('variant_id', $variantId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $quantityToSell;
        $discountPerUnit = $quantityToSell > 0 ? round($discount / $quantityToSell, 2) : 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $batch->remaining_quantity);
            $batchDiscount = round($discountPerUnit * $take, 2);
            // total_unit_cost est la colonne coût unitaire dans stock_batches
            $unitCost = (float) $batch->total_unit_cost;
            $profit = round(($unitPriceAtSale - $unitCost) * $take - $batchDiscount, 2);

            // Créer l'entrée dans la table de batch appropriée
            match ($itemType) {
                'sale' => SaleItemBatch::create([
                    'sale_item_id' => $itemId,
                    'batch_id' => $batch->id,
                    'quantity' => $take,
                    'unit_price_at_sale' => $unitPriceAtSale,
                    'discount_at_sale' => $batchDiscount,
                    'status' => $status,
                    'location_id' => $locationId,
                ]),
                'reservation' => ReservationItemBatch::create([
                    'reservation_item_id' => $itemId,
                    'batch_id' => $batch->id,
                    'quantity' => $take,
                    'unit_cost' => $unitCost,
                    'unit_price_at_sale' => $unitPriceAtSale,
                    'discount_amount' => $batchDiscount,
                    'profit' => $profit,
                    'status' => $status,
                ]),
                'credit' => CreditItemBatch::create([
                    'credit_item_id' => $itemId,
                    'batch_id' => $batch->id,
                    'quantity' => $take,
                    'unit_cost' => $unitCost,
                    'unit_price_at_sale' => $unitPriceAtSale,
                    'discount_amount' => $batchDiscount,
                    'profit' => $profit,
                    'status' => $status,
                ]),
            };

            $batch->remaining_quantity -= $take;
            $batch->save();

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuffisant en FIFO pour la variante #{$variantId} (manque {$remaining})");
        }

        // Décrémenter le stock global (removeStock met aussi à jour le stock total de la variante)
        $pvl = ProductVariantLocation::where('variant_id', $variantId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->firstOrFail();

        $pvl->removeStock($quantityToSell);

        // Mouvement de stock — colonnes réelles : from_location_id, movement_type, performed_by
        $movementType = match ($itemType) {
            'reservation' => 'reservation',
            'credit' => 'credit',
            default => 'sale',
        };

        StockMovement::create([
            'variant_id' => $variantId,
            'from_location_id' => $locationId,
            'to_location_id' => null,
            'quantity' => $quantityToSell,
            'movement_type' => $movementType,
            'sale_id' => $itemType === 'sale' ? DB::table('sale_items')->where('id', $itemId)->value('sale_id') : null,
            'reservation_item_id' => $itemType === 'reservation' ? $itemId : null,
            'credit_item_id' => $itemType === 'credit' ? $itemId : null,
            'performed_by' => Auth::id(),
        ]);
    }

    /**
     * Finalise les batches réservés (reserved → sold) pour une réservation
     */
    public function finalizeReservationFifo(int $reservationItemId): void
    {
        ReservationItemBatch::where('reservation_item_id', $reservationItemId)
            ->where('status', 'reserved')
            ->update(['status' => 'sold']);
    }

    /**
     * Libère les batches réservés (reserved → cancelled) pour une réservation
     */
    public function releaseReservationFifo(int $reservationItemId): void
    {
        $batches = ReservationItemBatch::where('reservation_item_id', $reservationItemId)
            ->where('status', 'reserved')
            ->get();

        foreach ($batches as $itemBatch) {
            $stockBatch = StockBatch::lockForUpdate()->findOrFail($itemBatch->batch_id);
            $stockBatch->remaining_quantity += $itemBatch->quantity;
            $stockBatch->save();

            $itemBatch->status = 'cancelled';
            $itemBatch->save();
        }

        // Restaurer le stock global (addStock recalcule aussi le stock total de la variante)
        $reservationItem = ReservationItem::findOrFail($reservationItemId);
        $pvl = ProductVariantLocation::where('variant_id', $reservationItem->variant_id)
            ->where('location_id', $reservationItem->location_id)
            ->lockForUpdate()
            ->firstOrFail();

        $pvl->addStock($reservationItem->quantity);

        StockMovement::create([
            'variant_id' => $reservationItem->variant_id,
            'to_location_id' => $reservationItem->location_id,
            'from_location_id' => null,
            'quantity' => $reservationItem->quantity,
            'movement_type' => 'return',
            'performed_by' => Auth::id(),
            'reason' => 'Annulation réservation',
        ]);
    }

    /**
     * Libère le stock pour un crédit annulé
     */
    public function releaseCreditFifo(int $creditItemId): void
    {
        $batches = CreditItemBatch::where('credit_item_id', $creditItemId)
            ->where('status', 'sold')
            ->get();

        foreach ($batches as $itemBatch) {
            $stockBatch = StockBatch::lockForUpdate()->findOrFail($itemBatch->batch_id);
            $stockBatch->remaining_quantity += $itemBatch->quantity;
            $stockBatch->save();

            $itemBatch->status = 'cancelled';
            $itemBatch->save();
        }

        $creditItem = CreditItem::findOrFail($creditItemId);
        $pvl = ProductVariantLocation::where('variant_id', $creditItem->variant_id)
            ->where('location_id', $creditItem->location_id)
            ->lockForUpdate()
            ->firstOrFail();

        $pvl->addStock($creditItem->quantity);

        StockMovement::create([
            'variant_id' => $creditItem->variant_id,
            'to_location_id' => $creditItem->location_id,
            'from_location_id' => null,
            'quantity' => $creditItem->quantity,
            'movement_type' => 'return',
            'performed_by' => Auth::id(),
            'reason' => 'Annulation crédit',
        ]);
    }

    /**
     * @deprecated Utilisez finalizeReservationFifo() à la place
     */
    public function finalizeFifo(int $saleItemId): void
    {
        SaleItemBatch::where('sale_item_id', $saleItemId)
            ->where('status', 'reserved')
            ->update(['status' => 'sold']);
    }

    /**
     * @deprecated Utilisez releaseReservationFifo() à la place
     */
    public function releaseFifo(int $saleItemId): void
    {
        $batches = SaleItemBatch::where('sale_item_id', $saleItemId)
            ->where('status', 'reserved')
            ->get();

        foreach ($batches as $itemBatch) {
            $stockBatch = StockBatch::lockForUpdate()->findOrFail($itemBatch->batch_id);
            $stockBatch->remaining_quantity += $itemBatch->quantity;
            $stockBatch->save();

            $itemBatch->status = 'cancelled';
            $itemBatch->save();
        }

        $saleItem = SaleItem::findOrFail($saleItemId);
        $pvl = ProductVariantLocation::where('variant_id', $saleItem->variant_id)
            ->where('location_id', $saleItem->location_id ?? 0)
            ->lockForUpdate()
            ->first();

        if ($pvl) {
            $pvl->quantity += $saleItem->quantity;
            $pvl->save();
        }

        StockMovement::create([
            'variant_id' => $saleItem->variant_id,
            'to_location_id' => $saleItem->location_id,
            'from_location_id' => null,
            'quantity' => $saleItem->quantity,
            'movement_type' => 'return',
            'performed_by' => Auth::id(),
            'reason' => 'Annulation réservation (legacy)',
        ]);
    }
}
