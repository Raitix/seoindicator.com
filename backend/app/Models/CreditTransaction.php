<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CreditTransaction model for tracking credit changes.
 * 
 * Transactions are immutable - no updated_at column.
 *
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property float $balance_after
 * @property string $type
 * @property string $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $payment_provider
 * @property string|null $payment_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * 
 * @see TASK-26-2
 * @see FEAT-026
 */
class CreditTransaction extends Model
{
    use HasFactory;

    // Type constants
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_USAGE = 'usage';
    public const TYPE_REFUND = 'refund';
    public const TYPE_BONUS = 'bonus';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'balance_after',
        'type',
        'description',
        'reference_type',
        'reference_id',
        'payment_provider',
        'payment_id',
        'metadata',
        'created_at',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the user that owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the credit record for this transaction.
     */
    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class, 'user_id', 'user_id');
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Scope a query to filter by transaction type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by payment provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProvider($query, ?string $provider)
    {
        if ($provider === null) {
            return $query->whereNull('payment_provider');
        }

        return $query->where('payment_provider', $provider);
    }

    /**
     * Scope a query to filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to order by most recent first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ============================================
    // Type Helpers
    // ============================================

    /**
     * Check if this is a purchase transaction.
     */
    public function isPurchase(): bool
    {
        return $this->type === self::TYPE_PURCHASE;
    }

    /**
     * Check if this is a usage transaction.
     */
    public function isUsage(): bool
    {
        return $this->type === self::TYPE_USAGE;
    }

    /**
     * Check if this is a refund transaction.
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if this is a bonus transaction.
     */
    public function isBonus(): bool
    {
        return $this->type === self::TYPE_BONUS;
    }

    /**
     * Check if this transaction has a payment reference.
     */
    public function hasPayment(): bool
    {
        return !empty($this->payment_provider) && !empty($this->payment_id);
    }

    /**
     * Check if this transaction has a polymorphic reference.
     */
    public function hasReference(): bool
    {
        return !empty($this->reference_type) && !empty($this->reference_id);
    }
}
