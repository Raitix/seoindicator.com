<?php

namespace Tests\Feature\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Http\Middleware\AIRateLimiter as AIRateLimiterMiddleware;
use App\Services\AI\AIRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
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
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-01 18:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ============================================
    // MW-001: Authenticated User Rate Limiting
    // ============================================

    #[Test]
    public function mw_001_01_request_under_limit_returns_200(): void
    {
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(5));

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $user = \App\Models\User::factory()->create(['id' => 123]);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:123', [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key) {
                return $key === 'ai_rate_limit:user:123';
            });

        $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        // Test passes if cache key matches user:123
        $this->assertTrue(true);
    }

    #[Test]
    public function mw_001_04_rate_limit_headers_present(): void
    {
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn([]);

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
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:127.0.0.1', [])
            ->andReturn($this->generateTimestamps(5));

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_002_02_anonymous_request_at_limit_returns_429(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:127.0.0.1', [])
            ->andReturn($this->generateTimestamps(10));

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function mw_002_03_ip_from_x_forwarded_for(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:203.0.113.1', [])
            ->andReturn([]);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '203.0.113.1',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function mw_002_04_ip_from_remote_addr(): void
    {
        // When no X-Forwarded-For, uses REMOTE_ADDR
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:127.0.0.1', [])
            ->andReturn([]);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ]);

        $response->assertStatus(200);
    }

    // ============================================
    // MW-003: 429 Response Format
    // ============================================

    #[Test]
    public function mw_003_01_json_error_response(): void
    {
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn([]);

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
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(5));

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $response->assertHeader('X-RateLimit-Remaining', '15');
    }

    #[Test]
    public function mw_004_03_x_ratelimit_reset_header(): void
    {
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn([]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');
        
        $this->assertGreaterThan(time(), $resetTime);
        $this->assertLessThan(time() + 3700, $resetTime); // Within window + buffer
    }

    #[Test]
    public function mw_004_04_headers_on_429_response(): void
    {
        $user = \App\Models\User::factory()->create();

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        $userA = \App\Models\User::factory()->create(['id' => 1]);
        $userB = \App\Models\User::factory()->create(['id' => 2]);

        // User A at limit
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($this->generateTimestamps(20));

        // User B new
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:2', [])
            ->andReturn([]);

        // User A blocked
        $responseA = $this->actingAs($userA)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ]);
        $responseA->assertStatus(429);

        // User B allowed
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
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:192.168.1.1', [])
            ->andReturn($this->generateTimestamps(10));

        // IP B new
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:192.168.1.2', [])
            ->andReturn([]);

        // IP A blocked
        $responseA = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['REMOTE_ADDR' => '192.168.1.1']);
        $responseA->assertStatus(429);

        // IP B allowed
        $responseB = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], ['REMOTE_ADDR' => '192.168.1.2']);
        $responseB->assertStatus(200);
    }

    #[Test]
    public function mw_005_03_user_and_ip_separate_limits(): void
    {
        $user = \App\Models\User::factory()->create();

        // User at limit
        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn($this->generateTimestamps(20));

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
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:::1', [])
            ->andReturn([]);

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

        Cache::shouldReceive('get')
            ->withArgs(function ($key) use ($ipv6) {
                return strpos($key, $ipv6) !== false;
            })
            ->andReturn([]);

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
        $user = \App\Models\User::factory()->create();

        // Authenticated user - X-Forwarded-For should be ignored
        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:{$user->id}", [])
            ->andReturn([]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/suggestions', [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
            ], [
                'X-Forwarded-For' => '1.2.3.4', // Should be ignored for authenticated
            ]);

        // Should use user ID, not spoofed IP
        $response->assertStatus(200);
    }

    #[Test]
    public function sc_001_02_malformed_x_forwarded_for(): void
    {
        Cache::shouldReceive('get')
            ->withArgs(function ($key) {
                // Should extract a valid IP somehow
                return strpos($key, 'ai_rate_limit:ip:') === 0;
            })
            ->andReturn([]);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '<script>alert(1)</script>',
        ]);

        // Should handle gracefully
        $this->assertContains($response->status(), [200, 400, 429]);
    }

    #[Test]
    public function sc_001_03_multiple_ips_in_x_forwarded_for(): void
    {
        // Standard format: client, proxy1, proxy2
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:203.0.113.1', [])
            ->andReturn([]);

        $response = $this->postJson('/api/v1/ai/suggestions', [
            'indicator' => 'title',
            'context' => ['title' => 'Test'],
        ], [
            'X-Forwarded-For' => '203.0.113.1, 198.51.100.1, 192.0.2.1',
        ]);

        // Should use first IP (client)
        $response->assertStatus(200);
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
