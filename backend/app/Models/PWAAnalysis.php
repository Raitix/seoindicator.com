<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PWAAnalysis model for storing cached PWA manifest analysis results.
 *
 * @property int $id
 * @property int $site_id
 * @property int|null $project_id
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
        'project_id',
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
     * Get the project that owns the PWA analysis.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to filter by site ID.
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope to filter analyses with manifest.
     */
    public function scopeWithManifest(Builder $query): Builder
    {
        return $query->where('has_manifest', true);
    }

    /**
     * Scope to filter analyses without manifest.
     */
    public function scopeWithoutManifest(Builder $query): Builder
    {
        return $query->where('has_manifest', false);
    }

    /**
     * Scope to filter by readiness level.
     */
    public function scopeByReadinessLevel(Builder $query, string $level): Builder
    {
        return $query->where('readiness_level', $level);
    }

    /**
     * Scope to filter by minimum PWA score.
     */
    public function scopeMinScore(Builder $query, int $minScore): Builder
    {
        return $query->where('pwa_score', '>=', $minScore);
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * Get manifest fields as structured data.
     *
     * @return array<string, array{present: bool, value: mixed}>
     */
    public function getManifestFieldsAttribute(): array
    {
        $manifest = $this->manifest_json ?? [];
        
        $fields = [
            'name' => ['present' => $this->has_name, 'value' => $manifest['name'] ?? null],
            'short_name' => ['present' => $this->has_short_name, 'value' => $manifest['short_name'] ?? null],
            'start_url' => ['present' => $this->has_start_url, 'value' => $manifest['start_url'] ?? null],
            'display' => ['present' => $this->has_display, 'value' => $manifest['display'] ?? null],
            'background_color' => ['present' => $this->has_background_color, 'value' => $manifest['background_color'] ?? null],
            'theme_color' => ['present' => $this->has_theme_color, 'value' => $manifest['theme_color'] ?? null],
            'description' => ['present' => $this->has_description, 'value' => $manifest['description'] ?? null],
        ];

        return $fields;
    }

    /**
     * Get icon validation summary.
     *
     * @return array<string, mixed>
     */
    public function getIconSummaryAttribute(): array
    {
        return [
            'total' => $this->icons_total,
            'valid' => $this->icons_valid,
            'validity_ratio' => $this->getIconValidityRatio(),
            'required' => [
                '192x192' => ['present' => $this->icons_192_present],
                '512x512' => ['present' => $this->icons_512_present],
                '180x180' => ['present' => $this->icons_180_present],
            ],
            'errors' => $this->icon_errors ?? [],
        ];
    }
}
