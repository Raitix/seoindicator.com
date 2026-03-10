<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;

/**
 * Business Location Service
 * 
 * @Task TASK-35-5, TASK-35-13
 * @Description Service for managing business locations
 */
class BusinessLocationService implements BusinessLocationServiceInterface
{
    protected ?bool $supportsBusinessLocationSoftDeletesCache = null;
    protected ?bool $supportsBusinessLocationActiveFlagCache = null;

    /**
     * Create a new business location.
     *
     * @param array $data Location data including site_id, name, address, city, country
     * @return BusinessLocation
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createLocation(array $data): BusinessLocation
    {
        $siteId = $data['site_id'];
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

        return $location;
    }

    /**
     * Update an existing business location.
     *
     * @param int $id Location ID
     * @param array $data Updated location data
     * @return BusinessLocation
     * @throws ModelNotFoundException
     */
    public function updateLocation(int $id, array $data): BusinessLocation
    {
        $location = $this->findLocation($id);

        // Only update provided fields
        $updatableFields = ['name', 'address', 'phone', 'website', 'category', 'hours', 'latitude', 'longitude', 'is_active'];

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
     * @param int $id Location ID
     * @param bool $force Force delete (hard delete)
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteLocation(int $id, bool $force = false): bool
    {
        $location = $this->findLocation($id);

        if ($force || !$this->supportsBusinessLocationSoftDeletes()) {
            return $location->forceDelete();
        }

        return $location->delete();
    }

    /**
     * Get all locations for a specific site.
     *
     * @param int $siteId Site ID
     * @param array $options Filter options
     * @return Collection|LengthAwarePaginator
     */
    public function getLocationsForSite(int $siteId, array $options = []): Collection|Paginator
    {
        $query = $this->newLocationQuery(($options['include_deleted'] ?? false) === true)
            ->where('site_id', $siteId);

        // Filter by active status
        if (($options['active_only'] ?? false) === true && $this->supportsBusinessLocationActiveFlag()) {
            $query->where('is_active', true);
        }

        // Filter by GBP connection status
        if (array_key_exists('gbp_connected', $options) && $options['gbp_connected'] !== null) {
            $query->where('gbp_connected', (bool) $options['gbp_connected']);
        }

        // Search filter
        if (filled($options['search'] ?? null)) {
            $search = trim((string) $options['search']);
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Eager load relations
        if (!empty($options['with'])) {
            $query->with($options['with']);
        }

        // Sorting
        $sortField = $options['sort_field'] ?? $options['sort'] ?? 'created_at';
        $sortField = is_string($sortField) && $sortField !== '' ? $sortField : 'created_at';

        $sortDirection = $options['sort_direction'] ?? null;
        if (!is_string($sortDirection) || !in_array(strtolower($sortDirection), ['asc', 'desc'], true)) {
            $sortDirection = str_starts_with($sortField, '-') ? 'desc' : 'asc';
        }

        $sortField = ltrim($sortField, '-');
        if ($sortField === 'is_gbp_connected') {
            $sortField = 'gbp_connected';
        }

        $allowedSortFields = ['name', 'created_at', 'updated_at', 'address', 'gbp_connected'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, strtolower($sortDirection));

        // Pagination
        if (!empty($options['per_page'])) {
            return $query->paginate($options['per_page'], ['*'], 'page', $options['page'] ?? 1);
        }

        return $query->get();
    }

    /**
     * Get all locations for a specific user (via their sites).
     *
     * @param int $userId User ID
     * @param array $options Filter options
     * @return Collection|LengthAwarePaginator
     */
    public function getLocationsForUser(int $userId, array $options = []): Collection|Paginator
    {
        $query = $this->newLocationQuery(($options['include_deleted'] ?? false) === true)
            ->where('user_id', $userId);

        // Filter by site
        if (!empty($options['site_id'])) {
            $query->where('site_id', $options['site_id']);
        }

        // Filter by active status
        if (($options['active_only'] ?? false) === true && $this->supportsBusinessLocationActiveFlag()) {
            $query->where('is_active', true);
        }

        // Filter by GBP connection status
        if (array_key_exists('gbp_connected', $options) && $options['gbp_connected'] !== null) {
            $query->where('gbp_connected', (bool) $options['gbp_connected']);
        }

        // Search filter
        if (filled($options['search'] ?? null)) {
            $search = trim((string) $options['search']);
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Eager load relations
        if (!empty($options['with'])) {
            $query->with($options['with']);
        }

        // Sorting
        $sortField = $options['sort_field'] ?? $options['sort'] ?? 'created_at';
        $sortField = is_string($sortField) && $sortField !== '' ? $sortField : 'created_at';

        $sortDirection = $options['sort_direction'] ?? null;
        if (!is_string($sortDirection) || !in_array(strtolower($sortDirection), ['asc', 'desc'], true)) {
            $sortDirection = str_starts_with($sortField, '-') ? 'desc' : 'asc';
        }

        $sortField = ltrim($sortField, '-');
        if ($sortField === 'is_gbp_connected') {
            $sortField = 'gbp_connected';
        }

        $allowedSortFields = ['name', 'created_at', 'updated_at', 'address', 'gbp_connected'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, strtolower($sortDirection));

        // Pagination
        if (!empty($options['per_page'])) {
            return $query->paginate($options['per_page'], ['*'], 'page', $options['page'] ?? 1);
        }

        return $query->get();
    }

    /**
     * Validate location data.
     *
     * @param array $data Location data to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateLocationData(array $data): array
    {
        $errors = [];

        // Required fields
        $required = ['name', 'address', 'site_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "The {$field} field is required.";
            }
        }

        // Name length
        if (!empty($data['name']) && strlen($data['name']) > 255) {
            $errors['name'] = 'The name may not be greater than 255 characters.';
        }

        // Address length
        if (!empty($data['address']) && strlen($data['address']) > 500) {
            $errors['address'] = 'The address may not be greater than 500 characters.';
        }

        // Phone format
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'The phone format is invalid.';
        }

        // Website URL
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors['website'] = 'The website must be a valid URL.';
        }

        // Coordinates
        if (isset($data['latitude']) && ($data['latitude'] < -90 || $data['latitude'] > 90)) {
            $errors['latitude'] = 'The latitude must be between -90 and 90.';
        }
        if (isset($data['longitude']) && ($data['longitude'] < -180 || $data['longitude'] > 180)) {
            $errors['longitude'] = 'The longitude must be between -180 and 180.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Find a location by ID.
     *
     * @param int $id Location ID
     * @return BusinessLocation
     * @throws ModelNotFoundException
     */
    public function findLocation(int $id): BusinessLocation
    {
        $location = $this->newLocationQuery()
            ->where('id', $id)
            ->first();

        if (!$location) {
            throw new ModelNotFoundException("Location with ID {$id} not found");
        }

        return $location;
    }

    /**
     * Restore a soft-deleted location.
     *
     * @param int $id Location ID
     * @return BusinessLocation
     * @throws ModelNotFoundException
     */
    public function restoreLocation(int $id): BusinessLocation
    {
        $location = $this->newLocationQuery(includeDeleted: true)
            ->where('id', $id)
            ->first();

        if (!$location) {
            throw new ModelNotFoundException("Location with ID {$id} not found");
        }

        if (!$this->supportsBusinessLocationSoftDeletes() || !$location->trashed()) {
            return $location;
        }

        $location->restore();

        return $location;
    }

    /**
     * Get a single location by ID (alias for findLocation).
     *
     * @param int $id
     * @return BusinessLocation|null
     */
    public function getLocation(int $id): ?BusinessLocation
    {
        try {
            return $this->findLocation($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Get paginated list of locations for a site.
     *
     * @param int $siteId
     * @param array $filters
     * @return Paginator
     */
    public function getLocations(int $siteId, array $filters = []): Paginator
    {
        $options = [
            'site_id' => $siteId,
            'active_only' => $filters['active_only'] ?? false,
            'gbp_connected' => $filters['is_gbp_connected'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort_field' => $filters['sort_field'] ?? null,
            'sort_direction' => $filters['sort_direction'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'per_page' => $filters['per_page'] ?? 15,
            'page' => $filters['page'] ?? 1,
        ];

        return $this->getLocationsForSite($siteId, $options);
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

    /**
     * Build a location query that tolerates production schema drift.
     */
    protected function newLocationQuery(bool $includeDeleted = false)
    {
        $query = BusinessLocation::query();

        if (!$this->supportsBusinessLocationSoftDeletes()) {
            return $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $includeDeleted ? $query->withTrashed() : $query;
    }

    /**
     * Determine whether the business_locations table currently supports soft deletes.
     */
    protected function supportsBusinessLocationSoftDeletes(): bool
    {
        if ($this->supportsBusinessLocationSoftDeletesCache !== null) {
            return $this->supportsBusinessLocationSoftDeletesCache;
        }

        $model = new BusinessLocation();
        $connection = $model->getConnectionName();

        $this->supportsBusinessLocationSoftDeletesCache = Schema::connection($connection)->hasColumn(
            $model->getTable(),
            $model->getDeletedAtColumn()
        );

        return $this->supportsBusinessLocationSoftDeletesCache;
    }

    /**
     * Determine whether the business_locations table currently supports the is_active flag.
     */
    protected function supportsBusinessLocationActiveFlag(): bool
    {
        if ($this->supportsBusinessLocationActiveFlagCache !== null) {
            return $this->supportsBusinessLocationActiveFlagCache;
        }

        $model = new BusinessLocation();
        $connection = $model->getConnectionName();

        $this->supportsBusinessLocationActiveFlagCache = Schema::connection($connection)->hasColumn(
            $model->getTable(),
            'is_active'
        );

        return $this->supportsBusinessLocationActiveFlagCache;
    }
}
