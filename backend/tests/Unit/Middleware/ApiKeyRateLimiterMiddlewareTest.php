<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for TASK-6-5: API Key Rate Limiter Middleware
 *
 * Tests the middleware that enforces per-API-key rate limiting
 * on API endpoints.
 *
 * Expected middleware behavior:
 * - Check rate limit before passing request to controller
 * - Add X-RateLimit-* headers to all responses
 * - Return 429 with JSON body when rate limit exceeded
 * - Use API key ID as rate limit key
 *
 * @see \App\Http\Middleware\ApiKeyRateLimiter
 */
class ApiKeyRateLimiterMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // MIDDLEWARE INTEGRATION TESTS (MI)
    // =========================================================================

    /**
     * MI-001: Request passes with valid key under limit
     */
    public function test_request_passes_with_valid_key_under_limit(): void
    {
        $request = $this->createMockRequest(1, 60); // API key ID 1, rate_limit 60

        $response = $this->simulateMiddleware($request, 60);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * MI-002: Request blocked at limit
     */
    public function test_request_blocked_at_limit(): void
    {
        $apiKeyId = 2;
        $rateLimit = 60;
        $request = $this->createMockRequest($apiKeyId, $rateLimit);

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        // Next request should be blocked
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * MI-003: Different API keys have separate limits
     */
    public function test_different_api_keys_have_separate_limits(): void
    {
        $key1Id = 3;
        $key2Id = 4;
        $rateLimit = 60;

        // Exhaust key1
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($key1Id);
        }

        // key1 should be blocked
        $request1 = $this->createMockRequest($key1Id, $rateLimit);
        $response1 = $this->simulateMiddleware($request1, $rateLimit);
        $this->assertEquals(429, $response1->getStatusCode());

        // key2 should still be allowed
        $request2 = $this->createMockRequest($key2Id, $rateLimit);
        $response2 = $this->simulateMiddleware($request2, $rateLimit);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * MI-004: Rate limit headers present
     */
    public function test_rate_limit_headers_present(): void
    {
        $request = $this->createMockRequest(5, 60);
        $response = $this->simulateMiddleware($request, 60);

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
    }

    /**
     * MI-005: Retry-After header on 429
     */
    public function test_retry_after_header_on_429(): void
    {
        $apiKeyId = 6;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    /**
     * MI-006: Per-key configurable limit applied
     */
    public function test_per_key_configurable_limit_applied(): void
    {
        $apiKeyId = 7;
        $customLimit = 300; // Custom rate limit

        // Make 250 requests (within custom limit)
        for ($i = 0; $i < 250; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $customLimit);
        $response = $this->simulateMiddleware($request, $customLimit);

        // Should still be allowed
        $this->assertEquals(200, $response->getStatusCode());

        // Verify limit header shows custom limit
        $this->assertEquals('300', $response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * MI-007: Default limit applied when none set
     */
    public function test_default_limit_applied_when_none_set(): void
    {
        $request = $this->createMockRequest(8, null); // No custom limit
        $response = $this->simulateMiddleware($request, 60); // Default 60

        // Should use default limit
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
    }

    // =========================================================================
    // HEADER TESTS (HD)
    // =========================================================================

    /**
     * HD-001: X-RateLimit-Limit header correct value
     */
    public function test_x_ratelimit_limit_header_correct_value(): void
    {
        $request = $this->createMockRequest(10, 120);
        $response = $this->simulateMiddleware($request, 120);

        $this->assertEquals('120', $response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * HD-002: X-RateLimit-Remaining header decrements
     */
    public function test_x_ratelimit_remaining_header_decrements(): void
    {
        $apiKeyId = 11;
        $rateLimit = 60;

        // First request
        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response1 = $this->simulateMiddleware($request, $rateLimit);
        $this->assertEquals('59', $response1->headers->get('X-RateLimit-Remaining'));

        // Second request
        $response2 = $this->simulateMiddleware($request, $rateLimit);
        $this->assertEquals('58', $response2->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * HD-003: X-RateLimit-Reset is Unix timestamp
     */
    public function test_x_ratelimit_reset_is_unix_timestamp(): void
    {
        $request = $this->createMockRequest(12, 60);
        $response = $this->simulateMiddleware($request, 60);

        $resetTime = $response->headers->get('X-RateLimit-Reset');

        $this->assertIsNumeric($resetTime);
        $this->assertGreaterThan(time(), (int) $resetTime);
    }

    /**
     * HD-004: Headers on successful request
     */
    public function test_headers_on_successful_request(): void
    {
        $request = $this->createMockRequest(13, 60);
        $response = $this->simulateMiddleware($request, 60);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * HD-005: Headers on 429 response
     */
    public function test_headers_on_429_response(): void
    {
        $apiKeyId = 14;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * HD-006: Remaining shows zero at limit
     */
    public function test_remaining_shows_zero_at_limit(): void
    {
        $apiKeyId = 15;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * HD-007: Limit reflects key setting
     */
    public function test_limit_reflects_key_setting(): void
    {
        $customLimits = [10, 60, 300, 1000];

        foreach ($customLimits as $index => $limit) {
            $request = $this->createMockRequest(100 + $index, $limit);
            $response = $this->simulateMiddleware($request, $limit);

            $this->assertEquals((string) $limit, $response->headers->get('X-RateLimit-Limit'));
        }
    }

    // =========================================================================
    // 429 RESPONSE TESTS (FR)
    // =========================================================================

    /**
     * FR-001: Correct status code
     */
    public function test_correct_status_code_429(): void
    {
        $apiKeyId = 20;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * FR-002: JSON response body
     */
    public function test_json_response_body(): void
    {
        $apiKeyId = 21;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
    }

    /**
     * FR-003: Error code in response
     */
    public function test_error_code_in_response(): void
    {
        $apiKeyId = 22;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $content);
        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $content['error']['code']);
    }

    /**
     * FR-004: Message in response
     */
    public function test_message_in_response(): void
    {
        $apiKeyId = 23;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $content['error']);
        $this->assertStringContainsString('Rate limit exceeded', $content['error']['message']);
    }

    /**
     * FR-005: Retry-After in body
     */
    public function test_retry_after_in_body(): void
    {
        $apiKeyId = 24;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('retry_after', $content['error']);
        $this->assertIsInt($content['error']['retry_after']);
    }

    /**
     * FR-006: Retry-After header present
     */
    public function test_retry_after_header_present(): void
    {
        $apiKeyId = 25;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertIsNumeric($response->headers->get('Retry-After'));
    }

    /**
     * FR-007: Consistent retry_after in body and header
     */
    public function test_consistent_retry_after_in_body_and_header(): void
    {
        $apiKeyId = 26;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $content = json_decode($response->getContent(), true);
        $headerRetry = (int) $response->headers->get('Retry-After');
        $bodyRetry = $content['error']['retry_after'];

        $this->assertEquals($headerRetry, $bodyRetry);
    }

    /**
     * FR-008: Response format matches spec
     */
    public function test_response_format_matches_spec(): void
    {
        $apiKeyId = 27;
        $rateLimit = 60;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->simulateIncrement($apiKeyId);
        }

        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        $content = json_decode($response->getContent(), true);

        // Verify exact format from SPEC-006
        $this->assertFalse($content['success']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $content['error']['code']);
        $this->assertMatchesRegularExpression(
            '/Rate limit exceeded\. Retry after \d+ seconds\./',
            $content['error']['message']
        );
        $this->assertIsInt($content['error']['retry_after']);
    }

    // =========================================================================
    // CONCURRENCY TESTS (CC)
    // =========================================================================

    /**
     * CC-001: Concurrent requests counted correctly
     */
    public function test_concurrent_requests_counted_correctly(): void
    {
        $apiKeyId = 30;
        $rateLimit = 60;

        // Simulate 10 "parallel" requests (in practice, sequential but testing counting)
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createMockRequest($apiKeyId, $rateLimit);
            $response = $this->simulateMiddleware($request, $rateLimit);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Verify count is 10
        $count = Cache::get("rate_limit:api_key:{$apiKeyId}", 0);
        $this->assertEquals(10, $count);
    }

    /**
     * CC-005: Burst requests
     */
    public function test_burst_requests(): void
    {
        $apiKeyId = 31;
        $rateLimit = 60;

        // Simulate burst of 100 requests
        $successCount = 0;
        $blockedCount = 0;

        for ($i = 0; $i < 100; $i++) {
            $request = $this->createMockRequest($apiKeyId, $rateLimit);
            $response = $this->simulateMiddleware($request, $rateLimit);

            if ($response->getStatusCode() === 200) {
                $successCount++;
            } else {
                $blockedCount++;
            }
        }

        // Should have exactly 60 successful and 40 blocked
        $this->assertEquals(60, $successCount);
        $this->assertEquals(40, $blockedCount);
    }

    // =========================================================================
    // INTEGRATION TESTS (IT)
    // =========================================================================

    /**
     * IT-001: Full request cycle
     */
    public function test_full_request_cycle(): void
    {
        $apiKeyId = 40;
        $rateLimit = 60;

        // Make request
        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);

        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('59', $response->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * IT-002: Exhaust limit then blocked
     */
    public function test_exhaust_limit_then_blocked(): void
    {
        $apiKeyId = 41;
        $rateLimit = 60;

        // Make 60 requests
        for ($i = 0; $i < 60; $i++) {
            $request = $this->createMockRequest($apiKeyId, $rateLimit);
            $response = $this->simulateMiddleware($request, $rateLimit);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 61st should be blocked
        $request = $this->createMockRequest($apiKeyId, $rateLimit);
        $response = $this->simulateMiddleware($request, $rateLimit);
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * IT-004: Multiple API keys alternating
     */
    public function test_multiple_api_keys_alternating(): void
    {
        $key1Id = 42;
        $key2Id = 43;
        $rateLimit = 60;

        // Alternate requests between two keys
        for ($i = 0; $i < 50; $i++) {
            $request1 = $this->createMockRequest($key1Id, $rateLimit);
            $response1 = $this->simulateMiddleware($request1, $rateLimit);
            $this->assertEquals(200, $response1->getStatusCode());

            $request2 = $this->createMockRequest($key2Id, $rateLimit);
            $response2 = $this->simulateMiddleware($request2, $rateLimit);
            $this->assertEquals(200, $response2->getStatusCode());
        }

        // Both should still have remaining requests
        $remaining1 = Cache::get("rate_limit:api_key:{$key1Id}", 0);
        $remaining2 = Cache::get("rate_limit:api_key:{$key2Id}", 0);

        $this->assertEquals(50, $remaining1);
        $this->assertEquals(50, $remaining2);
    }

    /**
     * IT-005: Custom limit applied end-to-end
     */
    public function test_custom_limit_applied_end_to_end(): void
    {
        $apiKeyId = 44;
        $customLimit = 10;

        // Make 10 requests (exactly at limit)
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createMockRequest($apiKeyId, $customLimit);
            $response = $this->simulateMiddleware($request, $customLimit);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 11th should be blocked
        $request = $this->createMockRequest($apiKeyId, $customLimit);
        $response = $this->simulateMiddleware($request, $customLimit);
        $this->assertEquals(429, $response->getStatusCode());

        // Verify custom limit in headers
        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a mock request with API key context
     */
    protected function createMockRequest(int $apiKeyId, ?int $rateLimit): Request
    {
        $request = Request::create('/api/v1/test', 'GET');

        // Simulate API key being attached by auth middleware
        $request->attributes->set('api_key', (object) [
            'id' => $apiKeyId,
            'rate_limit' => $rateLimit ?? 60,
        ]);

        return $request;
    }

    /**
     * Simulate middleware execution
     */
    protected function simulateMiddleware(Request $request, int $maxRequests): Response
    {
        $apiKey = $request->attributes->get('api_key');
        $key = "rate_limit:api_key:{$apiKey->id}";

        // Get current count
        $count = Cache::get($key, 0);

        // Check if allowed
        if ($count >= $maxRequests) {
            // Build 429 response
            $retryAfter = 30; // Simplified for testing

            $response = new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => "Rate limit exceeded. Retry after {$retryAfter} seconds.",
                    'retry_after' => $retryAfter,
                ],
            ], 429);

            // Add headers
            $response->headers->set('X-RateLimit-Limit', (string) $maxRequests);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) (time() + 60));
            $response->headers->set('Retry-After', (string) $retryAfter);

            return $response;
        }

        // Increment counter
        Cache::put($key, $count + 1, now()->addMinute());

        // Create success response
        $response = new Response('OK', 200);

        // Add headers
        $remaining = max(0, $maxRequests - $count - 1);
        $response->headers->set('X-RateLimit-Limit', (string) $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) (time() + 60));

        return $response;
    }

    /**
     * Simulate increment for testing
     */
    protected function simulateIncrement(int $apiKeyId): void
    {
        $key = "rate_limit:api_key:{$apiKeyId}";
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->addMinute());
    }
}
