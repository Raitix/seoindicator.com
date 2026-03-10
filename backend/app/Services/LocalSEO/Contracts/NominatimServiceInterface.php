<?php

namespace App\Services\LocalSEO\Contracts;

interface NominatimServiceInterface
{
    /**
     * Geocode an address to coordinates.
     *
     * @param string $address Street address
     * @param string $city City
     * @param string $state State/Province
     * @param string $postalCode Postal/ZIP code
     * @param string $country Country code (ISO 3166-1 alpha-2)
     * @return array|null ['latitude' => float, 'longitude' => float, 'display_name' => string] or null on failure
     */
    public function geocode(
        string $address,
        string $city,
        string $state = '',
        string $postalCode = '',
        string $country = ''
    ): ?array;

    /**
     * Reverse geocode coordinates to address.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null Address components or null on failure
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array;

    /**
     * Check if geocoding is available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Clear the geocoding cache.
     *
     * @return void
     */
    public function clearCache(): void;
}
