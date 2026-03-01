<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class GoogleAuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Rate Limiting Tests (RL)
    // Tests for rate limiting on callback endpoint
    // ==========================================

    /** @test */
    public function test_rl001_five_callbacks_allowed_per_minute(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(5)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 5 rapid requests - all should succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_{$i}",
                'state' => "state_{$i}",
            ]);
            $response->assertOk();
        }
    }

    /** @test */
    public function test_rl002_sixth_callback_blocked(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(5)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_{$i}",
                'state' => "state_{$i}",
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code_6',
            'state' => 'state_6',
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function test_rl003_rate_limit_per_ip(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(10)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // 5 requests from IP A
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_a_{$i}",
                'state' => "state_a_{$i}",
            ], ['REMOTE_ADDR' => '192.168.1.1']);
            $response->assertOk();
        }

        // 5 requests from IP B (different IP, should succeed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_b_{$i}",
                'state' => "state_b_{$i}",
            ], ['REMOTE_ADDR' => '192.168.1.2']);
            $response->assertOk();
        }
    }

    /** @test */
    public function test_rl004_rate_limit_resets_after_minute(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(6)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_{$i}",
                'state' => "state_{$i}",
            ]);
        }

        // Simulate time passing (1 minute + 1 second)
        $this->travel(61)->seconds();

        // 6th request should now succeed
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code_6',
            'state' => 'state_6',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_rl005_rate_limit_headers_present(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        // Check for rate limit headers
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    /** @test */
    public function test_rl006_retry_after_header_on_429(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(5)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 5 requests to hit limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_{$i}",
                'state' => "state_{$i}",
            ]);
        }

        // 6th request should have Retry-After header
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code_6',
            'state' => 'state_6',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    /** @test */
    public function test_rl007_rate_limit_error_response(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->times(5)
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 5 requests to hit limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/google/callback', [
                'code' => "code_{$i}",
                'state' => "state_{$i}",
            ]);
        }

        // 6th request
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code_6',
            'state' => 'state_6',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'rate_limit_exceeded',
                ],
            ]);
    }

    /** @test */
    public function test_rl008_rate_limit_does_not_affect_redirect(): void
    {
        Cache::flush();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->times(10)
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 10 redirect requests - all should succeed (no rate limit on redirect)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/google');
            $response->assertOk();
        }
    }
}
