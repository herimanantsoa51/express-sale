<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model PlannedExpense
 * Représente les charges fixes planifiées (charges globales de l'entreprise)
 * Sert uniquement de référence et de rappel
 */
class PlannedExpense extends Model
{
    use HasFactory;

    const FREQUENCY_DAILY = 'daily';

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_YEARLY = 'yearly';

    protected $fillable = [
        'expense_category_id',
        'name',
        'description',
        'estimated_amount',
        'frequency',
        'day_of_week', // 1-7 pour weekly
        'day_of_month', // 1-31 pour monthly
        'start_date',
        'end_date',
        'next_due_date',
        'recipient_name',
        'is_active',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    /* ===================== RELATIONS ===================== */

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * Transactions réellement effectuées liées à cette charge planifiée
     */
    public function relatedTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'planned_expense_id');
    }

    /* ===================== SCOPES ===================== */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByFrequency($query, ?string $frequency)
    {
        // Ignorer si frequency est null ou vide
        if (! $frequency) {
            return $query;
        }

        return $query->where('frequency', $frequency);
    }

    /**
     * Charges dues pour notification (dans les X prochains jours)
     */
    public function scopeDueForNotification($query, int $daysAhead = 7)
    {
        return $query->active()
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [
                now()->startOfDay(),
                now()->addDays($daysAhead)->endOfDay(),
            ]);

    }

    /**
     * Charges en retard
     */
    public function scopeOverdue($query)
    {
        return $query->active()
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<', now()->toDateString());
    }

    /* ===================== MÉTHODES ===================== */

    /**
     * Calcule la prochaine échéance
     */
    public function calculateNextDueDate(?Carbon $fromDate = null): Carbon
    {
        $fromDate = $fromDate ?? $this->next_due_date ?? $this->start_date ?? now();

        return match ($this->frequency) {
            self::FREQUENCY_DAILY => $fromDate->copy()->addDay(),

            self::FREQUENCY_WEEKLY => tap(
                Carbon::parse($fromDate)->startOfDay(),
                function ($date) {
                    if ($date->dayOfWeek === ($this->day_of_week % 7)) {
                        // Si on est déjà le bon jour → semaine suivante
                        $date->addWeek();
                    } else {
                        $date->next($this->day_of_week);
                    }
                }
            ),

            self::FREQUENCY_MONTHLY => $fromDate->copy()->addMonth()
                ->setDay(min($this->day_of_month ?? 1, $fromDate->copy()->addMonth()->daysInMonth)),

            self::FREQUENCY_YEARLY => $fromDate->copy()->addYear(),

            default => $fromDate->copy()->addMonth(),
        };
    }

    /**
     * Met à jour la prochaine échéance
     */
    public function updateNextDueDate(): void
    {
        $this->next_due_date = $this->calculateNextDueDate();
        $this->save();
    }

    /**
     * Vérifie si en retard
     */
    public function isOverdue(): bool
    {
        return $this->next_due_date
            && $this->next_due_date->lt(now()->startOfDay());
    }

    /**
     * Jours restants avant échéance (négatif si en retard)
     */
    public function daysUntilDue(): ?int
    {
        if (! $this->next_due_date) {
            return null;
        }

        return now()->diffInDays($this->next_due_date, false);
    }

    /* ===================== MÉTHODES STATIQUES ===================== */

    /**
     * Calcule le total estimé par fréquence
     */
    public static function estimatedTotalByFrequency(string $frequency): float
    {
        return self::active()
            ->where('frequency', $frequency)
            ->sum('estimated_amount');
    }

    /**
     * Calcule le revenu minimum nécessaire par mois
     */
    public static function calculateMinimumMonthlyIncome(): float
    {
        $daily = self::estimatedTotalByFrequency(self::FREQUENCY_DAILY) * 30;
        $weekly = self::estimatedTotalByFrequency(self::FREQUENCY_WEEKLY) * 4.33;
        $monthly = self::estimatedTotalByFrequency(self::FREQUENCY_MONTHLY);
        $yearly = self::estimatedTotalByFrequency(self::FREQUENCY_YEARLY) / 12;

        return $daily + $weekly + $monthly + $yearly;
    }

    /**
     * Statistiques complètes
     */
    public static function getStats(): array
    {
        $daily = self::estimatedTotalByFrequency(self::FREQUENCY_DAILY);
        $weekly = self::estimatedTotalByFrequency(self::FREQUENCY_WEEKLY);
        $monthly = self::estimatedTotalByFrequency(self::FREQUENCY_MONTHLY);
        $yearly = self::estimatedTotalByFrequency(self::FREQUENCY_YEARLY);

        return [
            'by_frequency' => [
                'daily' => (float) $daily,
                'weekly' => (float) $weekly,
                'monthly' => (float) $monthly,
                'yearly' => (float) $yearly,
            ],
            'monthly_equivalent' => [
                'daily' => (float) $daily * 30,
                'weekly' => (float) $weekly * 4.33,
                'monthly' => (float) $monthly,
                'yearly' => (float) $yearly / 12,
            ],
            'minimum_monthly_income' => (float) self::calculateMinimumMonthlyIncome(),
            'active_count' => self::active()->count(),
            'overdue_count' => self::overdue()->count(),
            'upcoming_7_days' => self::dueForNotification(7)->count(),
        ];
    }

    /**
     * Retourne les fréquences disponibles
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Quotidienne',
            self::FREQUENCY_WEEKLY => 'Hebdomadaire',
            self::FREQUENCY_MONTHLY => 'Mensuelle',
            self::FREQUENCY_YEARLY => 'Annuelle',
        ];
    }
}
