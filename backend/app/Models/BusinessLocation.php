<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessLocation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'business_locations';

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

    protected static function booted(): void
    {
        static::deleting(function (self $location): void {
            $location->alerts()->delete();
            $location->localRankings()->delete();
            $location->localCompetitors()->delete();
            $location->reviews()->delete();
            $location->insights()->delete();
            $location->posts()->delete();
        });
    }

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
     * Get the insights for this location.
     */
    public function insights(): HasMany
    {
        return $this->hasMany(GBPInsight::class, 'location_id');
    }

    /**
     * Get the reviews for this location.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(GBPReview::class, 'location_id');
    }

    /**
     * Get the local rankings for this location.
     */
    public function rankings(): HasMany
    {
        return $this->localRankings();
    }

    /**
     * Get the local competitors for this location.
     */
    public function competitors(): HasMany
    {
        return $this->localCompetitors();
    }

    /**
     * Get the posts for this location.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(GBPPost::class, 'location_id');
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
     * Scope alias for connected GBP locations.
     */
    public function scopeConnected($query)
    {
        return $this->scopeGbpConnected($query);
    }

    /**
     * Scope locations to a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
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
        return (bool) ($this->gbp_connected ?? false);
    }

    /**
     * Get a human-friendly full address label.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_values(array_filter([
            blank($this->name) ? null : $this->name,
            blank($this->address) ? null : $this->address,
        ]));

        return implode(' - ', $parts);
    }

    /**
     * Get coordinates as an associative array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Determine whether the location has GBP connected.
     */
    public function hasConnectedGBP(): bool
    {
        return $this->is_gbp_connected;
    }

    /**
     * Determine whether the location has a valid GBP token.
     */
    public function hasValidGBPToken(): bool
    {
        return filled($this->gbp_access_token)
            && $this->gbp_token_expires_at !== null
            && $this->gbp_token_expires_at->isFuture();
    }

    /**
     * Get a Google Maps URL for the location.
     */
    public function getMapsUrl(): string
    {
        if (filled($this->gbp_place_id)) {
            return 'https://www.google.com/maps/place/?q=place_id:' . urlencode($this->gbp_place_id);
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            return 'https://www.google.com/maps?q=' . $this->latitude . ',' . $this->longitude;
        }

        return 'https://www.google.com/maps/search/' . urlencode(trim($this->full_address));
    }

    /**
     * Get a value from the settings payload.
     */
    public function getSettingsValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
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

    public function setGbpConnectedAttribute($value): void
    {
        $this->attributes['gbp_connected'] = $value ?? false;
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = $value ?? true;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value ?? '';
    }

    /**
     * Build a has-many relation for an existing table that does not yet have a dedicated model task completed.
     */
    protected function genericHasMany(string $table): HasMany
    {
        $related = new class extends Model
        {
            public $timestamps = false;

            protected $guarded = [];
        };

        $related->setTable($table);
        $related->setConnection($this->getConnectionName());

        return $this->newHasMany(
            $related->newQuery(),
            $this,
            'location_id',
            $this->getKeyName()
        );
    }
}
