<?php

namespace App\Services;

use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\GSCConnection;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Models\AnchorText;
use App\Repositories\BacklinkRepository;
use App\Repositories\ReferringDomainRepository;
use App\Repositories\AnchorTextRepository;
use App\Services\Backlinks\BacklinkSnapshotService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * GSC Backlink Fetcher Service for Google Search Console Links API integration.
 *
 * Fetches referring domains and sample backlinks from GSC API.
 * Handles pagination, rate limiting, and data storage.
 *
 * @see https://developers.google.com/webmaster-tools/search-console-api-original/v3/sites-links
 */
class GSCBacklinkFetcher
{
    /**
     * Google Search Console API base URL.
     */
    protected const API_BASE_URL = 'https://www.googleapis.com/webmasters/v3';

    /**
     * Maximum pages per API request for pagination.
     */
    protected const MAX_RESULTS_PER_PAGE = 100;

    /**
     * Maximum retry attempts for rate-limited requests.
     */
    protected const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Base delay for exponential backoff (seconds).
     */
    protected const BACKOFF_BASE_DELAY = 1;

    /**
     * Sync lock duration in seconds (5 minutes).
     */
    protected const SYNC_LOCK_DURATION = 300;

    /**
     * Cache TTL for backlink data (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Maximum requests per minute for rate limiting.
     */
    protected const MAX_REQUESTS_PER_MINUTE = 200;

    /**
     * OAuth service for token management.
     */
    private OAuthService $oauthService;

    /**
     * Backlink repository for data storage.
     */
    private BacklinkRepository $backlinkRepository;

    /**
     * ReferringDomain repository for data storage.
     */
    private ReferringDomainRepository $referringDomainRepository;

    /**
     * AnchorText repository for data storage.
     */
    private AnchorTextRepository $anchorTextRepository;

    /**
     * Progress callback for sync operations.
     */
    private $progressCallback = null;

    /**
     * Cancellation flag for sync operations.
     */
    private bool $cancelled = false;

    /**
     * Create a new GSCBacklinkFetcher instance.
     */
    public function __construct(
        OAuthService $oauthService,
        BacklinkRepository $backlinkRepository,
        ReferringDomainRepository $referringDomainRepository,
        AnchorTextRepository $anchorTextRepository
    ) {
        $this->oauthService = $oauthService;
        $this->backlinkRepository = $backlinkRepository;
        $this->referringDomainRepository = $referringDomainRepository;
        $this->anchorTextRepository = $anchorTextRepository;
    }

    /**
     * Fetch referring domains from GSC Links API.
     *
     * @param GSCConnection $connection The GSC connection
     * @param string|null $cacheKey Optional cache key for results
     * @return array List of referring domains with backlink counts
     * @throws RuntimeException If API request fails
     */
    public function fetchReferringDomains(GSCConnection $connection, ?string $cacheKey = null): array
    {
        if (!$connection->isActive()) {
            throw new RuntimeException("Connection is not active: {$connection->id}");
        }

        // Check cache first
        if ($cacheKey) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Ensure valid token
        if (!$this->oauthService->ensureValidToken($connection)) {
            throw new RuntimeException("Failed to refresh token for connection: {$connection->id}");
        }

        $allDomains = [];
        $nextPageToken = null;

        do {
            $response = $this->fetchSitesPage($connection, $nextPageToken);

            if (isset($response['sites'])) {
                $domains = $this->parseReferringDomains($response['sites'], $connection);
                $allDomains = $this->mergeDomainCounts($allDomains, $domains);
            }

            $nextPageToken = $response['nextPageToken'] ?? null;
        } while ($nextPageToken && !$this->cancelled);

        // Cache results
        if ($cacheKey) {
            Cache::put($cacheKey, $allDomains, self::CACHE_TTL);
        }

        return array_values($allDomains);
    }

