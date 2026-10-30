<?php

namespace App\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use App\Services\LocalSEO\Exceptions\BusinessLocationServiceException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Business Location Service
 *
 * @Task TASK-35-5, TASK-35-13
 * @Description Service for managing business locations
 */
class BusinessLocationService implements BusinessLocationServiceInterface
{
    private const LIST_CACHE_TTL_SECONDS = 300;
    private const GEOCODE_CACHE_TTL_SECONDS = 86400;
    private const SITE_CACHE_VERSION_KEY = 'local-seo:business-locations:site-version:';
    private const USER_CACHE_VERSION_KEY = 'local-seo:business-locations:user-version:';
    private const SITE_LIST_CACHE_KEY = 'local-seo:business-locations:site-list:';
    private const USER_LIST_CACHE_KEY = 'local-seo:business-locations:user-list:';
    private const GEOCODE_CACHE_KEY = 'local-seo:business-locations:geocode:';

    protected ?bool $supportsBusinessLocationSoftDeletesCache = null;
    protected ?bool $supportsBusinessLocationActiveFlagCache = null;

    public function __construct(
        protected NominatimServiceInterface $nominatimService
    ) {
    }

    /**
     * Create a new business location.
     *
     * @param array $data Location data including site_id, name, address, city, country
     * @return BusinessLocation
     * @throws ValidationException
     */
    public function createLocation(array $data): BusinessLocation
    {
        $payload = $this->normalizePayload($data);
        $this->validateCreatePayload($payload);

        $site = Site::query()->findOrFail((int) $payload['site_id']);
        $coordinates = $this->resolveCreateCoordinates($payload);
        $settings = $this->buildSettings(null, $payload);

        try {
            /** @var BusinessLocation $location */
            $location = DB::transaction(function () use ($payload, $site, $coordinates, $settings) {
                $location = new BusinessLocation();
                $location->user_id = (int) ($payload['user_id'] ?? $site->user_id);
                $location->site_id = (int) $site->id;
                $location->name = $payload['name'];
                $location->address = $payload['address'];
                $location->phone = $payload['phone'] ?? null;
                $location->website = $payload['website'] ?? null;
                $location->category = $payload['category'] ?? null;
                $location->latitude = $coordinates['latitude'];
                $location->longitude = $coordinates['longitude'];
                $location->gbp_place_id = $payload['gbp_place_id'] ?? null;
                $location->gbp_connected = (bool) ($payload['gbp_connected'] ?? false);
                if ($this->supportsBusinessLocationActiveFlag()) {
                    $location->is_active = array_key_exists('is_active', $payload)
                        ? (bool) $payload['is_active']
                        : true;
                }
                $location->settings = $settings;
                $location->save();

                return $location;
            });
        } catch (ValidationException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BusinessLocationServiceException('Failed to create business location.', 0, $exception);
        }

        $this->invalidateLocationCaches((int) $location->site_id, (int) $location->user_id);

        return $this->hydrateComputedAttributes($location->fresh() ?? $location);
    }

