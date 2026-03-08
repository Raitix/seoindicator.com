<?php

namespace App\Models\AIVisibility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Visibility Alert Model.
 *
 * Stores alerts triggered by threshold breaches.
 *
 * @property int $id
 * @property int $project_id
 * @property string $alert_type
 * @property float $threshold
 * @property float $previous_value
 * @property float $current_value
 * @property float $change_percent
 * @property \Illuminate\Support\Carbon $triggered_at
 * @property bool $is_read
 * @property bool $is_resolved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-34-2
 * @see SPEC-034
 */
class AIVisibilityAlert extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AIVisibility\AIVisibilityAlertFactory::new();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $alert) {
            if (empty($alert->triggered_at)) {
                $alert->triggered_at = now();
            }
        });
    }

    /**
     * Alert type constants.
     */
    public const TYPE_MENTION_SPIKE = 'mention_spike';
    public const TYPE_MENTION_DROP = 'mention_drop';
    public const TYPE_COMPETITOR_GAIN = 'competitor_gain';

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_visibility_alerts';

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_read' => false,
        'is_resolved' => false,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'alert_type',
        'threshold',
        'previous_value',
        'current_value',
        'change_percent',
        'triggered_at',
        'is_read',
        'is_resolved',
        'resolved_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'threshold' => 'decimal:2',
            'previous_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'change_percent' => 'decimal:2',
            'triggered_at' => 'datetime',
            'is_read' => 'boolean',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the project that owns this alert.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(AIVisibilityProject::class, 'project_id');
    }

    /**
     * Scope to get unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark the alert as resolved.
     */
    public function markAsResolved(): bool
    {
        return $this->update(['is_resolved' => true]);
    }

    /**
     * Check if this is a mention spike alert.
     */
    public function isMentionSpike(): bool
    {
        return $this->alert_type === self::TYPE_MENTION_SPIKE;
    }

    /**
     * Check if this is a mention drop alert.
     */
    public function isMentionDrop(): bool
    {
        return $this->alert_type === self::TYPE_MENTION_DROP;
    }

    /**
     * Check if this is a competitor gain alert.
     */
    public function isCompetitorGain(): bool
    {
        return $this->alert_type === self::TYPE_COMPETITOR_GAIN;
    }
}
