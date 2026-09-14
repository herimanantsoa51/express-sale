<?php

namespace App\Services;

use App\Enums\SaleType;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Credit;
use App\Models\ProductVariant;
use App\Models\ProductVariantLocation;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\TransactionType;
use Illuminate\Support\Facades\Auth;

/**
 * Service utilitaire partagé pour toutes les opérations de vente.
 *
 * La logique métier spécifique est dans :
 * - ImmediateSaleService (ventes immédiates)
 * - CreditSaleService (crédits + échéances)
 * - ReservationService (réservations + acomptes)
 */
class SaleService
{
    public function __construct(private StockService $stockService) {}

    /**
     * Génère un numéro de vente unique
     * Format: VNT-YYYYMMDD-NNNN
     */
    public static function generateSaleNumber(): string
    {
        $today = today();
        $dateStr = now()->format('Ymd');

        $lastSale = Sale::whereDate('sale_date', $today)
            ->orderByDesc('sale_number')
            ->lockForUpdate()
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
     */
    public function validateStockAvailability(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $pvl = ProductVariantLocation::where('variant_id', $item['variant_id'])
                ->where('location_id', $item['location_id'])
                ->first();

            if (! $pvl) {
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
     */
    public function calculateTotals(array $items, float $discountAmount = 0): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        if ($discountAmount > $subtotal) {
            throw new \Exception("La remise ({$discountAmount} Ar) ne peut pas dépasser le sous-total ({$subtotal} Ar)");
        }

        $total = max(0, $subtotal - $discountAmount);

        return [
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    /**
     * Enrichit les items avec le prix unitaire récupéré depuis Product.base_price
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
            if (! $variant || ! $variant->product) {
                throw new \Exception("Variante #{$item['variant_id']} ou produit associé introuvable");
            }
            if (! $variant->is_active) {
                throw new \Exception("La variante #{$item['variant_id']} ({$variant->sku}) est désactivée et ne peut pas être vendue");
            }
            if (! $variant->product->is_active) {
                throw new \Exception("Le produit « {$variant->product->name} » est désactivé et ne peut pas être vendu");
            }

            $item['unit_price'] = (float) $variant->product->base_price;

            return $item;
        }, $items);
    }

    /**
     * Répartit un discount total sur plusieurs items proportionnellement
     */
    public function distributeDiscount(array $items, float $totalDiscount): array
    {
        $subtotalTotal = 0;

        foreach ($items as &$item) {
            $item['subtotal'] = $item['quantity'] * $item['unit_price'];
            $subtotalTotal += $item['subtotal'];
        }
        unset($item);

        if ($subtotalTotal <= 0 || $totalDiscount <= 0) {
            foreach ($items as &$item) {
                $item['discount_amount'] = 0;
                $item['subtotal_after_discount'] = $item['subtotal'];
            }
            unset($item);

            return $items;
        }

        $distributed = 0;
        foreach ($items as $index => $item) {
            if ($index === count($items) - 1) {
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
     * Crée une transaction financière pour une vente
     */
    public function createSaleTransaction(
        Sale $sale,
        int $accountId,
        float $amount,
        string $description,
        string $notes = '',
        ?int $reservationId = null,
        ?int $creditId = null,
    ): AccountTransaction {
        $account = Account::lockForUpdate()->findOrFail($accountId);

        $incomeType = TransactionType::where('code', 'INCOME')->first();
        if (! $incomeType) {
            throw new \Exception('Type de transaction INCOME non configuré');
        }

        $balanceBefore = $account->current_balance;
        $balanceAfter = bcadd((string) $balanceBefore, (string) $amount, 2);

        $transaction = AccountTransaction::create([
            'account_id' => $accountId,
            'transaction_type_id' => $incomeType->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_date' => now(),
            'sale_id' => $sale->id,
            'reservation_id' => $reservationId,
            'credit_id' => $creditId,
            'description' => $description,
            'created_by' => Auth::id(),
            'notes' => $notes,
        ]);

        if (! $transaction) {
            throw new \Exception('Échec de la création de la transaction');
        }

        $account->current_balance = $balanceAfter;
        $account->save();

        $transaction->refresh();

        return $transaction;
    }

    /**
     * Alias public pour createSaleTransaction (rétro-compatibilité)
     */
    public function createSaleTransactionPublic(Sale $sale, int $accountId, float $amount, string $description): AccountTransaction
    {
        return $this->createSaleTransaction($sale, $accountId, $amount, $description);
    }
    // ...existing code...

    /**
     * Crée une transaction financière pour une réservation (indépendante de Sale)
     */
    public function createReservationTransaction(
        Reservation $reservation,
        int $accountId,
        float $amount,
        string $description,
        string $notes = '',
    ): AccountTransaction {
        $account = Account::lockForUpdate()->findOrFail($accountId);

        $incomeType = TransactionType::where('code', 'INCOME')->first();
        if (! $incomeType) {
            throw new \Exception('Type de transaction INCOME non configuré');
        }

        $balanceBefore = $account->current_balance;
        $balanceAfter = bcadd((string) $balanceBefore, (string) $amount, 2);

        $transaction = AccountTransaction::create([
            'account_id' => $accountId,
            'transaction_type_id' => $incomeType->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_date' => now(),
            'reservation_id' => $reservation->id,
            'description' => $description,
            'created_by' => Auth::id(),
            'notes' => $notes,
        ]);

        if (! $transaction) {
            throw new \Exception('Échec de la création de la transaction');
        }

        $account->current_balance = $balanceAfter;
        $account->save();

        $transaction->refresh();

        return $transaction;
    }

    /**
     * Crée une transaction financière pour un crédit (indépendante de Sale)
     */
    public function createCreditTransaction(
        Credit $credit,
        int $accountId,
        float $amount,
        string $description,
        string $notes = '',
    ): AccountTransaction {
        $account = Account::lockForUpdate()->findOrFail($accountId);

        $incomeType = TransactionType::where('code', 'INCOME')->first();
        if (! $incomeType) {
            throw new \Exception('Type de transaction INCOME non configuré');
        }

        $balanceBefore = $account->current_balance;
        $balanceAfter = bcadd((string) $balanceBefore, (string) $amount, 2);

        $transaction = AccountTransaction::create([
            'account_id' => $accountId,
            'transaction_type_id' => $incomeType->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_date' => now(),
            'credit_id' => $credit->id,
            'description' => $description,
            'created_by' => Auth::id(),
            'notes' => $notes,
        ]);

        if (! $transaction) {
            throw new \Exception('Échec de la création de la transaction');
        }

        $account->current_balance = $balanceAfter;
        $account->save();

        $transaction->refresh();

        return $transaction;
    }

    // ...existing code...
    /**
     * Génère un numéro de référence pour la transaction
     */
    public function generateTransactionReference(Sale $sale): string
    {
        $prefix = match ($sale->sale_type) {
            SaleType::IMMEDIATE => 'VNT',
            SaleType::CREDIT => 'CRD',
            SaleType::RESERVATION => 'RSV',
        };

        return sprintf('%s-%s', $prefix, $sale->sale_number);
    }
}