    /**
     * Update an existing business location.
     *
     * @param int $id Location ID
     * @param array $data Updated location data
     * @return BusinessLocation
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function updateLocation(int $id, array $data): BusinessLocation
    {
        $location = $this->findLocationRecord($id);
        $payload = $this->normalizePayload($data);
        unset($payload['site_id'], $payload['user_id'], $payload['gbp_location_id'], $payload['gbp_place_id'], $payload['gbp_connected']);
        $this->validateUpdatePayload($payload, $location);

        $coordinates = $this->resolveUpdateCoordinates($location, $payload);
        $settings = $this->buildSettings($this->normalizeSettings($location->settings), $payload);

        try {
            DB::transaction(function () use ($location, $payload, $coordinates, $settings) {
                if (array_key_exists('name', $payload)) {
                    $location->name = $payload['name'];
                }

                if (array_key_exists('address', $payload)) {
                    $location->address = $payload['address'];
                }

                if (array_key_exists('phone', $payload)) {
                    $location->phone = $payload['phone'];
                }

                if (array_key_exists('website', $payload)) {
                    $location->website = $payload['website'];
                }

                if (array_key_exists('category', $payload)) {
                    $location->category = $payload['category'];
                }

                if (array_key_exists('is_active', $payload) && $this->supportsBusinessLocationActiveFlag()) {
                    $location->is_active = (bool) $payload['is_active'];
                }

                $location->latitude = $coordinates['latitude'];
                $location->longitude = $coordinates['longitude'];
                $location->settings = $settings;
                $location->save();
            });
        } catch (ValidationException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BusinessLocationServiceException('Failed to update business location.', 0, $exception);
        }

        $this->invalidateLocationCaches((int) $location->site_id, (int) $location->user_id);

        return $this->hydrateComputedAttributes($location->fresh() ?? $location);
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
        $location = $this->findLocationRecord($id);
        $siteId = (int) $location->site_id;
        $userId = (int) $location->user_id;

        try {
            $deleted = DB::transaction(function () use ($location, $force) {
                if ($force || !$this->supportsBusinessLocationSoftDeletes()) {
                    return (bool) $location->forceDelete();
                }

                $settings = $this->normalizeSettings($location->settings);
                unset($settings['gbp_location_id']);

                $location->gbp_access_token = null;
                $location->gbp_refresh_token = null;
                $location->gbp_token_expires_at = null;
                $location->gbp_connected = false;
                $location->settings = empty($settings) ? null : $settings;
                $location->save();

                return (bool) $location->delete();
            });
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BusinessLocationServiceException('Failed to delete business location.', 0, $exception);
        }

        $this->invalidateLocationCaches($siteId, $userId);

        return $deleted;
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
        $normalizedOptions = $this->normalizeListOptions($options);
        $cacheKey = $this->siteListCacheKey($siteId, $normalizedOptions);

        return Cache::remember($cacheKey, self::LIST_CACHE_TTL_SECONDS, function () use ($siteId, $normalizedOptions) {
            $query = $this->newLocationQuery(($normalizedOptions['include_deleted'] ?? false) === true)
                ->where('site_id', $siteId);

            if (($normalizedOptions['active_only'] ?? false) === true && $this->supportsBusinessLocationActiveFlag()) {
                $query->where('is_active', true);
            }

            if (array_key_exists('gbp_connected', $normalizedOptions) && $normalizedOptions['gbp_connected'] !== null) {
                $query->where('gbp_connected', (bool) $normalizedOptions['gbp_connected']);
            }

            if (!empty($normalizedOptions['with'])) {
                $query->with($normalizedOptions['with']);
            }

            if (!empty($normalizedOptions['search'])) {
                $search = trim((string) $normalizedOptions['search']);
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            [$sortField, $sortDirection] = $this->resolveSort($normalizedOptions);
            $query->orderBy($sortField, $sortDirection);

            if (!empty($normalizedOptions['per_page'])) {
                $result = $query->paginate(
                    (int) $normalizedOptions['per_page'],
                    ['*'],
                    'page',
                    (int) ($normalizedOptions['page'] ?? 1)
                );

                return $this->hydrateComputedAttributesOnResult($result);
            }

            return $this->hydrateComputedAttributesOnResult($query->get());
        });
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
        $normalizedOptions = $this->normalizeListOptions($options);
        $cacheKey = $this->userListCacheKey($userId, $normalizedOptions);

        return Cache::remember($cacheKey, self::LIST_CACHE_TTL_SECONDS, function () use ($userId, $normalizedOptions) {
            $query = $this->newLocationQuery(($normalizedOptions['include_deleted'] ?? false) === true)
                ->where('user_id', $userId);

            if (!empty($normalizedOptions['site_id'])) {
                $query->where('site_id', (int) $normalizedOptions['site_id']);
            }

            if (($normalizedOptions['active_only'] ?? false) === true && $this->supportsBusinessLocationActiveFlag()) {
                $query->where('is_active', true);
            }

            if (array_key_exists('gbp_connected', $normalizedOptions) && $normalizedOptions['gbp_connected'] !== null) {
                $query->where('gbp_connected', (bool) $normalizedOptions['gbp_connected']);
            }

            if (!empty($normalizedOptions['with'])) {
                $query->with($normalizedOptions['with']);
            }

            if (!empty($normalizedOptions['search'])) {
                $search = trim((string) $normalizedOptions['search']);
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            [$sortField, $sortDirection] = $this->resolveSort($normalizedOptions);
            $query->orderBy($sortField, $sortDirection);

            if (!empty($normalizedOptions['per_page'])) {
                $result = $query->paginate(
                    (int) $normalizedOptions['per_page'],
                    ['*'],
                    'page',
                    (int) ($normalizedOptions['page'] ?? 1)
                );

                return $this->hydrateComputedAttributesOnResult($result);
            }

            return $this->hydrateComputedAttributesOnResult($query->get());
        });
    }

    /**
     * Validate location data.
     *
     * @param array $data Location data to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateLocationData(array $data): array
    {
        $payload = $this->normalizePayload($data);
        $validator = $this->makeValidator($payload, false);

        $this->applyDuplicateRule($validator, $payload);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->toArray(),
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
        return $this->hydrateComputedAttributes($this->findLocationRecord($id));
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
        $location = $this->findLocationRecord($id, includeDeleted: true);

        if ($this->supportsBusinessLocationSoftDeletes() && $location->trashed()) {
            $location->restore();
            $location->refresh();
        }

        $this->invalidateLocationCaches((int) $location->site_id, (int) $location->user_id);

        return $this->hydrateComputedAttributes($location);
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
        } catch (ModelNotFoundException) {
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
            'active_only' => $filters['active_only'] ?? false,
            'gbp_connected' => $filters['is_gbp_connected'] ?? $filters['gbp_connected'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort_field' => $filters['sort_field'] ?? null,
            'sort_direction' => $filters['sort_direction'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'per_page' => $filters['per_page'] ?? 15,
            'page' => $filters['page'] ?? 1,
        ];

        /** @var Paginator $result */
        $result = $this->getLocationsForSite($siteId, $options);

