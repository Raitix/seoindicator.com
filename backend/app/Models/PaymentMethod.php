<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentMethod Model for TASK-26-11.
 * 
 * Stores references to payment methods in Stripe.
 * No sensitive card data is stored locally.
 */
class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'provider',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'exp_month' => 'integer',
            'exp_year' => 'integer',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => 'stripe',
        'is_default' => false,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns this payment method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by user ID.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter default payment methods.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to order by created_at descending (newest first).
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the formatted display string for the payment method.
     * E.g., "Visa ending in 4242"
     */
    public function getFormattedDisplayAttribute(): string
    {
        $brand = $this->brand ? ucfirst($this->brand) : 'Card';
        
        if ($this->last_four) {
            return "{$brand} ending in {$this->last_four}";
        }
        
        return $brand;
    }

    /**
     * Get the masked card number.
     * E.g., "**** **** **** 4242"
     */
    public function getMaskedCardAttribute(): string
    {
        if (!$this->last_four) {
            return '****';
        }
        
        return "**** **** **** {$this->last_four}";
    }

    /**
     * Check if the payment method is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        $now = now();
        $expiry = \Carbon\Carbon::createFromDate($this->exp_year, $this->exp_month, 1)->endOfMonth();
        
        return $expiry->isPast();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Set this payment method as the default for the user.
     * Clears any existing default first.
     */
    public function setAsDefault(): void
    {
        // Clear existing default for this user
        static::where('user_id', $this->user_id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this as default
        $this->is_default = true;
        $this->save();
    }

    /**
     * Find the default payment method for a user.
     */
    public static function findDefaultForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }
}
