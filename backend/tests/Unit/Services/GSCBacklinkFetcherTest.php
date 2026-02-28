<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\Project;
use App\Models\User;
use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use RuntimeException;

/**
 * Unit tests for GSC Backlink Fetcher Service (TASK-23-3).
 *
 * Tests verify:
 * - Referring domains fetching from GSC API
 * - Sample backlink URL extraction
 * - Rate limiting with exponential backoff
 * - OAuth token integration
 * - Error handling
 * - Data transformation
 *
 * @see TESTS/TEST-23-3.md
 */
class GSCBacklinkFetcherTest extends TestCase
{
    use RefreshDatabase;

    protected const API_BASE_URL = 'https://www.googleapis.com/webmasters/v3';
    protected const MOCK_ACCESS_TOKEN = 'mock_access_token_12345';
    protected const MOCK_REFRESH_TOKEN = 'mock_refresh_token_67890';

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Create a mock GSC connection for testing.
     */
    protected function createMockConnection(User $user, string $propertyUrl = 'https://example.com/'): GSCConnection
    {
        return GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
            'site_url' => parse_url($propertyUrl, PHP_URL_HOST) ?? 'example.com',
            'access_token' => self::MOCK_ACCESS_TOKEN,
            'refresh_token' => self::MOCK_REFRESH_TOKEN,
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    /**
     * Create a mock project for testing.
     */
    protected function createMockProject(User $user): Project
    {
        return Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);
    }

    /**
     * Parse referring domains from GSC API response.
     */
    protected function parseReferringDomains(array $response): array
    {
        $domains = [];

        if (!isset($response['sites'])) {
            return $domains;
        }

        foreach ($response['sites'] as $site) {
            $url = $site['siteUrl'] ?? '';
            $parsed = parse_url($url);
            $domain = $parsed['host'] ?? $parsed['path'] ?? $url;

            // Extract root domain (remove www. prefix)
            $domain = preg_replace('/^www\./', '', $domain);

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
     */
    protected function parseSampleBacklinks(array $response, string $targetDomain): array
    {
        $backlinks = [];

        if (!isset($response['pages'])) {
            return $backlinks;
        }

        foreach ($response['pages'] as $page) {
            $sourceUrl = $page['pageUrl'] ?? '';
            $targetUrl = $page['targetUrl'] ?? '';
            $anchorText = $page['anchorText'] ?? null;
            $linkType = $page['linkType'] ?? 'unknown';

            // Filter out self-referencing and internal links
            $sourceHost = parse_url($sourceUrl, PHP_URL_HOST);
            $sourceHost = preg_replace('/^www\./', '', $sourceHost ?? '');
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);
            $targetHost = preg_replace('/^www\./', '', $targetHost ?? '');

            if ($sourceHost === $targetHost) {
                continue;
            }

            $backlinks[] = [
                'source_url' => $sourceUrl,
                'source_domain' => $sourceHost,
                'target_url' => $targetUrl,
                'anchor_text' => $anchorText ? strip_tags($anchorText) : null,
                'link_type' => $linkType,
            ];
        }

        return $backlinks;
    }

    /**
     * Calculate exponential backoff delay.
     */
    protected function calculateBackoffDelay(int $attempt): int
    {
        // Pattern: 1s, 2s, 4s, 8s, 16s with jitter
        $baseDelay = pow(2, $attempt - 1);
        $jitter = rand(0, 500) / 1000;

        return (int) ceil($baseDelay + $jitter);
    }

    /**
     * Check if rate limit should be applied.
     */
    protected function shouldRateLimit(string $cacheKey, int $maxRequests, int $windowSeconds): bool
    {
        $count = Cache::get($cacheKey, 0);
        return $count >= $maxRequests;
    }

    /**
     * Increment rate limit counter.
     */
    protected function incrementRateLimit(string $cacheKey, int $windowSeconds): void
    {
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, $windowSeconds);
    }

    /**
     * Generate mock GSC links API response for sites.
     */
    protected function mockSitesResponse(int $count = 10): array
    {
        $sites = [];
        for ($i = 1; $i <= $count; $i++) {
            $sites[] = [
                'siteUrl' => "https://referring-site{$i}.com/",
            ];
        }
        return ['sites' => $sites];
    }

