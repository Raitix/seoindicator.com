<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnalysisHistory model for tracking tool analyses.
 *
 * Stores results, status, and metadata for each analysis performed by users.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $site_id
 * @property string $tool_name
 * @property string $input_url
 * @property string $status
 * @property array|null $results
 * @property string|null $error_message
 * @property int|null $analysis_duration_ms
 * @property \Illuminate\Support\Carbon|null $analyzed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Site|null $site
 */
class AnalysisHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analysis_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'tool_name',
        'input_url',
        'status',
        'results',
        'error_message',
        'analysis_duration_ms',
        'analyzed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'results' => 'array',
        'analyzed_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the user that owns the analysis.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site associated with the analysis.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by tool name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $toolName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTool($query, string $toolName)
    {
        return $query->where('tool_name', $toolName);
    }

    /**
     * Scope a query to filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to order by analyzed_at descending (latest first).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('analyzed_at', 'desc');
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Support\Carbon|string $startDate
     * @param \Illuminate\Support\Carbon|string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('analyzed_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Get the formatted duration string.
     *
     * @return string|null
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if ($this->analysis_duration_ms === null) {
            return null;
        }

        $ms = $this->analysis_duration_ms;

        if ($ms < 1000) {
            return $ms . 'ms';
        }

        return number_format($ms / 1000, 2) . 's';
    }

    /**
     * Check if the analysis is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the analysis is failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the analysis is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
