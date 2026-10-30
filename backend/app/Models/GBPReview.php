<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

class GBPReview extends Model
{
    use HasFactory;

    protected $table = 'gbp_reviews';

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

    protected $casts = [
        'location_id' => 'integer',
        'rating' => 'integer',
        'owner_response_date' => 'datetime',
        'review_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            if (($review->rating ?? null) === null || $review->rating < 1 || $review->rating > 5) {
                throw new QueryException(
                    $review->getConnection()->getName(),
                    'gbp_reviews validation',
                    ['rating' => $review->rating],
                    new \InvalidArgumentException('rating must be between 1 and 5.')
                );
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopeNeutral($query)
    {
        return $query->where('rating', 3);
    }

    public function scopeUnresponded($query)
    {
        return $query->whereNull('owner_response');
    }

    public function scopeResponded($query)
    {
        return $query->whereNotNull('owner_response');
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('review_date', '>=', now()->subDays($days));
    }

    public function scopeByRating($query, int $rating)
    {
        return $this->scopeWithRating($query, $rating);
    }

    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('review_text_sentiment', strtolower($sentiment));
    }

    public function scopeHasResponse($query, bool $hasResponse)
    {
        return $hasResponse ? $this->scopeResponded($query) : $this->scopeUnresponded($query);
    }

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

    public function scopeOrderByField($query, string $sort)
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        return match ($field) {
            'rating' => $query->orderBy('rating', $direction),
            'date', 'review_date' => $query->orderBy('review_date', $direction),
            default => $query->orderBy('review_date', 'desc'),
        };
    }

    public function hasOwnerResponse(): bool
    {
        return filled($this->owner_response);
    }

    public function hasResponse(): bool
    {
        return $this->hasOwnerResponse();
    }

    public function getSentimentLabelAttribute(): string
    {
        return match ($this->review_text_sentiment) {
            'positive' => 'Positive',
            'negative' => 'Negative',
            default => 'Neutral',
        };
    }

    public function getStarRatingAttribute(): string
    {
        $rating = max(0, min(5, (int) $this->rating));

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    public function getIsPositiveAttribute(): bool
    {
        return (int) $this->rating >= 4;
    }

    public function getIsNegativeAttribute(): bool
    {
        return (int) $this->rating <= 2;
    }

    public function setGoogleReviewIdAttribute($value): void
    {
        $this->attributes['google_review_id'] = $this->truncate($value, 255);
    }

    public function setAuthorNameAttribute($value): void
    {
        $this->attributes['author_name'] = $this->nullableTruncate($value, 255);
    }

    public function setAuthorPhotoAttribute($value): void
    {
        $this->attributes['author_photo'] = $this->nullableTruncate($value, 500);
    }

    public function setRatingAttribute($value): void
    {
        $this->attributes['rating'] = (int) $value;
    }

    public function setReviewTextSentimentAttribute($value): void
    {
        $this->attributes['review_text_sentiment'] = $value === null
            ? null
            : mb_substr(strtolower((string) $value), 0, 20);
    }

    private function truncate($value, int $length): string
    {
        return mb_substr((string) $value, 0, $length);
    }

    private function nullableTruncate($value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->truncate($value, $length);
    }
}
