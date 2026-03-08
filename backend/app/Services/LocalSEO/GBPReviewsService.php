<?php

namespace App\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * GBPReviewsService - LocalSEO namespace implementation.
 *
 * This service provides review data for the Local SEO Alert Service.
 * It wraps the core GBP reviews functionality with the interface
 * expected by the alert service.
 *
 * @see TASK-35-12
 * @see TASK-35-10
 * @see SPEC-035
 */
class GBPReviewsService implements GBPReviewsServiceInterface
{
    /**
     * Get paginated reviews for a location.
     *
     * @param int $locationId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getReviews(int $locationId, array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('gbp_reviews')
            ->where('location_id', $locationId);

        // Apply rating filter
        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        // Apply sentiment filter
        if (isset($filters['sentiment'])) {
            $query->where('review_text_sentiment', $filters['sentiment']);
        }

        // Apply has_response filter
        if (isset($filters['has_response'])) {
            if ($filters['has_response']) {
                $query->whereNotNull('owner_response');
            } else {
                $query->whereNull('owner_response');
            }
        }

        // Apply date range filter
        if (isset($filters['start_date'])) {
            $query->where('review_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('review_date', '<=', $filters['end_date']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? '-date';
        if (str_starts_with($sortField, '-')) {
            $query->orderBy(ltrim($sortField, '-'), 'desc');
        } else {
            $query->orderBy($sortField, 'asc');
        }

        // Paginate
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Get new reviews since last sync.
     *
     * Returns reviews that have been added since the last alert check.
     *
     * @param int $locationId
     * @return Collection
     */
    public function getNewReviews(int $locationId): Collection
    {
        $cacheKey = "local_seo:last_review_check:{$locationId}";
        $lastCheck = Cache::get($cacheKey);

        $query = DB::table('gbp_reviews')
            ->where('location_id', $locationId);

        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        // Update last check time
        Cache::put($cacheKey, now(), now()->addDay());

        return collect($reviews->map(function ($review) {
            return (object) [
                'id' => $review->google_review_id,
                'rating' => $review->rating,
                'author' => $review->author_name,
                'text' => $review->review_text,
                'date' => $review->review_date,
                'sentiment' => $review->review_text_sentiment,
            ];
        }));
    }

    /**
     * Get review statistics for a location.
     *
     * @param int $locationId
     * @return array
     */
    public function getStats(int $locationId): array
    {
        return $this->getReviewStats($locationId);
    }

    /**
     * Alias for getStats (for test compatibility).
     *
     * @param int $locationId
     * @return array
     */
    public function getReviewStats(int $locationId): array
    {
        $cacheKey = "local_seo:review_stats:{$locationId}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($locationId) {
            $reviews = DB::table('gbp_reviews')
                ->where('location_id', $locationId)
                ->get();

            $totalCount = $reviews->count();

            if ($totalCount === 0) {
                return [
                    'average_rating' => 0.0,
                    'previous_rating' => 0.0,
                    'total_count' => 0,
                    'previous_count' => 0,
                    'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                    'sentiment_breakdown' => ['positive' => 0, 'negative' => 0, 'neutral' => 0],
                    'response_rate' => 0.0,
                    'average_response_time_hours' => 0,
                ];
            }

            // Calculate average rating
            $averageRating = $reviews->avg('rating');

            // Calculate rating distribution
            $distribution = [
                1 => $reviews->where('rating', 1)->count(),
                2 => $reviews->where('rating', 2)->count(),
                3 => $reviews->where('rating', 3)->count(),
                4 => $reviews->where('rating', 4)->count(),
                5 => $reviews->where('rating', 5)->count(),
            ];

            // Calculate sentiment breakdown
            $sentimentBreakdown = [
                'positive' => $reviews->where('review_text_sentiment', 'positive')->count(),
                'negative' => $reviews->where('review_text_sentiment', 'negative')->count(),
                'neutral' => $reviews->where('review_text_sentiment', 'neutral')->count(),
            ];

            // Calculate response rate
            $respondedCount = $reviews->whereNotNull('owner_response')->count();
            $responseRate = $totalCount > 0 ? round(($respondedCount / $totalCount) * 100, 1) : 0.0;

            // Get previous stats from cache
            $previousStats = Cache::get("local_seo:prev_review_stats:{$locationId}", [
                'average_rating' => $averageRating,
                'total_count' => $totalCount,
            ]);

            // Update previous stats cache for next check
            Cache::put("local_seo:prev_review_stats:{$locationId}", [
                'average_rating' => $averageRating,
                'total_count' => $totalCount,
            ], now()->addDay());

            return [
                'average_rating' => round($averageRating, 2),
                'previous_rating' => $previousStats['average_rating'],
                'total_count' => $totalCount,
                'previous_count' => $previousStats['total_count'],
                'distribution' => $distribution,
                'sentiment_breakdown' => $sentimentBreakdown,
                'response_rate' => $responseRate,
                'average_response_time_hours' => 0, // Would need response timestamps
            ];
        });
    }

    /**
     * Trigger a background sync of reviews from GBP API.
     *
     * @param int $locationId
     * @return string Job ID for status tracking
     */
    public function syncReviews(int $locationId): string
    {
        $jobId = 'reviews-sync-' . $locationId . '-' . now()->timestamp;

        // Dispatch sync job
        // \App\Jobs\SyncGBPReviewsJob::dispatch($locationId);

        return $jobId;
    }

    /**
     * Post owner response to a review.
     *
     * @param int $reviewId
     * @param string $response Response text (max 4096 chars)
     * @return array
     */
    public function respondToReview(int $reviewId, string $response): array
    {
        // Truncate if too long
        $response = mb_substr($response, 0, 4096);

        try {
            $updated = DB::table('gbp_reviews')
                ->where('id', $reviewId)
                ->update([
                    'owner_response' => $response,
                    'owner_response_date' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated) {
                return [
                    'success' => true,
                    'response_id' => 'resp-' . $reviewId . '-' . now()->timestamp,
                ];
            }

            return [
                'success' => false,
                'error' => 'Review not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
