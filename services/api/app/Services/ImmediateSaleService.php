<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImmediateSaleService
{
    public function __construct(
        private SaleService $saleService,
        private StockService $stockService,
    ) {}

    /**
     * Crée une vente immédiate (payée directement)
     */
    public function createImmediateSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $data['items'] = $this->saleService->enrichItemsWithPrices($data['items']);

            $stockValidation = $this->saleService->validateStockAvailability($data['items']);
            if (! $stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            $totals = $this->saleService->calculateTotals($data['items'], $data['discount_amount'] ?? 0);

            $sale = Sale::create([
                'sale_number' => $this->saleService->generateSaleNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => Auth::id(),
                'sale_date' => now(),
                'sale_type' => SaleType::IMMEDIATE,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'total_amount' => $totals['total'],
                'payment_status' => PaymentStatus::PAID,
                'payment_method' => PaymentMethod::from($data['payment_method']),
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                $data['items'] = $this->saleService->distributeDiscount($data['items'], $data['discount_amount'] ?? 0);
            }

            foreach ($data['items'] as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                ]);

                $this->stockService->consumeFifo(
                    saleItemId: $saleItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'],
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                    status: 'sold'
                );
            }

            $this->saleService->createSaleTransaction(
                $sale,
                $data['account_id'],
                $totals['total'],
                "Vente #{$sale->sale_number}"
            );

            if ($sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                $customer->recordPurchase($totals['total']);
            }

            Log::info('Vente immédiate créée', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $totals['total'],
                'user_id' => Auth::id(),
            ]);

            return $sale->load(['items.variant.product', 'customer', 'user', 'transactions']);
        });
    }

    /**
     * Annuler une vente immédiate : marque uniquement la vente comme annulée.
     *
     * Politique métier : AUCUN automatisme au-delà du statut — ni remboursement
     * des encaissements, ni remise en stock. Ces opérations sont faites
     * manuellement par l'opérateur (transaction inverse, ajustement de stock).
     */
    public function cancelImmediateSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->sale_type !== SaleType::IMMEDIATE) {
                throw new \Exception('Seules les ventes immédiates peuvent être annulées avec cette méthode.');
            }
            if ($sale->status != SaleStatus::CONFIRMED) {
                throw new \Exception('Cette vente a deja été annulé.');
            }
            if ($sale->payment_status === PaymentStatus::CANCELLED) {
                throw new \Exception('Cette vente est déjà annulée.');
            }

            $sale->update([
                'status' => SaleStatus::CANCELLED,
                'payment_status' => PaymentStatus::CANCELLED,
            ]);

            Log::info('Vente immédiate annulée', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
            ]);

            return $sale->load(['items.variant.product', 'customer', 'user', 'transactions']);
        });
    }
}
