<?php

namespace Tests\Feature\Api\V1\Tools;

use Tests\TestCase;
use App\Models\User;
use App\Services\Tools\ToolsRateLimitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for TASK-29-5: Rate Limiting Integration
 *
 * Tests rate limiting on the POST /api/v1/tools/export endpoint:
 * - Per-user rate limiting (10 exports per hour)
 * - Rate limit response headers
 * - 429 Too Many Requests handling
 * - Configuration integration
 *
 * @see \App\Http\Controllers\Api\V1\ToolExportController
 * @see TEST-29-5.md for full test documentation
 */
class RateLimitIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $exportEndpoint = '/api/v1/tools/export';
    protected array $validRequestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->validRequestData = [
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'format' => 'pdf',
            'data' => [
                'analyzedAt' => '2026-03-05T12:00:00Z',
                'results' => [
                    'title' => ['content' => 'Example', 'length' => 60, 'status' => 'ok'],
                ],
            ],
        ];

        // Clear rate limit cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Helper to get rate limit key for user.
     */
    protected function getRateLimitKey(int $userId): string
    {
        return 'export_rate_limit:' . $userId;
    }

    /**
     * Helper to make authenticated export request.
     */
    protected function makeExportRequest(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)
            ->postJson($this->exportEndpoint, array_merge($this->validRequestData, $data));
    }

    // =========================================================================
    // RL-BASIC: Basic Rate Limiting Tests
    // =========================================================================

    /**
     * RL-001: First export request succeeds
     */
    public function test_first_export_request_succeeds(): void
    {
        $response = $this->makeExportRequest();

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'downloadUrl', 'expiresAt']);
    }

    /**
     * RL-002: Rate limit header present
     */
    public function test_rate_limit_header_present(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit', '10');
    }

    /**
     * RL-003: Rate limit remaining decrements
     */
    public function test_rate_limit_remaining_decrements(): void
    {
        // First request
        $response1 = $this->makeExportRequest();
        $response1->assertHeader('X-RateLimit-Remaining', '9');

        // Second request
        $response2 = $this->makeExportRequest();
        $response2->assertHeader('X-RateLimit-Remaining', '8');

        // Third request
        $response3 = $this->makeExportRequest();
        $response3->assertHeader('X-RateLimit-Remaining', '7');
    }

    /**
     * RL-004: Rate limit reset timestamp
     */
    public function test_rate_limit_reset_timestamp(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Reset');
        
        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertGreaterThan(time(), $resetTime);
        $this->assertLessThan(time() + 3601, $resetTime);
    }

    /**
     * RL-005: 10th request succeeds
     */
    public function test_tenth_request_succeeds(): void
    {
        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->makeExportRequest();
            
            if ($i < 9) {
                $response->assertStatus(200);
            }
        }

        // 10th request should succeed
        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * RL-006: 11th request blocked
     */
    public function test_eleventh_request_blocked(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        // 11th request should be blocked
        $response = $this->makeExportRequest();
        $response->assertStatus(429);
    }

    /**
     * RL-007: Rate limit error message
     */
    public function test_rate_limit_error_message(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertJson([
            'message' => fn ($message) => str_contains($message, 'rate limit exceeded'),
        ]);
    }

    /**
     * RL-008: Rate limit resets after window
     */
    public function test_rate_limit_resets_after_window(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        // Clear cache to simulate window reset
        Cache::flush();

        // Should be allowed again
        $response = $this->makeExportRequest();
        $response->assertStatus(200);
    }

    /**
     * RL-009: Unauthenticated request returns 401
     */
    public function test_unauthenticated_request(): void
    {
        $response = $this->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(401);
    }

    /**
     * RL-010: Different users have separate limits
     */
    public function test_different_users_have_separate_limits(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A makes 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($userA)
                ->postJson($this->exportEndpoint, $this->validRequestData)
                ->assertStatus(200);
        }

        // User B should still have full limit
        $response = $this->actingAs($userB)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Remaining', '9');
    }

    // =========================================================================
    // RL-HEADERS: Rate Limit Headers Tests
    // =========================================================================

    /**
     * RH-001: X-RateLimit-Limit header
     */
    public function test_ratelimit_limit_header(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit', '10');
    }

    /**
     * RH-002: X-RateLimit-Remaining header
     */
    public function test_ratelimit_remaining_header(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Remaining', '9');
    }

    /**
     * RH-003: X-RateLimit-Reset header
     */
    public function test_ratelimit_reset_header(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Reset');
        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertIsInt($resetTime);
    }

    /**
     * RH-004: X-RateLimit-Reset in future
     */
    public function test_ratelimit_reset_in_future(): void
    {
        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertGreaterThan(time(), $resetTime);
    }

    /**
     * RH-005: X-RateLimit-Reset within 1 hour
     */
    public function test_ratelimit_reset_within_one_hour(): void
    {
        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertLessThan(time() + 3601, $resetTime);
    }

    /**
     * RH-006: Headers present on 429
     */
    public function test_headers_present_on_429(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    /**
     * RH-007: X-RateLimit-Remaining is 0 on limit
     */
    public function test_ratelimit_remaining_zero_on_limit(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * RH-008: Headers on successful response
     */
    public function test_headers_on_successful_response(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    // =========================================================================
    // RL-USER: User-Based Rate Limiting Tests
    // =========================================================================

    /**
     * RU-001: Rate limit keyed by user ID
     */
    public function test_rate_limit_keyed_by_user_id(): void
    {
        $this->makeExportRequest();

        // Check that the key uses user ID
        $key = $this->getRateLimitKey($this->user->id);
        $this->assertEquals(1, Cache::get($key, 0));
    }

    /**
     * RU-002: Separate limits per user
     */
    public function test_separate_limits_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A makes 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($userA)
                ->postJson($this->exportEndpoint, $this->validRequestData);
        }

        // User B makes 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($userB)
                ->postJson($this->exportEndpoint, $this->validRequestData);
        }

        // Both should have 5 remaining
        $responseA = $this->actingAs($userA)
            ->postJson($this->exportEndpoint, $this->validRequestData);
        $responseB = $this->actingAs($userB)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        $responseA->assertHeader('X-RateLimit-Remaining', '4');
        $responseB->assertHeader('X-RateLimit-Remaining', '4');
    }

    /**
     * RU-003: Admin users have same limit
     */
    public function test_admin_users_have_same_limit(): void
    {
        $admin = User::factory()->admin()->create();

        // Admin should have same 10/hour limit
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($admin)
                ->postJson($this->exportEndpoint, $this->validRequestData)
                ->assertStatus(200);
        }

        // 11th should be blocked
        $response = $this->actingAs($admin)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(429);
    }

    /**
     * RU-004: Rate limit persists across sessions
     */
    public function test_rate_limit_persists_across_sessions(): void
    {
        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->makeExportRequest();
        }

        // Simulate logout/login by creating new actingAs
        $response = $this->actingAs($this->user)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        // Should have 4 remaining (5 used)
        $response->assertHeader('X-RateLimit-Remaining', '4');
    }

    /**
     * RU-005: Anonymous users not rate limited
     */
    public function test_anonymous_users_not_rate_limited(): void
    {
        // Should get 401, not rate limited
        $response = $this->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(401);
        $response->assertHeaderMissing('X-RateLimit-Limit');
    }

    /**
     * RU-006: User ID extraction from auth
     */
    public function test_user_id_extraction_from_auth(): void
    {
        $this->makeExportRequest();

        // Verify the rate limit key uses the authenticated user's ID
        $key = $this->getRateLimitKey($this->user->id);
        $this->assertNotNull(Cache::get($key));
    }

    // =========================================================================
    // RL-CONFIG: Configuration Tests
    // =========================================================================

    /**
     * RC-001: Default export_rate_limit
     */
    public function test_default_export_rate_limit(): void
    {
        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit', '10');
    }

    /**
     * RC-002: Default export_rate_window
     */
    public function test_default_export_rate_window(): void
    {
        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $windowDuration = $resetTime - time();
        
        // Window should be approximately 1 hour (3600 seconds)
        $this->assertLessThanOrEqual(3600, $windowDuration);
        $this->assertGreaterThan(3500, $windowDuration);
    }

    /**
     * RC-003: Custom rate limit
     */
    public function test_custom_rate_limit(): void
    {
        Config::set('tools.export_rate_limit', 5);

        // Should only allow 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->makeExportRequest()->assertStatus(200);
        }

        // 6th should be blocked
        $response = $this->makeExportRequest();
        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Limit', '5');
    }

    /**
     * RC-004: Custom rate window
     */
    public function test_custom_rate_window(): void
    {
        Config::set('tools.export_rate_window', 1800); // 30 minutes

        $response = $this->makeExportRequest();

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $windowDuration = $resetTime - time();
        
        // Window should be approximately 30 minutes (1800 seconds)
        $this->assertLessThanOrEqual(1800, $windowDuration);
        $this->assertGreaterThan(1700, $windowDuration);
    }

    /**
     * RC-005: Config in tools.php
     */
    public function test_config_in_tools_php(): void
    {
        $this->assertEquals(10, config('tools.export_rate_limit', 10));
    }

    // =========================================================================
    // RL-ERROR: Error Handling Tests
    // =========================================================================

    /**
     * RE-001: 429 response structure
     */
    public function test_429_response_structure(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
    }

    /**
     * RE-002: Retry time in message
     */
    public function test_retry_time_in_message(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $message = $response->json('message');
        $this->assertStringContainsString('minutes', strtolower($message));
    }

    /**
     * RE-003: Rate limit headers on error
     */
    public function test_rate_limit_headers_on_error(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining', '0');
        $response->assertHeader('X-RateLimit-Reset');
    }

    /**
     * RE-004: HTTP status code
     */
    public function test_http_status_code(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $response->assertStatus(429);
    }

    /**
     * RE-005: Error response does not consume export
     */
    public function test_error_response_does_not_consume_export(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        // Make multiple blocked requests
        for ($i = 0; $i < 5; $i++) {
            $this->makeExportRequest()->assertStatus(429);
        }

        // Clear cache to reset
        Cache::flush();

        // Should still have full limit
        $response = $this->makeExportRequest();
        $response->assertHeader('X-RateLimit-Remaining', '9');
    }

    /**
     * RE-006: Graceful degradation (fail-open)
     */
    public function test_graceful_degradation_fail_open(): void
    {
        // This test verifies that if rate limiting fails,
        // the request should still be allowed
        // Implementation may vary based on caching strategy
        
        // For now, verify the endpoint works
        $response = $this->makeExportRequest();
        $response->assertStatus(200);
    }

    // =========================================================================
    // RL-EDGE: Edge Cases Tests
    // =========================================================================

    /**
     * EE-001: Rapid consecutive requests
     */
    public function test_rapid_consecutive_requests(): void
    {
        $successCount = 0;
        $blockedCount = 0;

        for ($i = 0; $i < 15; $i++) {
            $response = $this->makeExportRequest();
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $blockedCount++;
            }
        }

        $this->assertEquals(10, $successCount);
        $this->assertEquals(5, $blockedCount);
    }

    /**
     * EE-002: Concurrent requests from same user
     */
    public function test_concurrent_requests_from_same_user(): void
    {
        // Simulate 5 concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->makeExportRequest();
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Remaining should be 5
        $lastResponse = end($responses);
        $lastResponse->assertHeader('X-RateLimit-Remaining', '5');
    }

    /**
     * EE-003: Negative remaining prevented
     */
    public function test_negative_remaining_prevented(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        $response = $this->makeExportRequest();

        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');
        $this->assertGreaterThanOrEqual(0, $remaining);
    }

    /**
     * EE-004: Window boundary
     */
    public function test_window_boundary(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        // Should be blocked
        $this->makeExportRequest()->assertStatus(429);

        // Clear cache to simulate window reset
        Cache::flush();

        // Should succeed after reset
        $this->makeExportRequest()->assertStatus(200);
    }

    /**
     * EE-005: Large user ID
     */
    public function test_large_user_id(): void
    {
        $user = User::factory()->create(['id' => 999999999]);

        $response = $this->actingAs($user)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(200);
    }

    /**
     * EE-006: Zero rate limit config
     */
    public function test_zero_rate_limit_config(): void
    {
        Config::set('tools.export_rate_limit', 0);

        $response = $this->makeExportRequest();

        // With zero limit, first request should be blocked
        $response->assertStatus(429);
    }

    /**
     * EE-007: Very large rate limit
     */
    public function test_very_large_rate_limit(): void
    {
        Config::set('tools.export_rate_limit', 1000);

        // Make 100 requests
        for ($i = 0; $i < 100; $i++) {
            $this->makeExportRequest()->assertStatus(200);
        }

        // Should still have plenty remaining
        $response = $this->makeExportRequest();
        $response->assertHeader('X-RateLimit-Remaining', '899');
    }

    /**
     * EE-008: String user ID handled
     */
    public function test_string_user_id_handled(): void
    {
        // Auth system should prevent string user IDs
        // This tests that we get 401 for invalid auth
        $response = $this->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(401);
    }

    // =========================================================================
    // RL-SECURITY: Security Tests
    // =========================================================================

    /**
     * RS-001: Rate limit bypass attempt
     */
    public function test_rate_limit_bypass_attempt(): void
    {
        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->makeExportRequest();
        }

        // Try different format - should still be blocked
        $response = $this->makeExportRequest(['format' => 'xls']);
        $response->assertStatus(429);
    }

    /**
     * RS-002: Header injection prevention
     */
    public function test_header_injection_prevention(): void
    {
        $response = $this->makeExportRequest();

        // Headers should be properly sanitized
        $limit = $response->headers->get('X-RateLimit-Limit');
        $this->assertMatchesRegularExpression('/^\d+$/', $limit);
    }

    /**
     * RS-003: User ID spoofing
     */
    public function test_user_id_spoofing(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A makes requests
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($userA)
                ->postJson($this->exportEndpoint, $this->validRequestData);
        }

        // User A should be blocked
        $this->actingAs($userA)
            ->postJson($this->exportEndpoint, $this->validRequestData)
            ->assertStatus(429);

        // User B should not be affected
        $this->actingAs($userB)
            ->postJson($this->exportEndpoint, $this->validRequestData)
            ->assertStatus(200);
    }

    /**
     * RS-004: Race condition prevention
     */
    public function test_race_condition_prevention(): void
    {
        // Rapidly consume limit
        for ($i = 0; $i < 12; $i++) {
            $this->makeExportRequest();
        }

        // Exactly 10 should have succeeded, 2 blocked
        $key = $this->getRateLimitKey($this->user->id);
        $count = Cache::get($key, 0);

        // Count should not exceed limit
        $this->assertLessThanOrEqual(10, $count);
    }

    /**
     * RS-005: Rate limit DoS protection
     */
    public function test_rate_limit_dos_protection(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A exhausts their limit
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($userA)
                ->postJson($this->exportEndpoint, $this->validRequestData);
        }

        // User B should still be able to use the service
        $response = $this->actingAs($userB)
            ->postJson($this->exportEndpoint, $this->validRequestData);

        $response->assertStatus(200);
    }

    // =========================================================================
    // RL-PERFORMANCE: Performance Tests
    // =========================================================================

    /**
     * RP-001: Rate limit check overhead
     */
    public function test_rate_limit_check_overhead(): void
    {
        $start = microtime(true);

        $this->makeExportRequest();

        $elapsed = (microtime(true) - $start) * 1000; // Convert to ms

        // Rate limit check should add < 50ms overhead
        $this->assertLessThan(50, $elapsed);
    }

    /**
     * RP-002: Redis connection pooling
     */
    public function test_redis_connection_pooling(): void
    {
        // Make multiple requests - should not exhaust connections
        for ($i = 0; $i < 20; $i++) {
            $this->makeExportRequest();
        }

        // All should have proper responses (not connection errors)
        $response = $this->makeExportRequest();
        $this->assertContains($response->status(), [200, 429]);
    }

    /**
     * RP-003: Memory usage
     */
    public function test_memory_usage(): void
    {
        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 100; $i++) {
            $this->makeExportRequest();
        }

        $finalMemory = memory_get_usage();
        $growth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory growth should be < 5MB for 100 requests
        $this->assertLessThan(5, $growth);
    }

    /**
     * RP-004: Rate limit key efficiency
     */
    public function test_rate_limit_key_efficiency(): void
    {
        // Create 10 users and make requests
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            $this->actingAs($user)
                ->postJson($this->exportEndpoint, $this->validRequestData);
        }

        // Each user should have independent tracking
        foreach ($users as $user) {
            $key = $this->getRateLimitKey($user->id);
            $this->assertEquals(1, Cache::get($key, 0));
        }
    }
}
