<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LocalRanking;
use App\Models\LocalCompetitor;

class BusinessLocation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'gbp_place_id',
        'gbp_access_token',
        'gbp_refresh_token',
        'gbp_token_expires_at',
        'name',
        'address',
        'phone',
        'website',
        'category',
        'latitude',
        'longitude',
        'gbp_connected',
        'settings',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gbp_connected' => 'boolean',
        'gbp_token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * @Security GBP tokens are NEVER exposed in API responses
     */
    protected $hidden = [
        'gbp_access_token',
        'gbp_refresh_token',
    ];

    /**
     * Get the user that owns this business location.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the site this location belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the alerts for this location.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(LocalSEOAlert::class, 'location_id');
    }

    /**
     * Get the local rankings for this location.
     */
    public function localRankings(): HasMany
    {
        return $this->hasMany(LocalRanking::class, 'location_id');
    }

    /**
     * Get the local competitors for this location.
     */
    public function localCompetitors(): HasMany
    {
        return $this->hasMany(LocalCompetitor::class, 'location_id');
    }

    /**
     * Scope to get only active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only GBP-connected locations.
     */
    public function scopeGbpConnected($query)
    {
        return $query->where('gbp_connected', true);
    }

    /**
     * Alias for gbp_place_id (used in tests).
     */
    public function getGoogleLocationIdAttribute(): ?string
    {
        return $this->gbp_place_id;
    }

    /**
     * Alias for gbp_connected (used in tests).
     * 
     * @Task TASK-35-13
     */
    public function getIsGbpConnectedAttribute(): bool
    {
        return $this->gbp_connected ?? false;
    }

    /**
     * Setter for is_gbp_connected that maps to gbp_connected column.
     * This allows tests and factories to use is_gbp_connected as an alias.
     * 
     * @Task TASK-35-13
     */
    public function setIsGbpConnectedAttribute(bool $value): void
    {
        $this->attributes['gbp_connected'] = $value;
    }
}
