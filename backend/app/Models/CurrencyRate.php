<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'euro_rate',
        'yuan_rate',
        'dollar_rate',
        'dirham_rate',
        'baht_rate',
        'effective_date',
        'notes',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'euro_rate' => 'decimal:4',
        'yuan_rate' => 'decimal:4',
        'dollar_rate' => 'decimal:4',
        'dirham_rate' => 'decimal:4',
        'baht_rate' => 'decimal:4',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'effective_date',
        'created_at',
        'updated_at'
    ];  

    /* ===================== RELATIONS ===================== */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ===================== SCOPES ===================== */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function scopeLatest($query)
    {
        return $query
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /* ===================== LOGIQUE MÉTIER ===================== */

    /**
     * Retourne le taux actuel (ou un taux précis)
     */
    public static function getCurrentRate($currency = null)
    {
        $rate = self::active()->first();

        if (!$rate) return null;

        if ($currency) {
            $field = strtolower($currency) . '_rate';
            return $rate->$field ?? null;
        }

        return $rate;
    }

    public static function getRateAtDate($date, $currency = null)
    {
        $rate = self::where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$rate) return null;

        if ($currency) {
            $field = strtolower($currency) . '_rate';
            return $rate->$field ?? null;
        }

        return $rate;
    }

    /**
     * Conversion vers Ariary
     * Exemple : 10 USD → 10 × dollar_rate
     */
    public function convertToAriary(float $amount, string $currency): ?float
    {
        $field = strtolower($currency) . '_rate';

        if (!isset($this->$field)) {
            return null;
        }

        return round($amount * $this->$field, 2);
    }

    /* ===================== MÉTA ===================== */


    public function isRecent()
    {
        return $this->effective_date->diffInDays(Carbon::today()) <= 7;
    }

    public function isCurrent()
    {
        $current = self::getCurrentRate();
        return $current && $current->id === $this->id;
    }

    public static function getSystemRate()
    {
        return self::where('is_active', true)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    protected static function booted()
    {   
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::saving(function ($model) {
            if ($model->is_active) {
                self::where('id', '!=', $model->id)
                    ->update(['is_active' => false]);
            }
        });
    }


}
