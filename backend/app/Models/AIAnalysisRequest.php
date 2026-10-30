<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AIAnalysisRequest model for tracking AI analysis metadata.
 *
 * Stores detailed information about AI API calls including
 * token usage, latency, and truncation strategies.
 *
 * @property int $id
 * @property int $analysis_id
 * @property string $model
 * @property int|null $prompt_tokens
 * @property int|null $response_tokens
 * @property int|null $total_tokens
 * @property int|null $latency_ms
 * @property array|null $truncation_strategies
 * @property string|null $request_hash
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\SEOAnalysis $analysis
 *
 * @see TASK-31-11
 * @see SPEC-031
 */
class AIAnalysisRequest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_analysis_requests';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'analysis_id',
        'model',
        'prompt_tokens',
        'response_tokens',
        'total_tokens',
        'latency_ms',
        'truncation_strategies',
        'request_hash',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'truncation_strategies' => 'array',
        'prompt_tokens' => 'integer',
        'response_tokens' => 'integer',
        'total_tokens' => 'integer',
        'latency_ms' => 'integer',
        'analysis_id' => 'integer',
    ];

    /**
     * Relationship to SEO analysis.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(SEOAnalysis::class, 'analysis_id');
    }

    /**
     * Check if request was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if request failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if content was truncated.
     */
    public function wasTruncated(): bool
    {
        return !empty($this->truncation_strategies);
    }

    /**
     * Get latency in seconds.
     */
    public function getLatencySeconds(): float
    {
        return ($this->latency_ms ?? 0) / 1000;
    }

    /**
     * Scope for successful requests.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope by model.
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(array $metadata = []): void
    {
        $this->update(array_merge(
            ['status' => self::STATUS_COMPLETED],
            $metadata
        ));
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}
