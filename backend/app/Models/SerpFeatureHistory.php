<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SerpFeatureHistory model for tracking SERP feature changes.
 *
 * @property int $id
 * @property int $keyword_id
 * @property string $feature_type
 * @property string $action 'gained' or 'lost'
 * @property string|null $previous_url
 * @property string|null $current_url
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Keyword $keyword
 */
class SerpFeatureHistory extends Model
{
    use HasFactory;

    protected $table = 'serp_feature_history';

    protected $fillable = [
        'keyword_id',
        'feature_type',
        'action',
        'previous_url',
        'current_url',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];

    /**
     * Get the keyword that owns this history entry.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope by action type.
     */
    public function scopeGained($query)
    {
        return $query->where('action', 'gained');
    }

    /**
     * Scope by action type.
     */
    public function scopeLost($query)
    {
        return $query->where('action', 'lost');
    }

    /**
     * Scope by feature type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('feature_type', $type);
    }

    /**
     * Scope by keyword.
     */
    public function scopeForKeyword($query, int $keywordId)
    {
        return $query->where('keyword_id', $keywordId);
    }

    /**
     * Scope for recent changes within hours.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('detected_at', '>=', now()->subHours($hours));
    }

    /**
     * Format for API response.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'keyword_id' => $this->keyword_id,
            'feature_type' => $this->feature_type,
            'action' => $this->action,
            'previous_url' => $this->previous_url,
            'current_url' => $this->current_url,
            'detected_at' => $this->detected_at->toIso8601String(),
        ];
    }
}
