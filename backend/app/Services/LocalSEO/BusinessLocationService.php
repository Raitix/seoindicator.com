<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Business Location Service
 * 
 * @Task TASK-35-5, TASK-35-13
 * @Description Service for managing business locations
 */
class BusinessLocationService implements BusinessLocationServiceInterface
{
    /**
     * Get paginated list of locations for a site.
     *
     * @param int $siteId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getLocations(int $siteId, array $filters = []): LengthAwarePaginator
    {
        $query = BusinessLocation::query()
            ->where('site_id', $siteId)
            ->whereNull('deleted_at');

        // Filter by GBP connection status
        if (isset($filters['is_gbp_connected'])) {
            $query->where('gbp_connected', $filters['is_gbp_connected']);
        }

        // Search by name or address
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if (!empty($filters['sort'])) {
            $sort = $filters['sort'];
            if (str_starts_with($sort, '-')) {
                $sortDirection = 'desc';
                $sortField = ltrim($sort, '-');
            } else {
                $sortDirection = 'asc';
                $sortField = $sort;
            }

            // Validate sort field
            $allowedSortFields = ['name', 'created_at', 'updated_at', 'address'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
        }

        $query->orderBy($sortField, $sortDirection);

        return $query->paginate(15);
    }

    /**
     * Create a new business location.
     *
     * @param int $siteId
     * @param array $data
     * @return BusinessLocation
     */
    public function createLocation(int $siteId, array $data): BusinessLocation
    {
        $site = Site::findOrFail($siteId);

        $location = new BusinessLocation();
        $location->user_id = $data['user_id'] ?? $site->user_id;
        $location->site_id = $siteId;
        $location->name = $data['name'];
        $location->address = $data['address'];
        $location->phone = $data['phone'] ?? null;
        $location->website = $data['website'] ?? null;
        $location->category = $data['category'] ?? null;
        $location->gbp_place_id = $data['gbp_place_id'] ?? null;
        $location->gbp_connected = false;
        $location->is_active = true;
        $location->save();

        // Geocode address to get coordinates (placeholder - would use geocoding service)
        // $this->geocodeLocation($location);

        return $location;
    }

    /**
     * Get a single location by ID.
     *
     * @param int $id
     * @return BusinessLocation|null
     */
    public function getLocation(int $id): ?BusinessLocation
    {
        return BusinessLocation::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Update a business location.
     *
     * @param int $id
     * @param array $data
     * @return BusinessLocation
     */
    public function updateLocation(int $id, array $data): BusinessLocation
    {
        $location = $this->getLocation($id);

        if (!$location) {
            throw new \Exception('Location not found');
        }

        // Only update provided fields
        $updatableFields = ['name', 'address', 'phone', 'website', 'category', 'hours', 'latitude', 'longitude'];

        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $data)) {
                $location->$field = $data[$field];
            }
        }

        $location->save();

        return $location;
    }

    /**
     * Delete a business location (soft delete).
     *
     * @param int $id
     * @return void
     */
    public function deleteLocation(int $id): void
    {
        $location = BusinessLocation::find($id);

        if ($location) {
            $location->delete();
        }
    }

    /**
     * Get insights summary for a location.
     *
     * @param int $locationId
     * @return array
     */
    public function getInsightsSummary(int $locationId): array
    {
        // Placeholder - would aggregate from GBP insights data
        return [
            'total_queries' => 0,
            'total_views' => 0,
            'total_actions' => 0,
            'last_sync_at' => null,
        ];
    }

    /**
     * Get review stats for a location.
     *
     * @param int $locationId
     * @return array
     */
    public function getReviewStats(int $locationId): array
    {
        // Placeholder - would aggregate from GBP reviews data
        return [
            'average_rating' => 0,
            'total_reviews' => 0,
            'response_rate' => 0,
        ];
    }
}
