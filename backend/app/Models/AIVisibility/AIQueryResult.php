<?php

namespace App\Models\AIVisibility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Query Result Model.
 *
 * Stores the results from AI platform queries.
 *
 * @property int $id
 * @property int $project_id
 * @property string $platform
 * @property string $query_text
 * @property string|null $response_text
 * @property string|null $model_used
 * @property int|null $tokens_used
 * @property int|null $latency_ms
 * @property bool $success
 * @property string|null $error_message
 * @property array $mentions
 * @property bool $brand_mentioned
 * @property array $competitor_mentions
 * @property string|null $query_category
 * @property string|null $sentiment
 * @property float|null $sentiment_score
 * @property \Illuminate\Support\Carbon|null $queried_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-34-2
 * @see SPEC-034
 */
class AIQueryResult extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AIVisibility\AIQueryResultFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_query_results';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'platform',
        'query_text',
        'response_text',
        'model_used',
        'tokens_used',
        'latency_ms',
        'success',
        'error_message',
        'mentions',
        'brand_mentioned',
        'competitor_mentions',
        'query_category',
        'sentiment',
        'sentiment_score',
        'queried_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'mentions' => 'array',
            'brand_mentioned' => 'boolean',
            'competitor_mentions' => 'array',
            'success' => 'boolean',
            'sentiment_score' => 'decimal:2',
            'queried_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns this result.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AIVisibilityProject::class, 'project_id');
    }

    /**
     * Scope to get only successful results.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInPeriod($query, string $period)
    {
        $now = now();
        
        return match ($period) {
            'day' => $query->where('queried_at', '>=', $now->subDay()),
            'week' => $query->where('queried_at', '>=', $now->subWeek()),
            'month' => $query->where('queried_at', '>=', $now->subMonth()),
            default => $query,
        };
    }

    /**
     * Get the mention count.
     */
    public function getMentionCount(): int
    {
        return count($this->mentions ?? []);
    }

    /**
     * Check if a specific brand is mentioned.
     */
    public function mentionsBrand(string $brand): bool
    {
        foreach ($this->mentions ?? [] as $mention) {
            if (isset($mention['brand']) && strcasecmp($mention['brand'], $brand) === 0) {
                return true;
            }
        }
        
        return false;
    }
}