    /**
     * Fetch sample backlinks for a specific referring domain.
     *
     * @param GSCConnection $connection The GSC connection
     * @param string $targetDomain The target domain to filter by
     * @return array List of sample backlinks
     * @throws RuntimeException If API request fails
     */
    public function fetchSampleBacklinks(GSCConnection $connection, string $targetDomain): array
    {
        if (!$connection->isActive()) {
            throw new RuntimeException("Connection is not active: {$connection->id}");
        }

        // Ensure valid token
        if (!$this->oauthService->ensureValidToken($connection)) {
            throw new RuntimeException("Failed to refresh token for connection: {$connection->id}");
        }

        $allBacklinks = [];
        $nextPageToken = null;

        do {
            $response = $this->fetchPagesPage($connection, $targetDomain, $nextPageToken);

            if (isset($response['pages'])) {
                $backlinks = $this->parseSampleBacklinks($response['pages'], $targetDomain);
                $allBacklinks = array_merge($allBacklinks, $backlinks);
            }

            $nextPageToken = $response['nextPageToken'] ?? null;
        } while ($nextPageToken && !$this->cancelled);

        return $allBacklinks;
    }

    /**
     * Sync all backlinks for a project.
     *
     * @param Project $project The project to sync
     * @param callable|null $progress Optional progress callback
     * @return array Sync results with counts and status
     * @throws RuntimeException If sync fails
     */
    public function syncProject(Project $project, ?callable $progress = null): array
    {
        $this->progressCallback = $progress;
        $this->cancelled = false;

        // Find GSC connection for project
        $connection = $this->findConnectionForProject($project);

        if (!$connection) {
            throw new RuntimeException("No GSC connection found for project: {$project->id}");
        }

        if (!$connection->isActive()) {
            throw new RuntimeException("GSC connection is not active for project: {$project->id}");
        }

        // Acquire sync lock
        $lockKey = "gsc_backlink_sync:{$project->id}";
        $lock = Cache::lock($lockKey, self::SYNC_LOCK_DURATION);

        if (!$lock->get()) {
            throw new RuntimeException("Sync already in progress for project: {$project->id}");
        }

        try {
            $results = $this->performSync($project, $connection);

            // Invalidate cache after sync
            $this->invalidateCache($project->id);

            return $results;
        } finally {
            $lock->release();
            $this->progressCallback = null;
        }
    }

    /**
     * Cancel an ongoing sync operation.
     */
    public function cancel(): void
    {
        $this->cancelled = true;
    }

    /**
     * Get cached backlinks for a project.
     *
     * @param int $projectId
     * @return array|null
     */
    public function getCachedBacklinks(int $projectId): ?array
    {
        return Cache::get($this->getCacheKey($projectId));
    }

    /**
     * Invalidate cache for a project.
     *
     * @param int $projectId
     */
    public function invalidateCache(int $projectId): void
    {
        Cache::forget($this->getCacheKey($projectId));
    }

    /**
     * Get cache key for a project.
     *
     * @param int $projectId
     * @return string
     */
    public function getCacheKey(int $projectId): string
    {
        return "backlinks:project-{$projectId}:v1";
    }

    /**
     * Find GSC connection for a project.
     *
     * @param Project $project
     * @return GSCConnection|null
     */
    protected function findConnectionForProject(Project $project): ?GSCConnection
    {
        $domain = $project->domain ?? parse_url($project->name, PHP_URL_HOST);

        if (!$domain) {
            return null;
        }

        // Try to find connection by domain match
        return GSCConnection::where('user_id', $project->user_id)
            ->where(function ($query) use ($domain) {
                $query->where('site_url', 'like', "%{$domain}%")
                    ->orWhere('property_url', 'like', "%{$domain}%");
            })
            ->first();
    }

