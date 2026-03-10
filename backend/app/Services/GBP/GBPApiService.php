<?php

namespace App\Services\GBP;

use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Google Business Profile API client service.
 *
 * Red-phase compile-safe scaffold for TASK-35-8. The concrete HTTP request,
 * retry, refresh, and normalization pipeline is intentionally left for the
 * Developer pass; tests in GBPApiServiceTest define the expected behavior.
 *
 * @see TASK-35-8
 * @see SPEC-035
 */
class GBPApiService implements GBPApiServiceInterface
{
    public function __construct(
        protected ?GoogleOAuthServiceInterface $oauthService = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAccounts(string $accessToken): array
    {
        return $this->requestJson('GET', '/accounts', $accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocations(string $accountId, string $accessToken): array
    {
        $accountName = $this->normalizeAccountName($accountId);

        return $this->requestJson('GET', "/{$accountName}/locations", $accessToken);
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
     * Placeholder request pipeline.
     *
     * @param string $method
     * @param string $path
     * @param string $accessToken
     * @param array $query
     * @param array $payload
     * @return array
     */
    protected function requestJson(string $method, string $path, string $accessToken, array $query = [], array $payload = []): array
    {
        throw new RuntimeException('GBPApiService request pipeline not implemented yet.');
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
     * Resolve the refresh token associated with an access token.
     *
     * The implementation is intentionally deferred to the Developer pass
     * because the current task contract only passes an access token, while the
     * refresh workflow depends on TASK-35-7 token storage decisions.
     */
    protected function resolveRefreshToken(string $accessToken): ?string
    {
        return null;
    }

    /**
     * Backoff hook used by retry tests.
     */
    protected function pauseMilliseconds(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
