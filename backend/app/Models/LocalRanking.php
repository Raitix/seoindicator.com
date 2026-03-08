<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LocalRanking model for tracking local SEO rankings.
 *
 * Represents a ranking snapshot for a business location
 * in local search results (local pack and local organic).
 *
 * @property int $id
 * @property int $location_id
 * @property int $keyword_id
 * @property string $search_location
 * @property float|null $search_lat
 * @property float|null $search_lng
 * @property int|null $local_pack_position
 * @property int|null $local_organic_position
 * @property array|null $competitors_in_pack
 * @property \Illuminate\Support\Carbon $checked_at
 *
 * @property-read \App\Models\BusinessLocation $location
 * @property-read \App\Models\Keyword $keyword
 */
class LocalRanking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'location_id',
        'keyword_id',
        'search_location',
        'search_lat',
        'search_lng',
        'local_pack_position',
        'local_organic_position',
        'competitors_in_pack',
        'checked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'search_lat' => 'decimal:8',
        'search_lng' => 'decimal:8',
        'local_pack_position' => 'integer',
        'local_organic_position' => 'integer',
        'competitors_in_pack' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Get the business location for this ranking.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Get the keyword for this ranking.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope for local pack positions (positions 1-3).
     */
    public function scopeInPack($query)
    {
        return $query->whereNotNull('local_pack_position')
            ->where('local_pack_position', '<=', 3);
    }

    /**
     * Scope for date range filtering.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('checked_at', [$startDate, $endDate]);
    }

    /**
     * Check if business is in local pack.
     */
    public function inLocalPack(): bool
    {
        return $this->local_pack_position !== null && $this->local_pack_position <= 3;
    }

    /**
     * Get position change from previous check.
     */
    public function getPositionChange(): ?int
    {
        $previous = static::where('location_id', $this->location_id)
            ->where('keyword_id', $this->keyword_id)
            ->where('checked_at', '<', $this->checked_at)
            ->orderByDesc('checked_at')
            ->first();

        if (!$previous || $previous->local_pack_position === null || $this->local_pack_position === null) {
            return null;
        }

        return $previous->local_pack_position - $this->local_pack_position;
    }
}