        return $result;
    }

    /**
     * Get insights summary for a location.
     *
     * @param int $locationId
     * @return array
     */
    public function getInsightsSummary(int $locationId): array
    {
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
        return [
            'average_rating' => 0,
            'total_reviews' => 0,
            'response_rate' => 0,
        ];
    }

    protected function validateCreatePayload(array $payload): void
    {
        $validator = $this->makeValidator($payload, false);
        $this->applyDuplicateRule($validator, $payload);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected function validateUpdatePayload(array $payload, BusinessLocation $location): void
    {
        $validator = $this->makeValidator($payload, true);
        $this->applyDuplicateRule($validator, $payload, $location);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected function makeValidator(array $payload, bool $updating)
    {
        $nameRules = [$updating ? 'sometimes' : 'required', 'string', 'filled', 'max:255'];
        $addressRules = [$updating ? 'sometimes' : 'required', 'string', 'filled', 'max:500'];

        return Validator::make($payload, [
            'site_id' => [$updating ? 'sometimes' : 'required', 'integer', Rule::exists('sites', 'id')],
            'name' => $nameRules,
            'address' => $addressRules,
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)\.]+$/'],
            'website' => ['nullable', 'string', 'url', 'max:2048'],
            'category' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'hours' => ['nullable', 'array'],
            'business_hours' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'gbp_place_id' => ['nullable', 'string', 'max:255'],
            'gbp_location_id' => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function applyDuplicateRule($validator, array $payload, ?BusinessLocation $existing = null): void
    {
        $validator->after(function ($validator) use ($payload, $existing) {
            if (empty($payload['site_id']) && !$existing) {
                return;
            }

            $siteId = (int) ($payload['site_id'] ?? $existing?->site_id ?? 0);
            $name = $payload['name'] ?? $existing?->name;
            $address = $payload['address'] ?? $existing?->address;

            if ($siteId === 0 || $name === null || $address === null) {
                return;
            }

            $duplicateQuery = BusinessLocation::query()
                ->where('site_id', $siteId)
                ->where('name', $name)
                ->where('address', $address);

            if ($existing) {
                $duplicateQuery->whereKeyNot($existing->getKey());
            }

            if ($duplicateQuery->exists()) {
                $validator->errors()->add('address', 'A location with this name and address already exists for the site.');
            }
        });
    }

    protected function normalizePayload(array $payload): array
    {
        $trimKeys = [
            'name',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'website',
            'category',
            'gbp_place_id',
            'gbp_location_id',
        ];

        foreach ($trimKeys as $key) {
            if (array_key_exists($key, $payload) && is_string($payload[$key])) {
                $payload[$key] = trim($payload[$key]);
            }
        }

        foreach (['phone', 'website', 'category', 'gbp_place_id', 'gbp_location_id', 'city', 'state', 'postal_code', 'country'] as $nullableKey) {
            if (array_key_exists($nullableKey, $payload) && $payload[$nullableKey] === '') {
                $payload[$nullableKey] = null;
            }
        }

        foreach (['site_id', 'user_id'] as $integerKey) {
            if (array_key_exists($integerKey, $payload) && is_numeric($payload[$integerKey])) {
                $payload[$integerKey] = (int) $payload[$integerKey];
            }
        }

        foreach (['latitude', 'longitude'] as $floatKey) {
            if (array_key_exists($floatKey, $payload) && $payload[$floatKey] !== null && is_numeric($payload[$floatKey])) {
                $payload[$floatKey] = (float) $payload[$floatKey];
            }
        }

        if (array_key_exists('hours', $payload) && !array_key_exists('business_hours', $payload)) {
            $payload['business_hours'] = $payload['hours'];
        }

        if (array_key_exists('business_hours', $payload) && !array_key_exists('hours', $payload)) {
            $payload['hours'] = $payload['business_hours'];
        }

        return $payload;
    }

    protected function normalizeSettings(mixed $settings): array
    {
        return is_array($settings) ? $settings : [];
    }

    protected function buildSettings(?array $currentSettings, array $payload): ?array
    {
        $settings = array_key_exists('settings', $payload)
            ? $this->normalizeSettings($payload['settings'])
            : ($currentSettings ?? []);

        if (array_key_exists('hours', $payload)) {
            $settings['hours'] = $payload['hours'];
        }

        if (array_key_exists('business_hours', $payload)) {
            $settings['hours'] = $payload['business_hours'];
        }

        if (array_key_exists('gbp_location_id', $payload)) {
            $settings['gbp_location_id'] = $payload['gbp_location_id'];
        }

        return empty($settings) ? null : $settings;
    }

    protected function resolveCreateCoordinates(array $payload): array
    {
        if ($this->hasManualCoordinates($payload)) {
            return [
                'latitude' => $this->normalizeCoordinate($payload['latitude']),
                'longitude' => $this->normalizeCoordinate($payload['longitude']),
            ];
        }

        if (($payload['skip_geocode'] ?? false) === true) {
            return ['latitude' => null, 'longitude' => null];
        }

        return $this->geocodeAddress($this->buildGeocodeQuery($payload));
    }

    protected function resolveUpdateCoordinates(BusinessLocation $location, array $payload): array
    {
        if ($this->hasManualCoordinates($payload)) {
            return [
                'latitude' => $this->normalizeCoordinate($payload['latitude']),
                'longitude' => $this->normalizeCoordinate($payload['longitude']),
            ];
        }

        $addressChanged = array_key_exists('address', $payload) && $payload['address'] !== $location->address;

        if ($addressChanged && (($payload['skip_geocode'] ?? false) !== true)) {
            return $this->geocodeAddress($this->buildGeocodeQuery($payload, $location));
        }

        return [
            'latitude' => $location->latitude !== null ? (float) $location->latitude : null,
            'longitude' => $location->longitude !== null ? (float) $location->longitude : null,
        ];
    }

    protected function hasManualCoordinates(array $payload): bool
    {
        return array_key_exists('latitude', $payload)
            && array_key_exists('longitude', $payload)
            && $payload['latitude'] !== null
            && $payload['longitude'] !== null;
    }

    protected function buildGeocodeQuery(array $payload, ?BusinessLocation $location = null): string
    {
        $parts = array_values(array_filter([
            $payload['address'] ?? $location?->address,
            $payload['city'] ?? null,
            $payload['state'] ?? null,
            $payload['postal_code'] ?? null,
            $payload['country'] ?? null,
        ], static fn ($value) => $value !== null && $value !== ''));

        return implode(', ', $parts);
    }

    protected function geocodeAddress(string $query): array
    {
        if ($query === '') {
            return ['latitude' => null, 'longitude' => null];
        }

        $cacheKey = self::GEOCODE_CACHE_KEY . md5($query);
        $geocodeResult = Cache::remember($cacheKey, self::GEOCODE_CACHE_TTL_SECONDS, function () use ($query) {
            try {
                return $this->nominatimService->geocode($query);
            } catch (Throwable) {
                return null;
            }
        });

        if (!is_array($geocodeResult)) {
            return ['latitude' => null, 'longitude' => null];
        }

        $latitude = $geocodeResult['latitude'] ?? $geocodeResult['lat'] ?? null;
        $longitude = $geocodeResult['longitude'] ?? $geocodeResult['lng'] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return ['latitude' => null, 'longitude' => null];
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return ['latitude' => null, 'longitude' => null];
        }

        return [
            'latitude' => $this->normalizeCoordinate($latitude),
            'longitude' => $this->normalizeCoordinate($longitude),
        ];
    }

    protected function normalizeCoordinate(float $coordinate): float
    {
        return round($coordinate, 6);
    }

    protected function normalizeListOptions(array $options): array
    {
        if (array_key_exists('is_gbp_connected', $options) && !array_key_exists('gbp_connected', $options)) {
            $options['gbp_connected'] = $options['is_gbp_connected'];
        }

        if (array_key_exists('per_page', $options) && $options['per_page'] !== null) {
            $options['per_page'] = max(1, (int) $options['per_page']);
        }

        if (array_key_exists('page', $options) && $options['page'] !== null) {
            $options['page'] = max(1, (int) $options['page']);
        }

        if (array_key_exists('with', $options) && is_array($options['with'])) {
            sort($options['with']);
        }

        return $options;
    }

    protected function resolveSort(array $options): array
    {
        $sortField = $options['sort_field'] ?? $options['sort'] ?? 'created_at';
        $sortDirection = $options['sort_direction'] ?? null;

        if (!is_string($sortField) || $sortField === '') {
            $sortField = 'created_at';
        }

        if (!is_string($sortDirection) || !in_array(strtolower($sortDirection), ['asc', 'desc'], true)) {
            if (str_starts_with($sortField, '-')) {
                $sortDirection = 'desc';
            } elseif (($options['sort'] ?? null) === 'name') {
                $sortDirection = 'asc';
            } else {
                $sortDirection = 'desc';
            }
        }

        $sortField = ltrim($sortField, '-');
        if ($sortField === 'is_gbp_connected') {
            $sortField = 'gbp_connected';
        }

        $allowedSortFields = ['name', 'created_at', 'updated_at', 'address', 'gbp_connected'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        return [$sortField, strtolower($sortDirection)];
    }

    protected function hydrateComputedAttributesOnResult(Collection|Paginator $result): Collection|Paginator
    {
        if ($result instanceof Paginator) {
            $items = array_map(fn ($item) => $this->hydrateComputedAttributes($item), $result->items());
            $result->setCollection(new Collection($items));

            return $result;
        }

        return $result->map(fn ($item) => $this->hydrateComputedAttributes($item));
    }

    protected function hydrateComputedAttributes(BusinessLocation $location): BusinessLocation
    {
        $settings = $this->normalizeSettings($location->settings);
        $hours = $settings['hours'] ?? null;

        $location->syncOriginal();
        $location->forceFill([
            'hours' => $hours,
            'business_hours' => $hours,
            'gbp_location_id' => $settings['gbp_location_id'] ?? null,
        ]);
        $location->syncOriginalAttributes(['hours', 'business_hours', 'gbp_location_id']);

        return $location;
    }

    protected function findLocationRecord(int $id, bool $includeDeleted = false): BusinessLocation
    {
        $location = $this->newLocationQuery($includeDeleted)
            ->whereKey($id)
            ->first();

        if (!$location) {
            $exception = new ModelNotFoundException();
            $exception->setModel(BusinessLocation::class, [$id]);

            throw $exception;
        }

        return $location;
    }

    protected function invalidateLocationCaches(int $siteId, int $userId): void
    {
        $this->bumpCacheVersion(self::SITE_CACHE_VERSION_KEY . $siteId);
        $this->bumpCacheVersion(self::USER_CACHE_VERSION_KEY . $userId);
    }

    protected function bumpCacheVersion(string $key): void
    {
        Cache::forever($key, ((int) Cache::get($key, 0)) + 1);
    }

    protected function siteListCacheKey(int $siteId, array $options): string
    {
        $version = (int) Cache::get(self::SITE_CACHE_VERSION_KEY . $siteId, 0);

        return self::SITE_LIST_CACHE_KEY . $siteId . ':v' . $version . ':' . md5(json_encode($options));
    }

    protected function userListCacheKey(int $userId, array $options): string
    {
        $version = (int) Cache::get(self::USER_CACHE_VERSION_KEY . $userId, 0);

        return self::USER_LIST_CACHE_KEY . $userId . ':v' . $version . ':' . md5(json_encode($options));
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
        $model = new BusinessLocation();
        $connection = $model->getConnectionName();

        return Schema::connection($connection)->hasColumn(
            $model->getTable(),
            'is_active'
        );
    }
}