    /**
     * Perform the actual sync operation.
     *
     * @param Project $project
     * @param GSCConnection $connection
     * @return array
     */
    protected function performSync(Project $project, GSCConnection $connection): array
    {
        $totalDomains = 0;
        $totalBacklinks = 0;
        $errors = [];

        $this->reportProgress('Starting backlink sync', 0);

        // Fetch referring domains
        try {
            $domains = $this->fetchReferringDomains($connection);
            $totalDomains = count($domains);
        } catch (RuntimeException $e) {
            Log::error('Failed to fetch referring domains', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->reportProgress("Found {$totalDomains} referring domains", 10);

        // Track active source URLs for stale detection
        $activeSourceUrls = [];

        // Process each domain
        foreach ($domains as $index => $domainData) {
            if ($this->cancelled) {
                $this->reportProgress('Sync cancelled', -1);
                break;
            }

            try {
                $domain = $domainData['domain'];
                $backlinksCount = $domainData['backlinks_count'] ?? 0;

                // Upsert referring domain
                $this->referringDomainRepository->upsert([
                    'project_id' => $project->id,
                    'domain' => $domain,
                    'backlinks_count' => $backlinksCount,
                    'last_seen_at' => now(),
                ]);

                // Fetch sample backlinks for domain
                $samples = $this->fetchSampleBacklinks($connection, $project->domain ?? '');

                // Store sample backlinks
                foreach ($samples as $backlink) {
                    $activeSourceUrls[] = $backlink['source_url'];

                    $this->storeBacklink($project->id, $backlink);
                    $totalBacklinks++;
                }

                // Update anchor text aggregation
                $this->updateAnchorTexts($project->id, $samples);

                $progress = 10 + (($index + 1) / $totalDomains * 80);
                $this->reportProgress("Processed domain: {$domain}", (int) $progress);
            } catch (RuntimeException $e) {
                $errors[] = [
                    'domain' => $domainData['domain'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::warning('Failed to process domain', [
                    'project_id' => $project->id,
                    'domain' => $domainData['domain'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Deactivate stale backlinks
        if (!empty($activeSourceUrls)) {
            $deactivatedCount = $this->backlinkRepository->deactivateStale($project->id, $activeSourceUrls);
            $this->reportProgress("Deactivated {$deactivatedCount} stale backlinks", 90);
        }

        // Create snapshot
        $this->createSnapshot($project);

        // Update project sync timestamp
        $project->update(['backlinks_last_synced_at' => now()]);

        $this->reportProgress('Sync complete', 100);

        return [
            'success' => empty($errors),
            'domains_processed' => $totalDomains,
            'backlinks_stored' => $totalBacklinks,
            'errors' => $errors,
        ];
    }

    /**
     * Fetch a page of sites from GSC API.
     *
     * @param GSCConnection $connection
     * @param string|null $pageToken
     * @return array
     */
    protected function fetchSitesPage(GSCConnection $connection, ?string $pageToken = null): array
    {
        $url = self::API_BASE_URL . '/sites/' . urlencode($connection->property_url) . '/links';

        $params = [
            'target' => 'external',
            'permissionLevel' => 'site',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        return $this->makeApiRequest($connection, $url, $params);
    }

    /**
     * Fetch a page of backlinks from GSC API.
     *
     * @param GSCConnection $connection
     * @param string $targetDomain
     * @param string|null $pageToken
     * @return array
     */
    protected function fetchPagesPage(GSCConnection $connection, string $targetDomain, ?string $pageToken = null): array
    {
        $url = self::API_BASE_URL . '/sites/' . urlencode($connection->property_url) . '/links';

        $params = [
            'target' => 'external',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        return $this->makeApiRequest($connection, $url, $params);
    }

    /**
     * Make an API request with rate limiting and retry logic.
     *
     * @param GSCConnection $connection
     * @param string $url
     * @param array $params
     * @return array
     * @throws RuntimeException
     */
    protected function makeApiRequest(GSCConnection $connection, string $url, array $params = []): array
    {
        // Check rate limit
        $this->throttleRequests($connection->id);

        $attempt = 0;
        $lastError = null;

        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            $attempt++;

            try {
                $response = Http::withToken($connection->access_token)
                    ->timeout(30)
                    ->get($url, $params);

                // Track successful request
                $this->trackRequest($connection->id);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                // Handle rate limiting
                if ($response->status() === 429) {
                    $this->handleRateLimit($response, $attempt);
                    continue;
                }

                // Handle authorization errors
                if ($response->status() === 403) {
                    $error = $response->json('error.message', 'Insufficient permission');
                    throw new RuntimeException("Authorization failed: {$error}");
                }

                // Handle not found
                if ($response->status() === 404) {
                    throw new RuntimeException("Property not found or not verified: {$connection->property_url}");
                }

                // Handle server errors with retry
                if ($response->status() >= 500) {
                    $lastError = "Server error: {$response->status()}";
                    $this->waitForBackoff($attempt);
                    continue;
                }

                // Other errors
                $error = $response->json('error.message', 'Unknown API error');
                throw new RuntimeException("GSC API error: {$error}");
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e->getMessage();

                if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                    $this->waitForBackoff($attempt);
                    continue;
                }

                throw new RuntimeException("Connection failed after {$attempt} attempts: {$lastError}");
            } catch (\Illuminate\Http\Client\RequestException $e) {
                throw new RuntimeException("Request failed: {$e->getMessage()}");
            }
        }

        throw new RuntimeException("Failed after {$attempt} attempts: {$lastError}");
    }

    /**
     * Handle rate limiting with exponential backoff.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param int $attempt
     * @throws RuntimeException
     */
    protected function handleRateLimit($response, int $attempt): void
    {
        if ($attempt >= self::MAX_RETRY_ATTEMPTS) {
            throw new RuntimeException("Max retry attempts exceeded for rate-limited request");
        }

        $retryAfter = $response->header('Retry-After');
        $delay = $retryAfter ? (int) $retryAfter : $this->calculateBackoffDelay($attempt);

        Log::warning("GSC API rate limited, waiting {$delay} seconds", [
            'attempt' => $attempt,
            'retry_after' => $retryAfter,
        ]);

        sleep($delay);
    }

    /**
     * Calculate exponential backoff delay.
     *
     * @param int $attempt
     * @return int
     */
    protected function calculateBackoffDelay(int $attempt): int
    {
        // Pattern: 1s, 2s, 4s, 8s, 16s with jitter
        $delay = self::BACKOFF_BASE_DELAY * pow(2, $attempt - 1);
        $jitter = rand(0, 500) / 1000;

        return (int) ceil($delay + $jitter);
    }

    /**
     * Wait for backoff period.
     *
     * @param int $attempt
     */
    protected function waitForBackoff(int $attempt): void
    {
        $delay = $this->calculateBackoffDelay($attempt);
        sleep($delay);
    }

    /**
     * Throttle requests to respect rate limits.
     *
     * @param int $connectionId
     */
    protected function throttleRequests(int $connectionId): void
    {
        $cacheKey = "gsc_rate_limit:{$connectionId}";
        $count = Cache::get($cacheKey, 0);

        if ($count >= self::MAX_REQUESTS_PER_MINUTE) {
            // Wait for rate limit window to expire
            $ttl = Cache::get("{$cacheKey}:ttl", 60);
            sleep($ttl);
            Cache::put($cacheKey, 0, 60);
        }
    }

    /**
     * Track API request for rate limiting.
     *
     * @param int $connectionId
     */
    protected function trackRequest(int $connectionId): void
    {
        $cacheKey = "gsc_rate_limit:{$connectionId}";
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 60);
    }

    /**
     * Parse referring domains from GSC API response.
     *
     * @param array $sites
     * @param GSCConnection $connection
     * @return array
     */
    protected function parseReferringDomains(array $sites, GSCConnection $connection): array
    {
        $domains = [];
        $targetDomain = $this->extractDomain($connection->property_url);

        foreach ($sites as $site) {
            $url = $site['siteUrl'] ?? '';
            $domain = $this->extractDomain($url);

            if (empty($domain)) {
                continue;
            }

            // Filter out self-referencing domains
            if ($domain === $targetDomain) {
                continue;
            }

            // Normalize domain (remove www.)
            $domain = $this->normalizeDomain($domain);

            $domains[$domain] = ($domains[$domain] ?? 0) + 1;
        }

        return array_map(function ($count, $domain) {
            return [
                'domain' => $domain,
                'backlinks_count' => $count,
            ];
        }, $domains, array_keys($domains));
    }

    /**
     * Parse sample backlinks from GSC API response.
     *
     * @param array $pages
     * @param string $targetDomain
     * @return array
     */
    protected function parseSampleBacklinks(array $pages, string $targetDomain): array
    {
        $backlinks = [];
        $normalizedTargetDomain = $this->normalizeDomain($targetDomain);

        foreach ($pages as $page) {
            $sourceUrl = $page['pageUrl'] ?? '';
            $targetUrl = $page['targetUrl'] ?? '';
            $anchorText = $page['anchorText'] ?? null;
            $linkType = $page['linkType'] ?? 'dofollow';

            if (empty($sourceUrl) || empty($targetUrl)) {
                continue;
            }

            $sourceDomain = $this->extractDomain($sourceUrl);
            $sourceDomain = $this->normalizeDomain($sourceDomain);

            // Filter out self-referencing and internal links
            $targetHost = $this->normalizeDomain($this->extractDomain($targetUrl));

            if ($sourceDomain === $targetHost) {
                continue;
            }

            // Filter out internal links to target domain
            if ($sourceDomain === $normalizedTargetDomain) {
                continue;
            }

            $backlinks[] = [
                'source_url' => $sourceUrl,
                'source_domain' => $sourceDomain,
                'target_url' => $targetUrl,
                'anchor_text' => $this->sanitizeAnchorText($anchorText),
                'link_type' => $this->normalizeLinkType($linkType),
            ];
        }

        return $backlinks;
    }

    /**
     * Merge domain counts from multiple pages.
     *
     * @param array $existing
     * @param array $new
     * @return array
     */
    protected function mergeDomainCounts(array $existing, array $new): array
    {
        foreach ($new as $domainData) {
            $domain = $domainData['domain'];
            if (isset($existing[$domain])) {
                $existing[$domain] += $domainData['backlinks_count'];
            } else {
                $existing[$domain] = $domainData['backlinks_count'];
            }
        }

        return $existing;
    }

    /**
     * Extract domain from URL.
     *
     * @param string $url
     * @return string
     */
    protected function extractDomain(string $url): string
    {
        // Handle URLs without protocol
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $parsed['path'] ?? '';

        return $host;
    }

    /**
     * Normalize domain (remove www., lowercase).
     *
     * @param string $domain
     * @return string
     */
    protected function normalizeDomain(string $domain): string
    {
        $domain = strtolower($domain);
        $domain = preg_replace('/^www\./', '', $domain);

        return $domain;
    }

    /**
     * Sanitize anchor text (strip HTML, trim whitespace).
     *
     * @param string|null $text
     * @return string|null
     */
    protected function sanitizeAnchorText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Strip HTML tags
        $text = strip_tags($text);

        // Trim whitespace
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * Normalize link type.
     *
     * @param string|null $linkType
     * @return string
     */
    protected function normalizeLinkType(?string $linkType): string
    {
        if ($linkType === null || $linkType === '') {
            return 'dofollow';
        }

        $linkType = strtolower($linkType);

        // Treat nofollow, sponsored, ugc as nofollow
        if (in_array($linkType, ['nofollow', 'sponsored', 'ugc'])) {
            return 'nofollow';
        }

        return 'dofollow';
    }

    /**
     * Store a backlink in the database.
     *
     * @param int $projectId
     * @param array $data
     */
    protected function storeBacklink(int $projectId, array $data): void
    {
        Backlink::updateOrCreate(
            [
                'project_id' => $projectId,
                'source_url' => $data['source_url'],
            ],
            [
                'source_domain' => $data['source_domain'],
                'target_url' => $data['target_url'],
                'anchor_text' => $data['anchor_text'],
                'link_type' => $data['link_type'],
                'is_active' => true,
                'last_checked_at' => now(),
                'first_seen_at' => now(),
            ]
        );
    }

    /**
     * Update anchor text aggregation for a project.
     *
     * @param int $projectId
     * @param array $backlinks
     */
    protected function updateAnchorTexts(int $projectId, array $backlinks): void
    {
        $anchorCounts = [];

        foreach ($backlinks as $backlink) {
            $anchor = $backlink['anchor_text'];
            if ($anchor === null) {
                continue;
            }

            $key = md5($anchor);
            if (!isset($anchorCounts[$key])) {
                $anchorCounts[$key] = [
                    'anchor_text' => $anchor,
                    'count' => 0,
                ];
            }
            $anchorCounts[$key]['count']++;
        }

        foreach ($anchorCounts as $data) {
            AnchorText::updateOrCreate(
                [
                    'project_id' => $projectId,
                    'anchor_text' => $data['anchor_text'],
                ],
                [
                    'backlinks_count' => $data['count'],
                ]
            );
        }
    }

    /**
     * Create a snapshot after sync.
     *
     * @param Project $project
     */
    protected function createSnapshot(Project $project): void
    {
        $snapshotService = app(BacklinkSnapshotService::class);
        $snapshotService->createSnapshot($project);
    }

    /**
     * Report progress to callback if available.
     *
     * @param string $message
     * @param int $percentage
     */
    protected function reportProgress(string $message, int $percentage): void
    {
        if ($this->progressCallback !== null) {
            call_user_func($this->progressCallback, $message, $percentage);
        }
    }
}
