<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SiteNotificationSettings model for per-site notification preferences.
 *
 * @property int $id
 * @property int $site_id
 * @property bool $email_notifications
 * @property bool $in_app_notifications
 * @property bool $serp_feature_alerts
 */
class SiteNotificationSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'email_notifications',
        'in_app_notifications',
        'serp_feature_alerts',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'in_app_notifications' => 'boolean',
        'serp_feature_alerts' => 'boolean',
    ];

    /**
     * Get the site that owns these settings.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get default settings for a new site.
     */
    public static function getDefaults(): array
    {
        return [
            'email_notifications' => true,
            'in_app_notifications' => true,
            'serp_feature_alerts' => true,
        ];
    }

    /**
     * Check if SERP feature alerts are enabled for this site.
     */
    public function isSerpFeatureAlertsEnabled(): bool
    {
        return $this->serp_feature_alerts && ($this->email_notifications || $this->in_app_notifications);
    }
}
