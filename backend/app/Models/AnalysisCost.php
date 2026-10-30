<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnalysisCost model for tracking per-analysis resource costs.
 *
 * Stores cost information for each analysis, enabling cost transparency
 * and future monetization.
 *
 * @property int $id
 * @property int $analysis_id
 * @property string $cost_type
 * @property string $cost_units
 * @property string $cost_currency
 * @property string $cost_amount
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @property-read \App\Models\AnalysisHistory $analysis
 */
class AnalysisCost extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analysis_costs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'analysis_id',
        'cost_type',
        'cost_units',
        'cost_currency',
        'cost_amount',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cost_units' => 'decimal:4',
        'cost_amount' => 'decimal:6',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the analysis that owns this cost.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AnalysisHistory::class, 'analysis_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to filter by analysis_id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $analysisId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAnalysis($query, int $analysisId)
    {
        return $query->where('analysis_id', $analysisId);
    }

    /**
     * Scope a query to filter by cost_type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('cost_type', $type);
    }

    /**
     * Scope a query to filter by currency.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('cost_currency', $currency);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the formatted cost amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency_symbol . $this->cost_amount;
    }

    /**
     * Get the currency symbol.
     *
     * @return string
     */
    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->cost_currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => $this->cost_currency . ' ',
        };
    }

    /**
     * Get the human-readable cost type label.
     *
     * @return string
     */
    public function getCostTypeLabelAttribute(): string
    {
        $label = ucwords(str_replace('_', ' ', $this->cost_type));

        // Handle common acronyms
        $acronyms = ['Api', 'Cpu', 'Gpu', 'Url', 'Id'];
        $replacements = ['API', 'CPU', 'GPU', 'URL', 'ID'];

        return str_replace($acronyms, $replacements, $label);
    }

    /**
     * Check if the cost is zero.
     *
     * @return bool
     */
    public function getIsZeroAttribute(): bool
    {
        return (float) $this->cost_amount === 0.0;
    }
}
