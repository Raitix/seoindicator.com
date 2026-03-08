<?php

namespace App\Services\LocalSEO\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\BusinessLocation;

/**
 * Business Location Service Interface
 * 
 * @Task TASK-35-13
 * @Description Interface for BusinessLocationService mocking
 */
interface BusinessLocationServiceInterface
{
    /**
     * Get paginated list of locations for a site
     *
     * @param int $siteId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getLocations(int $siteId, array $filters = []): LengthAwarePaginator;

    /**
     * Create a new business location
     *
     * @param int $siteId
     * @param array $data
     * @return BusinessLocation
     */
    public function createLocation(int $siteId, array $data): BusinessLocation;

    /**
     * Get a single location by ID
     *
     * @param int $id
     * @return BusinessLocation|null
     */
    public function getLocation(int $id): ?BusinessLocation;

    /**
     * Update a business location
     *
     * @param int $id
     * @param array $data
     * @return BusinessLocation
     */
    public function updateLocation(int $id, array $data): BusinessLocation;

    /**
     * Delete a business location (soft delete)
     *
     * @param int $id
     * @return void
     */
    public function deleteLocation(int $id): void;

    /**
     * Get insights summary for a location
     *
     * @param int $locationId
     * @return array
     */
    public function getInsightsSummary(int $locationId): array;

    /**
     * Get review stats for a location
     *
     * @param int $locationId
     * @return array
     */
    public function getReviewStats(int $locationId): array;
}
