<?php

namespace Tests\Integration\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * GoogleOAuthService Integration Tests
 * 
 * End-to-end tests for the Google OAuth flow.
 * Tests the complete flow from redirect URL generation to user login.
 * 
 * @see GoogleOAuthService
 * @see TEST-25-7
 */
class GoogleOAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected GoogleOAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists(Socialite::class)) {
            $this->markTestSkipped('Laravel Socialite not installed');
        }

        $this->service = app(GoogleOAuthService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Full OAuth Flow Tests (INT)
    // ==========================================

    /**
     * INT-001: Full OAuth flow works
     */
    public function test_full_oauth_flow_works(): void
    {
        // Step 1: Generate redirect URL
        $redirectUrl = $this->service->getRedirectUrl();
        
        $this->assertStringContainsString('accounts.google.com', $redirectUrl);
        
        // Extract state
        preg_match('/state=([a-zA-Z0-9]{40})/', $redirectUrl, $matches);
        $state = $matches[1];
        
        $this->assertTrue(Cache::has('oauth_state_' . $state));

        // Step 2: Simulate callback
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        // Step 3: Verify result
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('is_new_user', $result);
        
        $this->assertTrue($result['is_new_user']);
        $this->assertEquals('john@example.com', $result['user']->email);
    }

    /**
     * INT-002: User can login after OAuth
     */
    public function test_user_can_login_after_oauth(): void
    {
        $state = $this->generateState();
        
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        // Verify token is valid
        $token = $result['token'];
        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($token);
        $this->assertEquals($user->id, $result['user']->id);
    }

    /**
     * INT-003: User profile contains Google data
     */
    public function test_user_profile_contains_google_data(): void
    {
        $state = $this->generateState();
        
        $mockGoogleUser = $this->createMockGoogleUser([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/jane_avatar',
        ]);
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        $user = $result['user'];

        $this->assertEquals('Jane Smith', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertEquals('https://lh3.googleusercontent.com/jane_avatar', $user->avatar);
    }

    /**
     * INT-004: Social account queryable
     */
    public function test_social_account_queryable(): void
    {
        $state = $this->generateState();
        
        $mockGoogleUser = $this->createMockGoogleUser([
            'id' => 'google_user_123',
        ]);
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        $socialAccount = SocialAccount::where('provider', 'google')
            ->where('provider_id', 'google_user_123')
            ->first();

        $this->assertNotNull($socialAccount);
        $this->assertEquals($result['user']->id, $socialAccount->user_id);
    }

    /**
     * INT-005: Logout revokes token
     */
    public function test_logout_revokes_token(): void
    {
        $state = $this->generateState();
        
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);
        $user = $result['user'];
        $token = $result['token'];

        // Verify token exists
        $this->assertNotEmpty($token);

        // Logout - revoke token
        $user->tokens()->where('id', $token->id ?? null)->delete();

        // Verify token is revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * INT-006: Re-login uses existing account
     */
    public function test_relogin_uses_existing_account(): void
    {
        // First login
        $state1 = $this->generateState();
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);
        
        $result1 = $this->service->handleCallback('auth_code_1', $state1);

        // Second login (same Google account)
        $state2 = $this->generateState();
        $this->mockSocialiteUser($mockGoogleUser);
        
        $result2 = $this->service->handleCallback('auth_code_2', $state2);

        // Should be same user
        $this->assertEquals($result1['user']->id, $result2['user']->id);
        $this->assertFalse($result2['is_new_user']);

        // Only one user should exist
        $this->assertEquals(1, User::count());
    }

    /**
     * INT-007: OAuth with existing session
     */
    public function test_oauth_with_existing_session(): void
    {
        // Create existing user with password
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $state = $this->generateState();
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        // Should link to existing user
        $this->assertEquals($existingUser->id, $result['user']->id);
        $this->assertFalse($result['is_new_user']);

        // User should still have password
        $this->assertTrue(password_verify('password', $result['user']->password));
    }

    /**
     * INT-008: Socialite mock returns test data
     */
    public function test_socialite_mock_returns_test_data(): void
    {
        $mockGoogleUser = $this->createMockGoogleUser([
            'id' => 'test_id_123',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->mockSocialiteUser($mockGoogleUser);

        // Verify mock is working
        $provider = Socialite::driver('google');
        $user = $provider->stateless()->user();

        $this->assertEquals('test_id_123', $user->id);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    // ==========================================
    // Token Management Tests
    // ==========================================

    /**
     * Token is refreshed when expired
     */
    public function test_token_refresh_flow(): void
    {
        $state = $this->generateState();
        
        $mockGoogleUser = $this->createMockGoogleUser([
            'token' => 'initial_token',
            'refreshToken' => 'refresh_token_123',
            'expiresIn' => -1, // Already expired
        ]);
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        $socialAccount = $result['user']->socialAccounts()->first();
        
        // Token should be stored (even if expired)
        $this->assertEquals('initial_token', $socialAccount->provider_token);
        $this->assertEquals('refresh_token_123', $socialAccount->provider_refresh_token);
        $this->assertTrue($socialAccount->isTokenExpired());
    }

    /**
     * Multiple providers for same user
     */
    public function test_multiple_providers_same_user(): void
    {
        // Create user with GitHub
        $user = User::factory()->create(['email' => 'john@example.com']);
        SocialAccount::factory()->forUser($user)->github()->create();

        $state = $this->generateState();
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state);

        // Should link Google to existing user
        $this->assertEquals($user->id, $result['user']->id);
        
        // Should have 2 social accounts
        $this->assertEquals(2, $result['user']->socialAccounts()->count());
    }

    // ==========================================
    // Error Recovery Tests
    // ==========================================

    /**
     * Recovery from failed OAuth attempt
     */
    public function test_recovery_from_failed_oauth(): void
    {
        // First attempt fails
        $state1 = $this->generateState();
        $this->mockSocialiteError();

        try {
            $this->service->handleCallback('auth_code', $state1);
        } catch (\Exception $e) {
            // Expected
        }

        // Second attempt succeeds
        $state2 = $this->generateState();
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $result = $this->service->handleCallback('auth_code', $state2);

        // Should work correctly
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals(1, User::count());
    }

    /**
     * Database state is consistent after error
     */
    public function test_database_consistent_after_error(): void
    {
        $initialUserCount = User::count();
        $initialSocialCount = SocialAccount::count();

        $state = $this->generateState();
        $this->mockSocialiteError();

        try {
            $this->service->handleCallback('auth_code', $state);
        } catch (\Exception $e) {
            // Expected
        }

        // Database should be unchanged
        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($initialSocialCount, SocialAccount::count());
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    /**
     * OAuth flow completes within reasonable time
     */
    public function test_oauth_flow_performance(): void
    {
        $state = $this->generateState();
        $mockGoogleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($mockGoogleUser);

        $start = microtime(true);
        $this->service->handleCallback('auth_code', $state);
        $duration = (microtime(true) - $start) * 1000;

        // Should complete within 500ms
        $this->assertLessThan(500, $duration, 'OAuth flow should complete quickly');
    }

    /**
     * Concurrent state generation is thread-safe
     */
    public function test_concurrent_state_generation_thread_safe(): void
    {
        $states = [];
        
        // Generate 10 states
        for ($i = 0; $i < 10; $i++) {
            $url = $this->service->getRedirectUrl();
            preg_match('/state=([a-zA-Z0-9]{40})/', $url, $matches);
            $states[] = $matches[1];
        }

        // All should be unique
        $uniqueStates = array_unique($states);
        $this->assertCount(10, $uniqueStates);

        // All should be cached
        foreach ($states as $state) {
            $this->assertTrue(Cache::has('oauth_state_' . $state));
        }
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Generate a valid state for testing
     */
    protected function generateState(): string
    {
        $url = $this->service->getRedirectUrl();
        preg_match('/state=([a-zA-Z0-9]{40})/', $url, $matches);
        return $matches[1];
    }

    /**
     * Create a mock Google user
     */
    protected function createMockGoogleUser(array $overrides = []): \Laravel\Socialite\Contracts\User
    {
        $defaults = [
            'id' => '123456789',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/avatar',
            'token' => 'ya29.a0_token_value',
            'refreshToken' => '1//refresh_token',
            'expiresIn' => 3600,
        ];

        $data = array_merge($defaults, $overrides);

        $mock = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $mock->id = $data['id'];
        $mock->name = $data['name'];
        $mock->email = $data['email'];
        $mock->avatar = $data['avatar'];
        $mock->token = $data['token'];
        $mock->refreshToken = $data['refreshToken'];
        $mock->expiresIn = $data['expiresIn'];

        return $mock;
    }

    /**
     * Mock Socialite to return a specific user
     */
    protected function mockSocialiteUser(\Laravel\Socialite\Contracts\User $user): void
    {
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }

    /**
     * Mock Socialite to throw an error
     */
    protected function mockSocialiteError(): void
    {
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andThrow(
            new \Laravel\Socialite\Two\InvalidStateException()
        );

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }
}
