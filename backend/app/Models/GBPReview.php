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
 * @property string $gbp_review_id
 * @property int $rating
 * @property string|null $text
 * @property string $author
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string $sentiment
 * @property bool $has_response
 * @property string|null $response_text
 * @property \Carbon\Carbon|null $responded_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class GBPReview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'location_id',
        'gbp_review_id',
        'rating',
        'text',
        'author',
        'reviewed_at',
        'sentiment',
        'has_response',
        'response_text',
        'responded_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'location_id' => 'integer',
        'rating' => 'integer',
        'reviewed_at' => 'datetime',
        'has_response' => 'boolean',
        'responded_at' => 'datetime',
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
        return $query->where('sentiment', $sentiment);
    }

    /**
     * Scope to filter by has_response.
     */
    public function scopeHasResponse($query, bool $hasResponse)
    {
        return $query->where('has_response', $hasResponse);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->whereDate('reviewed_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('reviewed_at', '<=', $endDate);
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
            $field = $field === 'date' ? 'reviewed_at' : $field;
            return $query->orderBy($field, $direction);
        }

        return $query->orderBy('reviewed_at', 'desc');
    }
}
