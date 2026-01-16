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

class SaleService
{
    /**
     * Génère un numéro de vente unique
     * Format: VNT-YYYYMMDD-NNNN
     */
    public function generateSaleNumber(): string
    {
        $today = now()->format('Ymd');
        $count = Sale::whereDate('sale_date', today())->count() + 1;
        return sprintf('VNT-%s-%04d', $today, $count);
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

            // Création des items et mouvement de stock
            foreach ($data['items'] as $item) {
                // Créer l'item de vente
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                ]);

                // Diminuer le stock
                $this->decreaseStock(
                    $item['variant_id'],
                    $item['location_id'],
                    $item['quantity'],
                    $sale->id
                );
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

            // Vérifier si le client peut avoir ce crédit
            if (!$customer->canGetCredit($totals['total'])) {
                throw new \Exception(
                    "Le client ne peut pas obtenir ce crédit. " .
                    "Disponible: {$customer->getAvailableCredit()}, Demandé: {$totals['total']}"
                );
            }

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

            // Création des items et mouvement de stock
            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                ]);

                // Diminuer le stock (les produits sont donnés au client)
                $this->decreaseStock(
                    $item['variant_id'],
                    $item['location_id'],
                    $item['quantity'],
                    $sale->id
                );
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
            // Enrichir les items avec les prix depuis Product.base_price
            $data['items'] = $this->enrichItemsWithPrices($data['items']);

            // Validation du stock
            $stockValidation = $this->validateStockAvailability($data['items']);
            if (!$stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            // Calcul des totaux
            $totals = $this->calculateTotals($data['items'], $data['discount_amount'] ?? 0);
            $depositAmount = $data['deposit_amount'] ?? 0;

            // Vérifier que l'acompte ne dépasse pas le total
            if ($depositAmount > $totals['total']) {
                throw new \Exception("L'acompte ne peut pas dépasser le montant total");
            }

            // Déterminer le statut de paiement
            $paymentStatus = PaymentStatus::PENDING;
            if ($depositAmount > 0) {
                $paymentStatus = ($depositAmount >= $totals['total']) 
                    ? PaymentStatus::PAID 
                    : PaymentStatus::PARTIAL;
            }

            // Création de la vente
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
                'payment_method' => isset($data['payment_method']) ? PaymentMethod::from($data['payment_method']) : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Création des items et BLOCAGE du stock (pas de diminution réelle)
            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                ]);

                // Bloquer le stock pour la réservation (transfert vers location "réservé")
                $this->reserveStock(
                    $item['variant_id'],
                    $item['location_id'],
                    $item['quantity'],
                    $sale->id
                );
            }

            // Création de la réservation
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

            // Créer la transaction pour l'acompte si fourni
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
                'customer_id' => $data['customer_id'],
                'total' => $totals['total'],
                'deposit' => $depositAmount,
                'expiry_date' => $data['expiry_date'],
            ]);

            return $sale->load(['items.variant.product', 'customer', 'user', 'reservation', 'transactions']);
        });
    }

    /**
     * Payer une échéance de crédit
     */
    public function payCreditInstallment(int $installmentId, array $data): CreditInstallment
    {
        return DB::transaction(function () use ($installmentId, $data) {
            $installment = CreditInstallment::with('credit.customer')->findOrFail($installmentId);
            $credit = $installment->credit;

            // Vérifier que l'échéance n'est pas déjà payée
            if ($installment->isPaid()) {
                throw new \Exception("Cette échéance est déjà payée");
            }

            $amount = min($data['amount'], $installment->getRemainingAmount());

            // Mettre à jour l'échéance
            $installment->amount_paid += $amount;


            // Vérifier si l'échéance est complètement payée
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
            ]);

            return $installment->load(['credit.sale']);
        });
    }

    /**
     * Compléter une réservation (paiement final)
     */
    public function completeReservation(int $reservationId, array $data): Reservation
    {
        return DB::transaction(function () use ($reservationId, $data) {
            $reservation = Reservation::with(['sale.items', 'customer'])->findOrFail($reservationId);

            // Vérifier que la réservation est active
            if (!$reservation->isActive()) {
                throw new \Exception("Cette réservation n'est plus active");
            }

            // Vérifier que la réservation n'est pas expirée
            if ($reservation->isExpired()) {
                throw new \Exception("Cette réservation a expiré");
            }

            $remainingAmount = $reservation->remaining_amount;

            // Créer la transaction pour le paiement final
            $this->createSaleTransaction(
                $reservation->sale,
                $data['account_id'],
                $remainingAmount,
                "Paiement final réservation #{$reservation->sale->sale_number}",
                $data['notes'] ?? ''
            );

            // Mettre à jour la réservation
            $reservation->deposit_amount += $remainingAmount;
            $reservation->remaining_amount = 0;
            $reservation->status = 'completed';
            $reservation->completed_at = now();
            $reservation->save();

            // Mettre à jour la vente
            $reservation->sale->update([
                'payment_status' => PaymentStatus::PAID,
            ]);

            // Finaliser le stock (transférer de "réservé" vers "vendu"/sortie)
            foreach ($reservation->sale->items as $item) {
                $this->finalizeReservedStock($item->variant_id, $item->quantity, $reservation->sale->id);
            }

            // Mettre à jour les points de fidélité
            $reservation->customer->recordPurchase($reservation->total_amount);

            Log::info('Réservation complétée', [
                'reservation_id' => $reservation->id,
                'sale_id' => $reservation->sale_id,
                'final_amount' => $remainingAmount,
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

            // Vérifier que la réservation peut être annulée
            if ($reservation->isCompleted()) {
                throw new \Exception("Une réservation complétée ne peut pas être annulée");
            }

            // Libérer le stock réservé
            foreach ($reservation->sale->items as $item) {
                $this->releaseReservedStock($item->variant_id, $item->quantity, $reservation->sale->id);
            }

            // Mettre à jour la réservation
            $reservation->status = 'cancelled';
            $reservation->cancellation_reason = $reason;
            $reservation->save();

            // Mettre à jour la vente
            $reservation->sale->update(['payment_status' => PaymentStatus::CANCELLED]);

            // Enregistrer l'annulation pour le score du client
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
    protected function decreaseStock(int $variantId, int $locationId, int $quantity, int $saleId): void
    {
        $pvl = ProductVariantLocation::where('variant_id', $variantId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($pvl->quantity < $quantity) {
            throw new \Exception("Stock insuffisant pour la variante #{$variantId}");
        }

        $pvl->decrement('quantity', $quantity);

        // Créer le mouvement de stock
        StockMovement::createSale(
            $variantId,
            $locationId,
            $quantity,
            $saleId,
            Auth::id(),
            "Vente #{$saleId}"
        );

        // Recalculer le stock total du variant
        $pvl->variant->recalculateTotalStock();
    }

    /**
     * Réserve le stock (bloque pour une réservation)
     * Le stock reste physiquement à l'emplacement mais est marqué comme réservé
     */
    protected function reserveStock(int $variantId, int $locationId, int $quantity, int $saleId): void
    {
        $pvl = ProductVariantLocation::where('variant_id', $variantId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($pvl->quantity < $quantity) {
            throw new \Exception("Stock insuffisant pour réserver la variante #{$variantId}");
        }

        // Diminuer le stock disponible
        $pvl->decrement('quantity', $quantity);

        // Créer un mouvement de type réservation
        StockMovement::create([
            'variant_id' => $variantId,
            'from_location_id' => $locationId,
            'to_location_id' => null, // Stock bloqué
            'quantity' => $quantity,
            'movement_type' => 'reservation',
            'sale_id' => $saleId,
            'performed_by' => Auth::id(),
            'reason' => 'reservation',
            'notes' => "Réservation vente #{$saleId}",
        ]);

        $pvl->variant->recalculateTotalStock();
    }

    /**
     * Finalise le stock réservé (après complétion de la réservation)
     */
    protected function finalizeReservedStock(int $variantId, int $quantity, int $saleId): void
    {
        // Trouver le mouvement de réservation original pour récupérer l'emplacement
        $reservationMovement = StockMovement::where('variant_id', $variantId)
            ->where('sale_id', $saleId)
            ->where('movement_type', 'reservation')
            ->first();

        // Créer un mouvement de finalisation avec l'emplacement d'origine
        StockMovement::create([
            'variant_id' => $variantId,
            'from_location_id' => $reservationMovement?->from_location_id ?? null,
            'to_location_id' => null, // Sortie définitive (vendu)
            'quantity' => $quantity,
            'movement_type' => 'sale',
            'sale_id' => $saleId,
            'performed_by' => Auth::id(),
            'reason' => 'reservation_completed',
            'notes' => "Réservation #{$saleId} complétée - stock libéré",
        ]);
    }

    /**
     * Libère le stock réservé (en cas d'annulation)
     */
    protected function releaseReservedStock(int $variantId, int $quantity, int $saleId): void
    {
        // Trouver le mouvement de réservation original
        $reservationMovement = StockMovement::where('variant_id', $variantId)
            ->where('sale_id', $saleId)
            ->where('movement_type', 'reservation')
            ->first();

        if ($reservationMovement && $reservationMovement->from_location_id) {
            // Remettre le stock à l'emplacement original
            $pvl = ProductVariantLocation::firstOrCreate(
                [
                    'variant_id' => $variantId,
                    'location_id' => $reservationMovement->from_location_id,
                ],
                ['quantity' => 0]
            );

            $pvl->increment('quantity', $quantity);

            // Créer un mouvement de libération
            StockMovement::create([
                'variant_id' => $variantId,
                'from_location_id' => null,
                'to_location_id' => $reservationMovement->from_location_id,
                'quantity' => $quantity,
                'movement_type' => 'return',
                'sale_id' => $saleId,
                'performed_by' => Auth::id(),
                'reason' => 'reservation_cancelled',
                'notes' => "Réservation #{$saleId} annulée - stock libéré",
            ]);

            $pvl->variant->recalculateTotalStock();
        }
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
    protected function createSaleTransaction(Sale $sale, int $accountId, float $amount, string $description,string $notes=''): AccountTransaction
    {
        $account = Account::lockForUpdate()->findOrFail($accountId);
        
        // Récupérer le type de transaction INCOME
        $incomeType = TransactionType::where('code', 'INCOME')->first();
        if (!$incomeType) {
            throw new \Exception('Type de transaction INCOME non configuré');
        }

        $balanceBefore = $account->current_balance;
        $balanceAfter = bcadd((string)$balanceBefore, (string)$amount, 2);

        // Générer le numéro de référence

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
            'created_at' => now(),
            'notes' => $notes,
        ]);

        // Mettre à jour le solde du compte
        $account->current_balance = $balanceAfter;
        $account->save();

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
}
