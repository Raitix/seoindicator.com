<?php

namespace App\Services\LocalSEO\Contracts;

interface NominatimServiceInterface
{
    /**
     * Geocode a free-form address string into coordinates.
     *
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function geocode(string $address): ?array;

    /**
     * Reverse geocode coordinates into a human-readable address string.
     */
    public function reverseGeocode(float $lat, float $lng): ?string;
}
