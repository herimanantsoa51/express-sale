<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Customer - Client du système
 * 
 * Gère les informations clients avec système de scoring automatique:
 * 
 * Reliability Score (0-10):
 * - Calculé automatiquement selon les paiements de crédits/réservations
 * - Score initial: 5.00
 * - Augmente avec: paiements à temps, respect des échéances
 * - Diminue avec: retards, annulations, défauts de paiement
 * 
 * Loyalty Points:
 * - Gagnés automatiquement à chaque achat
 * - Peuvent être utilisés pour remises/avantages
 * - Reset possible par période (annuel, etc.)
 * 
 * Credit Limit:
 * - Montant maximum autorisé en crédit simultané
 * - Ajustable selon reliability_score et historique
 * 
 * @property int $id
 * @property string $name Nom complet du client
 * @property string|null $phone Numéro de téléphone
 * @property string|null $address Adresse postale
 * @property float $reliability_score Score de fiabilité (0-10)
 * @property int $loyalty_points Points de fidélité accumulés
 * @property float $credit_limit Plafond de crédit autorisé
 * @property string|null $notes Notes internes
 * @property bool $is_active Client actif/inactif
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * Relations:
 * @property-read Collection|Sale[] $sales Toutes les ventes
 * @property-read Collection|Credit[] $credits Crédits actifs et historiques
 * @property-read Collection|Reservation[] $reservations Réservations
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'customer_number',
        'reliability_score',
        'loyalty_points',
        'credit_limit',
        'notes',
        'is_active',
        'is_extra_customer',
    ];

    protected $casts = [
        'reliability_score' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'loyalty_points' => 'integer',
        'is_active' => 'boolean',
        'is_extra_customer' => 'boolean',
    ];

    /**
     * Relations
     */

    /**
     * Toutes les ventes du client
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Crédits du client (actifs et historiques)
     */
    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    /**
     * Réservations du client
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Scopes pour recherche et filtrage
     */

    /**
     * Clients actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Clients par score de fiabilité minimum
     */
    public function scopeReliable($query, float $minScore = 7.0)
    {
        return $query->where('reliability_score', '>=', $minScore);
    }

    public function scopeExtraCustomers($query)
    {
        return $query->where('is_extra_customer', true);
    }

    /**
     * Clients à risque (score faible)
     */
    public function scopeAtRisk($query, float $maxScore = 4.0)
    {
        return $query->where('reliability_score', '<=', $maxScore);
    }

    /**
     * Clients VIP (nombreux achats ou montant élevé)
     * Basé sur les données calculées à la volée
     */
    public function scopeVip($query, int $minPurchases = 10, float $minSpent = 1000000)
    {
        return $query->whereHas('sales', function($q) use ($minPurchases) {
            $q->selectRaw('customer_id, COUNT(*) as total')
              ->groupBy('customer_id')
              ->havingRaw('COUNT(*) >= ?', [$minPurchases]);
        })->orWhereHas('sales', function($q) use ($minSpent) {
            $q->selectRaw('customer_id, SUM(total_amount) as total')
              ->groupBy('customer_id')
              ->havingRaw('SUM(total_amount) >= ?', [$minSpent]);
        });
    }

    /**
     * Clients avec crédits actifs
     */
    public function scopeWithActiveCredits($query)
    {
        return $query->whereHas('credits', function($q) {
            $q->whereIn('status', ['active', 'partial_paid', 'overdue']);
        });
    }

    /**
     * Clients avec réservations actives
     */
    public function scopeWithActiveReservations($query)
    {
        return $query->whereHas('reservations', function($q) {
            $q->whereIn('status', ['pending', 'confirmed', 'partial_paid']);
        });
    }

    /**
     * Clients inactifs depuis X jours
     * Basé sur la date de la dernière vente
     */
    public function scopeInactiveSince($query, int $days = 90)
    {
        return $query->whereDoesntHave('sales', function($q) use ($days) {
            $q->where('sale_date', '>=', now()->subDays($days));
        });
    }

    /**
     * Recherche par nom ou téléphone
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('phone', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Méthodes de gestion du score de fiabilité
     */

    /**
     * Calcule le nombre de paiements à temps
     * Basé sur les échéances de crédit payées avant la date d'échéance
     */
    public function getOnTimePaymentsCount(): int
    {
        return $this->credits()
            ->join('credit_installments', 'credits.id', '=', 'credit_installments.credit_id')
            ->where('credit_installments.status', 'paid')
            ->whereColumn('credit_installments.paid_date', '<=', 'credit_installments.due_date')
            ->count();
    }

    /**
     * Calcule le nombre de paiements en retard
     * Basé sur les échéances payées après la date d'échéance
     */
    public function getLatePaymentsCount(): int
    {
        return $this->credits()
            ->join('credit_installments', 'credits.id', '=', 'credit_installments.credit_id')
            ->where('credit_installments.status', 'paid')
            ->whereColumn('credit_installments.paid_date', '>', 'credit_installments.due_date')
            ->count();
    }

    /**
     * Calcule le nombre de réservations annulées
     */
    public function getCancelledReservationsCount(): int
    {
        return $this->reservations()
            ->where('status', 'cancelled')
            ->count();
    }

    /**
     * Met à jour le score de fiabilité automatiquement
     * Appelé après chaque événement impactant le score
     */
    public function recalculateReliabilityScore(): void
    {
        $onTimePayments = $this->getOnTimePaymentsCount();
        $latePayments = $this->getLatePaymentsCount();
        $cancelledReservations = $this->getCancelledReservationsCount();
        
        $totalPayments = $onTimePayments + $latePayments;
        
        if ($totalPayments === 0) {
            // Score par défaut si aucun historique
            $this->reliability_score = 5.00;
            $this->save();
            return;
        }

        // Calcul basé sur le ratio paiements à temps
        $onTimeRatio = $onTimePayments / $totalPayments;
        
        // Score de base (0-8 selon ratio)
        $baseScore = $onTimeRatio * 8;
        
        // Bonus pour historique positif (jusqu'à +2 points)
        $bonus = 0;
        if ($onTimePayments >= 20 && $onTimeRatio >= 0.95) {
            $bonus = 2.0; // Client très fiable
        } elseif ($onTimePayments >= 10 && $onTimeRatio >= 0.90) {
            $bonus = 1.0; // Bon client
        } elseif ($onTimePayments >= 5 && $onTimeRatio >= 0.85) {
            $bonus = 0.5; // Client correct
        }
        
        // Pénalités pour réservations annulées
        $penalty = min(2.0, $cancelledReservations * 0.2);
        
        // Score final (entre 0 et 10)
        $finalScore = max(0, min(10, $baseScore + $bonus - $penalty));
        
        $this->reliability_score = round($finalScore, 2);
        $this->save();
    }

    /**
     * Enregistre un paiement à temps
     * Augmente le score
     */
    public function recordOnTimePayment(): void
    {
        $this->recalculateReliabilityScore();
    }

    /**
     * Enregistre un paiement en retard
     * Diminue le score
     */
    public function recordLatePayment(): void
    {
        $this->recalculateReliabilityScore();
    }

    /**
     * Enregistre une réservation annulée
     * Impacte négativement le score
     */
    public function recordCancelledReservation(): void
    {
        $this->recalculateReliabilityScore();
    }

    /**
     * Ajuste le score manuellement (admin)
     * 
     * @param float $newScore Score entre 0 et 10
     * @param string|null $reason Raison de l'ajustement
     */
    public function adjustScore(float $newScore, ?string $reason = null): void
    {
        $newScore = max(0, min(10, $newScore));
        
        $oldScore = $this->reliability_score;
        $this->reliability_score = $newScore;
        
        // Ajouter la raison aux notes si fournie
        if ($reason) {
            $note = sprintf(
                "[%s] Score ajusté de %.2f à %.2f: %s",
                now()->format('Y-m-d H:i'),
                $oldScore,
                $newScore,
                $reason
            );
            $this->notes = trim($this->notes . "\n" . $note);
        }
        
        $this->save();
    }

    /**
     * Méthodes de gestion des points de fidélité
     */

    /**
     * Ajoute des points de fidélité
     * Généralement appelé après un achat
     * 
     * @param int $points Nombre de points à ajouter
     * @param string|null $reason Raison de l'ajout
     */
    public function addLoyaltyPoints(int $points, ?string $reason = null): void
    {
        $this->increment('loyalty_points', $points);
        
        // Optionnel: logger la raison
        if ($reason) {
            $note = sprintf(
                "[%s] +%d points: %s",
                now()->format('Y-m-d H:i'),
                $points,
                $reason
            );
            $this->notes = trim($this->notes . "\n" . $note);
            $this->save();
        }
    }

    /**
     * Calcule les points gagnés pour un montant d'achat
     * Règle par défaut: 1 point par 1000 Ar dépensé
     * 
     * @param float $amount Montant de l'achat
     * @return int Nombre de points gagnés
     */
    public function calculateLoyaltyPoints(float $amount): int
    {
        // 1 point par tranche de 1000 Ar
        return (int) floor($amount / 1000);
    }

    /**
     * Utilise des points de fidélité
     * 
     * @param int $points Nombre de points à utiliser
     * @return bool Succès ou échec
     */
    public function useLoyaltyPoints(int $points): bool
    {
        if ($points > $this->loyalty_points) {
            return false;
        }
        
        $this->decrement('loyalty_points', $points);
        return true;
    }

    /**
     * Réinitialise les points de fidélité (reset annuel, etc.)
     */
    public function resetLoyaltyPoints(): void
    {
        $this->loyalty_points = 0;
        $this->save();
    }

    /**
     * Méthodes de gestion du crédit
     */

    /**
     * Calcule le crédit actuellement utilisé
     * 
     * @return float Montant total des crédits actifs
     */
    public function getCurrentCreditUsage(): float
    {
        return $this->credits()
            ->whereIn('status', ['active', 'partial_paid', 'overdue'])
            ->sum('amount_due');
    }

    /**
     * Calcule le crédit disponible
     * 
     * @return float Montant disponible pour nouveaux crédits
     */
    public function getAvailableCredit(): float
    {
        return max(0, $this->credit_limit - $this->getCurrentCreditUsage());
    }

    /**
     * Vérifie si le client peut avoir un crédit supplémentaire
     * 
     * @param float $amount Montant du crédit demandé
     * @return bool Peut avoir le crédit ou non
     */
    public function canGetCredit(float $amount): bool
    {
        // Vérifier si actif
        if (!$this->is_active) {
            return false;
        }
        
        // Vérifier le score minimum (4.0)
        if ($this->reliability_score < 4.0) {
            return false;
        }
        
        // Vérifier si le montant est dans la limite
        return $this->getAvailableCredit() >= $amount;
    }

    /**
     * Ajuste automatiquement la limite de crédit selon le score
     */
    public function adjustCreditLimit(): void
    {
        $score = $this->reliability_score;
        $baseLimit = 100000; // 100 000 Ar de base
        
        if ($score >= 9.0) {
            // Excellent: 5x le montant de base
            $newLimit = $baseLimit * 5;
        } elseif ($score >= 8.0) {
            // Très bon: 3x
            $newLimit = $baseLimit * 3;
        } elseif ($score >= 7.0) {
            // Bon: 2x
            $newLimit = $baseLimit * 2;
        } elseif ($score >= 6.0) {
            // Moyen: 1.5x
            $newLimit = $baseLimit * 1.5;
        } elseif ($score >= 5.0) {
            // Acceptable: 1x
            $newLimit = $baseLimit;
        } elseif ($score >= 4.0) {
            // Limite: 0.5x
            $newLimit = $baseLimit * 0.5;
        } else {
            // Trop risqué: aucun crédit
            $newLimit = 0;
        }
        
        $this->credit_limit = $newLimit;
        $this->save();
    }

    /**
     * Méthodes de gestion des achats
     */

    /**
     * Enregistre un nouvel achat
     * Met à jour les statistiques et points de fidélité
     * 
     * @param float $amount Montant de l'achat
     */
    public function recordPurchase(float $amount): void
    {
        // Ajout automatique de points de fidélité
        $points = $this->calculateLoyaltyPoints($amount);
        if ($points > 0) {
            $this->addLoyaltyPoints($points, "Achat de {$amount} Ar");
        }
    }

    /**
     * Méthodes de vérification d'état
     */

    /**
     * Vérifie si le client a des crédits en retard
     */
    public function hasOverdueCredits(): bool
    {
        return $this->credits()
            ->where('status', 'overdue')
            ->exists();
    }

    /**
     * Vérifie si le client a des réservations actives
     */
    public function hasActiveReservations(): bool
    {
        return $this->reservations()
            ->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
            ->where('expiry_date', '>', now())
            ->exists();
    }

    /**
     * Vérifie si le client est VIP
     * Basé sur le nombre d'achats ou montant total
     */
    public function isVip(): bool
    {
        $totalPurchases = $this->sales()->count();
        $totalSpent = $this->sales()->sum('total_amount');
        
        return $totalPurchases >= 10 || $totalSpent >= 1000000;
    }

    /**
     * Vérifie si le client est à risque
     */
    public function isAtRisk(): bool
    {
        return $this->reliability_score <= 4.0;
    }

    /**
     * Vérifie si le client est inactif
     */
    public function isInactive(int $days = 90): bool
    {
        $lastSale = $this->sales()
            ->orderBy('sale_date', 'desc')
            ->first();
        
        if (!$lastSale) {
            return true;
        }
        
        return $lastSale->sale_date->addDays($days) < now();
    }

    /**
     * Méthodes d'analyse et statistiques
     */

    /**
     * Obtient le nombre total d'achats
     */
    public function getTotalPurchases(): int
    {
        return $this->sales()->count();
    }

    /**
     * Obtient le montant total dépensé
     */
    public function getTotalSpent(): float
    {
        return (float) $this->sales()->sum('total_amount');
    }

    /**
     * Calcule le panier moyen du client
     */
    public function getAverageOrderValue(): float
    {
        $totalPurchases = $this->getTotalPurchases();
        
        if ($totalPurchases === 0) {
            return 0;
        }
        
        return $this->getTotalSpent() / $totalPurchases;
    }

    /**
     * Calcule le taux de paiement à temps (en %)
     */
    public function getOnTimePaymentRate(): float
    {
        $onTimePayments = $this->getOnTimePaymentsCount();
        $latePayments = $this->getLatePaymentsCount();
        $totalPayments = $onTimePayments + $latePayments;
        
        if ($totalPayments === 0) {
            return 100; // Aucun historique = 100%
        }
        
        return ($onTimePayments / $totalPayments) * 100;
    }

    /**
     * Obtient la date du dernier achat
     */
    public function getLastPurchaseDate(): ?\Carbon\Carbon
    {
        $lastSale = $this->sales()
            ->orderBy('sale_date', 'desc')
            ->first();
        
        return $lastSale?->sale_date;
    }

    /**
     * Obtient la date du premier achat
     */
    public function getFirstPurchaseDate(): ?\Carbon\Carbon
    {
        $firstSale = $this->sales()
            ->orderBy('sale_date', 'asc')
            ->first();
        
        return $firstSale?->sale_date;
    }

    /**
     * Obtient le nombre de jours depuis le dernier achat
     */
    public function getDaysSinceLastPurchase(): ?int
    {
        $lastPurchaseDate = $this->getLastPurchaseDate();
        
        if (!$lastPurchaseDate) {
            return null;
        }
        
        return now()->diffInDays($lastPurchaseDate);
    }

    /**
     * Obtient la durée de la relation client (en jours)
     */
    public function getCustomerLifetimeDays(): ?int
    {
        $firstPurchaseDate = $this->getFirstPurchaseDate();
        
        if (!$firstPurchaseDate) {
            return null;
        }
        
        return now()->diffInDays($firstPurchaseDate);
    }

    /**
     * Calcule la valeur vie client (Customer Lifetime Value)
     */
    public function getLifetimeValue(): float
    {
        return $this->getTotalSpent();
    }

    /**
     * Obtient un résumé du profil client
     */
    public function getProfileSummary(): array
    {
        return [
            'name' => $this->name,
            'reliability_score' => $this->reliability_score,
            'reliability_level' => $this->getReliabilityLevel(),
            'loyalty_points' => $this->loyalty_points,
            'total_purchases' => $this->getTotalPurchases(),
            'total_spent' => $this->getTotalSpent(),
            'average_order' => $this->getAverageOrderValue(),
            'on_time_rate' => round($this->getOnTimePaymentRate(), 2),
            'available_credit' => $this->getAvailableCredit(),
            'is_vip' => $this->isVip(),
            'is_at_risk' => $this->isAtRisk(),
            'days_since_last_purchase' => $this->getDaysSinceLastPurchase(),
            'customer_lifetime_days' => $this->getCustomerLifetimeDays(),
        ];
    }

    /**
     * Obtient le niveau de fiabilité (texte)
     */
    public function getReliabilityLevel(): string
    {
        $score = $this->reliability_score;
        
        if ($score >= 9.0) return 'Excellent';
        if ($score >= 8.0) return 'Très bon';
        if ($score >= 7.0) return 'Bon';
        if ($score >= 6.0) return 'Moyen';
        if ($score >= 5.0) return 'Acceptable';
        if ($score >= 4.0) return 'À surveiller';
        return 'À risque';
    }

    /**
     * Obtient la couleur du badge selon le score (pour UI)
     */
    public function getScoreBadgeColor(): string
    {
        $score = $this->reliability_score;
        
        if ($score >= 8.0) return 'green';    // Excellent/Très bon
        if ($score >= 6.0) return 'blue';     // Bon/Moyen
        if ($score >= 4.0) return 'yellow';   // Acceptable/À surveiller
        return 'red';                          // À risque
    }
    
    protected static function booted()
    {
        static::creating(function ($customer) {
            $customer->customer_number = self::generateCustomerNumber();
        });
    }

    /**
     * Generate unique customer number in format CL-YYYYMMDD-XXXX
     */
    public static function generateCustomerNumber(): string
    {
        $prefix = 'CL-' . date('Ymd') . '-';
        
        // Get the last customer number for today
        $lastCustomer = self::where('customer_number', 'like', $prefix . '%')
            ->orderBy('customer_number', 'desc')
            ->first();
        
        if ($lastCustomer) {
            // Extract the sequential number and increment
            $lastNumber = (int) substr($lastCustomer->customer_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            // First customer of the day
            $nextNumber = 1;
        }
        
        // Format with leading zeros (4 digits)
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}