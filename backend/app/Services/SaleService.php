<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleType;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\ProductVariantLocation;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\SaleItemBatch;
use App\Services\StockService;
use App\Models\StockBatch;
use App\Enums\SaleStatus;
class SaleService
{   


    public function __construct(private StockService $stockService)
    {
    }
    /**
     * Génère un numéro de vente unique
     * Format: VNT-YYYYMMDD-NNNN
     */
    public function generateSaleNumber(): string
    {
        $today = today();
        $dateStr = now()->format('Ymd');

        $lastSale = Sale::whereDate('sale_date', $today)
            ->orderByDesc('sale_number') // 🔑 tri lexicographique OK
            ->lockForUpdate()            // 🔒 ligne réelle
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->sale_number, -4);
            $next = $lastNumber + 1;
        } else {
            $next = 1;
        }

        return sprintf('VNT-%s-%04d', $dateStr, $next);
    }



    /**
     * Vérifie la disponibilité du stock pour tous les items
     * 
     * @param array $items Items avec variant_id, location_id, quantity
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStockAvailability(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $pvl = ProductVariantLocation::where('variant_id', $item['variant_id'])
                ->where('location_id', $item['location_id'])
                ->first();

            if (!$pvl) {
                $errors[] = "Article #{$index}: Variante non trouvée à cet emplacement";
                continue;
            }

            if ($pvl->quantity < $item['quantity']) {
                $errors[] = "Article #{$index}: Stock insuffisant (disponible: {$pvl->quantity}, demandé: {$item['quantity']})";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calcule le sous-total et le total d'une vente
     * 
     * @param array $items Items avec quantity et unit_price
     * @param float $discountAmount Montant de la remise
     * @return array ['subtotal' => float, 'total' => float]
     */
    public function calculateTotals(array $items, float $discountAmount = 0): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $total = max(0, $subtotal - $discountAmount);

        return [
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    /**
     * Enrichit les items avec le prix unitaire récupéré depuis Product.base_price
     * 
     * @param array $items Items avec variant_id, location_id, quantity
     * @return array Items enrichis avec unit_price
     */
    public function enrichItemsWithPrices(array $items): array
    {
        $variantIds = array_column($items, 'variant_id');
        $variants = ProductVariant::with('product')
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        return array_map(function ($item) use ($variants) {
            $variant = $variants[$item['variant_id']] ?? null;
            if (!$variant || !$variant->product) {
                throw new \Exception("Variante #{$item['variant_id']} ou produit associé introuvable");
            }
            
            $item['unit_price'] = (float) $variant->product->base_price;
            return $item;
        }, $items);
    }
        /**
         * Répartit un discount total sur plusieurs items proportionnellement au montant de chaque item.
         *
         * @param array $items Chaque item doit avoir ['quantity' => int, 'unit_price' => float]
         * @param float $totalDiscount Le montant total à répartir
         * @return array Chaque item avec ['quantity', 'unit_price', 'discount_amount', 'subtotal_after_discount']
         */
        public function distributeDiscount(array $items, float $totalDiscount): array
        {
            $subtotalTotal = 0;

            // 1️⃣ Calculer le subtotal de chaque item et le total
            foreach ($items as &$item) {
                $item['subtotal'] = $item['quantity'] * $item['unit_price'];
                $subtotalTotal += $item['subtotal'];
            }
            unset($item);

            if ($subtotalTotal <= 0 || $totalDiscount <= 0) {
                // Pas de discount à répartir
                foreach ($items as &$item) {
                    $item['discount_amount'] = 0;
                    $item['subtotal_after_discount'] = $item['subtotal'];
                }
                unset($item);
                return $items;
            }

            // 2️⃣ Répartir le discount proportionnellement
            $distributed = 0;
            foreach ($items as $index => $item) {
                if ($index === count($items) - 1) {
                    // Ajuster le dernier item pour corriger les arrondis
                    $itemDiscount = round($totalDiscount - $distributed, 2);
                } else {
                    $itemDiscount = round($totalDiscount * ($item['subtotal'] / $subtotalTotal), 2);
                    $distributed += $itemDiscount;
                }

                $items[$index]['discount_amount'] = $itemDiscount;
                $items[$index]['subtotal_after_discount'] = $item['subtotal'] - $itemDiscount;
            }

            return $items;
        }

    /**
     * Crée une vente immédiate (payée directement)
     */
    public function createImmediateSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Enrichir les items avec les prix depuis Product.base_price
            $data['items'] = $this->enrichItemsWithPrices($data['items']);

            // Validation du stock
            $stockValidation = $this->validateStockAvailability($data['items']);
            if (!$stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            // Calcul des totaux
            $totals = $this->calculateTotals($data['items'], $data['discount_amount'] ?? 0);

            // Création de la vente
            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
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
            if(isset($data['discount_amount']) && $data['discount_amount']>0){
                $data['items'] = $this->distributeDiscount($data['items'], $data['discount_amount'] ?? 0);
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
        
                // ✅ FIFO avec location
                $this->stockService->consumeFifo(
                    saleItemId: $saleItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'], // ✅ AJOUTÉ
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                    status: 'sold' // ✅ EXPLICITE
                );
        
                // ⚠️ SUPPRIMER decreaseStock() car déjà fait dans consumeFifo
            }
            

            // Créer la transaction financière (entrée d'argent)
            $this->createSaleTransaction(
                $sale,
                $data['account_id'],
                $totals['total'],
                "Vente #{$sale->sale_number}"
            );

            // Mettre à jour les points de fidélité du client
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
     * Crée une vente à crédit
     */
    public function createCreditSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Enrichir les items avec les prix depuis Product.base_price
            
            $data['items'] = $this->enrichItemsWithPrices($data['items']);

            // Vérification client
            $customer = Customer::findOrFail($data['customer_id']);
            
            // Calcul des totaux
            $totals = $this->calculateTotals($data['items'], $data['discount_amount'] ?? 0);

            // // Vérifier si le client peut avoir ce crédit
            // if (!$customer->canGetCredit($totals['total'])) {
            //     throw new \Exception(
            //         "Le client ne peut pas obtenir ce crédit. " .
            //         "Disponible: {$customer->getAvailableCredit()}, Demandé: {$totals['total']}"
            //     );
            // }

            // Validation du stock
            $stockValidation = $this->validateStockAvailability($data['items']);
            if (!$stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            // Création de la vente
            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'sale_date' => now(),
                'sale_type' => SaleType::CREDIT,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'total_amount' => $totals['total'],
                'payment_status' => PaymentStatus::PENDING,
                'payment_method' => null,
                'notes' => $data['notes'] ?? null,
            ]);
            if(isset($data['discount_amount']) && $data['discount_amount']>0){
                $data['items'] = $this->distributeDiscount($data['items'], $data['discount_amount'] ?? 0);
            }
            // Création des items et mouvement de stock
            foreach ($data['items'] as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                ]);
        
                // ✅ FIFO avec location
                $this->stockService->consumeFifo(
                    saleItemId: $saleItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'], // ✅ AJOUTÉ
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                    status: 'sold'
                );
        
                // ⚠️ SUPPRIMER decreaseStock()
            }

            // Création du crédit
            $credit = Credit::create([
                'sale_id' => $sale->id,
                'customer_id' => $data['customer_id'],
                'total_amount' => $totals['total'],
                'amount_paid' => 0,
                'amount_due' => $totals['total'],
                'credit_date' => now(),
                'due_date' => Carbon::parse($data['due_date']),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            // Création des échéances
            if (!empty($data['installments'])) {
                // Échéances personnalisées
                $installmentNumber = 1;
                foreach ($data['installments'] as $installment) {
                    CreditInstallment::create([
                        'credit_id' => $credit->id,
                        'installment_number' => $installmentNumber++,
                        'due_date' => Carbon::parse($installment['due_date']),
                        'amount_due' => $installment['amount'],
                        'amount_paid' => 0,
                        'status' => 'pending',
                    ]);
                }
            } else {
                // Une seule échéance à la date d'échéance
                CreditInstallment::create([
                    'credit_id' => $credit->id,
                    'installment_number' => 1,
                    'due_date' => Carbon::parse($data['due_date']),
                    'amount_due' => $totals['total'],
                    'amount_paid' => 0,
                    'status' => 'pending',
                ]);
            }

            Log::info('Vente à crédit créée', [
                'sale_id' => $sale->id,
                'credit_id' => $credit->id,
                'customer_id' => $data['customer_id'],
                'total' => $totals['total'],
                'due_date' => $data['due_date'],
            ]);

            return $sale->load(['items.variant.product', 'customer', 'user', 'credit.installments']);
        });
    }

    /**
     * Crée une réservation
     */
    public function createReservation(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $data['items'] = $this->enrichItemsWithPrices($data['items']);
            
            $stockValidation = $this->validateStockAvailability($data['items']);
            if (!$stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            $totals = $this->calculateTotals($data['items'], $data['discount_amount'] ?? 0);
            $depositAmount = $data['deposit_amount'] ?? 0;

            if ($depositAmount > $totals['total']) {
                throw new \Exception("L'acompte ne peut pas dépasser le montant total");
            }

            $paymentStatus = PaymentStatus::PENDING;
            if ($depositAmount > 0) {
                $paymentStatus = ($depositAmount >= $totals['total']) 
                    ? PaymentStatus::PAID 
                    : PaymentStatus::PARTIAL;
            }

            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'sale_date' => now(),
                'sale_type' => SaleType::RESERVATION,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'total_amount' => $totals['total'],
                'payment_status' => $paymentStatus,
                'payment_method' => isset($data['payment_method']) 
                    ? PaymentMethod::from($data['payment_method']) 
                    : null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                $data['items'] = $this->distributeDiscount($data['items'], $data['discount_amount']);
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

                // ✅ FIFO avec status 'reserved'
                $this->stockService->consumeFifo(
                    saleItemId: $saleItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'], // ✅ LOCATION
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                    status: 'reserved' // ✅ RESERVATION
                );
            }

            // ⚠️ SUPPRIMER l'ancienne méthode reserveStock()

            $reservation = Reservation::create([
                'sale_id' => $sale->id,
                'customer_id' => $data['customer_id'],
                'reservation_date' => now(),
                'expiry_date' => Carbon::parse($data['expiry_date']),
                'total_amount' => $totals['total'],
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $totals['total'] - $depositAmount,
                'status' => $depositAmount > 0 ? 'confirmed' : 'pending',
            ]);

            if ($depositAmount > 0 && isset($data['account_id'])) {
                $this->createSaleTransaction(
                    $sale,
                    $data['account_id'],
                    $depositAmount,
                    "Acompte réservation #{$sale->sale_number}"
                );
            }

            Log::info('Réservation créée', [
                'sale_id' => $sale->id,
                'reservation_id' => $reservation->id,
            ]);

            return $sale->load(['items.variant.product', 'customer', 'user', 'reservation', 'transactions']);
        });
    }

    /**
     * ✅ NOUVEAU : Libérer credit_quantity quand crédit payé
     * À appeler dans payCreditInstallment() quand le crédit est complètement soldé
     */
    protected function releaseCreditQuantity(Credit $credit): void
    {
        foreach ($credit->sale->items as $saleItem) {
            $variant = ProductVariant::find($saleItem->variant_id);
            if ($variant) {
                $variant->decrement('credit_quantity', $saleItem->quantity);
            }
        }
        
        Log::info("Credit_quantity libéré pour crédit #{$credit->id}");
    }

    /**
     * ✅ MISE À JOUR : payCreditInstallment avec libération credit_quantity
     */
    public function payCreditInstallment(int $installmentId, array $data): CreditInstallment
    {
        return DB::transaction(function () use ($installmentId, $data) {
            $installment = CreditInstallment::with('credit.customer')->findOrFail($installmentId);
            $credit = $installment->credit;

            if ($installment->isPaid()) {
                throw new \Exception("Cette échéance est déjà payée");
            }

            $amount = min($data['amount'], $installment->getRemainingAmount());

            // Mettre à jour l'échéance
            $installment->amount_paid += $amount;

            if ($installment->amount_paid >= $installment->amount_due) {
                $installment->status = 'paid';
                $installment->paid_date = now();
            } else {
                $installment->status = 'partial';
            }

            $installment->save();

            // Mettre à jour le crédit
            $credit->amount_paid += $amount;
            $credit->amount_due -= $amount;
            $credit->last_payment_date = now();

            // Vérifier si le crédit est complètement payé
            if ($credit->amount_due <= 0) {
                $credit->status = 'completed';
                $credit->sale->update(['payment_status' => PaymentStatus::PAID]);
                
                // ✅ NOUVEAU : Libérer credit_quantity
                $this->releaseCreditQuantity($credit);
            } else {
                $credit->status = 'partial_paid';
                $credit->sale->update(['payment_status' => PaymentStatus::PARTIAL]);
            }

            $credit->save();

            // Créer la transaction financière
            $transaction = $this->createSaleTransaction(
                $credit->sale,
                $data['account_id'],
                $amount,
                "Paiement crédit #{$credit->sale->sale_number} - Échéance #{$installment->installment_number}",
                $data['notes'] ?? ''
            );

            $installment->installmentTransactions()->create([
                'installment_id' => $installment->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_date' => now(),
            ]);

            // Mettre à jour le score de fiabilité du client
            $isOnTime = !$installment->isOverdue();
            if ($isOnTime) {
                $credit->customer->recordOnTimePayment();
            } else {
                $credit->customer->recordLatePayment();
            }

            Log::info('Échéance de crédit payée', [
                'installment_id' => $installment->id,
                'credit_id' => $credit->id,
                'amount' => $amount,
                'is_on_time' => $isOnTime,
                'credit_fully_paid' => $credit->amount_due <= 0,
            ]);

            return $installment->load(['credit.sale']);
        });
    }

    /**
     * ✅ FINALISER RESERVATION - Simplifiée
     */
    public function completeReservation(int $reservationId, array $data): Reservation
    {
        return DB::transaction(function () use ($reservationId, $data) {
            $reservation = Reservation::with(['sale.items', 'customer'])->findOrFail($reservationId);

            if (!$reservation->isActive()) {
                throw new \Exception("Cette réservation n'est plus active");
            }

            if ($reservation->isExpired()) {
                throw new \Exception("Cette réservation a expiré");
            }

            $remainingAmount = $reservation->remaining_amount;

            $transaction = $this->createSaleTransaction(
                $reservation->sale,
                $data['account_id'],
                $remainingAmount,
                "Paiement final réservation #{$reservation->sale->sale_number}",
                $data['notes'] ?? ''
            );
            
            // Ajoutez cette ligne pour déboguer
            if (!$transaction || !$transaction->id) {
                throw new \Exception("Erreur lors de la création de la transaction");
            }

            $reservation->deposit_amount += $remainingAmount;
            $reservation->remaining_amount = 0;
            $reservation->transaction_complete_id = $transaction->id;
            $reservation->status = 'completed';
            $reservation->completed_at = now();
            $reservation->save();

            $reservation->sale->update([
                'payment_status' => PaymentStatus::PAID,
            ]);

            // ✅ Finaliser tous les items réservés
            foreach ($reservation->sale->items as $item) {
                $this->stockService->finalizeFifo($item->id);
            }

            $reservation->customer->recordPurchase($reservation->total_amount);

            Log::info('Réservation complétée', [
                'reservation_id' => $reservation->id,
            ]);

            return $reservation->load(['sale.items.variant.product', 'sale.transactions', 'customer']);
        });
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(int $reservationId, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = Reservation::with(['sale.items', 'customer'])->findOrFail($reservationId);

            if ($reservation->isCompleted()) {
                throw new \Exception("Une réservation complétée ne peut pas être annulée");
            }

            // ✅ Libérer tous les items réservés
            foreach ($reservation->sale->items as $item) {
                $this->stockService->releaseFifo($item->id);
            }

            $reservation->status = 'cancelled';
            $reservation->cancellation_reason = $reason;
            $reservation->save();

            $reservation->sale->update(['payment_status' => PaymentStatus::CANCELLED]);

            $reservation->customer->recordCancelledReservation();

            Log::info('Réservation annulée', [
                'reservation_id' => $reservation->id,
                'reason' => $reason,
            ]);

            return $reservation->load(['sale', 'customer']);
        });
    }
    /**
     * Diminue le stock d'un emplacement (pour ventes immédiates et crédits)
     */
    protected function decreaseStock(
        int $variantId,
        int $locationId,
        int $quantity,
        int $saleId
    ): void {
        $pvl = ProductVariantLocation::where('variant_id', $variantId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->firstOrFail();
    
        // 🔴 NE PAS REVALIDER LE STOCK ICI
        $pvl->decrement('quantity', $quantity);
    
        StockMovement::createSale(
            $variantId,
            $locationId,
            $quantity,
            $saleId,
            Auth::id(),
            "Vente #{$saleId}"
        );
    
        $pvl->variant->refresh();
    }
    

    protected function reserveStock(
        int $saleItemId,
        int $variantId,
        int $locationId,
        int $quantity,
        float $itemDiscount=0,
        float $unitPriceAtSale=0
    ): void {
        DB::transaction(function () use (
            $saleItemId,
            $variantId,
            $locationId,
            $quantity,
            $itemDiscount,
            $unitPriceAtSale,
        ) {
    
            $pvl = ProductVariantLocation::where('variant_id', $variantId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->firstOrFail();
    
            // 1️⃣ Vérifier disponibilité réelle
            $availableQty = $pvl->quantity - $pvl->reserved_quantity;
            if ($availableQty < $quantity) {
                throw new \Exception("Stock insuffisant pour réserver la variante #{$variantId}");
            }
    
            // 2️⃣ Marquer la réservation au niveau location
            $pvl->increment('reserved_quantity', $quantity);
    
            // 3️⃣ Préparer la répartition du discount par unité
            $discountPerUnit = $quantity > 0
                ? $itemDiscount / $quantity
                : 0;
    
            // 4️⃣ Réservation FIFO sur les batches
            $remaining = $quantity;
    
            $batches = StockBatch::where('variant_id', $variantId)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('received_date')
                ->lockForUpdate()
                ->get();
    
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
    
                $batchAvailable =
                    $batch->remaining_quantity - ($batch->reserved_quantity ?? 0);
    
                if ($batchAvailable <= 0) continue;
    
                $toReserve = min($remaining, $batchAvailable);
    
                // 5️⃣ Marquer batch réservé
                $batch->increment('reserved_quantity', $toReserve);
    
                // 6️⃣ Créer le lien SALE ↔ BATCH
                SaleItemBatch::create([
                    'sale_item_id' => $saleItemId,
                    'batch_id' => $batch->id,
                    'quantity' => $toReserve,
                    'unit_price_at_sale' => $unitPriceAtSale,
                    'discount_at_sale' => round(
                        $discountPerUnit,
                        2
                    ),
                    'location_id' => $locationId,
                    'status' => 'reserved', // très important
                ]);
    
                $remaining -= $toReserve;
            }
    
            if ($remaining > 0) {
                throw new \Exception(
                    "Impossible de réserver la totalité de la quantité FIFO pour la variante #{$variantId}"
                );
            }
    
            // 7️⃣ Mouvement de stock (logique / audit)
            StockMovement::create([
                'variant_id' => $variantId,
                'from_location_id' => $locationId,
                'to_location_id' => null,
                'quantity' => $quantity,
                'movement_type' => 'reservation',
                'sale_id' => SaleItem::find($saleItemId)?->sale_id,
                'performed_by' => Auth::id(),
                'reason' => 'reservation',
                'notes' => "Réservation stock (FIFO + discount réparti)",
            ]);
            $variant = ProductVariant::find($variantId);
            $variant->recalculateTotalStock();
        });
    }
    
    /**
     * Finalise le stock réservé pour une réservation (vente effective)
     */
    protected function finalizeReservedStock(SaleItem $saleItem): void
    {
        DB::transaction(function () use ($saleItem) {

            
                $variant = $saleItem->variant;
                $pvl = $variant->locations()->lockForUpdate()->firstOrFail();

            foreach (
                $saleItem->saleItemBatches()->lockForUpdate()->get()
                as $sib
            ) {
                if ($sib->status !== 'reserved') {
                    continue;
                }

                $batch = $sib->batch;

                // 🔒 SÉCURITÉ
                if ($batch->reserved_quantity < $sib->quantity) {
                    throw new \Exception("StockBatch incohérent");
                }

                // ✅ 1. Stock batch principal
                $batch->decrement('reserved_quantity', $sib->quantity);
                $batch->decrement('remaining_quantity', $sib->quantity);

                // ✅ 2. Stock global variant/location
                $pvl->decrement('reserved_quantity', $sib->quantity);
                $pvl->decrement('quantity', $sib->quantity);

                // ✅ 3. SaleItemBatch
                $sib->update([
                    'status' => 'sold',
                ]);

                // ✅ 4. Mouvement de stock (traçabilité)
                StockMovement::create([
                    'variant_id' => $saleItem->variant_id,
                    'from_location_id' => $pvl->location_id,
                    'to_location_id' => null,
                    'quantity' => $sib->quantity,
                    'movement_type' => 'sale',
                    'sale_id' => $saleItem->sale_id,
                    'performed_by' => Auth::id(),
                    'reason' => 'reservation_completed',
                    'notes' => "Batch {$batch->id} vendu",
                ]);
            }
            $variant->recalculateTotalStock();
        });
    }


    /**
 * Libère le stock réservé (en cas d'annulation)
 */
    protected function releaseReservedStock(SaleItem $saleItem): void
    {
    DB::transaction(function() use ($saleItem) {

        $variant = $saleItem->variant;
        $pvl = $variant->locations()->lockForUpdate()->firstOrFail();
        // On parcourt les SaleItemBatches réservés
        foreach ($saleItem->saleItemBatches()->lockForUpdate()->get() as $sib) {
            if ($sib->status !== 'reserved') {
                continue; // déjà libéré ou vendu
            }

            $batch = $sib->batch;

            if (($batch->reserved_quantity ?? 0) < $sib->quantity) {
                throw new \Exception("StockBatch incohérent pour libération");
            }

            // 🔹 1. Stock batch principal : décrémente réservé uniquement
            $batch->decrement('reserved_quantity', $sib->quantity);

            // 🔹 2. Stock global location/variant
            $pvl->decrement('reserved_quantity', $sib->quantity);

            // 🔹 3. SaleItemBatch : statut libéré
            $sib->update([
                'status' => 'cancelled',
            ]);

            // 🔹 4. Mouvement de stock
            StockMovement::create([
                'variant_id' => $saleItem->variant_id,
                'from_location_id' => null,
                'to_location_id' => $pvl->id,
                'quantity' => $sib->quantity,
                'movement_type' => 'return',
                'sale_id' => $saleItem->sale_id,
                'performed_by' => Auth::id(),
                'reason' => 'reservation_cancelled',
                'notes' => "Réservation #{$saleItem->sale_id} annulée - stock libéré",
            ]);
        }
        $variant->recalculateTotalStock();
    });
}



    /**
     * Crée une transaction financière pour une vente (méthode publique pour le controller)
     */
    public function createSaleTransactionPublic(Sale $sale, int $accountId, float $amount, string $description): AccountTransaction
    {
        return $this->createSaleTransaction($sale, $accountId, $amount, $description);
    }

    /**
     * Crée une transaction financière pour une vente
     */
    protected function createSaleTransaction(Sale $sale, int $accountId, float $amount, string $description, string $notes = ''): AccountTransaction
    {
        $account = Account::lockForUpdate()->findOrFail($accountId);
        
        // Récupérer le type de transaction INCOME
        $incomeType = TransactionType::where('code', 'INCOME')->first();
        if (!$incomeType) {
            throw new \Exception('Type de transaction INCOME non configuré');
        }

        $balanceBefore = $account->current_balance;
        $balanceAfter = bcadd((string)$balanceBefore, (string)$amount, 2);

        $transaction = AccountTransaction::create([
            'account_id' => $accountId,
            'transaction_type_id' => $incomeType->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_date' => now(),
            'sale_id' => $sale->id,
            'description' => $description,
            'created_by' => Auth::id(),
            'notes' => $notes,
        ]);

        // ✅ Vérifier que la transaction a bien été créée
        if (!$transaction) {
            throw new \Exception("Échec de la création de la transaction");
        }

        // Mettre à jour le solde du compte
        $account->current_balance = $balanceAfter;
        $account->save();

        // ✅ Recharger pour s'assurer d'avoir l'ID
        $transaction->refresh();

        return $transaction;
    }

    /**
     * Génère un numéro de référence pour la transaction
     */
    protected function generateTransactionReference(Sale $sale): string
    {
        $prefix = match($sale->sale_type) {
            SaleType::IMMEDIATE => 'VNT',
            SaleType::CREDIT => 'CRD',
            SaleType::RESERVATION => 'RSV',
        };

        return sprintf('%s-%s', $prefix, $sale->sale_number);
    }

    public function cancelImmediateSale(Sale $sale)
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->sale_type !== SaleType::IMMEDIATE) {
                throw new \Exception("Seules les ventes immédiates peuvent être annulées avec cette méthode.");
            }
            if($sale->status != SaleStatus::CONFIRMED){
                throw new \Exception("Cette vente a deja été annulé.");
            }
            if ($sale->payment_status === PaymentStatus::CANCELLED) {
                throw new \Exception("Cette vente est déjà annulée.");
            }

            // Restaurer le stock
            $batchId = 'CAND-' . now()->format('Ymd-His') . '-' . Str::random(6);
            foreach ($sale->items as $item) {
                $this->stockService->restockFromSaleItem($item,$batchId);
            }
            Log::info('Vente immédiate annulée', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
            ]);
            $sale->update(['status'=>'CANCELLED']);
            return $sale->load(['items.variant.product', 'customer', 'user', 'transactions']);
        });

    }
    public function cancelCredit(Credit $credit)
    {   
        return DB::transaction(function () use ($credit) {
            $sale = $credit->sale;
            Log::info('Annulation du crédit demandé', [
                'credit_id' => $credit->id,
                'sale_number' => $sale->sale_number,
                'sale_id' => $sale->id
            ]);

            if ($sale->sale_type !== SaleType::CREDIT) {
                throw new \Exception("Seules les ventes a credits peuvent être annulées avec cette méthode.");
            }
            if($sale->status != SaleStatus::CONFIRMED){
                throw new \Exception("Cette vente a deja été annulé.");
            }
            if ($sale->payment_status === PaymentStatus::CANCELLED) {
                throw new \Exception("Cette vente est déjà annulée.");
            }
            // Restaurer le stock
            $batchId = 'CAND-' . now()->format('Ymd-His') . '-' . Str::random(6);
            foreach ($sale->items as $item) {
                $this->stockService->restockFromSaleItem($item,$batchId);
            }
            $credit->update(['status'=>'cancelled']);
            Log::info('Credit annulé', [
                'credit_id' => $credit->id,
                'sale_number' => $sale->sale_number,
            ]);
            $sale->update(['status'=>'CANCELLED']);
            return $credit->load(['sale.items.variant.product', 'customer', 'sale.transactions']);
        });
    }
}
