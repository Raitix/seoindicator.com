<?php

namespace App\Services\Fetchers;

use App\Services\Fetchers\Exceptions\FetchException;
use App\Services\Fetchers\Exceptions\NetworkException;
use App\Services\Fetchers\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Http;

/**
 * Fetches keyword suggestions from Google Suggest API.
 *
 * Uses the free Google Suggest API to get keyword suggestions
 * based on a seed keyword. Includes caching and error handling.
 *
 * @see TASK-5-4
 * @see SPEC-005
 */
class GoogleSuggestFetcher implements SuggestFetcherInterface
{
    /**
     * Google Suggest API endpoint.
     */
    protected const API_URL = 'http://suggestqueries.google.com/complete/search';

    /**
     * Default cache TTL in seconds (1 hour).
     */
    protected const DEFAULT_TTL = 3600;

    /**
     * Default request timeout in seconds.
     */
    protected const DEFAULT_TIMEOUT = 10;

    /**
     * Cache TTL in seconds.
     */
    protected int $ttl;

    /**
     * Request timeout in seconds.
     */
    protected int $timeout;

    /**
     * In-memory cache for current request.
     */
    protected array $cache = [];

    /**
     * Create a new GoogleSuggestFetcher instance.
     *
     * @param int $ttl Cache TTL in seconds
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(int $ttl = self::DEFAULT_TTL, int $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->ttl = $ttl;
        $this->timeout = $timeout;
    }

    /**
     * Fetch suggestions for a keyword.
     *
     * @param string $keyword
     * @return array<string> List of suggestion strings
     */
    public function fetch(string $keyword): array
    {
        // Normalize keyword
        $keyword = trim($keyword);

        // Handle empty keyword
        if (empty($keyword)) {
            return [];
        }

        // Check cache
        $cacheKey = md5($keyword);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $suggestions = $this->makeRequest($keyword);
            $this->cache[$cacheKey] = $suggestions;
            return $suggestions;
        } catch (RateLimitException | FetchException | NetworkException $e) {
            throw $e;
        }
    }

    /**
     * Make the fetcher invokable.
     *
     * @param string $keyword
     * @return array
     */
    public function __invoke(string $keyword): array
    {
        return $this->fetch($keyword);
    }

    /**
     * Make HTTP request to Google Suggest API.
     *
     * @param string $keyword
     * @return array<string>
     * @throws RateLimitException
     * @throws FetchException
     * @throws NetworkException
     */
    protected function makeRequest(string $keyword): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get(self::API_URL, [
                    'client' => 'firefox',
                    'q' => $keyword,
                ]);

            // Handle HTTP status codes
            if ($response->status() === 429) {
                throw new RateLimitException('Rate limited by Google Suggest API');
            }

            if ($response->status() >= 500) {
                throw new FetchException('Server error from Google Suggest API');
            }

            // 404 and other client errors return empty
            if ($response->status() >= 400) {
                return [];
            }

            return $this->parseResponse($response->body());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new NetworkException('Network error: ' . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new FetchException('Request error: ' . $e->getMessage());
        }
    }

    /**
     * Parse the Google Suggest API response.
     *
     * Response format: ["original query", ["suggestion1", "suggestion2", ...]]
     *
     * @param string $body
     * @return array<string>
     */
    protected function parseResponse(string $body): array
    {
        // Try to decode JSON
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        // Validate structure
        if (!is_array($data) || count($data) < 2) {
            return [];
        }

        // Extract suggestions array (second element)
        $suggestions = $data[1] ?? [];

        // Filter to only string values
        return array_values(array_filter($suggestions, 'is_string'));
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
