<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Model AccountTransaction
 * Représente une transaction monétaire
 */
class AccountTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'transaction_type_id',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'related_account_id',
        'related_transaction_id',
        'supplier_id',
        'freight_forwarder_id',
        'stock_receipt_id',
        'expense_category_id',
        'reversed_transaction_id',
        'recipient_name',
        'sale_id',
        'reservation_id',
        'credit_id',
        'reference_number',
        'description',
        'planned_expense_id',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */
    public function plannedExpense()
    {
        return $this->belongsTo(PlannedExpense::class);
    }

    /**
     * Compte concerné par la transaction
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Type de transaction
     */
    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * Compte lié (pour les transferts)
     */
    public function relatedAccount()
    {
        return $this->belongsTo(Account::class, 'related_account_id');
    }

    /**
     * Transaction liée (contrepartie d'un transfert)
     */
    public function relatedTransaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'related_transaction_id');
    }

    /**
     * Transaction qui a été annulée par celle-ci
     */
    public function reversedTransaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'reversed_transaction_id');
    }

    /**
     * Transaction qui annule celle-ci
     */
    public function reversingTransaction()
    {
        return $this->hasOne(AccountTransaction::class, 'reversed_transaction_id');
    }

    /**
     * Vérifie si la transaction a déjà été annulée
     */
    public function isReversed(): bool
    {
        return $this->reversingTransaction()->exists();
    }

    /**
     * Fournisseur (si paiement fournisseur)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Transitaire (si paiement transitaire)
     */
    public function freightForwarder()
    {
        return $this->belongsTo(FreightForwarder::class);
    }

    /**
     * Réception de stock (si lié à un réapprovisionnement)
     */
    public function stockReceipt()
    {
        return $this->belongsTo(StockReceipt::class);
    }

    /**
     * Catégorie de dépense
     */
    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function scopeNotCancelled($query)
    {
        return $query->whereNull('reversed_transaction_id')
            ->whereDoesntHave('reversingTransaction');
    }

    /**
     * Vente liée
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Réservation liée (lien direct)
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Crédit lié (lien direct)
     */
    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    /**
     * Créateur de la transaction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installmentTransation(): HasOne
    {
        return $this->hasOne(InstallmentTransaction::class, 'transaction_id');
    }

    /* ===================== SCOPES ===================== */

    /**
     * Filtre par compte
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Ajoutez ce scope
    public function scopeNotCancelledWithAlias($query, $alias = null)
    {
        $tableAlias = $alias ?: $this->getTable();

        return $query->where("{$tableAlias}.reversed_transaction_id", null)
            ->whereNotExists(function ($q) use ($tableAlias) {
                $q->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw("at2.reversed_transaction_id = {$tableAlias}.id");
            });
    }

    /**
     * Filtre par type
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('transaction_type_id', $typeId);
    }

    /**
     * Filtre par catégorie de type
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->whereHas('transactionType', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }

    /**
     * Filtre par période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Filtre les revenus
     */
    public function scopeIncome($query)
    {
        return $query->byCategory('income');
    }

    /**
     * Filtre les dépenses
     */
    public function scopeExpense($query)
    {
        return $query->byCategory('expense');
    }

    /**
     * Filtre les transferts
     */
    public function scopeTransfer($query)
    {
        return $query->byCategory('transfer');
    }

    /**
     * Tri par date décroissante
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /* ===================== MÉTHODES ===================== */

    /**
     * Vérifie si c'est un revenu
     */
    public function isIncome(): bool
    {
        return $this->transactionType->category === 'income';
    }

    /**
     * Vérifie si c'est une dépense
     */
    public function isExpense(): bool
    {
        return $this->transactionType->category === 'expense';
    }

    /**
     * Vérifie si c'est un transfert
     */
    public function isTransfer(): bool
    {
        return $this->transactionType->category === 'transfer';
    }

    /**
     * Vérifie si la transaction peut être annulée par l'utilisateur
     */
    public function canBeCancelledBy(int $userId): bool
    {
        return $this->created_by === $userId;
    }

    /**
     * Génère un numéro de référence unique basé sur le type de transaction
     * Format:
     * - INCOME: FAC-YYYYMMDD-NNNNNN
     * - EXPENSE: RE-YYYYMMDD-NNNNNN
     * - OPENING_BALANCE: OPN-YYYYMMDD-NNNNNN
     * - TRANSFER: TRF-YYYYMMDD-NNNNNN
     * - REVERSAL: REV-YYYYMMDD-NNNNNN
     * - REFUND: RFD-YYYYMMDD-NNNNNN
     */
    protected static function generateReferenceNumber(string $typeCode, $transactionDate): string
    {
        // Mapping des codes de transaction vers les préfixes
        $prefixMap = [
            'INCOME' => 'FAC',
            'EXPENSE' => 'RE',
            'OPENING_BALANCE' => 'OPN',
            'TRANSFER' => 'TRF',
            'REVERSAL' => 'REV',
            'REFUND' => 'RFD',
        ];

        $prefix = $prefixMap[$typeCode] ?? 'TXN';

        // Formater la date (YYYYMMDD)
        $dateStr = \Carbon\Carbon::parse($transactionDate)->format('Ymd');

        // Compter les transactions du même type pour la même date
        $count = self::whereHas('transactionType', function ($q) use ($typeCode) {
            $q->where('code', $typeCode);
        })
            ->whereDate('transaction_date', \Carbon\Carbon::parse($transactionDate))
            ->count();

        // Incrémenter pour la nouvelle transaction
        $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$dateStr}-{$sequence}";
    }

    /**
     * Annule la transaction en utilisant les fonctions PostgreSQL appropriées
     */
    public function cancel(): bool
    {
        return $this->reverse();
    }

    /**
     * Crée une transaction inverse pour annuler celle-ci
     */
    public function reverse(): bool
    {
        // Vérifier si déjà annulée
        if ($this->isReversed()) {
            throw new \Exception('Cette transaction a déjà été annulée');
        }

        return DB::transaction(function () {
            $account = $this->account;

            // Récupérer le type de transaction REVERSAL
            $reversalType = TransactionType::where('code', 'REVERSAL')->first();

            // Si pas de type REVERSAL, utiliser le même type que la transaction originale
            if (! $reversalType) {
                $reversalType = $this->transactionType;
            }

            // Le montant reste POSITIF (comme dans votre système)
            $reversalAmount = abs($this->amount);

            // Calculer les nouveaux soldes en INVERSANT l'effet de la transaction originale
            $balanceBefore = $account->current_balance;

            // Si la transaction originale a DIMINUÉ le solde, l'annulation doit l'AUGMENTER
            if ($this->balance_after < $this->balance_before) {
                $balanceAfter = bcadd((string) $balanceBefore, (string) $reversalAmount, 2);
            } else {
                // Si la transaction originale a AUGMENTÉ le solde, l'annulation doit le DIMINUER
                $balanceAfter = bcsub((string) $balanceBefore, (string) $reversalAmount, 2);
            }

            // Créer la description d'annulation
            $description = 'Annulation: '.($this->description ?? "Transaction #{$this->id}");

            // Générer le numéro de référence automatiquement
            $referenceNumber = self::generateReferenceNumber('REVERSAL', now());

            // Annuler les effets métier liés aux crédits
            if ($this->installmentTransation()->exists()) {
                $this->cancelCreditInstallmentTransaction($this);
            }

            // Annuler les effets métier liés aux réservations (utiliser le lien direct)
            if ($this->reservation_id) {
                $this->cancelReservationDeposit($this);
            }

            // Créer la transaction inverse en copiant TOUS les champs de contexte
            $reversal = self::create([
                'account_id' => $this->account_id,
                'transaction_type_id' => $reversalType->id,
                'amount' => $reversalAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => now(),
                'reversed_transaction_id' => $this->id,
                'description' => $description,
                'notes' => "Annulation de la transaction #{$this->reference_number}".($this->notes ? " - Original: {$this->notes}" : ''),
                'reference_number' => $referenceNumber,
                // Copier TOUS les champs de contexte (même s'ils sont null)
                'supplier_id' => $this->supplier_id,
                'freight_forwarder_id' => $this->freight_forwarder_id,
                // 'stock_receipt_id' => $this->stock_receipt_id,
                'expense_category_id' => $this->expense_category_id,
                'recipient_name' => $this->recipient_name,
                'reservation_id' => $this->reservation_id,
                'credit_id' => $this->credit_id,
                'related_account_id' => $this->related_account_id,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            // Mettre à jour le solde du compte
            $account->current_balance = $balanceAfter;
            $account->save();

            // Si c'est un transfert, annuler aussi la transaction liée
            if ($this->isTransfer() && $this->related_transaction_id) {
                $relatedTransaction = self::find($this->related_transaction_id);
                if ($relatedTransaction && ! $relatedTransaction->isReversed()) {
                    $relatedAccount = $relatedTransaction->account;

                    // Montant toujours positif
                    $relatedReversalAmount = abs($relatedTransaction->amount);

                    // Calculer les soldes en inversant l'effet
                    $relatedBalanceBefore = $relatedAccount->current_balance;

                    if ($relatedTransaction->balance_after < $relatedTransaction->balance_before) {
                        $relatedBalanceAfter = bcadd((string) $relatedBalanceBefore, (string) $relatedReversalAmount, 2);
                    } else {
                        $relatedBalanceAfter = bcsub((string) $relatedBalanceBefore, (string) $relatedReversalAmount, 2);
                    }

                    // Générer le numéro de référence pour la transaction liée
                    $relatedReferenceNumber = self::generateReferenceNumber('REVERSAL', now());

                    // Créer la transaction inverse pour le compte lié
                    $relatedReversal = self::create([
                        'account_id' => $relatedTransaction->account_id,
                        'transaction_type_id' => $reversalType->id,
                        'amount' => $relatedReversalAmount,
                        'balance_before' => $relatedBalanceBefore,
                        'balance_after' => $relatedBalanceAfter,
                        'transaction_date' => now(),
                        'reversed_transaction_id' => $relatedTransaction->id,
                        'related_account_id' => $this->account_id,
                        'related_transaction_id' => $reversal->id,
                        'description' => 'Annulation: '.($relatedTransaction->description ?? "Transaction #{$relatedTransaction->reference_number}"),
                        'notes' => "Annulation de la transaction #{$relatedTransaction->reference_number}",
                        'reference_number' => $relatedReferenceNumber,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Lier les transactions d'annulation entre elles
                    $reversal->update(['related_transaction_id' => $relatedReversal->id]);

                    // Mettre à jour le solde du compte lié
                    $relatedAccount->current_balance = $relatedBalanceAfter;
                    $relatedAccount->save();
                }
            }

            // Recharger les modèles
            $reversal->refresh();
            $account->refresh();

            // Journalisation
            Log::info('Transaction annulée par création d\'une transaction inverse', [
                'original_transaction_id' => $this->id,
                'reversal_transaction_id' => $reversal->id,
                'reference_number' => $referenceNumber,
                'account' => $account->name,
                'original_amount' => $this->amount,
                'reversal_amount' => $reversalAmount,
                'user_id' => Auth::id(),
            ]);

            return true;
        });
    }

    /* ===================== MÉTHODES STATIQUES ===================== */

    /**
     * Crée une nouvelle transaction avec mise à jour automatique du solde via PostgreSQL
     */
    public static function createTransaction(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $account = Account::findOrFail($data['account_id']);
            $transactionType = TransactionType::findOrFail($data['transaction_type_id']);

            // Calculer le solde avant
            $balanceBefore = $account->current_balance;

            // Déterminer le montant avec signe approprié
            $amount = abs($data['amount']);
            if ($transactionType->category === 'expense' || $transactionType->category === 'transfer') {
                $amount = -$amount;
            }

            // Le balance_after sera calculé par PostgreSQL, on met une valeur temporaire
            $balanceAfter = $balanceBefore;

            // Générer le numéro de référence si non fourni
            $transactionDate = $data['transaction_date'] ?? now();
            $referenceNumber = $data['reference_number'] ?? self::generateReferenceNumber($transactionType->code, $transactionDate);

            // Créer la transaction
            $transaction = self::create([
                ...$data,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $transactionDate,
                'reference_number' => $referenceNumber,
                'created_by' => Auth::id(),
                'created_at' => $data['created_at'] ?? now(),
            ]);

            // Recharger pour obtenir le balance_after et current_balance mis à jour par PostgreSQL
            $transaction->refresh();
            $account->refresh();

            return $transaction;
        });
    }

    /**
     * Crée un transfert entre deux comptes en utilisant la fonction PostgreSQL
     */
    public static function createTransfer(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        ?string $notes = null,
        ?string $referenceNumber = null,
        ?string $transactionDate = null
    ): array {
        try {
            return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $notes, $referenceNumber, $transactionDate) {
                // Validation préliminaire
                if ($fromAccountId === $toAccountId) {
                    throw new \Exception('Impossible de transférer vers le même compte');
                }

                if ($amount <= 0) {
                    throw new \Exception('Le montant doit être supérieur à zéro');
                }

                // Vérifier que les comptes existent et les verrouiller
                $from = Account::lockForUpdate()->find($fromAccountId);
                $to = Account::lockForUpdate()->find($toAccountId);

                if (! $from) {
                    throw new \Exception('Compte source introuvable');
                }

                if (! $to) {
                    throw new \Exception('Compte destination introuvable');
                }

                // Vérifier le solde disponible
                if (bccomp((string) $from->current_balance, (string) $amount, 2) < 0) {
                    throw new \Exception(sprintf(
                        'Solde insuffisant dans le compte source (solde: %s, montant: %s)',
                        number_format($from->current_balance, 2, ',', ' '),
                        number_format($amount, 2, ',', ' ')
                    ));
                }

                // Récupérer le type de transaction TRANSFER
                $transferType = TransactionType::where('code', 'TRANSFER')->first();
                if (! $transferType) {
                    throw new \Exception('Type de transaction TRANSFER non configuré');
                }

                // Gestion de la date de transaction
                $now = now();
                $transactionDateObj = $now;

                if (! empty($transactionDate)) {
                    try {
                        if (str_contains($transactionDate, 'T')) {
                            $transactionDateObj = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s', $transactionDate);
                        } else {
                            $transactionDateObj = \Carbon\Carbon::parse($transactionDate);
                        }

                        if (! $transactionDateObj || ! $transactionDateObj->isValid()) {
                            Log::warning('Date de transaction invalide, utilisation de la date actuelle', [
                                'provided_date' => $transactionDate,
                                'fallback_to' => $now,
                            ]);
                            $transactionDateObj = $now;
                        }

                    } catch (\Exception $e) {
                        Log::warning('Erreur de parsing de la date, utilisation de la date actuelle', [
                            'provided_date' => $transactionDate,
                            'error' => $e->getMessage(),
                            'fallback_to' => $now,
                        ]);
                        $transactionDateObj = $now;
                    }
                }

                // Générer le numéro de référence si non fourni
                $generatedReference = $referenceNumber ?? self::generateReferenceNumber('TRANSFER', $transactionDateObj);

                // Calculer les soldes avec précision
                $balanceBeforeFrom = $from->current_balance;
                $balanceAfterFrom = bcsub((string) $balanceBeforeFrom, (string) $amount, 2);

                $balanceBeforeTo = $to->current_balance;
                $balanceAfterTo = bcadd((string) $balanceBeforeTo, (string) $amount, 2);

                $createdAt = $now;

                // Créer la transaction de débit (compte source)
                $debit = self::create([
                    'account_id' => $from->id,
                    'transaction_type_id' => $transferType->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBeforeFrom,
                    'balance_after' => $balanceAfterFrom,
                    'transaction_date' => $transactionDateObj,
                    'related_account_id' => $to->id,
                    'description' => 'Transfert vers compte '.$to->name,
                    'notes' => $notes,
                    'reference_number' => $generatedReference,
                    'created_at' => $createdAt,
                ]);

                // Créer la transaction de crédit (compte destination) avec le même numéro de référence
                $credit = self::create([
                    'account_id' => $to->id,
                    'transaction_type_id' => $transferType->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBeforeTo,
                    'balance_after' => $balanceAfterTo,
                    'transaction_date' => $transactionDateObj,
                    'related_account_id' => $from->id,
                    'related_transaction_id' => $debit->id,
                    'description' => 'Transfert de compte '.$from->name,
                    'notes' => $notes,
                    'reference_number' => $generatedReference,
                    'created_at' => $createdAt,
                ]);

                // Mettre à jour la transaction de débit avec l'ID de la transaction liée
                $debit->update(['related_transaction_id' => $credit->id]);

                // Mettre à jour les soldes des comptes
                $from->current_balance = $balanceAfterFrom;
                $from->save();

                $to->current_balance = $balanceAfterTo;
                $to->save();

                // Recharger les modèles
                $debit->refresh();
                $credit->refresh();
                $from->refresh();
                $to->refresh();

                // Journalisation
                Log::info('Transfert créé avec succès', [
                    'debit_id' => $debit->id,
                    'credit_id' => $credit->id,
                    'reference_number' => $generatedReference,
                    'from_account' => $from->name,
                    'to_account' => $to->name,
                    'amount' => $amount,
                    'transaction_date' => $transactionDateObj->format('Y-m-d H:i:s'),
                    'user_id' => Auth::id(),
                ]);

                return [
                    'outgoing' => $debit,
                    'incoming' => $credit,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Échec du transfert', [
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Enregistre une dépense opérationnelle
     */
    public static function recordOperatingExpense(
        int $accountId,
        int $expenseCategoryId,
        float $amount,
        ?string $recipientName,
        ?string $notes,
        ?string $transactionDate = null,
        ?string $referenceNumber = null,
        ?int $plannedExpenseId = null,
    ): self {
        try {
            return DB::transaction(function () use ($accountId, $expenseCategoryId, $amount, $recipientName, $notes, $transactionDate, $referenceNumber, $plannedExpenseId) {
                // Validation
                if ($amount <= 0) {
                    throw new \Exception('Le montant doit être supérieur à zéro');
                }

                // Vérifier que le compte existe et le verrouiller
                $account = Account::lockForUpdate()->find($accountId);
                if (! $account) {
                    throw new \Exception('Compte introuvable');
                }

                // Vérifier le solde
                if (bccomp((string) $account->current_balance, (string) $amount, 2) < 0) {
                    throw new \Exception(sprintf(
                        'Solde insuffisant (solde: %s, montant: %s)',
                        number_format($account->current_balance, 2, ',', ' '),
                        number_format($amount, 2, ',', ' ')
                    ));
                }

                // Récupérer le type de transaction EXPENSE
                $expenseType = TransactionType::where('code', 'EXPENSE')->first();
                if (! $expenseType) {
                    throw new \Exception('Type de transaction EXPENSE non configuré');
                }

                // Récupérer la catégorie de dépense
                $expenseCategory = ExpenseCategory::find($expenseCategoryId);
                if (! $expenseCategory) {
                    throw new \Exception('Catégorie de dépense introuvable');
                }

                // Gestion de la date de transaction
                $now = now();
                $transactionDateObj = $now;

                if (! empty($transactionDate)) {
                    try {
                        if (str_contains($transactionDate, 'T')) {
                            $transactionDateObj = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s', $transactionDate);
                        } else {
                            $transactionDateObj = \Carbon\Carbon::parse($transactionDate);
                        }

                        if (! $transactionDateObj || ! $transactionDateObj->isValid()) {
                            Log::warning('Date invalide, utilisation de la date actuelle', [
                                'provided_date' => $transactionDate,
                            ]);
                            $transactionDateObj = $now;
                        }

                    } catch (\Exception $e) {
                        Log::warning('Erreur de parsing de date', [
                            'provided_date' => $transactionDate,
                            'error' => $e->getMessage(),
                        ]);
                        $transactionDateObj = $now;
                    }
                }

                // Générer le numéro de référence si non fourni
                $generatedReference = $referenceNumber ?? self::generateReferenceNumber('EXPENSE', $transactionDateObj);

                // Calculer les soldes
                $balanceBefore = $account->current_balance;
                $balanceAfter = bcsub((string) $balanceBefore, (string) $amount, 2);

                // Créer la description automatique basée sur la catégorie
                $categoryName = $expenseCategory->name;
                $description = $notes;

                if (empty($description)) {
                    $recipientPart = $recipientName ? ' à '.$recipientName : '';
                    $description = "Dépense {$categoryName}{$recipientPart}";
                }

                // Créer la transaction
                $transaction = self::create([
                    'account_id' => $account->id,
                    'transaction_type_id' => $expenseType->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'transaction_date' => $transactionDateObj,
                    'expense_category_id' => $expenseCategoryId,
                    'recipient_name' => $recipientName,
                    'description' => $description,
                    'planned_expense_id' => $plannedExpenseId,
                    'notes' => $notes,
                    'reference_number' => $generatedReference,
                    'created_by' => Auth::id(),
                    'created_at' => $now,
                ]);

                // Mettre à jour le solde du compte
                $account->current_balance = $balanceAfter;
                $account->save();

                // Recharger
                $transaction->refresh();
                $account->refresh();

                // Journalisation
                Log::info('Dépense opérationnelle enregistrée', [
                    'transaction_id' => $transaction->id,
                    'reference_number' => $generatedReference,
                    'account' => $account->name,
                    'expense_category' => $categoryName,
                    'amount' => $amount,
                    'recipient' => $recipientName,
                    'transaction_date' => $transactionDateObj->format('Y-m-d H:i:s'),
                    'user_id' => Auth::id(),
                ]);

                return $transaction;
            });
        } catch (\Exception $e) {
            Log::error('Échec de l\'enregistrement de la dépense', [
                'account_id' => $accountId,
                'expense_category_id' => $expenseCategoryId,
                'amount' => $amount,
                'recipient_name' => $recipientName,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function cancelCreditInstallmentTransaction(AccountTransaction $transaction)
    {
        // CHARGER TOUTES les relations nécessaires en UNE SEULE requête
        $transaction->load('installmentTransation.installment.credit');

        $installmentTransaction = $transaction->installmentTransation;

        if (! $installmentTransaction) {
            return;
        }

        $installmentTransaction->update(['status' => 'CANCELLED']);

        $creditInstallment = $installmentTransaction->installment;
        if (! $creditInstallment) {
            return;
        }

        $creditInstallment->decrement('amount_paid', $installmentTransaction->amount);
        $creditInstallment->refresh();

        if ($creditInstallment->amount_paid <= 0) {
            $creditInstallment->update(['status' => 'pending']);
        } elseif ($creditInstallment->amount_paid < $creditInstallment->amount_due) {
            $creditInstallment->update(['status' => 'partial']);
        }

        $credit = $creditInstallment->credit;
        if (! $credit) {
            return;
        }

        $credit->decrement('amount_paid', $installmentTransaction->amount);
        $credit->increment('amount_due', $installmentTransaction->amount);
        $credit->refresh();

        if (! in_array($credit->status, ['cancelled', 'recovered', 'defaulted'])) {
            if ($credit->amount_paid > 0) {
                $credit->update(['status' => 'partial_paid']);
            } else {
                $credit->update(['status' => 'active']);
            }
        }
    }

    private function cancelReservationDeposit(AccountTransaction $transaction)
    {
        // CHARGER TOUTES les relations nécessaires en UNE SEULE requête
        $transaction->load('reservation');

        $reservation = $transaction->reservation;
        if (! $reservation) {
            return;
        }

        $reservation->increment('remaining_amount', $transaction->amount);
        if ($reservation->deposit_amount <= $transaction->amount) {
            $reservation->decrement('deposit_amount', $transaction->amount);
        }
        $reservation->refresh();
    }
    /* ===================== ÉVÉNEMENTS ===================== */

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            // Générer automatiquement le numéro de référence si non fourni
            if (empty($model->reference_number) && $model->transactionType) {
                $model->reference_number = self::generateReferenceNumber(
                    $model->transactionType->code,
                    $model->transaction_date ?? now()
                );
            }
        });
    }
}
