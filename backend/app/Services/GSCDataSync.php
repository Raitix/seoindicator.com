<?php

namespace App\Services;

use App\Models\GSCConnection;
use App\Models\GSCData;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use RuntimeException;

/**
 * GSC Data Sync Service for Google Search Console API integration.
 *
 * Handles fetching and storing Search Console data:
 * - Daily sync jobs for all connected projects
 * - Historical import with custom date ranges
 * - Rate limiting with exponential backoff
 * - Batch processing for large datasets
 *
 * @see https://developers.google.com/webmaster-tools/search-console-api-original/v3/searchanalytics
 */
class GSCDataSync
{
    /**
     * Google Search Console API base URL.
     */
    protected const API_BASE_URL = 'https://searchconsole.googleapis.com/v1';

    /**
     * Maximum rows per API request (GSC API limit).
     */
    protected const MAX_ROWS_PER_REQUEST = 25000;

    /**
     * Default days to look back for sync (GSC data delay).
     */
    protected const DEFAULT_LOOKBACK_DAYS = 3;

    /**
     * Maximum days per API request.
     */
    protected const MAX_DAYS_PER_REQUEST = 500;

    /**
     * Rate limit lock duration in seconds.
     */
    protected const RATE_LIMIT_LOCK_DURATION = 60;

    /**
     * Maximum retry attempts for rate-limited requests.
     */
    protected const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Base delay for exponential backoff (seconds).
     */
    protected const BACKOFF_BASE_DELAY = 1;

    /**
     * Sync lock duration in seconds.
     */
    protected const SYNC_LOCK_DURATION = 300;

    /**
     * OAuth service for token management.
     */
    private OAuthService $oauthService;

