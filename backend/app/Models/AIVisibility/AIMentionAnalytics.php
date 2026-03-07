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
 * @property int $total_queries
 * @property int $brand_mentions
 * @property float $mention_rate
 * @property int $positive_mentions
 * @property int $neutral_mentions
 * @property int $negative_mentions
 * @property array $competitor_stats
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
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
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'platform',
        'total_queries',
        'brand_mentions',
        'mention_rate',
        'positive_mentions',
        'neutral_mentions',
        'negative_mentions',
        'competitor_stats',
        'period_start',
        'period_end',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'mention_rate' => 'decimal:2',
            'competitor_stats' => 'array',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    /**
     * Get the project that owns this analytics.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AIVisibilityProject::class, 'project_id');
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

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