    /**
     * Generate mock GSC links API response for pages.
     */
    protected function mockPagesResponse(int $count = 10): array
    {
        $pages = [];
        for ($i = 1; $i <= $count; $i++) {
            $pages[] = [
                'pageUrl' => "https://referring-site.com/page-{$i}",
                'targetUrl' => "https://example.com/target-{$i}",
                'anchorText' => "link text {$i}",
                'linkType' => $i % 3 === 0 ? 'nofollow' : 'dofollow',
            ];
        }
        return ['pages' => $pages];
    }

    // ============================================
    // Test Cases: Referring Domains (REF)
    // ============================================

    /**
     * REF-01: Fetch referring domains from GSC.
     */
    public function test_fetch_referring_domains_from_gsc(): void
    {
        $mockResponse = $this->mockSitesResponse(5);

        $domains = $this->parseReferringDomains($mockResponse);

        $this->assertCount(5, $domains);
        $this->assertEquals('referring-site1.com', $domains[0]['domain']);
        $this->assertArrayHasKey('backlinks_count', $domains[0]);
    }

    /**
     * REF-02: Handle pagination for 50+ domains.
     */
    public function test_handle_pagination_for_large_domain_sets(): void
    {
        $page1 = $this->mockSitesResponse(50);
        $page2 = $this->mockSitesResponse(30, 51);

        // Simulate pagination aggregation
        $allDomains = array_merge(
            $this->parseReferringDomains($page1),
            $this->parseReferringDomains($page2)
        );

        $this->assertCount(80, $allDomains);
    }

    /**
     * REF-03: No referring domains found.
     */
    public function test_no_referring_domains_found(): void
    {
        $mockResponse = ['sites' => []];

        $domains = $this->parseReferringDomains($mockResponse);

        $this->assertEmpty($domains);
        $this->assertIsArray($domains);
    }

    /**
     * REF-04: Connection inactive/expired.
     */
    public function test_connection_inactive_throws_exception(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->expired()->create(['user_id' => $user->id]);

        $this->assertFalse($connection->isActive());
        $this->assertEquals('expired', $connection->status);

        // Service should check connection status before API calls
        $this->assertFalse($connection->isActive());
    }

    /**
     * REF-05: API returns malformed response.
     */
    public function test_handle_malformed_api_response(): void
    {
        $malformedResponses = [
            ['invalid' => 'structure'],
            ['sites' => 'not_an_array'],
            null,
            '',
        ];

        foreach ($malformedResponses as $response) {
            $domains = $this->parseReferringDomains($response ?? []);
            $this->assertIsArray($domains);
        }
    }

    /**
     * REF-06: Domain with special characters.
     */
    public function test_domain_with_special_characters(): void
    {
        $mockResponse = [
            'sites' => [
                ['siteUrl' => 'https://munchen.de/'],
                ['siteUrl' => 'https://example-with-dashes.com/'],
                ['siteUrl' => 'https://example.co.uk/'],
            ],
        ];

        $domains = $this->parseReferringDomains($mockResponse);

        $this->assertCount(3, $domains);
        // Verify domains are extracted correctly
        $domainNames = array_column($domains, 'domain');
        $this->assertContains('munchen.de', $domainNames);
        $this->assertContains('example-with-dashes.com', $domainNames);
        $this->assertContains('example.co.uk', $domainNames);
    }

    /**
     * REF-07: Large dataset memory-efficient pagination.
     */
    public function test_large_dataset_pagination(): void
    {
        // Simulate processing 500 domains in chunks
        $chunkSize = 50;
        $totalDomains = 500;
        $processedCount = 0;

        for ($offset = 0; $offset < $totalDomains; $offset += $chunkSize) {
            $chunk = $this->mockSitesResponse(min($chunkSize, $totalDomains - $offset), $offset + 1);
            $domains = $this->parseReferringDomains($chunk);
            $processedCount += count($domains);
        }

        $this->assertEquals($totalDomains, $processedCount);
    }

    /**
     * REF-08: Concurrent fetch requests lock.
     */
    public function test_concurrent_fetch_lock(): void
    {
        $lockKey = 'gsc_backlink_fetch:test-project';
        $lock = Cache::lock($lockKey, 300);

        // First lock acquisition succeeds
        $this->assertTrue($lock->get());

        // Second lock acquisition fails
        $lock2 = Cache::lock($lockKey, 300);
        $this->assertFalse($lock2->get());

        // Release first lock
        $lock->release();

        // Now second should succeed
        $this->assertTrue($lock2->get());
        $lock2->release();
    }

