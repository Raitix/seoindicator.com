<?php

namespace Tests\Feature\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Feature tests for AI Rate Limiter middleware.
 * 
 * Tests HTTP-level rate limiting for AI suggestion endpoints,
 * covering authenticated users, anonymous IP-based limiting,
 * and 429 response handling.
 * 
 * @see TASK-21-10: Add Rate Limiting
 */
class AIRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-01 18:00:00'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        parent::tearDown();
    }

    // ============================================
    // MW-001: Authenticated User Rate Limiting
    // ============================================

    #[Test]
    public function mw_001_01_request_under_limit_returns_200(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 5 requests
        $key = "ai_rate_limit:user:{$user->id}";
        Cache::put($key, $this->generateTimestamps(5), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_001_02_request_at_limit_returns_429(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 20 requests (at limit)
        $key = "ai_rate_limit:user:{$user->id}";
        Cache::put($key, $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function mw_001_03_user_identifier_is_user_id(): void
    {
        $user = User::factory()->create(['id' => 123]);

        // Empty cache - should be allowed
        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // Verify the cache key was created with user:123
        $this->assertTrue(Cache::has('ai_rate_limit:user:123'));
    }

    #[Test]
    public function mw_001_04_rate_limit_headers_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    // ============================================
    // MW-002: Anonymous (IP) Rate Limiting
    // ============================================

    #[Test]
    public function mw_002_01_anonymous_request_under_limit_returns_200(): void
    {
        // Pre-populate cache with 5 requests for IP
        Cache::put('ai_rate_limit:ip:127.0.0.1', $this->generateTimestamps(5), 3600);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_002_02_anonymous_request_at_limit_returns_429(): void
    {
        // Pre-populate cache with 10 requests (IP limit)
        Cache::put('ai_rate_limit:ip:127.0.0.1', $this->generateTimestamps(10), 3600);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function mw_002_03_ip_from_x_forwarded_for(): void
    {
        // Empty cache for this IP
        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '203.0.113.1',
        ]);

        $response->assertStatus(200);
        
        // Verify cache key was created with the forwarded IP
        $this->assertTrue(Cache::has('ai_rate_limit:ip:203.0.113.1'));
    }

    #[Test]
    public function mw_002_04_ip_from_remote_addr(): void
    {
        // When no X-Forwarded-For, uses REMOTE_ADDR (127.0.0.1 in tests)
        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(200);
        
        // Verify cache key was created with 127.0.0.1
        $this->assertTrue(Cache::has('ai_rate_limit:ip:127.0.0.1'));
    }

    // ============================================
    // MW-003: 429 Response Format
    // ============================================

    #[Test]
    public function mw_003_01_json_error_response(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 20 requests (at limit)
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
            ],
        ]);
    }

    #[Test]
    public function mw_003_02_retry_after_header(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 20 requests
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertHeader('Retry-After');
        
        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(3600, $retryAfter);
    }

    #[Test]
    public function mw_003_03_error_message_format(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 20 requests
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
                'retry_after',
            ],
        ]);
    }

    #[Test]
    public function mw_003_04_error_code_is_rate_limit_exceeded(): void
    {
        $user = User::factory()->create();

        // Pre-populate cache with 20 requests
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $this->assertEquals(
            'RATE_LIMIT_EXCEEDED',
            $response->json('error.code')
        );
    }

    // ============================================
    // MW-004: Rate Limit Headers
    // ============================================

    #[Test]
    public function mw_004_01_x_ratelimit_limit_header(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertHeader('X-RateLimit-Limit', '20');
    }

    #[Test]
    public function mw_004_02_x_ratelimit_remaining_header(): void
    {
        $user = User::factory()->create();

        // Pre-populate with 5 requests
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(5), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // After 5 requests + 1 new = 6 used, 14 remaining
        $response->assertHeader('X-RateLimit-Remaining', '14');
    }

    #[Test]
    public function mw_004_03_x_ratelimit_reset_header(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        $now = Carbon::now()->timestamp;
        
        // Reset time should be in the future (window seconds from now)
        $this->assertGreaterThanOrEqual($now, $resetTime);
        $this->assertLessThan($now + 3700, $resetTime); // Within window + buffer
    }

    #[Test]
    public function mw_004_04_headers_on_429_response(): void
    {
        $user = User::factory()->create();

        // Pre-populate with 20 requests (at limit)
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
        $response->assertHeader('Retry-After');
    }

    // ============================================
    // MW-005: Key Isolation
    // ============================================

    #[Test]
    public function mw_005_01_different_users_isolated(): void
    {
        $userA = User::factory()->create(['id' => 1]);
        $userB = User::factory()->create(['id' => 2]);

        // User A at limit
        Cache::put('ai_rate_limit:user:1', $this->generateTimestamps(20), 3600);

        // User A blocked
        $responseA = $this->actingAs($userA)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        $responseA->assertStatus(429);

        // User B allowed (new cache key)
        $responseB = $this->actingAs($userB)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        $responseB->assertStatus(200);
    }

    #[Test]
    public function mw_005_02_different_ips_isolated(): void
    {
        // IP A at limit
        Cache::put('ai_rate_limit:ip:192.168.1.1', $this->generateTimestamps(10), 3600);

        // IP A blocked - simulate via X-Forwarded-For
        $responseA = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['X-Forwarded-For' => '192.168.1.1']);
        $responseA->assertStatus(429);

        // IP B allowed
        $responseB = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['X-Forwarded-For' => '192.168.1.2']);
        $responseB->assertStatus(200);
    }

    #[Test]
    public function mw_005_03_user_and_ip_separate_limits(): void
    {
        $user = User::factory()->create();

        // User at limit
        Cache::put("ai_rate_limit:user:{$user->id}", $this->generateTimestamps(20), 3600);

        // Authenticated user blocked (uses user limit, not IP)
        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertStatus(429);
    }

    // ============================================
    // MW-006: Edge Cases
    // ============================================

    #[Test]
    public function mw_006_01_ipv6_address(): void
    {
        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['REMOTE_ADDR' => '::1']);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_006_02_long_ipv6_address(): void
    {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['REMOTE_ADDR' => $ipv6]);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_006_03_options_request_not_limited(): void
    {
        $response = $this->options('/api/v1/ai/suggestions');

        // OPTIONS should not hit rate limiter
        $response->assertStatus(200);
    }

    // ============================================
    // SC-001: Security Tests
    // ============================================

    #[Test]
    public function sc_001_01_header_spoofing_ignored(): void
    {
        $user = User::factory()->create();

        // Authenticated user - X-Forwarded-For should be ignored
        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ], [
                'X-Forwarded-For' => '1.2.3.4', // Should be ignored for authenticated
            ]);

        // Should use user ID, not spoofed IP
        $response->assertStatus(200);
        
        // Verify cache key is for user, not IP
        $this->assertTrue(Cache::has("ai_rate_limit:user:{$user->id}"));
    }

    #[Test]
    public function sc_001_02_malformed_x_forwarded_for(): void
    {
        // Should fall back to 127.0.0.1 when X-Forwarded-For is invalid
        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '<script>alert(1)</script>',
        ]);

        // Should handle gracefully (may use default IP)
        $this->assertContains($response->status(), [200, 400, 429, 422]);
    }

    #[Test]
    public function sc_001_03_multiple_ips_in_x_forwarded_for(): void
    {
        // Standard format: client, proxy1, proxy2
        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '203.0.113.1, 198.51.100.1, 192.0.2.1',
        ]);

        // Should use first IP (client)
        $response->assertStatus(200);
        
        // Verify cache key uses first IP
        $this->assertTrue(Cache::has('ai_rate_limit:ip:203.0.113.1'));
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function generateTimestamps(int $count): array
    {
        $timestamps = [];
        $baseTime = Carbon::now()->subMinutes(30);

        for ($i = 0; $i < $count; $i++) {
            $timestamps[] = $baseTime->addMinute()->toIso8601String();
        }

        return $timestamps;
    }
}