    /**
     * Create a new GSCDataSync instance.
     */
    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Sync GSC data for a specific project.
     *
     * @param Project $project The project to sync
     * @param string|null $fromDate Optional start date (Y-m-d), defaults to 3 days ago
     * @param string|null $toDate Optional end date (Y-m-d), defaults to yesterday
     * @return array Sync results with counts and status
     * @throws RuntimeException If sync fails
     */
    public function syncProject(Project $project, ?string $fromDate = null, ?string $toDate = null): array
    {
        $connection = $project->gscConnection;

        if (!$connection || !$connection->isActive()) {
            throw new RuntimeException("No active GSC connection for project: {$project->url}");
        }

        // Acquire sync lock to prevent concurrent syncs
        $lockKey = "gsc_sync:{$connection->id}";
        $lock = Cache::lock($lockKey, self::SYNC_LOCK_DURATION);

        if (!$lock->get()) {
            throw new RuntimeException("Sync already in progress for project: {$project->url}");
        }

        try {
            // Ensure valid token
            if (!$this->oauthService->ensureValidToken($connection)) {
                throw new RuntimeException("Failed to refresh token for connection: {$connection->id}");
            }

            // Calculate date range
            $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::yesterday()->subDays(self::DEFAULT_LOOKBACK_DAYS);
            $endDate = $toDate ? Carbon::parse($toDate) : Carbon::yesterday();

            // Validate date range
            if ($startDate->gt($endDate)) {
                throw new RuntimeException("Start date must be before end date");
            }

            // Fetch and store data
            $result = $this->fetchAndStoreData($project, $connection, $startDate, $endDate);

            // Update last synced timestamp
            $connection->markAsSynced();

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Sync all active GSC connections.
     *
     * @return array Results for each connection
     */
    public function syncAllConnections(): array
    {
        $results = [];
        $connections = GSCConnection::readyForSync()->with('user.projects')->get();

        foreach ($connections as $connection) {
            try {
                // Find project associated with this connection
                $project = Project::where('user_id', $connection->user_id)
                    ->where('url', 'like', '%' . $connection->site_url . '%')
                    ->first();

                if (!$project) {
                    Log::warning("No project found for GSC connection", [
                        'connection_id' => $connection->id,
                        'site_url' => $connection->site_url,
                    ]);
                    continue;
                }

                $results[$connection->id] = $this->syncProject($project);
            } catch (RuntimeException $e) {
                Log::error("Failed to sync GSC connection", [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$connection->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Fetch a batch of data from GSC API.
     *
     * @param GSCConnection $connection The GSC connection
     * @param array $params API request parameters
     * @return array Parsed API response
     * @throws RuntimeException If API request fails
     */
    public function fetchBatch(GSCConnection $connection, array $params): array
    {
        $url = self::API_BASE_URL . '/sites/' . urlencode($connection->property_url) . '/searchAnalytics/query';

        $attempt = 0;
        $lastError = null;

        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            $attempt++;

            try {
                $response = Http::withToken($connection->access_token)
                    ->timeout(60)
                    ->post($url, $params);

                if ($response->successful()) {
                    return $this->parseResponse($response->json());
                }

                // Handle rate limiting
                if ($response->status() === 429) {
                    $this->handleRateLimit($response, $attempt);
                    continue;
                }

                // Handle other errors
                $error = $response->json('error.message', 'Unknown API error');
                throw new RuntimeException("GSC API error: {$error}");
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e->getMessage();
                $this->waitForBackoff($attempt);
            }
        }

        throw new RuntimeException("Failed to fetch GSC data after {$attempt} attempts: {$lastError}");
    }

    /**
     * Handle rate limiting with exponential backoff.
     *
     * @param \Illuminate\Http\Client\Response $response The rate-limited response
     * @param int $attempt Current attempt number
     * @throws RuntimeException If max attempts exceeded
     */
    public function handleRateLimit($response, int $attempt): void
    {
        if ($attempt >= self::MAX_RETRY_ATTEMPTS) {
            throw new RuntimeException("Max retry attempts exceeded for rate-limited request");
        }

        // Get retry-after header or use exponential backoff
        $retryAfter = $response->header('Retry-After');
        $delay = $retryAfter ? (int) $retryAfter : $this->calculateBackoffDelay($attempt);

        Log::warning("GSC API rate limited, waiting {$delay} seconds", [
            'attempt' => $attempt,
            'retry_after' => $retryAfter,
        ]);

        // Set rate limit lock
        Cache::put('gsc_rate_limit', true, $delay);

        sleep($delay);
    }

    /**
     * Calculate exponential backoff delay.
     *
     * @param int $attempt Current attempt number
     * @return int Delay in seconds
     */
    protected function calculateBackoffDelay(int $attempt): int
    {
        // Exponential backoff with jitter: 1s, 2s, 4s, 8s, 16s
        $delay = self::BACKOFF_BASE_DELAY * pow(2, $attempt - 1);
        
        // Add jitter (random 0-500ms)
        $jitter = rand(0, 500) / 1000;
        
        return (int) ceil($delay + $jitter);
    }

    /**
     * Wait for backoff period.
     *
     * @param int $attempt Current attempt number
     */
    protected function waitForBackoff(int $attempt): void
    {
        $delay = $this->calculateBackoffDelay($attempt);
        sleep($delay);
    }

    /**
     * Fetch and store data for a date range.
     *
     * @param Project $project The project
     * @param GSCConnection $connection The GSC connection
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Sync results
     */
    protected function fetchAndStoreData(Project $project, GSCConnection $connection, Carbon $startDate, Carbon $endDate): array
    {
        $totalRows = 0;
        $totalStored = 0;
        $errors = [];

        // Process in date chunks if range is too large
        $dateChunks = $this->chunkDateRange($startDate, $endDate);

        foreach ($dateChunks as $chunk) {
            $params = [
                'startDate' => $chunk['start']->format('Y-m-d'),
                'endDate' => $chunk['end']->format('Y-m-d'),
                'dimensions' => ['query', 'page'],
                'rowLimit' => self::MAX_ROWS_PER_REQUEST,
            ];

            try {
                $data = $this->fetchBatch($connection, $params);
                $totalRows += count($data);

                $stored = $this->storeData($project, $data);
                $totalStored += $stored;
            } catch (RuntimeException $e) {
                $errors[] = [
                    'date_range' => "{$chunk['start']} to {$chunk['end']}",
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to fetch GSC data for date range", [
                    'project_id' => $project->id,
                    'start_date' => $chunk['start']->toDateString(),
                    'end_date' => $chunk['end']->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => empty($errors),
            'rows_fetched' => $totalRows,
            'rows_stored' => $totalStored,
            'errors' => $errors,
        ];
    }

    /**
     * Store fetched data in database.
     *
     * @param Project $project The project
     * @param array $data Parsed GSC data
     * @return int Number of records stored
     */
    protected function storeData(Project $project, array $data): int
    {
        $stored = 0;

        foreach ($data as $row) {
            // Use upsert to handle duplicates
            $stored += GSCData::upsert(
                [
                    'project_id' => $project->id,
                    'query' => $row['query'],
                    'page' => $row['page'],
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'ctr' => $row['ctr'],
                    'avg_position' => $row['position'],
                    'data_date' => $row['date'],
                ],
                ['project_id', 'query', 'page', 'data_date'],
                ['clicks', 'impressions', 'ctr', 'avg_position']
            );
        }

        return $stored;
    }

    /**
     * Parse GSC API response.
     *
     * @param array $response Raw API response
     * @return array Parsed data rows
     */
    protected function parseResponse(array $response): array
    {
        $parsed = [];

        if (!isset($response['rows'])) {
            return $parsed;
        }

        foreach ($response['rows'] as $row) {
            $keys = $row['keys'] ?? [];

            $parsed[] = [
                'query' => $keys[0] ?? '',
                'page' => $keys[1] ?? null,
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
                'position' => $row['position'] ?? 0,
                'date' => $keys[2] ?? null, // If date dimension is included
            ];
        }

        return $parsed;
    }

    /**
     * Chunk a date range into smaller segments.
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of date chunks
     */
    protected function chunkDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $chunks = [];
        $currentStart = $startDate->copy();

        while ($currentStart->lte($endDate)) {
            $currentEnd = $currentStart->copy()->addDays(self::MAX_DAYS_PER_REQUEST - 1);
            
            if ($currentEnd->gt($endDate)) {
                $currentEnd = $endDate->copy();
            }

            $chunks[] = [
                'start' => $currentStart->copy(),
                'end' => $currentEnd->copy(),
            ];

            $currentStart = $currentEnd->copy()->addDay();
        }

        return $chunks;
    }

    /**
     * Import historical data for a project.
     *
     * @param Project $project The project
     * @param string $fromDate Start date (Y-m-d)
     * @param string $toDate End date (Y-m-d)
     * @return array Import results
     */
    public function importHistorical(Project $project, string $fromDate, string $toDate): array
    {
        $startDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($toDate);

        // GSC API only allows data up to 16 months old
        $oldestAllowed = Carbon::now()->subMonths(16);
        
        if ($startDate->lt($oldestAllowed)) {
            Log::warning("Historical import date adjusted to 16-month limit", [
                'original' => $fromDate,
                'adjusted' => $oldestAllowed->toDateString(),
            ]);
            $startDate = $oldestAllowed;
        }

        return $this->syncProject($project, $startDate->toDateString(), $endDate->toDateString());
    }

    /**
     * Get sync status for a project.
     *
     * @param Project $project The project
     * @return array Sync status information
     */
    public function getSyncStatus(Project $project): array
    {
        $connection = $project->gscConnection;

        if (!$connection) {
            return [
                'connected' => false,
                'last_sync' => null,
                'data_available' => false,
            ];
        }

        $lastData = GSCData::where('project_id', $project->id)
            ->latest('data_date')
            ->first();

        return [
            'connected' => $connection->isActive(),
            'connection_status' => $connection->status,
            'last_sync' => $connection->last_synced_at?->toIso8601String(),
            'last_data_date' => $lastData?->data_date?->toDateString(),
            'data_available' => $lastData !== null,
            'total_queries' => GSCData::where('project_id', $project->id)
                ->distinct('query')
                ->count('query'),
        ];
    }

    /**
     * Clear all GSC data for a project.
     *
     * @param Project $project The project
     * @return int Number of records deleted
     */
    public function clearProjectData(Project $project): int
    {
        return GSCData::where('project_id', $project->id)->delete();
    }
}
