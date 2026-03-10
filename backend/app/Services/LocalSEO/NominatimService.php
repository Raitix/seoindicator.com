<?php

namespace App\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use BadMethodCallException;

class NominatimService implements NominatimServiceInterface
{
    public const DEFAULT_BASE_URL = 'https://nominatim.openstreetmap.org';
    public const CACHE_TTL_DAYS = 30;
    public const RATE_LIMIT_SECONDS = 1;

    /**
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function geocode(string $address): ?array
    {
        throw new BadMethodCallException('NominatimService::geocode() is not implemented yet. Implement against TASK-35-6 tests.');
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        throw new BadMethodCallException('NominatimService::reverseGeocode() is not implemented yet. Implement against TASK-35-6 tests.');
    }
}
