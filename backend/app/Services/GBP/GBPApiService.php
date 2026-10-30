<?php

namespace App\Services\GBP;

use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Google Business Profile API client service.
 *
 * Implements the GBP API v4 client with:
 * - Authenticated requests via Bearer token
 * - Automatic token refresh on 401 responses
 * - Exponential backoff retry on 429 rate limiting
 * - Location data normalization
 *
 * @see TASK-35-8
 * @see SPEC-035
 */
class GBPApiService implements GBPApiServiceInterface
{
    /**
     * Maximum retry attempts for rate-limited requests.
     */
    protected const MAX_RETRIES = 3;

    /**
     * Base delay in milliseconds for exponential backoff.
     */
    protected const BASE_DELAY_MS = 1000;

    /**
     * Map of access tokens to refresh tokens for 401 retry support.
     * This is populated internally when the service needs to track tokens.
     */
    protected array $tokenMap = [];

    public function __construct(
        protected ?GoogleOAuthServiceInterface $oauthService = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAccounts(string $accessToken): array
    {
        $response = $this->requestJson('GET', '/accounts', $accessToken);

        return $response['accounts'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLocations(string $accountId, string $accessToken): array
    {
        $accountName = $this->normalizeAccountName($accountId);

        $response = $this->requestJson('GET', "/{$accountName}/locations", $accessToken);

        $locations = $response['locations'] ?? [];

        return array_map(fn ($location) => $this->normalizeLocationData($location), $locations);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation(string $locationName, string $accessToken): array
    {
        return $this->requestJson('GET', '/' . ltrim($locationName, '/'), $accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function getInsights(string $locationName, string $accessToken, array $dateRange): array
    {
        if (!isset($dateRange['start'], $dateRange['end'])) {
            throw new InvalidArgumentException('GBP insights date range must include start and end values.');
        }

        $accountName = $this->extractAccountName($locationName);

        return $this->requestJson('POST', "/{$accountName}/locations:reportInsights", $accessToken, [], [
            'locationNames' => [$locationName],
            'basicRequest' => [
                'metricRequests' => [
                    ['metric' => 'ALL'],
                ],
                'timeRange' => [
                    'startTime' => $dateRange['start'],
                    'endTime' => $dateRange['end'],
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviews(string $locationName, string $accessToken, int $pageSize = 50, ?string $pageToken = null): array
    {
        $query = [
            'pageSize' => $pageSize,
        ];

        if ($pageToken !== null) {
            $query['pageToken'] = $pageToken;
        }

        return $this->requestJson('GET', '/' . ltrim($locationName, '/') . '/reviews', $accessToken, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function respondToReview(string $locationName, string $reviewId, string $reply, string $accessToken): bool
    {
        if (trim($reply) === '') {
            throw new InvalidArgumentException('GBP review reply cannot be empty.');
        }

        $this->requestJson('PUT', '/' . ltrim($locationName, '/') . "/reviews/{$reviewId}/reply", $accessToken, [], [
            'comment' => $reply,
        ]);

        return true;
    }

    /**
     * Core HTTP request pipeline with retry logic.
     *
     * Handles:
     * - Bearer token authentication
     * - 401 responses with automatic token refresh retry
     * - 429 rate limiting with exponential backoff
     * - API error response handling
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $path API path (relative to base URL)
     * @param string $accessToken OAuth access token
     * @param array $query Query parameters
     * @param array $payload Request body (for POST/PUT)
     * @return array Decoded JSON response
     * @throws RuntimeException When API returns an error after retries
     */
    protected function requestJson(string $method, string $path, string $accessToken, array $query = [], array $payload = []): array
    {
        $url = $this->baseUrl() . '/' . ltrim($path, '/');

        $attempt = 0;
        $currentToken = $accessToken;
        $hasRetried401 = false;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            try {
                $response = $this->sendRequest($method, $url, $currentToken, $query, $payload);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                $statusCode = $response->status();

                // Handle 401 Unauthorized - attempt token refresh once
                if ($statusCode === 401 && !$hasRetried401) {
                    $refreshToken = $this->resolveRefreshToken($currentToken);

                    if ($refreshToken && $this->oauthService) {
                        $newTokens = $this->oauthService->refreshAccessToken($refreshToken);

                        if ($newTokens && isset($newTokens['access_token'])) {
                            $currentToken = $newTokens['access_token'];
                            $hasRetried401 = true;
                            $attempt--; // Don't count 401 retry against max attempts
                            continue;
                        }
                    }

                    throw new RuntimeException('GBP API authentication failed and token refresh unavailable.');
                }

                // Handle 429 Rate Limited - exponential backoff
                if ($statusCode === 429) {
                    $delayMs = self::BASE_DELAY_MS * pow(2, $attempt - 1);
                    $this->pauseMilliseconds($delayMs);
                    continue;
                }

                // Other API errors
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? "GBP API error (HTTP {$statusCode})";

                throw new RuntimeException($errorMessage, $statusCode);
            } catch (ConnectionException $e) {
                if ($attempt >= self::MAX_RETRIES) {
                    throw new RuntimeException('GBP API connection failed after retries: ' . $e->getMessage(), 0, $e);
                }

                $this->pauseMilliseconds(self::BASE_DELAY_MS * $attempt);
            }
        }

        throw new RuntimeException('GBP API request failed after maximum retries.');
    }

    /**
     * Send an HTTP request to the GBP API.
     *
     * @param string $method
     * @param string $url
     * @param string $accessToken
     * @param array $query
     * @param array $payload
     * @return \Illuminate\Http\Client\Response
     */
    protected function sendRequest(string $method, string $url, string $accessToken, array $query = [], array $payload = [])
    {
        $request = Http::withToken($accessToken)
            ->acceptJson()
            ->contentType('application/json');

        // Add query parameters
        if (!empty($query)) {
            $request = $request->withQueryParameters($query);
        }

        // Send request based on method
        if ($method === 'GET') {
            return $request->get($url);
        }

        if ($method === 'POST') {
            return $request->post($url, $payload);
        }

        if ($method === 'PUT') {
            return $request->put($url, $payload);
        }

        if ($method === 'DELETE') {
            return $request->delete($url);
        }

        return $request->send($method, $url, ['body' => json_encode($payload)]);
    }

    /**
     * Base URL for the GBP v4 REST API.
     */
    protected function baseUrl(): string
    {
        return rtrim((string) config('services.google.business_profile_base_url', 'https://mybusiness.googleapis.com/v4'), '/');
    }

    /**
     * Normalize raw account IDs into accounts/{id} resource names.
     */
    protected function normalizeAccountName(string $accountId): string
    {
        return str_starts_with($accountId, 'accounts/')
            ? $accountId
            : 'accounts/' . ltrim($accountId, '/');
    }

    /**
     * Extract the parent account resource name from a full location name.
     */
    protected function extractAccountName(string $locationName): string
    {
        $segments = explode('/', trim($locationName, '/'));

        if (count($segments) < 4 || $segments[0] !== 'accounts') {
            throw new InvalidArgumentException('GBP location name must use accounts/{accountId}/locations/{locationId} format.');
        }

        return implode('/', array_slice($segments, 0, 2));
    }

    /**
     * Normalize GBP location data to our standard format.
     *
     * Transforms:
     * - locationKey.placeId -> place_id
     * - title -> name
     * - storefrontAddress -> address (combined string)
     *
     * @param array $location Raw GBP location data
     * @return array Normalized location data
     */
    protected function normalizeLocationData(array $location): array
    {
        $normalized = [
            'name' => $location['title'] ?? $location['locationName'] ?? null,
            'place_id' => $location['locationKey']['placeId'] ?? null,
            'address' => $this->formatAddress($location['storefrontAddress'] ?? []),
        ];

        // Preserve original data alongside normalized fields
        return array_merge($location, $normalized);
    }

    /**
     * Format a GBP address object into a single-line address string.
     *
     * @param array $address GBP storefrontAddress object
     * @return string Formatted address
     */
    protected function formatAddress(array $address): string
    {
        $parts = [];

        // Add address lines
        if (!empty($address['addressLines'])) {
            $parts = array_merge($parts, $address['addressLines']);
        }

        // Add locality (city)
        if (!empty($address['locality'])) {
            $parts[] = $address['locality'];
        }

        // Add postal code
        if (!empty($address['postalCode'])) {
            $parts[] = $address['postalCode'];
        }

        // Add region code (country)
        if (!empty($address['regionCode'])) {
            $parts[] = $address['regionCode'];
        }

        return implode(', ', $parts);
    }

    /**
     * Resolve the refresh token associated with an access token.
     *
     * This method can be overridden by subclasses or configured via token map
     * to enable automatic token refresh on 401 responses.
     *
     * @param string $accessToken
     * @return string|null Refresh token if available
     */
    protected function resolveRefreshToken(string $accessToken): ?string
    {
        return $this->tokenMap[$accessToken] ?? null;
    }

    /**
     * Register a refresh token for an access token.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @return void
     */
    public function registerRefreshToken(string $accessToken, string $refreshToken): void
    {
        $this->tokenMap[$accessToken] = $refreshToken;
    }

    /**
     * Backoff hook used by retry tests.
     *
     * @param int $milliseconds Delay in milliseconds
     */
    protected function pauseMilliseconds(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
