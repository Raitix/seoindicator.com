<?php

namespace App\Models\AIVisibility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Mention Model.
 *
 * Stores individual mention records for brand and competitor tracking.
 * This is a per-mention tracking table (distinct from AIMentionAnalytics aggregation).
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $query_result_id
 * @property string $entity_type (brand|competitor)
 * @property string $entity_name
 * @property string $mention_type (recommendation|comparison|mention|citation|neutral)
 * @property string $sentiment (positive|negative|neutral|mixed)
 * @property float|null $sentiment_score
 * @property string|null $context
 * @property string $platform
 * @property \Illuminate\Support\Carbon|null $mentioned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-34-18
 * @see SPEC-034
 */
class AIMention extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_mentions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'query_result_id',
        'entity_type',
        'entity_name',
        'mention_type',
        'sentiment',
        'sentiment_score',
        'context',
        'platform',
        'mentioned_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sentiment_score' => 'decimal:2',
            'mentioned_at' => 'datetime',
        ];
    }

    /**
     * Entity type constants.
     */
    public const ENTITY_TYPE_BRAND = 'brand';
    public const ENTITY_TYPE_COMPETITOR = 'competitor';

    /**
     * Mention type constants.
     */
    public const MENTION_TYPE_RECOMMENDATION = 'recommendation';
    public const MENTION_TYPE_COMPARISON = 'comparison';
    public const MENTION_TYPE_MENTION = 'mention';
    public const MENTION_TYPE_CITATION = 'citation';
    public const MENTION_TYPE_NEUTRAL = 'neutral';

    /**
     * Sentiment constants.
     */
    public const SENTIMENT_POSITIVE = 'positive';
    public const SENTIMENT_NEGATIVE = 'negative';
    public const SENTIMENT_NEUTRAL = 'neutral';
    public const SENTIMENT_MIXED = 'mixed';

    /**
     * Get the project that owns this mention.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AIVisibilityProject::class, 'project_id');
    }

    /**
     * Get the query result that generated this mention.
     */
    public function queryResult(): BelongsTo
    {
        return $this->belongsTo(AIQueryResult::class, 'query_result_id');
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by brand mentions.
     */
    public function scopeBrandMentions($query)
    {
        return $query->where('entity_type', self::ENTITY_TYPE_BRAND);
    }

    /**
     * Scope to filter by competitor mentions.
     */
    public function scopeCompetitorMentions($query)
    {
        return $query->where('entity_type', self::ENTITY_TYPE_COMPETITOR);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by sentiment.
     */
    public function scopeWithSentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    /**
     * Scope to filter by entity name.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('mentioned_at', [$startDate, $endDate]);
    }

    /**
     * Check if this is a brand mention.
     */
    public function isBrandMention(): bool
    {
        return $this->entity_type === self::ENTITY_TYPE_BRAND;
    }

    /**
     * Check if this is a competitor mention.
     */
    public function isCompetitorMention(): bool
    {
        return $this->entity_type === self::ENTITY_TYPE_COMPETITOR;
    }

    /**
     * Check if sentiment is positive.
     */
    public function isPositive(): bool
    {
        return $this->sentiment === self::SENTIMENT_POSITIVE;
    }

    /**
     * Check if sentiment is negative.
     */
    public function isNegative(): bool
    {
        return $this->sentiment === self::SENTIMENT_NEGATIVE;
    }

    /**
     * Check if this is a recommendation.
     */
    public function isRecommendation(): bool
    {
        return $this->mention_type === self::MENTION_TYPE_RECOMMENDATION;
    }
}