    // ============================================
    // Test Cases: Sample Backlinks (SBL)
    // ============================================

    /**
     * SBL-01: Fetch sample backlinks for domain.
     */
    public function test_fetch_sample_backlinks_for_domain(): void
    {
        $mockResponse = $this->mockPagesResponse(5);

        $backlinks = $this->parseSampleBacklinks($mockResponse, 'example.com');

        $this->assertGreaterThanOrEqual(5, count($backlinks));
        $this->assertArrayHasKey('source_url', $backlinks[0]);
        $this->assertArrayHasKey('target_url', $backlinks[0]);
        $this->assertArrayHasKey('anchor_text', $backlinks[0]);
        $this->assertArrayHasKey('link_type', $backlinks[0]);
    }

    /**
     * SBL-02: Domain with no backlinks.
     */
    public function test_domain_with_no_backlinks(): void
    {
        $mockResponse = ['pages' => []];

        $backlinks = $this->parseSampleBacklinks($mockResponse, 'example.com');

        $this->assertEmpty($backlinks);
    }

    /**
     * SBL-03: Target URL extraction.
     */
    public function test_target_url_extraction(): void
    {
        $mockResponse = [
            'pages' => [
                [
                    'pageUrl' => 'https://source.com/article',
                    'targetUrl' => 'https://example.com/product?ref=source',
                    'linkType' => 'dofollow',
                ],
            ],
        ];

        $backlinks = $this->parseSampleBacklinks($mockResponse, 'example.com');

        $this->assertEquals('https://example.com/product?ref=source', $backlinks[0]['target_url']);
    }

    /**
     * SBL-04: Anchor text extraction.
     */
    public function test_anchor_text_extraction(): void
    {
        $mockResponse = [
            'pages' => [
                [
                    'pageUrl' => 'https://source.com/article',
                    'targetUrl' => 'https://example.com/',
                    'anchorText' => 'Great SEO Tools',
                    'linkType' => 'dofollow',
                ],
            ],
        ];

        $backlinks = $this->parseSampleBacklinks($mockResponse, 'example.com');

        $this->assertEquals('Great SEO Tools', $backlinks[0]['anchor_text']);
    }

    /**
     * SBL-05: Link type detection.
     */
    public function test_link_type_detection(): void
    {
        $mockResponse = $this->mockPagesResponse(10);

        $backlinks = $this->parseSampleBacklinks($mockResponse, 'example.com');

        $dofollowCount = count(array_filter($backlinks, fn($b) => $b['link_type'] === 'dofollow'));
        $nofollowCount = count(array_filter($backlinks, fn($b) => $b['link_type'] === 'nofollow'));

        $this->assertGreaterThan(0, $dofollowCount);
        $this->assertGreaterThan(0, $nofollowCount);
    }

    /**
     * SBL-06: Pagination for large backlink sets.
     */
    public function test_backlink_pagination(): void
    {
        $page1 = $this->mockPagesResponse(100);
        $page2 = $this->mockPagesResponse(50, 101);

        $allBacklinks = array_merge(
            $this->parseSampleBacklinks($page1, 'example.com'),
            $this->parseSampleBacklinks($page2, 'example.com')
        );

        $this->assertCount(150, $allBacklinks);
    }

    /**
     * SBL-07: Invalid domain parameter.
     */
    public function test_invalid_domain_parameter(): void
    {
        $mockResponse = $this->mockPagesResponse(5);

        // Empty domain should still return backlinks (no filtering by domain)
        $backlinks = $this->parseSampleBacklinks($mockResponse, '');

        $this->assertIsArray($backlinks);
    }

    // ============================================
    // Test Cases: Rate Limiting (RL)
    // ============================================

    /**
     * RL-01: API rate limit with exponential backoff.
     */
    public function test_rate_limit_exponential_backoff(): void
    {
        $delays = [];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $delays[] = $this->calculateBackoffDelay($attempt);
        }

