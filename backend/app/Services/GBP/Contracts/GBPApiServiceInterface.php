<?php

namespace App\Services\GBP\Contracts;

/**
 * Interface for GBP API Service.
 *
 * Defines the contract for interacting with Google Business Profile API.
 *
 * @see TASK-35-8
 * @see SPEC-035
 */
interface GBPApiServiceInterface
{
    /**
     * Get all GBP accounts the user has access to.
     *
     * @param string $accessToken
     * @return array
     */
    public function getAccounts(string $accessToken): array;

    /**
     * Get all locations under an account.
     *
     * @param string $accountId
     * @param string $accessToken
     * @return array
     */
    public function getLocations(string $accountId, string $accessToken): array;

    /**
     * Get detailed location info.
     *
     * @param string $locationName Format: accounts/{accountId}/locations/{locationId}
     * @param string $accessToken
     * @return array
     */
    public function getLocation(string $locationName, string $accessToken): array;

    /**
     * Get GBP insights data for a location.
     *
     * @param string $locationName
     * @param string $accessToken
     * @param array $dateRange ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return array
     */
    public function getInsights(string $locationName, string $accessToken, array $dateRange): array;

    /**
     * Get reviews with pagination.
     *
     * @param string $locationName
     * @param string $accessToken
     * @param int $pageSize
     * @param string|null $pageToken
     * @return array
     */
    public function getReviews(string $locationName, string $accessToken, int $pageSize = 50, ?string $pageToken = null): array;

    /**
     * Post owner response to a review.
     *
     * @param string $locationName
     * @param string $reviewId
     * @param string $reply
     * @param string $accessToken
     * @return bool
     */
    public function respondToReview(string $locationName, string $reviewId, string $reply, string $accessToken): bool;
}
