<?php

namespace App\Models\AIVisibility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Mention Analytics Model.
 *
 * Stores aggregated analytics for brand mentions.
 *
 * @property int $id
 * @property int $project_id
 * @property string $platform
 * @property string|null $period
 * @property int $total_queries
 * @property int $brand_mentions
 * @property float $mention_rate
 * @property int $positive_mentions
 * @property int $neutral_mentions
 * @property int $negative_mentions
 * @property int $mixed_mentions
 * @property float $average_sentiment
 * @property float $avg_sentiment_score Alias for average_sentiment
 * @property array $competitor_mentions
 * @property array $competitor_stats
 * @property float $share_of_voice
 * @property array|null $top_query_categories
 * @property string|null $mention_trend
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property \Illuminate\Support\Carbon|null $aggregated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-34-2
 * @see SPEC-034
 */
class AIMentionAnalytics extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_mention_analytics';

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'total_queries' => 0,
        'brand_mentions' => 0,
        'mention_rate' => 0.00,
        'share_of_voice' => 0.00,
        'average_sentiment' => 0.00,
        'positive_mentions' => 0,
        'neutral_mentions' => 0,
        'negative_mentions' => 0,
        'mixed_mentions' => 0,
        'mention_trend' => 'stable',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'platform',
        'period',
        'total_queries',
        'brand_mentions',
        'mention_rate',
        'positive_mentions',
        'neutral_mentions',
        'negative_mentions',
        'mixed_mentions',
        'average_sentiment',
        'avg_sentiment_score', // Alias accessor
        'competitor_mentions',
        'competitor_stats',
        'share_of_voice',
        'top_query_categories',
        'mention_trend',
        'period_start',
        'period_end',
        'aggregated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'mention_rate' => 'decimal:2',
            'average_sentiment' => 'decimal:2',
            'share_of_voice' => 'decimal:2',
            'competitor_mentions' => 'array',
            'competitor_stats' => 'array',
            'top_query_categories' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'aggregated_at' => 'datetime',
        ];
    }

    // ==================== ALIAS ACCESSORS/MUTATORS ====================

    /**
     * Get the average sentiment score (alias for average_sentiment).
     */
    public function getAvgSentimentScoreAttribute(): float
    {
        return $this->average_sentiment ?? 0.00;
    }

    /**
     * Set the average sentiment score (alias for average_sentiment).
     */
    public function setAvgSentimentScoreAttribute(float $value): void
    {
        $this->attributes['average_sentiment'] = $value;
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the project that owns this analytics.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AIVisibilityProject::class, 'project_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get the sentiment breakdown as an array.
     */
    public function getSentimentBreakdown(): array
    {
        return [
            'positive' => $this->positive_mentions,
            'neutral' => $this->neutral_mentions,
            'negative' => $this->negative_mentions,
        ];
    }

    /**
     * Calculate share of voice (percentage of total mentions).
     */
    public function calculateShareOfVoice(int $totalMentions): float
    {
        if ($totalMentions === 0) {
            return 0.0;
        }

        return round(($this->brand_mentions / $totalMentions) * 100, 2);
    }
}
