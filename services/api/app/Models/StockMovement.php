<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    const UPDATED_AT = null; // Pas de updated_at pour un historique

    protected $fillable = [
        'variant_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'movement_type',
        'sale_id',
        'stock_receipt_id',
        'performed_by',
        'reason',
        'notes',
        'batch_id',
        'loss_type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    // Types de mouvements
    const TYPE_TRANSFER = 'transfer';

    const TYPE_RECEIPT = 'receipt';

    const TYPE_SALE = 'sale';

    const TYPE_ADJUSTMENT = 'adjustment';

    const TYPE_RETURN = 'return';

    const TYPE_RESERVARTION = 'reservation';

    const TYPE_LOSS = 'loss';

    // Relations
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stockReceipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Scopes
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where(function ($q) use ($locationId) {
            $q->where('from_location_id', $locationId)
                ->orWhere('to_location_id', $locationId);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeTransfers($query)
    {
        return $query->where('movement_type', self::TYPE_TRANSFER);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Méthodes statiques pour créer des mouvements
    /**
     * Créer un mouvement de transfert entre locations
     */
    public static function createTransfer(
        int $variantId,
        int $fromLocationId,
        int $toLocationId,
        int $quantity,
        int $userId,
        ?string $reason = null,
        ?string $notes = null
    ): self {
        return self::create([
            'variant_id' => $variantId,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'movement_type' => self::TYPE_TRANSFER,
            'performed_by' => $userId,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * Créer un mouvement de réception
     */
    public static function createReceipt(
        int $variantId,
        int $toLocationId,
        int $quantity,
        int $stockReceiptId,
        int $userId,
        ?string $notes = null
    ): self {
        return self::create([
            'variant_id' => $variantId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'movement_type' => self::TYPE_RECEIPT,
            'stock_receipt_id' => $stockReceiptId,
            'performed_by' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Créer un mouvement de vente
     */
    public static function createSale(
        int $variantId,
        int $fromLocationId,
        int $quantity,
        int $saleId,
        int $userId,
        ?string $notes = null
    ): self {
        return self::create([
            'variant_id' => $variantId,
            'from_location_id' => $fromLocationId,
            'quantity' => $quantity,
            'movement_type' => self::TYPE_SALE,
            'sale_id' => $saleId,
            'performed_by' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Créer un mouvement d'ajustement
     */
    public static function createAdjustment(
        int $variantId,
        ?int $locationId,
        int $quantity,
        int $userId,
        string $reason,
        bool $isIncrease = true,
        ?string $notes = null
    ): self {
        return self::create([
            'variant_id' => $variantId,
            'from_location_id' => $isIncrease ? null : $locationId,
            'to_location_id' => $isIncrease ? $locationId : null,
            'quantity' => $quantity,
            'movement_type' => self::TYPE_ADJUSTMENT,
            'performed_by' => $userId,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * Créer un mouvement de retour
     */
    public static function createReturn(
        int $variantId,
        int $toLocationId,
        int $quantity,
        ?int $saleId,
        int $userId,
        string $reason,
        ?string $notes = null
    ): self {
        return self::create([
            'variant_id' => $variantId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'movement_type' => self::TYPE_RETURN,
            'sale_id' => $saleId,
            'performed_by' => $userId,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    // Méthodes utilitaires
    public function getMovementDescription(): string
    {
        $descriptions = [
            self::TYPE_TRANSFER => "Transfert de {$this->fromLocation->name} vers {$this->toLocation->name}",
            self::TYPE_RECEIPT => "Réception vers {$this->toLocation->name}",
            self::TYPE_SALE => "Vente depuis {$this->fromLocation->name}",
            self::TYPE_ADJUSTMENT => $this->isIncrease()
                ? "Ajustement positif vers {$this->toLocation->name}"
                : "Ajustement négatif depuis {$this->fromLocation->name}",
            self::TYPE_RETURN => "Retour vers {$this->toLocation->name}",
        ];

        return $descriptions[$this->movement_type] ?? 'Mouvement inconnu';
    }

    public function isIncrease(): bool
    {
        return $this->to_location_id !== null;
    }

    public function isDecrease(): bool
    {
        return $this->from_location_id !== null;
    }
}
