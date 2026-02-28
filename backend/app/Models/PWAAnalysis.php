<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PWAAnalysis model for storing cached PWA manifest analysis results.
 *
 * @property int $id
 * @property int $site_id
 * @property bool $has_manifest
 * @property string|null $manifest_url
 * @property bool $manifest_valid
 * @property array|null $manifest_json
 * @property bool $has_name
 * @property bool $has_short_name
 * @property bool $has_start_url
 * @property bool $has_display
 * @property bool $has_background_color
 * @property bool $has_theme_color
 * @property bool $has_description
 * @property int $icons_total
 * @property int $icons_valid
 * @property bool $icons_192_present
 * @property bool $icons_512_present
 * @property bool $icons_180_present
 * @property array|null $icon_errors
 * @property int $pwa_score
 * @property string $readiness_level
 * @property \Illuminate\Support\Carbon|null $analyzed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @see TASK-24-1
 * @see SPEC-024
 * @see FEAT-024
 */
class PWAAnalysis extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'pwa_analyses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'has_manifest',
        'manifest_url',
        'manifest_valid',
        'manifest_json',
        'has_name',
        'has_short_name',
        'has_start_url',
        'has_display',
        'has_background_color',
        'has_theme_color',
        'has_description',
        'icons_total',
        'icons_valid',
        'icons_192_present',
        'icons_512_present',
        'icons_180_present',
        'icon_errors',
        'pwa_score',
        'readiness_level',
        'analyzed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'manifest_json' => 'array',
        'icon_errors' => 'array',
        'has_manifest' => 'boolean',
        'manifest_valid' => 'boolean',
        'has_name' => 'boolean',
        'has_short_name' => 'boolean',
        'has_start_url' => 'boolean',
        'has_display' => 'boolean',
        'has_background_color' => 'boolean',
        'has_theme_color' => 'boolean',
        'has_description' => 'boolean',
        'icons_192_present' => 'boolean',
        'icons_512_present' => 'boolean',
        'icons_180_present' => 'boolean',
        'icons_total' => 'integer',
        'icons_valid' => 'integer',
        'pwa_score' => 'integer',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the site that owns the PWA analysis.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Determine if the manifest has all required fields.
     */
    public function hasAllRequiredFields(): bool
    {
        return $this->has_name
            && $this->has_short_name
            && $this->has_start_url
            && $this->has_display;
    }

    /**
     * Determine if all required icons are present.
     */
    public function hasAllRequiredIcons(): bool
    {
        return $this->icons_192_present && $this->icons_512_present;
    }

    /**
     * Get the icon validity ratio.
     */
    public function getIconValidityRatio(): float
    {
        if ($this->icons_total === 0) {
            return 0.0;
        }

        return $this->icons_valid / $this->icons_total;
    }

    /**
     * Check if the PWA is installable (minimum requirements met).
     */
    public function isInstallable(): bool
    {
        return $this->has_manifest
            && $this->manifest_valid
            && $this->hasAllRequiredFields()
            && $this->icons_192_present;
    }
}
