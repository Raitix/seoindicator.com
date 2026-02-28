<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KeywordAlertSettings model for per-keyword alert preferences.
 *
 * @property int $id
 * @property int $keyword_id
 * @property bool $enabled
 * @property int|null $custom_threshold
 * @property array|null $alert_events
 * @property array|null $notification_channels
 */
class KeywordAlertSettings extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'enabled' => true,
    ];

    /**
     * Available alert event types.
     */
    public const EVENT_TYPES = [
        'position_drop',
        'position_gain',
        'entered_top_10',
        'left_top_10',
        'serp_feature_gained',
        'serp_feature_lost',
    ];

    /**
     * Available notification channels.
     */
    public const CHANNELS = [
        'email',
        'in_app',
    ];

    protected $fillable = [
        'keyword_id',
        'enabled',
        'custom_threshold',
        'alert_events',
        'notification_channels',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'alert_events' => 'array',
        'notification_channels' => 'array',
    ];

    /**
     * Get the keyword that owns these settings.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Check if a specific event type is enabled.
     */
    public function isEventEnabled(string $eventType): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // If no specific events configured, all are enabled
        if (empty($this->alert_events)) {
            return true;
        }

        return in_array($eventType, $this->alert_events);
    }

    /**
     * Check if a specific channel is enabled.
     */
    public function isChannelEnabled(string $channel): bool
    {
        // If no specific channels configured, use defaults
        if (empty($this->notification_channels)) {
            return in_array($channel, ['email', 'in_app']);
        }

        return in_array($channel, $this->notification_channels);
    }

    /**
     * Get default settings for a new keyword.
     */
    public static function getDefaults(): array
    {
        return [
            'enabled' => true,
            'alert_events' => null, // null = all events
            'notification_channels' => null, // null = all channels
        ];
    }
}
