<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 * SEOAnalysis model for storing SEO analysis results.
 *
 * Stores analysis data with user/session tracking, domain info,
 * scores, and cached analysis data.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $domain
 * @property string $normalized_url
 * @property int $overall_score
 * @property string $status
 * @property string|null $error_message
 * @property array $analysis_data
 * @property string|null $ai_model
 * @property int|null $prompt_tokens
 * @property int|null $response_tokens
 * @property int|null $total_tokens
 * @property array|null $truncation_applied
 * @property string|null $overall_summary
 * @property array|null $category_scores
 * @property int|null $ai_request_id
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\AIAnalysisRequest|null $aiRequest
 *
 * @see TASK-18-2
 * @see TASK-31-11
 * @see SPEC-018
 * @see SPEC-031
 */
class SEOAnalysis extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_analyses';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'domain',
        'normalized_url',
        'overall_score',
        'status',
        'error_message',
        'analysis_data',
        'checked_at',
        'expires_at',
        // AI Unified Analysis fields (TASK-31-11)
        'ai_model',
        'prompt_tokens',
        'response_tokens',
        'total_tokens',
        'truncation_applied',
        'overall_summary',
        'category_scores',
        'ai_request_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'analysis_data' => 'array',
        'overall_score' => 'integer',
        'checked_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // AI Unified Analysis casts (TASK-31-11)
        'truncation_applied' => 'array',
        'category_scores' => 'array',
        'prompt_tokens' => 'integer',
        'response_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    /**
     * Get the user that owns this analysis.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to AI analysis request.
     */
    public function aiRequest(): HasOne
    {
        return $this->hasOne(AIAnalysisRequest::class, 'analysis_id');
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by session.
     */
    public function scopeBySession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope expired analyses.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope not expired analyses.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    /**
     * Scope by domain.
     */
    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope pending analyses.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope completed analyses.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope failed analyses.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope recent analyses (within last N days).
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope unified analyses (with AI model set).
     */
    public function scopeUnified(Builder $query): Builder
    {
        return $query->whereNotNull('ai_model');
    }

    /**
     * Scope legacy analyses (without AI model).
     */
    public function scopeLegacy(Builder $query): Builder
    {
        return $query->whereNull('ai_model');
    }

    /**
     * Mark the analysis as running.
     */
    public function markAsRunning(): void
    {
        $this->update(['status' => self::STATUS_RUNNING]);
    }

    /**
     * Mark the analysis as completed.
     */
    public function markAsCompleted(int $score, array $data): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'overall_score' => $score,
            'analysis_data' => $data,
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark the analysis as completed with unified AI data.
     */
    public function markAsCompletedUnified(array $data): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'overall_score' => $data['overall_score'] ?? 0,
            'overall_summary' => $data['overall_summary'] ?? null,
            'category_scores' => $data['category_scores'] ?? null,
            'analysis_data' => $data['analysis_data'] ?? [],
            'ai_model' => $data['ai_model'] ?? null,
            'prompt_tokens' => $data['prompt_tokens'] ?? null,
            'response_tokens' => $data['response_tokens'] ?? null,
            'total_tokens' => $data['total_tokens'] ?? null,
            'truncation_applied' => $data['truncation_applied'] ?? null,
            'ai_request_id' => $data['ai_request_id'] ?? null,
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark the analysis as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if the analysis is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the analysis is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the analysis is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the analysis is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the analysis has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if analysis was performed with unified AI.
     */
    public function wasUnifiedAnalysis(): bool
    {
        return !empty($this->ai_model);
    }

    /**
     * Get category score by name.
     */
    public function getCategoryScore(string $category): ?int
    {
        return $this->category_scores[$category] ?? null;
    }

    /**
     * Get truncation strategies applied.
     */
    public function getTruncationStrategies(): array
    {
        return $this->truncation_applied ?? [];
    }

    /**
     * Check if content was truncated.
     */
    public function wasTruncated(): bool
    {
        return !empty($this->truncation_applied);
    }

    /**
     * Get token usage summary.
     */
    public function getTokenUsage(): array
    {
        return [
            'prompt' => $this->prompt_tokens,
            'response' => $this->response_tokens,
            'total' => $this->total_tokens,
        ];
    }

    /**
     * Get AI model display name.
     */
    public function getAiModelDisplayName(): string
    {
        return match ($this->ai_model) {
            'google/gemini-2.0-flash-exp:free' => 'Gemini 2.0 Flash',
            'meta-llama/llama-3-8b-instruct:free' => 'Llama 3 8B',
            'mistralai/mistral-7b-instruct:free' => 'Mistral 7B',
            'qwen/qwen-2-7b-instruct:free' => 'Qwen 2 7B',
            default => $this->ai_model ?? 'Unknown',
        };
    }

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
