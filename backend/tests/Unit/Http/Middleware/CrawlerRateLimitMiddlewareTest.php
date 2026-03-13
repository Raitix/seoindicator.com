<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\CrawlerRateLimit;
use App\Services\Crawler\RateLimiterService;
use App\Services\Crawler\Data\RateLimitResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for CrawlerRateLimit middleware.
 *
 * Tests middleware that applies rate limiting to crawler endpoints.
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class CrawlerRateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createMockResult(bool $allowed, int $limit = 10, int $remaining = 5, ?int $retryAfter = null): RateLimitResult
    {
        if ($allowed) {
            return RateLimitResult::allowed($limit, $remaining, now()->addHour());
        }
        return RateLimitResult::denied($limit, now()->addHour(), $retryAfter ?? 3600);
    }

    protected function createRequest(string $domain = 'example.com'): Request
    {
        $request = Request::create('/api/crawler/jobs', 'POST');
        $request->headers->set('X-Target-Domain', $domain);
        return $request;
    }

    protected function createAllowedCheckResult(): array
    {
        return [
            'allowed' => true,
            'results' => [
                'daily' => $this->createMockResult(true),
                'concurrent' => $this->createMockResult(true),
                'domain' => $this->createMockResult(true),
                'global' => $this->createMockResult(true),
            ],
            'reason' => null,
        ];
    }

    protected function createBlockedCheckResult(string $reason): array
    {
        $results = [
            'daily' => $this->createMockResult(true),
            'concurrent' => $this->createMockResult(true),
            'domain' => $this->createMockResult(true),
            'global' => $this->createMockResult(true),
        ];

        // Set the blocking result
        $results[$reason] = $this->createMockResult(false, 10, 0, 3600);

        return [
            'allowed' => false,
            'results' => $results,
            'reason' => $reason,
        ];
    }

    // =========================================================================
    // Middleware Instantiation (CRM-INST-*)
    // =========================================================================

    /** @test */
    public function crm_inst_01_middleware_can_be_instantiated(): void
    {
        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $middleware = new CrawlerRateLimit($rateLimiter);

        $this->assertInstanceOf(CrawlerRateLimit::class, $middleware);
    }

    /** @test */
    public function crm_inst_02_middleware_resolved_from_container(): void
    {
        $middleware = app(CrawlerRateLimit::class);
        $this->assertInstanceOf(CrawlerRateLimit::class, $middleware);
    }

    // =========================================================================
    // Request Allowed (CRM-ALLOWED-*)
    // =========================================================================

    /** @test */
    public function crm_allowed_01_passes_when_all_limits_ok(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 9,
            'X-RateLimit-Reset' => now()->addHour()->timestamp,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function crm_allowed_02_adds_rate_limit_headers(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 8,
            'X-RateLimit-Reset' => 1234567890,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(10, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(8, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals(1234567890, $response->headers->get('X-RateLimit-Reset'));
    }

    // =========================================================================
    // Request Blocked (CRM-BLOCKED-*)
    // =========================================================================

    /** @test */
    public function crm_blocked_01_returns_429_when_user_daily_exceeded(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function crm_blocked_02_returns_429_when_concurrent_exceeded(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('concurrent'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function crm_blocked_03_returns_429_when_domain_rate_exceeded(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('domain'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function crm_blocked_04_returns_429_when_global_limit_exceeded(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('global'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    // =========================================================================
    // 429 Response Format (CRM-429-*)
    // =========================================================================

    /** @test */
    public function crm_429_01_response_format_is_json(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertJson($response->getContent());
    }

    /** @test */
    public function crm_429_02_response_has_message_field(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    /** @test */
    public function crm_429_03_response_has_limit_field(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('limit', $data);
        $this->assertEquals(10, $data['limit']);
    }

    /** @test */
    public function crm_429_04_response_has_remaining_field(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('remaining', $data);
        $this->assertEquals(0, $data['remaining']);
    }

    /** @test */
    public function crm_429_05_response_has_reset_at_field(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('resetAt', $data);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['resetAt']);
    }

    /** @test */
    public function crm_429_06_response_has_retry_after_field(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('retryAfter', $data);
        $this->assertEquals(3600, $data['retryAfter']);
    }

    /** @test */
    public function crm_429_07_full_response_structure(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('remaining', $data);
        $this->assertArrayHasKey('resetAt', $data);
        $this->assertArrayHasKey('retryAfter', $data);
    }

    // =========================================================================
    // Headers on 429 (CRM-HEADERS-*)
    // =========================================================================

    /** @test */
    public function crm_headers_01_429_has_x_ratelimit_limit(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(10, $response->headers->get('X-RateLimit-Limit'));
    }

    /** @test */
    public function crm_headers_02_429_has_x_ratelimit_remaining(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function crm_headers_03_429_has_x_ratelimit_reset(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /** @test */
    public function crm_headers_04_429_has_retry_after(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')->andReturn($this->createBlockedCheckResult('daily'));

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(3600, $response->headers->get('Retry-After'));
    }

    // =========================================================================
    // Unauthenticated Requests (CRM-AUTH-*)
    // =========================================================================

    /** @test */
    public function crm_auth_01_unauthenticated_request_rejected(): void
    {
        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        // No user resolver set - simulates unauthenticated request

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function crm_auth_02_unauthenticated_returns_json(): void
    {
        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Unauthenticated', $data['message']);
    }

    // =========================================================================
    // Domain Extraction (CRM-DOMAIN-*)
    // =========================================================================

    /** @test */
    public function crm_domain_01_extracts_domain_from_header(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')
            ->once()
            ->withArgs(function ($userId, $domain, $isAdmin) {
                return $domain === 'target-domain.com';
            })
            ->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 9,
            'X-RateLimit-Reset' => now()->addHour()->timestamp,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = Request::create('/api/crawler/jobs', 'POST');
        $request->headers->set('X-Target-Domain', 'target-domain.com');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function crm_domain_02_extracts_domain_from_request_body(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')
            ->once()
            ->withArgs(function ($userId, $domain, $isAdmin) {
                return $domain === 'body-domain.com';
            })
            ->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 9,
            'X-RateLimit-Reset' => now()->addHour()->timestamp,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = Request::create('/api/crawler/jobs', 'POST', ['url' => 'https://body-domain.com/page']);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function crm_domain_03_missing_domain_handled(): void
    {
        $user = $this->createUser();

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')->andReturn(false);
        $rateLimiter->shouldReceive('checkAllLimits')
            ->once()
            ->withArgs(function ($userId, $domain, $isAdmin) {
                return $domain === null;
            })
            ->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 9,
            'X-RateLimit-Reset' => now()->addHour()->timestamp,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = Request::create('/api/crawler/jobs', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    // =========================================================================
    // Admin Bypass (CRM-ADMIN-*)
    // =========================================================================

    /** @test */
    public function crm_admin_01_admin_bypasses_user_limits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rateLimiter = Mockery::mock(RateLimiterService::class);
        $rateLimiter->shouldReceive('isAdmin')
            ->once()
            ->andReturn(true);
        $rateLimiter->shouldReceive('checkAllLimits')
            ->once()
            ->withArgs(function ($userId, $domain, $isAdmin) {
                return $isAdmin === true;
            })
            ->andReturn($this->createAllowedCheckResult());
        $rateLimiter->shouldReceive('getHeaders')->andReturn([
            'X-RateLimit-Limit' => 10,
            'X-RateLimit-Remaining' => 10,
            'X-RateLimit-Reset' => now()->addHour()->timestamp,
        ]);

        $middleware = new CrawlerRateLimit($rateLimiter);
        $request = $this->createRequest();
        $request->setUserResolver(fn () => $admin);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
