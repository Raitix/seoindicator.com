<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LocalCompetitor model for tracking local SEO competitors.
 *
 * Represents a competitor that appears in local search results
 * for a business location.
 *
 * @property int $id
 * @property int $location_id
 * @property string $competitor_name
 * @property string|null $competitor_gbp_id
 * @property string|null $competitor_address
 * @property float|null $competitor_rating
 * @property int $competitor_review_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\BusinessLocation $location
 */
class LocalCompetitor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'location_id',
        'competitor_name',
        'competitor_gbp_id',
        'competitor_address',
        'competitor_rating',
        'competitor_review_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'competitor_rating' => 'decimal:1',
        'competitor_review_count' => 'integer',
    ];

    /**
     * Get the business location for this competitor.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Alias for the task/spec naming used in acceptance criteria.
     */
    public function businessLocation(): BelongsTo
    {
        return $this->location();
    }

    /**
     * Get Google Maps URL for this competitor.
     */
    public function getMapsUrl(): string
    {
        if ($this->competitor_gbp_id) {
            return "https://www.google.com/maps/place/?q=place_id:{$this->competitor_gbp_id}";
        }

        if ($this->competitor_address) {
            return 'https://www.google.com/maps/search/' . urlencode($this->competitor_name . ' ' . $this->competitor_address);
        }

        return 'https://www.google.com/maps/search/' . urlencode($this->competitor_name);
    }

    /**
     * Compare this competitor with a business location.
     */
    public function compareWithLocation(BusinessLocation $location): array
    {
        $locationRating = $location->rating ?? 0;
        $locationReviewCount = $location->review_count ?? 0;

        return [
            'rating_diff' => $this->competitor_rating ? ($locationRating - $this->competitor_rating) : null,
            'review_count_diff' => $this->competitor_review_count - $locationReviewCount,
            'competitor_rating' => $this->competitor_rating,
            'location_rating' => $locationRating,
            'competitor_reviews' => $this->competitor_review_count,
            'location_reviews' => $locationReviewCount,
        ];
    }
}
