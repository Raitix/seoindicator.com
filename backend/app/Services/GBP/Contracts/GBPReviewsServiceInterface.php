<?php

namespace App\Services\GBP\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface for GBP Reviews Service.
 *
 * Defines the contract for managing Google Business Profile reviews
 * including syncing, filtering, statistics, and sentiment analysis.
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
interface GBPReviewsServiceInterface
{
    /**
     * Sync reviews from GBP API for a business location.
     *
     * Fetches all reviews from GBP API (paginated), stores in gbp_reviews table,
     * updates existing reviews if changed, and triggers sentiment analysis.
     *
     * @param mixed $location BusinessLocation model instance
     * @return void
     */
    public function syncReviews($location): void;

    /**
     * Get paginated reviews for a business location with filtering and sorting.
     *
     * @param mixed $location BusinessLocation model instance
     * @param array $filters Available filters: rating, sentiment, date_from, date_to, sort, order, per_page
     * @return LengthAwarePaginator
     */
    public function getReviews($location, array $filters = []): LengthAwarePaginator;

    /**
     * Get review statistics for a business location.
     *
     * Returns average rating, total count, rating distribution, and sentiment breakdown.
     *
     * @param mixed $location BusinessLocation model instance
     * @return array
     */
    public function getReviewStats($location): array;

    /**
     * Post a response to a Google review.
     *
     * Posts response to Google and stores response locally.
     *
     * @param mixed $location BusinessLocation model instance
     * @param string $reviewId Google review ID
     * @param string $reply Response text
     * @return bool
     */
    public function respondToReview($location, string $reviewId, string $reply): bool;

    /**
     * Detect new negative reviews that haven't been alerted.
     *
     * Returns reviews with rating <= 2 and no prior alert.
     *
     * @param mixed $location BusinessLocation model instance
     * @return Collection
     */
    public function detectNewNegativeReviews($location): Collection;
}
