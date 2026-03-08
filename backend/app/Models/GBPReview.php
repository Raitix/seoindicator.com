<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @Task TASK-35-14
 * @Description Model for storing Google Business Profile reviews
 *
 * @property int $id
 * @property int $location_id
 * @property string $google_review_id
 * @property string|null $author_name
 * @property string|null $author_photo
 * @property int $rating
 * @property string|null $review_text
 * @property string|null $review_text_sentiment
 * @property string|null $owner_response
 * @property \Carbon\Carbon|null $owner_response_date
 * @property \Carbon\Carbon|null $review_date
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class GBPReview extends Model
{
    use HasFactory;

    protected $table = 'gbp_reviews';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'location_id',
        'google_review_id',
        'author_name',
        'author_photo',
        'rating',
        'review_text',
        'review_text_sentiment',
        'owner_response',
        'owner_response_date',
        'review_date',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'location_id' => 'integer',
        'rating' => 'integer',
        'owner_response_date' => 'datetime',
        'review_date' => 'datetime',
    ];

    /**
     * Get the location that owns this review.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope to filter by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to filter by sentiment.
     */
    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('review_text_sentiment', $sentiment);
    }

    /**
     * Scope to filter by has_response.
     */
    public function scopeHasResponse($query, bool $hasResponse)
    {
        if ($hasResponse) {
            return $query->whereNotNull('owner_response');
        }
        return $query->whereNull('owner_response');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->whereDate('review_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('review_date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to order by specified field.
     */
    public function scopeOrderByField($query, string $sort)
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (in_array($field, ['date', 'rating'])) {
            $field = $field === 'date' ? 'review_date' : $field;
            return $query->orderBy($field, $direction);
        }

        return $query->orderBy('review_date', 'desc');
    }

    /**
     * Check if review has owner response.
     */
    public function hasResponse(): bool
    {
        return !empty($this->owner_response);
    }
}
