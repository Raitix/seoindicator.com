<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Credit model for tracking user credit balances.
 *
 * @property int $id
 * @property int $user_id
 * @property float $balance
 * @property float $total_purchased
 * @property float $total_spent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @see TASK-26-2
 * @see FEAT-026
 */
class Credit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_purchased',
        'total_spent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'total_purchased' => 'decimal:2',
            'total_spent' => 'decimal:2',
        ];
    }

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the user that owns this credit record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this credit.
     * Uses user_id as the foreign key since credit_transactions table
     * doesn't have a credit_id column.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get the formatted balance with currency symbol.
     * 
     * @return string
     */
    public function getFormattedBalanceAttribute(): string
    {
        $amount = abs($this->balance);
        $formatted = '$' . number_format($amount, 2);
        
        return $this->balance < 0 ? '-' . $formatted : $formatted;
    }

    /**
     * Get the formatted total purchased with currency symbol.
     * 
     * @return string
     */
    public function getFormattedTotalPurchasedAttribute(): string
    {
        return '$' . number_format($this->total_purchased, 2);
    }

    /**
     * Get the formatted total spent with currency symbol.
     * 
     * @return string
     */
    public function getFormattedTotalSpentAttribute(): string
    {
        return '$' . number_format($this->total_spent, 2);
    }

    // ============================================
    // Business Logic
    // ============================================

    /**
     * Check if the user has sufficient balance for a given amount.
     * 
     * @param float $amount The amount to check
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        // Negative amounts are credits, always allowed
        if ($amount <= 0) {
            return true;
        }

        return $this->balance >= $amount;
    }
}
