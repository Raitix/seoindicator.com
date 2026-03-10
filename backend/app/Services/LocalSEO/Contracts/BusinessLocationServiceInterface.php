<?php

namespace App\Services\LocalSEO\Contracts;

use App\Models\BusinessLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface BusinessLocationServiceInterface
{
    /**
     * Create a new business location.
     *
     * @param array $data Location data including site_id, name, address, city, country
     * @return BusinessLocation
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createLocation(array $data): BusinessLocation;

    /**
     * Update an existing business location.
     *
     * @param int $id Location ID
     * @param array $data Updated location data
     * @return BusinessLocation
     * @throws ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateLocation(int $id, array $data): BusinessLocation;

    /**
     * Delete a business location (soft delete).
     *
     * @param int $id Location ID
     * @param bool $force Force delete (hard delete)
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteLocation(int $id, bool $force = false): bool;

    /**
     * Get all locations for a specific site.
     *
     * @param int $siteId Site ID
     * @param array $options Filter options (active_only, gbp_connected, include_deleted, with, sort, per_page, page)
     * @return Collection|LengthAwarePaginator
     */
    public function getLocationsForSite(int $siteId, array $options = []): Collection|LengthAwarePaginator;

    /**
     * Get all locations for a specific user (via their sites).
     *
     * @param int $userId User ID
     * @param array $options Filter options (active_only, gbp_connected, include_deleted, site_id, with, sort, per_page, page)
     * @return Collection|LengthAwarePaginator
     */
    public function getLocationsForUser(int $userId, array $options = []): Collection|LengthAwarePaginator;

    /**
     * Validate location data.
     *
     * @param array $data Location data to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateLocationData(array $data): array;

    /**
     * Find a location by ID.
     *
     * @param int $id Location ID
     * @return BusinessLocation
     * @throws ModelNotFoundException
     */
    public function findLocation(int $id): BusinessLocation;

    /**
     * Restore a soft-deleted location.
     *
     * @param int $id Location ID
     * @return BusinessLocation
     * @throws ModelNotFoundException
     */
    public function restoreLocation(int $id): BusinessLocation;
}
