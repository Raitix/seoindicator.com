<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ApiKeyRateLimiter;
use App\Services\ApiKeyRateLimiterService;
use App\Models\ApiKey;
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
    protected ApiKeyRateLimiterService $service;
    protected ApiKeyRateLimiter $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApiKeyRateLimiterService::class);
        $this->middleware = new ApiKeyRateLimiter($this->service);
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
        $apiKey = $this->createMockApiKey(1, 60);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * MI-002: Request blocked at limit
     */
    public function test_request_blocked_at_limit(): void
    {
        $apiKeyId = 2;
        $rateLimit = 60;
        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        // Exhaust limit
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        // Next request should be blocked
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key1 = 'api_key:' . $key1Id;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key1, 60000);
        }

        // key1 should be blocked
        $apiKey1 = $this->createMockApiKey($key1Id, $rateLimit);
        $request1 = $this->createRequestWithApiKey($apiKey1);
        $response1 = $this->middleware->handle($request1, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(429, $response1->getStatusCode());

        // key2 should still be allowed
        $apiKey2 = $this->createMockApiKey($key2Id, $rateLimit);
        $request2 = $this->createRequestWithApiKey($apiKey2);
        $response2 = $this->middleware->handle($request2, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * MI-004: Rate limit headers present
     */
    public function test_rate_limit_headers_present(): void
    {
        $apiKey = $this->createMockApiKey(5, 60);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    /**
     * MI-006: Per-key configurable limit applied
     */
    public function test_per_key_configurable_limit_applied(): void
    {
        $apiKeyId = 7;
        $customLimit = 300;

        // Make 250 requests (within custom limit)
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 250; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $customLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $apiKey = $this->createMockApiKey(8, null);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Should use default limit
        $this->assertEquals('60', $response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * MI-008: Missing API key passes through
     */
    public function test_missing_api_key_passes_through(): void
    {
        $request = Request::create('/api/v1/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Should pass through (auth middleware handles missing keys)
        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // HEADER TESTS (HD)
    // =========================================================================

    /**
     * HD-001: X-RateLimit-Limit header correct value
     */
    public function test_x_ratelimit_limit_header_correct_value(): void
    {
        $apiKey = $this->createMockApiKey(10, 120);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);
        $response1 = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals('59', $response1->headers->get('X-RateLimit-Remaining'));

        // Second request
        $response2 = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals('58', $response2->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * HD-003: X-RateLimit-Reset is Unix timestamp
     */
    public function test_x_ratelimit_reset_is_unix_timestamp(): void
    {
        $apiKey = $this->createMockApiKey(12, 60);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $resetTime = $response->headers->get('X-RateLimit-Reset');

        $this->assertIsNumeric($resetTime);
        $this->assertGreaterThan(time(), (int) $resetTime);
    }

    /**
     * HD-004: Headers on successful request
     */
    public function test_headers_on_successful_request(): void
    {
        $apiKey = $this->createMockApiKey(13, 60);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    /**
     * HD-007: Limit reflects key setting
     */
    public function test_limit_reflects_key_setting(): void
    {
        $customLimits = [10, 60, 300, 1000];

        foreach ($customLimits as $index => $limit) {
            Cache::flush();
            $apiKey = $this->createMockApiKey(100 + $index, $limit);
            $request = $this->createRequestWithApiKey($apiKey);

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
        $key = 'api_key:' . $apiKeyId;
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, 60000);
        }

        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
            $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
            $request = $this->createRequestWithApiKey($apiKey);
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Verify count is 10
        $count = $this->service->getCount('api_key:' . $apiKeyId, 60000);
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
            $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
            $request = $this->createRequestWithApiKey($apiKey);
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

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
        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

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
            $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
            $request = $this->createRequestWithApiKey($apiKey);
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 61st should be blocked
        $apiKey = $this->createMockApiKey($apiKeyId, $rateLimit);
        $request = $this->createRequestWithApiKey($apiKey);
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
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
            $apiKey1 = $this->createMockApiKey($key1Id, $rateLimit);
            $request1 = $this->createRequestWithApiKey($apiKey1);
            $response1 = $this->middleware->handle($request1, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response1->getStatusCode());

            $apiKey2 = $this->createMockApiKey($key2Id, $rateLimit);
            $request2 = $this->createRequestWithApiKey($apiKey2);
            $response2 = $this->middleware->handle($request2, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response2->getStatusCode());
        }

        // Both should still have remaining requests
        $count1 = $this->service->getCount('api_key:' . $key1Id, 60000);
        $count2 = $this->service->getCount('api_key:' . $key2Id, 60000);

        $this->assertEquals(50, $count1);
        $this->assertEquals(50, $count2);
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
            $apiKey = $this->createMockApiKey($apiKeyId, $customLimit);
            $request = $this->createRequestWithApiKey($apiKey);
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 11th should be blocked
        $apiKey = $this->createMockApiKey($apiKeyId, $customLimit);
        $request = $this->createRequestWithApiKey($apiKey);
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(429, $response->getStatusCode());

        // Verify custom limit in headers
        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a mock API key object
     */
    protected function createMockApiKey(int $id, ?int $rateLimit): ApiKey
    {
        $apiKey = Mockery::mock(ApiKey::class)->makePartial();
        $apiKey->shouldReceive('getAttribute')->with('id')->andReturn($id);
        $apiKey->shouldReceive('getAttribute')->with('rate_limit')->andReturn($rateLimit);
        return $apiKey;
    }

    /**
     * Create a request with API key attached
     */
    protected function createRequestWithApiKey(ApiKey $apiKey): Request
    {
        $request = Request::create('/api/v1/test', 'GET');
        $request->attributes->set('api_key', $apiKey);
        return $request;
    }
}
