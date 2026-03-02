<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CreditPackage Model.
 *
 * Represents a predefined pricing tier users can purchase.
 * 
 * @property int $id
 * @property string $name
 * @property float $credits
 * @property float $price_usd
 * @property int $bonus_percent
 * @property bool $is_popular
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read float $effective_credits
 * @property-read int $savings_percentage
 * @property-read string $formatted_price
 * @property-read string $formatted_credits
 */
class CreditPackage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'credits',
        'price_usd',
        'bonus_percent',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credits' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'bonus_percent' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'bonus_percent' => 0,
        'is_popular' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active packages.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order packages by sort_order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to only popular packages.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get effective credits including bonus.
     *
     * @return float
     */
    public function getEffectiveCreditsAttribute(): float
    {
        $bonus = $this->credits * ($this->bonus_percent / 100);
        return round($this->credits + $bonus, 2);
    }

    /**
     * Get savings percentage (alias for bonus_percent).
     *
     * @return int
     */
    public function getSavingsPercentageAttribute(): int
    {
        return $this->bonus_percent;
    }

    /**
     * Get formatted price with dollar sign.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price_usd, 2);
    }

    /**
     * Get formatted credits with dollar sign.
     *
     * @return string
     */
    public function getFormattedCreditsAttribute(): string
    {
        return '$' . number_format($this->credits, 2);
    }
}
