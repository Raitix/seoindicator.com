<?php

namespace App\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\NominatimServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class NominatimService implements NominatimServiceInterface
{
    public const DEFAULT_BASE_URL = 'https://nominatim.openstreetmap.org';
    public const CACHE_TTL_DAYS = 30;
    public const RATE_LIMIT_SECONDS = 1;

    protected string $baseUrl;
    protected string $userAgent;
    protected static ?float $lastRequestTime = null;

    public function __construct()
    {
        $this->baseUrl = Config::get('services.nominatim.base_url', self::DEFAULT_BASE_URL);
        $this->userAgent = $this->buildUserAgent();
    }

    /**
     * Geocode a free-form address string into coordinates.
     *
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function geocode(string $address): ?array
    {
        $cacheKey = 'geocode:' . md5($address);

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Apply rate limiting for uncached requests
        $this->throttle();

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/search", [
                'q' => $address,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
                return null;
            }

            $result = [
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon'],
                'display_name' => $data[0]['display_name'] ?? '',
            ];

            // Cache for 30 days
            Cache::put($cacheKey, $result, now()->addDays(self::CACHE_TTL_DAYS));

            return $result;
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * Reverse geocode coordinates into a human-readable address string.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $cacheKey = 'reverse_geocode:' . md5("{$lat},{$lng}");

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Apply rate limiting for uncached requests
        $this->throttle();

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->get("{$this->baseUrl}/reverse", [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'jsonv2',
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data) || !isset($data['display_name'])) {
                return null;
            }

            $result = $data['display_name'];

            // Cache for 30 days
            Cache::put($cacheKey, $result, now()->addDays(self::CACHE_TTL_DAYS));

            return $result;
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * Build the User-Agent header according to Nominatim ToS.
     */
    protected function buildUserAgent(): string
    {
        $customUserAgent = Config::get('services.nominatim.user_agent');

        if ($customUserAgent) {
            return $customUserAgent;
        }

        $appName = Config::get('app.name', 'SEOIndicator');
        $contactEmail = Config::get('services.nominatim.contact_email', 'ops@example.com');

        return "{$appName} ({$contactEmail})";
    }

    /**
     * Throttle requests to 1 per second (Nominatim requirement).
     */
    protected function throttle(): void
    {
        if (self::$lastRequestTime !== null) {
            $elapsed = microtime(true) - self::$lastRequestTime;
            $sleepTime = self::RATE_LIMIT_SECONDS - $elapsed;

            if ($sleepTime > 0) {
                usleep((int) ($sleepTime * 1_000_000));
            }
        }

        self::$lastRequestTime = microtime(true);
    }
}
