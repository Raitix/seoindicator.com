<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification model for in-app notifications.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int|null $keyword_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $read_at
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * SERP feature alert types.
     */
    public const TYPE_SERP_FEATURE_GAINED = 'serp_feature_gained';
    public const TYPE_SERP_FEATURE_LOST = 'serp_feature_lost';

    /**
     * Position change alert types.
     */
    public const TYPE_POSITION_DROP = 'position_drop';
    public const TYPE_POSITION_GAIN = 'position_gain';
    public const TYPE_ENTERED_TOP_10 = 'entered_top_10';
    public const TYPE_LEFT_TOP_10 = 'left_top_10';

    /**
     * All alert types.
     */
    public const ALERT_TYPES = [
        self::TYPE_SERP_FEATURE_GAINED,
        self::TYPE_SERP_FEATURE_LOST,
        self::TYPE_POSITION_DROP,
        self::TYPE_POSITION_GAIN,
        self::TYPE_ENTERED_TOP_10,
        self::TYPE_LEFT_TOP_10,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'keyword_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the keyword associated with this notification.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope SERP feature notifications.
     */
    public function scopeSerpFeature($query)
    {
        return $query->whereIn('type', [
            self::TYPE_SERP_FEATURE_GAINED,
            self::TYPE_SERP_FEATURE_LOST,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return true;
        }

        $this->read_at = now();
        return $this->save();
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        $this->read_at = null;
        return $this->save();
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Format for API response.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'keyword' => $this->keyword ? [
                'id' => $this->keyword->id,
                'keyword' => $this->keyword->keyword,
            ] : null,
            'data' => $this->data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
