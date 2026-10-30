<?php

namespace App\Models;

/**
 * Location model - Alias for BusinessLocation
 *
 * This model provides a convenient alias for BusinessLocation
 * to match API naming conventions.
 *
 * @Task TASK-35-14
 */
class Location extends BusinessLocation
{
    /**
     * The table associated with the model.
     * Explicitly set to match parent class table.
     */
    protected $table = 'business_locations';

    /**
     * The attributes that should be accessed via aliases.
     */
    protected $appends = ['gbp_location_id', 'is_gbp_connected'];

    /**
     * Get the GBP location ID (alias for gbp_place_id).
     */
    public function getGbpLocationIdAttribute(): ?string
    {
        return $this->gbp_place_id;
    }

    /**
     * Set the GBP location ID (alias for gbp_place_id).
     */
    public function setGbpLocationIdAttribute(?string $value): void
    {
        $this->attributes['gbp_place_id'] = $value;
    }

    /**
     * Get the is_gbp_connected attribute (alias for gbp_connected).
     */
    public function getIsGbpConnectedAttribute(): bool
    {
        return $this->gbp_connected;
    }

    /**
     * Set the is_gbp_connected attribute (alias for gbp_connected).
     */
    public function setIsGbpConnectedAttribute(bool $value): void
    {
        $this->attributes['gbp_connected'] = $value;
    }
}
