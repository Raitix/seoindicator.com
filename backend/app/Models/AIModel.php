<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AI Model representation for tracking available LLM models.
 * 
 * Stores pricing, capabilities, and metadata for AI models
 * synced from providers like OpenRouter.
 *
 * @property int $id
 * @property string $provider
 * @property string $model_id
 * @property string $display_name
 * @property float $cost_per_1k_input
 * @property float $cost_per_1k_output
 * @property int $context_window
 * @property bool $is_free_tier
 * @property int|null $free_tier_limit
 * @property array|null $capabilities
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AIModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider',
        'model_id',
        'display_name',
        'cost_per_1k_input',
        'cost_per_1k_output',
        'context_window',
        'is_free_tier',
        'free_tier_limit',
        'capabilities',
        'is_active',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cost_per_1k_input' => 'decimal:6',
        'cost_per_1k_output' => 'decimal:6',
        'context_window' => 'integer',
        'is_free_tier' => 'boolean',
        'free_tier_limit' => 'integer',
        'capabilities' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'cost_per_1k_input' => 0.000000,
        'cost_per_1k_output' => 0.000000,
        'context_window' => 4096,
        'is_free_tier' => false,
        'is_active' => true,
    ];

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active models.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include free tier models.
     */
    public function scopeFreeTier($query)
    {
        return $query->where('is_free_tier', true);
    }

    /**
     * Scope a query to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to order by display_name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_name');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the estimated cost per analysis.
     * 
     * Assumes ~2000 input tokens + ~500 output tokens per typical analysis.
     *
     * @return float
     */
    public function getCostPerAnalysisEstimateAttribute(): float
    {
        if ($this->is_free_tier) {
            return 0.0;
        }

        // Default estimate: 2000 input tokens + 500 output tokens
        $inputCost = (2000 / 1000) * $this->cost_per_1k_input;
        $outputCost = (500 / 1000) * $this->cost_per_1k_output;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get formatted pricing array.
     *
     * @return array
     */
    public function getFormattedPricingAttribute(): array
    {
        return [
            'input' => $this->formatCost($this->cost_per_1k_input),
            'output' => $this->formatCost($this->cost_per_1k_output),
            'input_per_million' => $this->cost_per_1k_input * 1000,
            'output_per_million' => $this->cost_per_1k_output * 1000,
        ];
    }

    /**
     * Format a cost value for display.
     *
     * @param float $value
     * @return string
     */
    private function formatCost(float $value): string
    {
        if ($value == 0) {
            return 'Free';
        }

        if ($value < 0.01) {
            return sprintf('$%.6f', $value);
        }

        return sprintf('$%.4f', $value);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Calculate cost for a specific number of tokens.
     *
     * @param int $inputTokens
     * @param int $outputTokens
     * @return float
     */
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        if ($this->is_free_tier) {
            return 0.0;
        }

        $inputCost = ($inputTokens / 1000) * $this->cost_per_1k_input;
        $outputCost = ($outputTokens / 1000) * $this->cost_per_1k_output;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Check if the model has a specific capability.
     *
     * @param string $capability
     * @return bool
     */
    public function hasCapability(string $capability): bool
    {
        return !empty($this->capabilities) && 
               isset($this->capabilities[$capability]) && 
               $this->capabilities[$capability] === true;
    }
}
