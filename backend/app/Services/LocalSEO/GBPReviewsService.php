<?php

namespace App\Services\LocalSEO;

use App\Models\GBPReview;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * GBPReviewsService - LocalSEO namespace implementation.
 *
 * This service provides review data for the Local SEO review pages and alert
 * workflows. It wraps the Local SEO review store with the interface expected by
 * the controller and downstream consumers.
 *
 * @see TASK-35-14
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
        $query = GBPReview::query()->where('location_id', $locationId);

        $ratings = array_values(array_unique(array_map('intval', $filters['ratings'] ?? [])));

        if (!empty($ratings)) {
            $query->whereIn('rating', $ratings);
        } elseif (isset($filters['rating'])) {
            $query->byRating((int) $filters['rating']);
        }

        if (isset($filters['sentiment'])) {
            $query->bySentiment((string) $filters['sentiment']);
        }

        if (array_key_exists('has_response', $filters) && $filters['has_response'] !== null) {
            $query->hasResponse((bool) $filters['has_response']);
        }

        $query->dateRange(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $query->orderByField((string) ($filters['sort'] ?? '-date'));

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get new reviews since last sync.
     *
     * @param int $locationId
     * @return Collection
     */
    public function getNewReviews(int $locationId): Collection
    {
        $cacheKey = "local_seo:last_review_check:{$locationId}";
        $lastCheck = Cache::get($cacheKey);

        $query = GBPReview::query()->where('location_id', $locationId);

        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        $reviews = $query->orderByDesc('created_at')->get();

        Cache::put($cacheKey, now(), now()->addDay());

        return $reviews->map(function (GBPReview $review) {
            return (object) [
                'id' => $review->google_review_id,
                'rating' => $review->rating,
                'author' => $review->author_name,
                'text' => $review->review_text,
                'date' => optional($review->review_date)->toIso8601String(),
                'sentiment' => $review->review_text_sentiment,
            ];
        });
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
            $reviews = GBPReview::query()->where('location_id', $locationId)->get();
            $totalCount = $reviews->count();

            if ($totalCount === 0) {
                $emptyDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                $emptySentiment = ['positive' => 0, 'negative' => 0, 'neutral' => 0];

                return [
                    'average_rating' => 0.0,
                    'previous_rating' => 0.0,
                    'total_count' => 0,
                    'previous_count' => 0,
                    'distribution' => $emptyDistribution,
                    'rating_distribution' => $emptyDistribution,
                    'sentiment_breakdown' => $emptySentiment,
                    'response_rate' => 0.0,
                    'average_response_time_hours' => 0,
                ];
            }

            $averageRating = (float) $reviews->avg('rating');
            $distribution = [
                1 => $reviews->where('rating', 1)->count(),
                2 => $reviews->where('rating', 2)->count(),
                3 => $reviews->where('rating', 3)->count(),
                4 => $reviews->where('rating', 4)->count(),
                5 => $reviews->where('rating', 5)->count(),
            ];
            $sentimentBreakdown = [
                'positive' => $reviews->where('review_text_sentiment', 'positive')->count(),
                'negative' => $reviews->where('review_text_sentiment', 'negative')->count(),
                'neutral' => $reviews->where('review_text_sentiment', 'neutral')->count(),
            ];
            $respondedCount = $reviews->filter(fn (GBPReview $review) => $review->hasResponse())->count();
            $responseRate = $totalCount > 0 ? round(($respondedCount / $totalCount) * 100, 1) : 0.0;

            $responseTimes = $reviews
                ->filter(fn (GBPReview $review) => $review->owner_response_date && $review->review_date)
                ->map(function (GBPReview $review) {
                    return $review->review_date?->diffInHours($review->owner_response_date);
                })
                ->filter(fn ($hours) => $hours !== null);

            $previousStats = Cache::get("local_seo:prev_review_stats:{$locationId}", [
                'average_rating' => $averageRating,
                'total_count' => $totalCount,
            ]);

            Cache::put("local_seo:prev_review_stats:{$locationId}", [
                'average_rating' => $averageRating,
                'total_count' => $totalCount,
            ], now()->addDay());

            return [
                'average_rating' => round($averageRating, 2),
                'previous_rating' => isset($previousStats['average_rating']) ? (float) $previousStats['average_rating'] : null,
                'total_count' => $totalCount,
                'previous_count' => isset($previousStats['total_count']) ? (int) $previousStats['total_count'] : null,
                'distribution' => $distribution,
                'rating_distribution' => $distribution,
                'sentiment_breakdown' => $sentimentBreakdown,
                'response_rate' => $responseRate,
                'average_response_time_hours' => $responseTimes->isNotEmpty() ? (int) round($responseTimes->avg()) : 0,
            ];
        });
    }

    /**
     * Trigger a background sync of reviews from GBP API.
     *
     * @param int $locationId
     * @return string
     */
    public function syncReviews(int $locationId): string
    {
        $jobId = 'reviews-sync-' . $locationId . '-' . now()->timestamp;

        Cache::forget("local_seo:review_stats:{$locationId}");

        // Placeholder until the background GBP sync job is wired in.
        // \App\Jobs\SyncGBPReviewsJob::dispatch($locationId);

        return $jobId;
    }

    /**
     * Post owner response to a review.
     *
     * @param int $reviewId
     * @param string $response
     * @return array
     */
    public function respondToReview(int $reviewId, string $response): array
    {
        $review = GBPReview::query()->find($reviewId);

        if (!$review) {
            return [
                'success' => false,
                'error' => 'Review not found',
            ];
        }

        if ($review->hasResponse()) {
            return [
                'success' => false,
                'error' => 'This review already has a response.',
            ];
        }

        $review->forceFill([
            'owner_response' => mb_substr(trim($response), 0, 4096),
            'owner_response_date' => now(),
        ])->save();

        Cache::forget("local_seo:review_stats:{$review->location_id}");

        return [
            'success' => true,
            'response_id' => 'resp-' . $reviewId . '-' . now()->timestamp,
        ];
    }
}
