<?php
namespace App\Services;

use App\Models\StockBatch;
use App\Models\SaleItemBatch;
use App\Models\ProductVariantLocation;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
class StockService
{
    /**
     * FIFO avec prise en compte de l'emplacement
     * 
     * @param int $saleItemId
     * @param int $variantId
     * @param int $locationId emplacement d'où prélever le stock
     * @param int $quantityToSell
     * @param float $unitPriceAtSale
     * @param float $discount
     * @param string $status 'sold' (immediate/credit) ou 'reserved' (reservation)
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
        DB::transaction(function () use (
            $saleItemId,
            $variantId,
            $locationId,
            $quantityToSell,
            $unitPriceAtSale,
            $discount,
            $status
        ) {
            // 1️⃣ Vérifier la disponibilité à cet emplacement
            $pvl = ProductVariantLocation::where('variant_id', $variantId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->firstOrFail();

            $availableQty = $status === 'reserved'
                ? $pvl->quantity - $pvl->reserved_quantity
                : $pvl->quantity;

            if ($availableQty < $quantityToSell) {
                throw new \Exception(
                    "Stock insuffisant à l'emplacement {$locationId}. " .
                    "Disponible: {$availableQty}, Demandé: {$quantityToSell}"
                );
            }

            // 2️⃣ Récupérer les batches FIFO pour CE variant
            // (indépendamment de l'emplacement, car les batches sont globaux)
            $batches = StockBatch::query()
                ->where('variant_id', $variantId)
                ->where('remaining_quantity', '>', 0)
                ->where('cost_status', 'validated')
                ->orderBy('received_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $remainingToConsume = $quantityToSell;
            $discountPerUnit = $quantityToSell > 0 
                ? $discount / $quantityToSell 
                : 0;

            // 3️⃣ Consommer les batches en FIFO
            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $consumableQty = min(
                    $batch->remaining_quantity,
                    $remainingToConsume
                );

                // 4️⃣ Créer le lien vente ↔ batch avec location
                SaleItemBatch::create([
                    'sale_item_id' => $saleItemId,
                    'batch_id' => $batch->id,
                    'quantity' => $consumableQty,
                    'unit_price_at_sale' => $unitPriceAtSale,
                    'discount_at_sale' => round($discountPerUnit, 2),
                    'location_id' => $locationId, // ✅ IMPORTANT
                    'status' => $status,
                ]);

                // 5️⃣ Décrémenter le batch
                if ($status === 'reserved') {
                    $batch->increment('reserved_quantity', $consumableQty);
                } else {
                    $batch->decrement('remaining_quantity', $consumableQty);
                }

                $remainingToConsume -= $consumableQty;
            }

            if ($remainingToConsume > 0) {
                throw new \Exception(
                    "Batches FIFO insuffisants pour variant {$variantId}"
                );
            }

            // 6️⃣ Mettre à jour ProductVariantLocation
            if ($status === 'reserved') {
                $pvl->increment('reserved_quantity', $quantityToSell);
            } else {
                $pvl->decrement('quantity', $quantityToSell);
            }

            // 7️⃣ Créer le mouvement de stock (traçabilité)
            $saleId = \App\Models\SaleItem::find($saleItemId)?->sale_id;
            
            \App\Models\StockMovement::create([
                'variant_id' => $variantId,
                'from_location_id' => $locationId,
                'to_location_id' => null,
                'quantity' => $quantityToSell,
                'movement_type' => $status === 'reserved' ? 'reservation' : 'sale',
                'sale_id' => $saleId,
                'performed_by' => Auth::id(),
                'reason' => $status === 'reserved' ? 'reservation' : 'sale',
                'notes' => $status === 'reserved' 
                    ? "Réservation stock FIFO - Vente #{$saleId}"
                    : "Vente FIFO - Vente #{$saleId}",
            ]);

            // 8️⃣ Recalculer le stock global du variant
            $pvl->variant->recalculateTotalStock();

            Log::info("FIFO consommé", [
                'variant_id' => $variantId,
                'location_id' => $locationId,
                'quantity' => $quantityToSell,
                'status' => $status,
            ]);
        });
    }

    /**
     * Libérer une réservation (annulation)
     */
    public function releaseFifo(int $saleItemId): void
    {
        DB::transaction(function () use ($saleItemId) {
            $saleItem = \App\Models\SaleItem::findOrFail($saleItemId);
            $saleId = $saleItem->sale_id;
            $variantId = null; // Pour recalculer à la fin
            
            $saleItemBatches = SaleItemBatch::where('sale_item_id', $saleItemId)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            if ($saleItemBatches->isEmpty()) {
                Log::warning("Aucun batch réservé à libérer pour sale_item #{$saleItemId}");
                return;
            }

            foreach ($saleItemBatches as $sib) {
                $batch = $sib->batch;
                $variantId = $batch->variant_id; // Mémoriser pour recalcul
                
                $pvl = ProductVariantLocation::where('variant_id', $batch->variant_id)
                    ->where('location_id', $sib->location_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Libérer le batch
                $batch->decrement('reserved_quantity', $sib->quantity);

                // Libérer la location
                $pvl->decrement('reserved_quantity', $sib->quantity);

                // ✅ Mouvement de stock (retour/libération)
                \App\Models\StockMovement::create([
                    'variant_id' => $batch->variant_id,
                    'from_location_id' => null,
                    'to_location_id' => $sib->location_id,
                    'quantity' => $sib->quantity,
                    'movement_type' => 'return',
                    'sale_id' => $saleId,
                    'performed_by' => Auth::id(),
                    'reason' => 'reservation_cancelled',
                    'notes' => "Réservation annulée - Vente #{$saleId}",
                ]);

                // Marquer le lien comme annulé
                $sib->update(['status' => 'cancelled']);
            }
            
            // 🔴 IMPORTANT : Recalculer le stock du variant
            if ($variantId) {
                $variant = \App\Models\ProductVariant::find($variantId);
                if ($variant) {
                    $variant->recalculateTotalStock();
                    Log::info("Stock variant #{$variantId} recalculé après libération");
                }
            }
        });
    }

    /**
     * Finaliser une réservation (passer de reserved → sold)
     */
    public function finalizeFifo(int $saleItemId): void
    {
        DB::transaction(function () use ($saleItemId) {
            $saleItem = \App\Models\SaleItem::findOrFail($saleItemId);
            $saleId = $saleItem->sale_id;
            $variantId = null;
            
            $saleItemBatches = SaleItemBatch::where('sale_item_id', $saleItemId)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            if ($saleItemBatches->isEmpty()) {
                Log::warning("Aucun batch réservé à finaliser pour sale_item #{$saleItemId}");
                return;
            }

            foreach ($saleItemBatches as $sib) {
                $batch = $sib->batch;
                $variantId = $batch->variant_id;
                
                $pvl = ProductVariantLocation::where('variant_id', $batch->variant_id)
                    ->where('location_id', $sib->location_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Décrémenter batch (réservé → vendu)
                $batch->decrement('reserved_quantity', $sib->quantity);
                $batch->decrement('remaining_quantity', $sib->quantity);

                // Décrémenter location
                $pvl->decrement('reserved_quantity', $sib->quantity);
                $pvl->decrement('quantity', $sib->quantity);

                // ✅ Mouvement de stock (finalisation réservation)
                \App\Models\StockMovement::create([
                    'variant_id' => $batch->variant_id,
                    'from_location_id' => $sib->location_id,
                    'to_location_id' => null,
                    'quantity' => $sib->quantity,
                    'movement_type' => 'sale',
                    'sale_id' => $saleId,
                    'performed_by' => Auth::id(),
                    'reason' => 'reservation_completed',
                    'notes' => "Finalisation réservation - Vente #{$saleId} - Batch {$batch->batch_number}",
                ]);

                // Marquer comme vendu
                $sib->update(['status' => 'sold']);
            }
            
            // 🔴 IMPORTANT : Recalculer le stock du variant
            if ($variantId) {
                $variant = \App\Models\ProductVariant::find($variantId);
                if ($variant) {
                    $variant->recalculateTotalStock();
                    Log::info("Stock variant #{$variantId} recalculé après finalisation");
                }
            }
        });
    }
    public function restockFromSaleItem(SaleItem $saleItem,$batchId): void
    {
        DB::transaction(function () use ($saleItem,$batchId) {
            $saleItemBatches = $saleItem->saleItemBatches()->lockForUpdate()->get();
            foreach ($saleItemBatches as $sib) {
                $batch = $sib->batch;
                $pvl = ProductVariantLocation::where('variant_id', $batch->variant_id)
                    ->where('location_id', $sib->location_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Restocker le batch
                $batch->increment('remaining_quantity', $sib->quantity);

                // Restocker la location
                $pvl->increment('quantity', $sib->quantity);

                // Créer le mouvement de stock (restockage)
                \App\Models\StockMovement::create([
                    'variant_id' => $batch->variant_id,
                    'from_location_id' => null,
                    'to_location_id' => $sib->location_id,
                    'quantity' => $sib->quantity,
                    'movement_type' => 'restock',
                    'sale_id' => $saleItem->sale_id,
                    'performed_by' => Auth::id(),
                    'reason' => 'sale_reversed',
                    'batch_id' => $batchId,
                    'notes' => "Restockage suite à annulation de vente - Vente #{$saleItem->sale_id}",
                ]);

                if ($pvl->variant_id) {
                    $variant = \App\Models\ProductVariant::find($pvl->variant_id);
                    if ($variant) {
                        $variant->recalculateTotalStock();
                        Log::info("Stock variant #{$pvl->variant_id} recalculé après annulation");
                    }
                }
                // Marquer le lien comme annulé
                $sib->update(['status' => 'cancelled']);
            }
        });
    }
}