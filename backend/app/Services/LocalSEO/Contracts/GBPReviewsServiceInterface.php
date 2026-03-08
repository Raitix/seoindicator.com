<?php

namespace App\Services\LocalSEO\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @Task TASK-35-14
 * @Description GBP Reviews Service Interface for mocking in tests
 */
interface GBPReviewsServiceInterface
{
    /**
     * Get paginated reviews for a location
     *
     * @param int $locationId
     * @param array $filters {
     *     @type int|null $per_page Items per page (default 15, max 100)
     *     @type int|null $rating Filter by rating (1-5)
     *     @type string|null $sentiment Filter by sentiment (positive, neutral, negative)
     *     @type bool|null $has_response Filter by has response
     *     @type string|null $start_date Filter by start date
     *     @type string|null $end_date Filter by end date
     *     @type string|null $sort Sort field (date, -date, rating, -rating)
     * }
     * @return LengthAwarePaginator
     */
    public function getReviews(int $locationId, array $filters = []): LengthAwarePaginator;

    /**
     * Get review statistics for a location
     *
     * @param int $locationId
     * @return array{
     *     average_rating: float,
     *     total_count: int,
     *     distribution: array<int, int>,
     *     sentiment_breakdown: array<string, int>,
     *     response_rate: float,
     *     average_response_time_hours: int
     * }
     */
    public function getStats(int $locationId): array;

    /**
     * Trigger a background sync of reviews from GBP API
     *
     * @param int $locationId
     * @return string Job ID for status tracking
     */
    public function syncReviews(int $locationId): string;

    /**
     * Post owner response to a review
     *
     * @param int $reviewId
     * @param string $response Response text (max 4096 chars)
     * @return array{
     *     success: bool,
     *     response_id?: string,
     *     error?: string
     * }
     */
    public function respondToReview(int $reviewId, string $response): array;
}
