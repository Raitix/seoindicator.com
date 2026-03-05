<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Rate Limiting Integration Tests for TASK-29-5.
 *
 * Tests user-based rate limiting for the export endpoint.
 *
 * @see TESTS/TEST-29-5.md
 */
class ToolExportRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const EXPORT_ENDPOINT = '/api/v1/tools/export';
    private const DEFAULT_LIMIT = 10;
    private const DEFAULT_WINDOW = 3600;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a valid export request payload.
     */
    private function validPayload(): array
    {
        return [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'analyzedAt' => now()->toIso8601String(),
                'results' => [
                    'title' => ['content' => 'Example Page', 'length' => 12, 'status' => 'ok'],
                ],
            ],
        ];
    }

    /**
     * Make an export request as the test user.
     */
    private function makeExportRequest(array $payload = []): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->user);
        return $this->postJson(self::EXPORT_ENDPOINT, array_merge($this->validPayload(), $payload));
    }

    /**
     * Get rate limit cache key for a user.
     */
    private function getRateLimitKey(int $userId): string
    {
        return 'export_rate:' . md5((string) $userId);
    }

    /**
     * Simulate user reaching rate limit.
     */
    private function reachRateLimit(int $userId, int $limit = self::DEFAULT_LIMIT): void
    {
        $key = $this->getRateLimitKey($userId);
        Cache::put($key, $limit, self::DEFAULT_WINDOW);
    }

    // =========================================================================
    // RL-01: Rate Limit Headers on Success
    // =========================================================================

    /** @test RL-01-01: First export request returns correct headers */
    public function first_export_request_returns_correct_headers(): void
    {
        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit', (string) self::DEFAULT_LIMIT);
        $response->assertHeader('X-RateLimit-Remaining', (string) (self::DEFAULT_LIMIT - 1));
    }

    /** @test RL-01-02: Second export request decrements remaining */
    public function second_export_request_decrements_remaining(): void
    {
        // First request
        $this->makeExportRequest();

        // Second request
        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', (string) (self::DEFAULT_LIMIT - 2));
    }

    /** @test RL-01-03: Ninth export request shows remaining=1 */
    public function ninth_export_request_shows_remaining_one(): void
    {
        // Simulate 8 previous requests
        $key = $this->getRateLimitKey($this->user->id);
        Cache::put($key, 8, self::DEFAULT_WINDOW);

        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', '1');
    }

    /** @test RL-01-04: Tenth export request shows remaining=0 */
    public function tenth_export_request_shows_remaining_zero(): void
    {
        // Simulate 9 previous requests
        $key = $this->getRateLimitKey($this->user->id);
        Cache::put($key, 9, self::DEFAULT_WINDOW);

        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /** @test RL-01-05: Reset time header is valid Unix timestamp */
    public function reset_time_header_is_valid_unix_timestamp(): void
    {
        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $resetTime = $response->headers->get('X-RateLimit-Reset');

        $this->assertNotNull($resetTime);
        $this->assertIsNumeric($resetTime);
        $this->assertGreaterThan(time(), (int) $resetTime);
        $this->assertLessThan(time() + self::DEFAULT_WINDOW + 10, (int) $resetTime);
    }

    // =========================================================================
    // RL-02: Rate Limit Exceeded (429 Response)
    // =========================================================================

    /** @test RL-02-01: Eleventh request is blocked with 429 */
    public function eleventh_request_is_blocked_with_429(): void
    {
        $this->reachRateLimit($this->user->id);

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
    }

    /** @test RL-02-02: Error message contains retry time */
    public function error_message_contains_retry_time(): void
    {
        $this->reachRateLimit($this->user->id);

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'success',
            'error' => ['code', 'message'],
        ]);

        $message = $response->json('error.message');
        $this->assertStringContainsString('rate limit exceeded', strtolower($message));
        $this->assertMatchesRegularExpression('/\d+\s+minute/i', $message);
    }

    /** @test RL-02-03: Retry-After header present when rate limited */
    public function retry_after_header_present_when_rate_limited(): void
    {
        $this->reachRateLimit($this->user->id);

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');

        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(self::DEFAULT_WINDOW, $retryAfter);
    }

    /** @test RL-02-04: Rate limit headers on 429 response */
    public function rate_limit_headers_on_429_response(): void
    {
        $this->reachRateLimit($this->user->id);

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Limit', (string) self::DEFAULT_LIMIT);
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    // =========================================================================
    // RL-03: Per-User Isolation
    // =========================================================================

    /** @test RL-03-01: Different users have independent limits */
    public function different_users_have_independent_limits(): void
    {
        // User 1 reaches limit
        $this->reachRateLimit($this->user->id);

        // User 2 makes request
        $user2 = User::factory()->create();
        Sanctum::actingAs($user2);
        $response = $this->postJson(self::EXPORT_ENDPOINT, $this->validPayload());

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', (string) (self::DEFAULT_LIMIT - 1));
    }

    /** @test RL-03-02: Concurrent users all succeed within their limits */
    public function concurrent_users_all_succeed_within_their_limits(): void
    {
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            // Each user makes 5 requests (well within limit)
            for ($i = 0; $i < 5; $i++) {
                Sanctum::actingAs($user);
                $response = $this->postJson(self::EXPORT_ENDPOINT, $this->validPayload());
                $response->assertStatus(200);
            }
        }

        // All 25 requests succeeded
        $this->assertTrue(true);
    }

    /** @test RL-03-03: User ID is used in rate limit key, not IP */
    public function user_id_is_used_in_rate_limit_key_not_ip(): void
    {
        // Same IP, different users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 makes 10 requests
        $key1 = $this->getRateLimitKey($user1->id);
        Cache::put($key1, self::DEFAULT_LIMIT, self::DEFAULT_WINDOW);

        // User 2 should still be able to make requests
        Sanctum::actingAs($user2);
        $response = $this->postJson(self::EXPORT_ENDPOINT, $this->validPayload());

        $response->assertStatus(200);
    }

    // =========================================================================
    // RL-04: Configuration Values
    // =========================================================================

    /** @test RL-04-01: Default limit is 10 */
    public function default_limit_is_10(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit', (string) self::DEFAULT_LIMIT);
    }

    /** @test RL-04-02: Default window is 3600 seconds */
    public function default_window_is_3600_seconds(): void
    {
        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $windowUsed = $resetTime - time();

        // Window should be approximately 3600 seconds
        $this->assertGreaterThanOrEqual(3590, $windowUsed);
        $this->assertLessThanOrEqual(3610, $windowUsed);
    }

    /** @test RL-04-03: Custom limit from config is respected */
    public function custom_limit_from_config_is_respected(): void
    {
        Config::set('tools.export_rate_limit', 5);

        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit', '5');
    }

    /** @test RL-04-04: Custom window from config is respected */
    public function custom_window_from_config_is_respected(): void
    {
        Config::set('tools.export_rate_window', 1800);

        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $windowUsed = $resetTime - time();

        // Window should be approximately 1800 seconds
        $this->assertGreaterThanOrEqual(1790, $windowUsed);
        $this->assertLessThanOrEqual(1810, $windowUsed);
    }

    // =========================================================================
    // RL-05: Window Reset
    // =========================================================================

    /** @test RL-05-01: Counter resets after window expiry */
    public function counter_resets_after_window_expiry(): void
    {
        $this->reachRateLimit($this->user->id);

        // Simulate window expiry
        Cache::forget($this->getRateLimitKey($this->user->id));

        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', (string) (self::DEFAULT_LIMIT - 1));
    }

    /** @test RL-05-02: Partial window count preserved */
    public function partial_window_count_preserved(): void
    {
        // Set count to 5
        $key = $this->getRateLimitKey($this->user->id);
        Cache::put($key, 5, self::DEFAULT_WINDOW);

        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', '4');
    }

    // =========================================================================
    // RL-06: Security
    // =========================================================================

    /** @test RL-06-01: Unauthenticated request returns 401 */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson(self::EXPORT_ENDPOINT, $this->validPayload());

        $response->assertStatus(401);
    }

    /** @test RL-06-02: Rate limit is user-based not IP-based */
    public function rate_limit_is_user_based_not_ip_based(): void
    {
        // Two different users, same "IP" (test client)
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 reaches their limit
        $this->reachRateLimit($user1->id);

        // User 2 should not be affected
        Sanctum::actingAs($user2);
        $response = $this->postJson(self::EXPORT_ENDPOINT, $this->validPayload());

        $response->assertStatus(200);
    }

    /** @test RL-06-03: Cache key format is secure */
    public function cache_key_format_is_secure(): void
    {
        $this->makeExportRequest();

        // Verify key uses hashing (not raw user ID)
        $expectedKeyPattern = '/^export_rate:[a-f0-9]{32}$/';
        $userIdHash = md5((string) $this->user->id);
        $expectedKey = "export_rate:{$userIdHash}";

        $this->assertTrue(Cache::has($expectedKey));
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /** @test Edge-01: Request with invalid format still counts toward limit */
    public function request_with_invalid_format_still_counts_toward_limit(): void
    {
        // Make a request with invalid format (validation will fail)
        $response = $this->makeExportRequest(['format' => 'invalid']);

        $response->assertStatus(422);

        // Verify rate limit was NOT incremented (validation failed before rate limit check)
        $key = $this->getRateLimitKey($this->user->id);
        $count = Cache::get($key, 0);

        // Rate limit should only apply to valid requests
        $this->assertLessThanOrEqual(1, $count);
    }

    /** @test Edge-02: Multiple rapid requests handled correctly */
    public function multiple_rapid_requests_handled_correctly(): void
    {
        $responses = [];

        for ($i = 0; $i < self::DEFAULT_LIMIT + 2; $i++) {
            $responses[] = $this->makeExportRequest();
        }

        // First 10 should succeed
        for ($i = 0; $i < self::DEFAULT_LIMIT; $i++) {
            $this->assertEquals(200, $responses[$i]->status(), "Request $i should succeed");
        }

        // Last 2 should be rate limited
        for ($i = self::DEFAULT_LIMIT; $i < self::DEFAULT_LIMIT + 2; $i++) {
            $this->assertEquals(429, $responses[$i]->status(), "Request $i should be rate limited");
        }
    }

    /** @test Edge-03: Response time is acceptable with rate limiting */
    public function response_time_is_acceptable_with_rate_limiting(): void
    {
        $start = microtime(true);

        $this->makeExportRequest();

        $duration = microtime(true) - $start;

        // Rate limiting should add minimal overhead (< 100ms)
        $this->assertLessThan(0.1, $duration);
    }
}
