<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Site model for tracked websites.
 *
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property string|null $name
 * @property bool $enable_serp_features
 */
class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'name',
        'enable_serp_features',
    ];

    protected $casts = [
        'enable_serp_features' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function keywordSuggestions(): HasMany
    {
        return $this->hasMany(KeywordSuggestion::class);
    }

    public function backlinkSummaries(): HasMany
    {
        return $this->hasMany(BacklinkSummary::class);
    }

    /**
     * Get the PWA analyses for this site.
     */
    public function pwaAnalyses(): HasMany
    {
        return $this->hasMany(PWAAnalysis::class);
    }

    /**
     * Get the latest PWA analysis for this site.
     */
    public function latestPwaAnalysis()
    {
        return $this->hasOne(PWAAnalysis::class)->latestOfMany('analyzed_at');
    }

    /**
     * Get the analysis history for this site.
     */
    public function analysisHistory(): HasMany
    {
        return $this->hasMany(AnalysisHistory::class);
    }
}
