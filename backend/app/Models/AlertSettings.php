<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AlertSettings model for user global alert preferences.
 *
 * @property int $id
 * @property int $user_id
 * @property bool $global_enabled
 * @property int $default_threshold
 * @property string|null $quiet_hours_start
 * @property string|null $quiet_hours_end
 * @property string $digest_mode
 * @property bool $serp_feature_alerts_enabled
 * @property array|null $serp_feature_types
 */
class AlertSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'global_enabled',
        'default_threshold',
        'quiet_hours_start',
        'quiet_hours_end',
        'digest_mode',
        'serp_feature_alerts_enabled',
        'serp_feature_types',
    ];

    protected $casts = [
        'global_enabled' => 'boolean',
        'serp_feature_alerts_enabled' => 'boolean',
        'serp_feature_types' => 'array',
    ];

    /**
     * Get the user that owns these settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if SERP feature alerts are enabled for a specific feature type.
     */
    public function isSerpFeatureAlertEnabled(string $featureType): bool
    {
        if (!$this->serp_feature_alerts_enabled) {
            return false;
        }

        // If no specific types configured, all are enabled
        if (empty($this->serp_feature_types)) {
            return true;
        }

        return in_array($featureType, $this->serp_feature_types);
    }

    /**
     * Check if current time is within quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Handle overnight quiet hours (e.g., 22:00 - 07:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Get default settings for a new user.
     */
    public static function getDefaults(): array
    {
        return [
            'global_enabled' => true,
            'default_threshold' => 3,
            'digest_mode' => 'instant',
            'serp_feature_alerts_enabled' => true,
            'serp_feature_types' => null,
        ];
    }
}
