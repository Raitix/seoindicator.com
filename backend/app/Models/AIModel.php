<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AI Model for billing and model selection.
 * 
 * Stores AI model information including pricing, capabilities,
 * and free tier availability synced from OpenRouter API.
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
     * Scope a query to order by display name.
     */
    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('display_name', $direction);
    }

    /**
     * Get the estimated cost per analysis.
     * Assumes ~2000 input tokens + ~500 output tokens per analysis.
     *
     * @return float
     */
    public function getCostPerAnalysisEstimateAttribute(): float
    {
        // Default analysis: 2000 input tokens + 500 output tokens
        $inputCost = (2000 / 1000) * (float) $this->cost_per_1k_input;
        $outputCost = (500 / 1000) * (float) $this->cost_per_1k_output;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get formatted pricing information.
     *
     * @return array
     */
    public function getFormattedPricingAttribute(): array
    {
        return [
            'input' => '$' . number_format((float) $this->cost_per_1k_input, 6) . ' per 1K tokens',
            'output' => '$' . number_format((float) $this->cost_per_1k_output, 6) . ' per 1K tokens',
            'input_per_1m' => round((float) $this->cost_per_1k_input * 1000, 2),
            'output_per_1m' => round((float) $this->cost_per_1k_output * 1000, 2),
        ];
    }

    /**
     * Check if the model has vision capability.
     */
    public function hasVision(): bool
    {
        return !empty($this->capabilities['vision']);
    }

    /**
     * Check if the model has tools/function calling capability.
     */
    public function hasTools(): bool
    {
        return !empty($this->capabilities['tools']);
    }
}