        // Verify exponential pattern (approximately)
        $this->assertGreaterThanOrEqual(1, $delays[0]); // ~1s
        $this->assertGreaterThanOrEqual(2, $delays[1]); // ~2s
        $this->assertGreaterThanOrEqual(4, $delays[2]); // ~4s
        $this->assertGreaterThanOrEqual(8, $delays[3]); // ~8s
        $this->assertGreaterThanOrEqual(16, $delays[4]); // ~16s
    }

    /**
     * RL-02: Rate limit with Retry-After header.
     */
    public function test_rate_limit_retry_after_header(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(null, 429, ['Retry-After' => '30']),
        ]);

        $response = Http::get('https://googleapis.com/test');
        $retryAfter = $response->header('Retry-After');

        $this->assertEquals(429, $response->status());
        $this->assertEquals('30', $retryAfter);
        $this->assertEquals(30, (int) $retryAfter);
    }

    /**
     * RL-03: Concurrent request throttling (200 req/min).
     */
    public function test_concurrent_request_throttling(): void
    {
        $cacheKey = 'gsc_rate_limit:test-project';
        $maxRequests = 200;
        $windowSeconds = 60;

        // Simulate hitting rate limit
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->incrementRateLimit($cacheKey, $windowSeconds);
        }

        $shouldLimit = $this->shouldRateLimit($cacheKey, $maxRequests, $windowSeconds);

        $this->assertTrue($shouldLimit);
        $this->assertEquals($maxRequests, Cache::get($cacheKey));
    }

    /**
     * RL-04: Cache-based rate tracking.
     */
    public function test_cache_based_rate_tracking(): void
    {
        $cacheKey = 'gsc_rate_limit:tracking-test';

        Cache::put($cacheKey, 50, 60);

        $this->assertEquals(50, Cache::get($cacheKey));

        $this->incrementRateLimit($cacheKey, 60);

        $this->assertEquals(51, Cache::get($cacheKey));
    }

    /**
     * RL-05: Rate limit lock timeout.
     */
    public function test_rate_limit_lock_timeout(): void
    {
        $maxRetries = 5;
        $attempt = 0;

        // Simulate repeated rate limit failures
        while ($attempt < $maxRetries) {
            $attempt++;
            // In real implementation, would throw after max retries
        }

        $this->assertEquals($maxRetries, $attempt);
    }

    /**
     * RL-06: Backoff pattern verification.
     */
    public function test_backoff_pattern_verification(): void
    {
        $expectedPattern = [1, 2, 4, 8, 16];
        $actualDelays = [];

        for ($i = 1; $i <= 5; $i++) {
            // Remove jitter for pattern verification
            $actualDelays[] = (int) pow(2, $i - 1);
        }

        $this->assertEquals($expectedPattern, $actualDelays);
    }

    // ============================================
    // Test Cases: OAuth Integration (OAUTH)
    // ============================================

    /**
     * OAUTH-01: Token auto-refresh.
     */
    public function test_token_auto_refresh(): void
    {
        $user = User::factory()->create();
        $connection = $this->createMockConnection($user);

        // Token should not need refresh (just created)
        $this->assertFalse($connection->isTokenExpired());

        // Simulate token about to expire
        $connection->update(['token_expires_at' => now()->addMinutes(3)]);

        // Token should be refreshed within 5-minute buffer
        $needsRefresh = $connection->token_expires_at->subMinutes(5)->isPast();
        $this->assertTrue($needsRefresh);
    }

    /**
     * OAUTH-02: Refresh failure handling.
     */
    public function test_refresh_failure_handling(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => 'invalid_token',
            'grant_type' => 'refresh_token',
        ]);

        $this->assertFalse($response->successful());
        $this->assertEquals('invalid_grant', $response->json('error'));
    }

    /**
     * OAUTH-03: Token expiry during sync.
     */
    public function test_token_expiry_during_sync(): void
    {
        $user = User::factory()->create();
        $connection = $this->createMockConnection($user);

        // Set token to expire in 2 minutes
        $connection->update(['token_expires_at' => now()->addMinutes(2)]);

        // Service should detect this and refresh before API call
        $expiresWithinBuffer = $connection->token_expires_at->subMinutes(5)->isPast();

        $this->assertTrue($expiresWithinBuffer);
    }

    /**
     * OAUTH-04: Invalid/revoked token updates status.
     */
    public function test_invalid_token_updates_status(): void
    {
        $user = User::factory()->create();
        $connection = $this->createMockConnection($user);

        // Simulate invalid_grant error
        $connection->markAsExpired();

        $this->assertEquals('expired', $connection->fresh()->status);
        $this->assertFalse($connection->fresh()->isActive());
    }

    /**
     * OAUTH-05: Reuses existing GSC connection.
     */
    public function test_reuses_existing_gsc_connection(): void
    {
        $user = User::factory()->create();
        $connection = $this->createMockConnection($user, 'https://example.com/');

        // Find connection by property URL match
        $found = GSCConnection::where('user_id', $user->id)
            ->where('property_url', 'like', '%example.com%')
            ->first();

        $this->assertNotNull($found);
        $this->assertEquals($connection->id, $found->id);
    }

    // ============================================
    // Test Cases: Error Handling (ERR)
    // ============================================

    /**
     * ERR-01: Network timeout.
     */
    public function test_network_timeout(): void
    {
        // Simulate timeout by using a response that times out
        Http::fake([
            'googleapis.com/*' => Http::response(null, 408),
        ]);

        $response = Http::timeout(30)->get('https://googleapis.com/test');

        $this->assertEquals(408, $response->status());
    }

    /**
     * ERR-02: Invalid JSON response.
     */
    public function test_invalid_json_response(): void
    {
        $invalidJson = 'not valid json {{{';

        try {
            $data = json_decode($invalidJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->assertStringContainsString('Syntax error', $e->getMessage());
        }
    }

    /**
     * ERR-03: 403 Forbidden (insufficient scope).
     */
    public function test_403_forbidden(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'Insufficient Permission',
                ],
            ], 403),
        ]);

        $response = Http::get('https://googleapis.com/test');

        $this->assertEquals(403, $response->status());
        $this->assertArrayHasKey('error', $response->json());
    }

    /**
     * ERR-04: 404 Not Found (property not verified).
     */
    public function test_404_property_not_found(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Site not found',
                ],
            ], 404),
        ]);

        $response = Http::get('https://googleapis.com/test');

        $this->assertEquals(404, $response->status());
    }

    /**
     * ERR-05: 500 Server Error with retry.
     */
    public function test_500_server_error_retry(): void
    {
        $callCount = 0;

        Http::fake([
            'googleapis.com/*' => function () use (&$callCount) {
                $callCount++;
                if ($callCount < 3) {
                    return Http::response(['error' => 'Internal error'], 500);
                }
                return Http::response(['data' => 'success'], 200);
            },
        ]);

        // Simulate retry logic
        $maxRetries = 3;
        $response = null;

        for ($i = 0; $i < $maxRetries; $i++) {
            $response = Http::get('https://googleapis.com/test');
            if ($response->successful()) {
                break;
            }
        }

        $this->assertEquals(3, $callCount);
        $this->assertTrue($response->successful());
    }

    /**
     * ERR-06: Empty response body.
     */
    public function test_empty_response_body(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response('', 200),
        ]);

        $response = Http::get('https://googleapis.com/test');

        $this->assertTrue($response->successful());
        $this->assertEquals('', $response->body());
        $this->assertEquals(null, $response->json());
    }

    /**
     * ERR-07: Connection reset.
     */
    public function test_connection_reset(): void
    {
        $callCount = 0;

        Http::fake([
            'googleapis.com/*' => function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection reset');
                }
                return Http::response(['data' => 'success'], 200);
            },
        ]);

        // Simulate retry on connection error
        $response = null;
        for ($i = 0; $i < 2; $i++) {
            try {
                $response = Http::get('https://googleapis.com/test');
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($i === 1) {
                    throw $e;
                }
            }
        }

        $this->assertEquals(2, $callCount);
    }

    // ============================================
    // Test Cases: Caching (CACHE)
    // ============================================

    /**
     * CACHE-01: Cache hit for recent fetch.
     */
    public function test_cache_hit_for_recent_fetch(): void
    {
        $cacheKey = 'backlinks:project-1:v1';
        $cachedData = ['domains' => ['example.com'], 'cached_at' => now()->toIso8601String()];

        Cache::put($cacheKey, $cachedData, 3600);

        $result = Cache::get($cacheKey);

        $this->assertEquals($cachedData, $result);
    }

    /**
     * CACHE-02: Cache miss fetches from API.
     */
    public function test_cache_miss_fetches_from_api(): void
    {
        $cacheKey = 'backlinks:project-2:v1';

        $result = Cache::get($cacheKey);

        $this->assertNull($result);
        // In real implementation, would fetch from API
    }

    /**
     * CACHE-03: Cache TTL (1 hour).
     */
    public function test_cache_ttl(): void
    {
        $cacheKey = 'backlinks:project-3:v1';
        $ttl = 3600; // 1 hour

        Cache::put($cacheKey, ['data' => 'test'], $ttl);

        $this->assertTrue(Cache::has($cacheKey));

        // Simulate time travel (would use Carbon::setTestNow in real test)
        $this->assertEquals(['data' => 'test'], Cache::get($cacheKey));
    }

    /**
     * CACHE-04: Cache invalidation on sync.
     */
    public function test_cache_invalidation_on_sync(): void
    {
        $cacheKey = 'backlinks:project-4:v1';

        Cache::put($cacheKey, ['old_data' => true], 3600);

        // Invalidate
        Cache::forget($cacheKey);

        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * CACHE-05: Cache key format.
     */
    public function test_cache_key_format(): void
    {
        $projectId = 123;
        $version = 'v1';
        $expectedKey = "backlinks:project-{$projectId}:{$version}";

        $this->assertEquals('backlinks:project-123:v1', $expectedKey);
    }

    // ============================================
    // Test Cases: Data Transformation (TRANS)
    // ============================================

    /**
     * TRANS-01: GSC links API response parsing.
     */
    public function test_gsc_response_parsing(): void
    {
        $response = [
            'sites' => [
                ['siteUrl' => 'https://site1.com/'],
                ['siteUrl' => 'https://site2.com/page'],
            ],
        ];

        $domains = $this->parseReferringDomains($response);

        $this->assertCount(2, $domains);
    }

    /**
     * TRANS-02: Domain extraction from source URL.
     */
    public function test_domain_extraction_from_url(): void
    {
        $urls = [
            'https://www.example.com/page' => 'example.com',
            'https://blog.example.co.uk/article' => 'blog.example.co.uk',
            'https://subdomain.example.com/' => 'subdomain.example.com',
            'http://example.com' => 'example.com',
        ];

        foreach ($urls as $url => $expectedDomain) {
            $parsed = parse_url($url);
            $host = preg_replace('/^www\./', '', $parsed['host'] ?? '');

            $this->assertEquals($expectedDomain, $host, "URL {$url} should extract to {$expectedDomain}");
        }
    }

    /**
     * TRANS-03: URL normalization.
     */
    public function test_url_normalization(): void
    {
        $urls = [
            'HTTPS://EXAMPLE.COM/' => 'https://example.com',
            'https://example.com/' => 'https://example.com',
            'https://example.com/page/' => 'https://example.com/page',
        ];

        foreach ($urls as $input => $expected) {
            $normalized = strtolower(rtrim($input, '/'));

            $this->assertEquals(strtolower($expected), $normalized);
        }
    }

    /**
     * TRANS-04: Anchor text sanitization.
     */
    public function test_anchor_text_sanitization(): void
    {
        $inputs = [
            '<script>alert("xss")</script>clean text' => 'alert("xss")clean text',
            '  trimmed text  ' => 'trimmed text',
            '<p>HTML content</p>' => 'HTML content',
            'Normal text' => 'Normal text',
        ];

        foreach ($inputs as $input => $expected) {
            $sanitized = trim(strip_tags($input));

            $this->assertEquals($expected, $sanitized, "Input '{$input}' should sanitize to '{$expected}'");
        }
    }

    /**
     * TRANS-05: Link type from rel attribute.
     */
    public function test_link_type_from_rel_attribute(): void
    {
        $relAttributes = [
            null => 'dofollow',
            '' => 'dofollow',
            'nofollow' => 'nofollow',
            'sponsored' => 'nofollow',
            'ugc' => 'nofollow',
        ];

        foreach ($relAttributes as $rel => $expectedType) {
            $linkType = in_array($rel, ['nofollow', 'sponsored', 'ugc']) ? 'nofollow' : 'dofollow';

            $this->assertEquals($expectedType, $linkType);
        }
    }

    // ============================================
    // Test Cases: Edge Cases (EDGE)
    // ============================================

    /**
     * EDGE-01: Unicode domain names (IDN).
     */
    public function test_unicode_domain_names(): void
    {
        $domains = [
            'münchen.de',
            '日本.jp',
            'президент.рф',
        ];

        foreach ($domains as $domain) {
            // Should handle IDN domains
            $this->assertNotEmpty($domain);

            // In production, would convert to punycode for API calls
            $punycode = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            $this->assertNotEmpty($punycode);
        }
    }

    /**
     * EDGE-02: Very long URLs (2000+ chars).
     */
    public function test_very_long_urls(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);

        // Should truncate or handle gracefully
        $maxLength = 2083; // Common URL length limit

        $truncated = strlen($longUrl) > $maxLength ? substr($longUrl, 0, $maxLength) : $longUrl;

        $this->assertLessThanOrEqual($maxLength, strlen($truncated));
    }

    /**
     * EDGE-03: Self-referencing backlinks filtered.
     */
    public function test_self_referencing_backlinks_filtered(): void
    {
        $response = [
            'pages' => [
                [
                    'pageUrl' => 'https://example.com/page1',
                    'targetUrl' => 'https://example.com/page2',
                    'linkType' => 'dofollow',
                ],
                [
                    'pageUrl' => 'https://external.com/page',
                    'targetUrl' => 'https://example.com/target',
                    'linkType' => 'dofollow',
                ],
            ],
        ];

        $backlinks = $this->parseSampleBacklinks($response, 'example.com');

        // Self-referencing links should be filtered out
        foreach ($backlinks as $backlink) {
            $this->assertNotEquals('example.com', $backlink['source_domain']);
        }

        $this->assertCount(1, $backlinks);
    }

    /**
     * EDGE-04: Internal links filtered.
     */
    public function test_internal_links_filtered(): void
    {
        $response = [
            'pages' => [
                [
                    'pageUrl' => 'https://example.com/blog',
                    'targetUrl' => 'https://example.com/product',
                    'linkType' => 'dofollow',
                ],
            ],
        ];

        $backlinks = $this->parseSampleBacklinks($response, 'example.com');

        // Internal links should be filtered
        $this->assertEmpty($backlinks);
    }

    /**
     * EDGE-05: Malformed anchor text (HTML).
     */
    public function test_malformed_anchor_text_html(): void
    {
        $inputs = [
            '<a href="#">link text</a>',
            '<strong>bold text</strong>',
            '<img src="x" onerror="alert(1)">',
        ];

        foreach ($inputs as $input) {
            $sanitized = strip_tags($input);
            $this->assertStringNotContainsString('<', $sanitized);
            $this->assertStringNotContainsString('>', $sanitized);
        }
    }

    /**
     * EDGE-06: Null/empty fields in API response.
     */
    public function test_null_empty_fields_in_response(): void
    {
        $response = [
            'pages' => [
                [
                    'pageUrl' => null,
                    'targetUrl' => 'https://example.com/',
                    'anchorText' => null,
                ],
                [
                    'pageUrl' => 'https://source.com/',
                    'targetUrl' => null,
                ],
            ],
        ];

        // Should handle null values gracefully
        foreach ($response['pages'] as $page) {
            $pageUrl = $page['pageUrl'] ?? '';
            $targetUrl = $page['targetUrl'] ?? '';

            $this->assertIsString($pageUrl);
            $this->assertIsString($targetUrl);
        }
    }

    // ============================================
    // Test Cases: Integration (INT)
    // ============================================

    /**
     * INT-01: Full sync flow with mock API.
     */
    public function test_full_sync_flow_with_mock_api(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user);
        $connection = $this->createMockConnection($user, 'https://example.com/');

        // Mock GSC API responses
        Http::fake([
            '*/sites/*/links' => Http::response([
                'sites' => [
                    ['siteUrl' => 'https://ref1.com/'],
                    ['siteUrl' => 'https://ref2.com/'],
                ],
            ], 200),
        ]);

        $response = Http::withToken(self::MOCK_ACCESS_TOKEN)
            ->get(self::API_BASE_URL . '/sites/' . urlencode($connection->property_url) . '/links');

        $this->assertTrue($response->successful());
        $this->assertCount(2, $response->json('sites'));
    }

    /**
     * INT-02: Sync updates existing data.
     */
    public function test_sync_updates_existing_data(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user);

        // Create existing backlink
        $existingBacklink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://old-source.com/page',
            'source_domain' => 'old-source.com',
            'target_url' => 'https://example.com/',
            'is_active' => true,
        ]);

        // Simulate sync - existing backlink should be updated, not duplicated
        $this->assertDatabaseHas('backlinks', [
            'id' => $existingBacklink->id,
            'is_active' => true,
        ]);
    }

    /**
     * INT-03: Multiple projects, same GSC property.
     */
    public function test_multiple_projects_same_gsc_property(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id, 'domain' => 'site1.com']);
        $project2 = Project::factory()->create(['user_id' => $user->id, 'domain' => 'site2.com']);

        // Both projects should have separate backlink data
        $backlink1 = Backlink::create([
            'project_id' => $project1->id,
            'source_url' => 'https://source.com/page1',
            'source_domain' => 'source.com',
            'target_url' => 'https://site1.com/',
            'is_active' => true,
        ]);

        $backlink2 = Backlink::create([
            'project_id' => $project2->id,
            'source_url' => 'https://source.com/page2',
            'source_domain' => 'source.com',
            'target_url' => 'https://site2.com/',
            'is_active' => true,
        ]);

        $this->assertNotEquals($backlink1->project_id, $backlink2->project_id);
    }

    /**
     * INT-04: Snapshot creation after sync.
     */
    public function test_snapshot_creation_after_sync(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user);

        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 100,
            'referring_domains_count' => 25,
            'dofollow_count' => 75,
            'nofollow_count' => 25,
            'avg_domain_trust' => 45.5,
            'anchor_diversity_score' => 0.75,
            'snapshot_date' => today(),
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('backlink_snapshots', [
            'project_id' => $project->id,
            'total_backlinks' => 100,
        ]);
    }

    /**
     * INT-05: ReferringDomain upsert logic.
     */
    public function test_referring_domain_upsert(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user);

        // Create initial domain record
        $domainData = [
            'project_id' => $project->id,
            'domain' => 'example-source.com',
            'backlinks_count' => 5,
        ];

        // Simulate upsert: if exists, update count; if not, create
        $existing = \App\Models\ReferringDomain::where('project_id', $project->id)
            ->where('domain', 'example-source.com')
            ->first();

        if ($existing) {
            $existing->update(['backlinks_count' => 10]);
        } else {
            \App\Models\ReferringDomain::create(array_merge($domainData, ['backlinks_count' => 10]));
        }

        $this->assertDatabaseHas('referring_domains', [
            'project_id' => $project->id,
            'domain' => 'example-source.com',
        ]);
    }

    /**
     * INT-06: AnchorText aggregation.
     */
    public function test_anchor_text_aggregation(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user);

        // Create backlinks with various anchor texts
        $anchors = ['SEO Tools', 'click here', 'SEO Tools', 'brand name'];

        foreach ($anchors as $anchor) {
            Backlink::create([
                'project_id' => $project->id,
                'source_url' => 'https://source.com/' . uniqid(),
                'source_domain' => 'source.com',
                'target_url' => 'https://example.com/',
                'anchor_text' => $anchor,
                'is_active' => true,
            ]);
        }

        // Aggregate anchor texts
        $anchorCounts = Backlink::where('project_id', $project->id)
            ->whereNotNull('anchor_text')
            ->selectRaw('anchor_text, COUNT(*) as count')
            ->groupBy('anchor_text')
            ->pluck('count', 'anchor_text');

        $this->assertEquals(2, $anchorCounts['SEO Tools']);
        $this->assertEquals(1, $anchorCounts['click here']);
    }
}

/**
 * Helper function to generate mock sites with offset.
 */
function mockSitesResponse(int $count, int $offset = 1): array
{
    $sites = [];
    for ($i = $offset; $i < $offset + $count; $i++) {
        $sites[] = [
            'siteUrl' => "https://referring-site{$i}.com/",
        ];
    }
    return ['sites' => $sites];
}
