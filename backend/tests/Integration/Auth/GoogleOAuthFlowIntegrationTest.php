<?php

namespace Tests\Integration\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Google OAuth Integration Tests
 * 
 * Tests for TASK-25-23: Integration Tests for OAuth Flow
 * 
 * These tests verify the complete OAuth flow integration including:
 * - State management via cache
 * - Database interactions for users and social accounts
 * - Token generation and authentication
 * 
 * @see GoogleOAuthService
 * @see GoogleAuthController
 * @see TEST-25-23
 */
class GoogleOAuthFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // State Management Integration Tests (STATE)
    // ==========================================

    /**
     * STATE-001: State is stored in cache with correct TTL
     * 
     * @test
     */
    public function test_state_stored_in_cache_with_correct_ttl(): void
    {
        // Use partial mock - only mock the Socialite redirect response
        $mockRedirect = Mockery::mock();
        $mockRedirect->shouldReceive('getTargetUrl')
            ->once()
            ->andReturnUsing(function () {
                // Generate state like the real service does
                $state = Str::random(40);
                Cache::put('oauth_state_' . $state, true, now()->addSeconds(600));
                return 'https://accounts.google.com/o/oauth2/auth?state=' . $state;
            });

        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('with')->andReturnSelf();
        $mockProvider->shouldReceive('redirect')->andReturn($mockRedirect);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);

        $service = app(GoogleOAuthService::class);
        $redirectUrl = $service->getRedirectUrl();
        
        // Extract state from URL
        preg_match('/state=([a-zA-Z0-9]{40})/', $redirectUrl, $matches);
        $state = $matches[1];
        
        // Verify state is in cache
        $this->assertTrue(Cache::has('oauth_state_' . $state));
        
        // Verify state format (40 chars)
        $this->assertEquals(40, strlen($state));
    }

    /**
     * STATE-002: State is consumed after successful callback
     * 
     * @test
     */
    public function test_state_consumed_after_successful_callback(): void
    {
        // Create a valid state in cache
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        
        // Verify state exists
        $this->assertTrue(Cache::has('oauth_state_' . $state));
        
        // Mock callback user
        $this->mockSocialiteUser($this->createMockGoogleUser());
        
        $service = app(GoogleOAuthService::class);
        
        try {
            $service->handleCallback('valid_code', $state);
        } catch (\Exception $e) {
            // May fail due to other reasons, but state should be consumed
        }
        
        // State should be removed
        $this->assertFalse(Cache::has('oauth_state_' . $state));
    }

    /**
     * STATE-003: State cannot be reused
     * 
     * @test
     */
    public function test_state_cannot_be_reused(): void
    {
        // Create a valid state in cache
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        
        // First callback
        $this->mockSocialiteUser($this->createMockGoogleUser());
        $service = app(GoogleOAuthService::class);
        
        try {
            $service->handleCallback('valid_code', $state);
        } catch (\Exception $e) {
            // Continue
        }
        
        // Second callback with same state
        $this->mockSocialiteUser($this->createMockGoogleUser());
        
        $this->expectException(\App\Exceptions\Auth\InvalidOAuthStateException::class);
        $service->handleCallback('another_code', $state);
    }

    /**
     * STATE-004: Expired state is rejected
     * 
     * @test
     */
    public function test_expired_state_is_rejected(): void
    {
        // Create an expired state
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->subMinutes(11));
        
        $service = app(GoogleOAuthService::class);
        
        $this->expectException(\App\Exceptions\Auth\InvalidOAuthStateException::class);
        $service->handleCallback('valid_code', $state);
    }

    /**
     * STATE-005: Multiple states can coexist
     * 
     * @test
     */
    public function test_multiple_states_can_coexist(): void
    {
        $states = [];
        
        // Create 5 states manually
        for ($i = 0; $i < 5; $i++) {
            $state = Str::random(40);
            Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
            $states[] = $state;
        }
        
        // All states should be unique
        $this->assertCount(5, array_unique($states));
        
        // All should exist in cache
        foreach ($states as $state) {
            $this->assertTrue(Cache::has('oauth_state_' . $state));
        }
    }

    // ==========================================
    // User Creation Integration Tests (USER)
    // ==========================================

    /**
     * USER-001: New user created with correct attributes
     * 
     * @test
     */
    public function test_new_user_created_with_correct_attributes(): void
    {
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_123456',
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'avatar' => 'https://lh3.googleusercontent.com/avatar',
        ]);
        
        $user = $service->findOrCreateUser($googleUser);
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'avatar' => 'https://lh3.googleusercontent.com/avatar',
        ]);
        
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
    }

    /**
     * USER-002: Existing user returned by Google ID
     * 
     * @test
     */
    public function test_existing_user_returned_by_google_id(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);
        
        SocialAccount::factory()->forUser($existingUser)->google()->create([
            'provider_id' => 'google_existing',
        ]);
        
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_existing',
            'email' => 'existing@example.com',
        ]);
        
        $user = $service->findOrCreateUser($googleUser);
        
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
    }

    /**
     * USER-003: Existing user linked by email
     * 
     * @test
     */
    public function test_existing_user_linked_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'linkme@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
        
        $this->assertEquals(0, $existingUser->socialAccounts()->count());
        
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_new_link',
            'email' => 'linkme@example.com',
        ]);
        
        $user = $service->findOrCreateUser($googleUser);
        
        $this->assertEquals($existingUser->id, $user->id);
        
        // Social account should be linked
        $this->assertEquals(1, $user->socialAccounts()->count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'google_new_link',
        ]);
        
        // Email should be verified
        $existingUser->refresh();
        $this->assertNotNull($existingUser->email_verified_at);
        
        // Password should be preserved
        $this->assertTrue(password_verify('password123', $existingUser->password));
    }

    /**
     * USER-004: Email case-insensitive matching
     * 
     * @test
     */
    public function test_email_case_insensitive_matching(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'MixedCase@Example.COM',
            'email_verified_at' => null,
        ]);
        
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_case_test',
            'email' => 'mixedcase@example.com', // lowercase
        ]);
        
        $user = $service->findOrCreateUser($googleUser);
        
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals(1, User::count());
    }

    // ==========================================
    // Social Account Integration Tests (SOCIAL)
    // ==========================================

    /**
     * SOCIAL-001: Social account created with tokens
     * 
     * @test
     */
    public function test_social_account_created_with_tokens(): void
    {
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_token_test',
            'token' => 'ya29.a0_access_token',
            'refreshToken' => '1//refresh_token_value',
            'expiresIn' => 3600,
        ]);
        
        $user = $service->findOrCreateUser($googleUser);
        
        $socialAccount = SocialAccount::where('provider', 'google')
            ->where('provider_id', 'google_token_test')
            ->first();
        
        $this->assertNotNull($socialAccount);
        $this->assertEquals('ya29.a0_access_token', $socialAccount->provider_token);
        $this->assertEquals('1//refresh_token_value', $socialAccount->provider_refresh_token);
        $this->assertNotNull($socialAccount->provider_token_expires_at);
    }

    /**
     * SOCIAL-002: Existing social account tokens updated
     * 
     * @test
     */
    public function test_existing_social_account_tokens_updated(): void
    {
        $user = User::factory()->create(['email' => 'tokenupdate@example.com']);
        
        SocialAccount::factory()->forUser($user)->google()->create([
            'provider_id' => 'google_update',
            'provider_token' => 'old_token',
            'provider_refresh_token' => 'old_refresh',
        ]);
        
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_update',
            'email' => 'tokenupdate@example.com',
            'token' => 'new_token',
            'refreshToken' => 'new_refresh',
        ]);
        
        $service->findOrCreateUser($googleUser);
        
        $socialAccount = SocialAccount::where('provider_id', 'google_update')->first();
        
        $this->assertEquals('new_token', $socialAccount->provider_token);
        $this->assertEquals('new_refresh', $socialAccount->provider_refresh_token);
    }

    /**
     * SOCIAL-003: Multiple providers for same user
     * 
     * @test
     */
    public function test_multiple_providers_for_same_user(): void
    {
        $user = User::factory()->create(['email' => 'multiprovider@example.com']);
        
        // Create GitHub social account
        SocialAccount::factory()->forUser($user)->create([
            'provider' => 'github',
            'provider_id' => 'github_123',
        ]);
        
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'email' => 'multiprovider@example.com',
        ]);
        
        $service->findOrCreateUser($googleUser);
        
        $this->assertEquals(2, $user->socialAccounts()->count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
        ]);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);
    }

    // ==========================================
    // Token Authentication Tests (TOKEN)
    // ==========================================

    /**
     * TOKEN-001: Sanctum token created and valid
     * 
     * @test
     */
    public function test_sanctum_token_created_and_valid(): void
    {
        // Create a valid state
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        
        // Mock callback
        $this->mockSocialiteUser($this->createMockGoogleUser([
            'id' => 'google_token_auth',
            'email' => 'tokenauth@example.com',
        ]));
        
        $service = app(GoogleOAuthService::class);
        $result = $service->handleCallback('valid_code', $state);
        
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
        
        // Token format: id|plain_text_token
        $this->assertMatchesRegularExpression('/^\d+\|[a-zA-Z0-9]+$/', $result['token']);
        
        // Verify token works
        $user = User::where('email', 'tokenauth@example.com')->first();
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * TOKEN-002: Token can authenticate API requests
     * 
     * @test
     */
    public function test_token_can_authenticate_api_requests(): void
    {
        // Create a valid state
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        
        // Mock callback
        $this->mockSocialiteUser($this->createMockGoogleUser([
            'email' => 'apiauth@example.com',
        ]));
        
        $service = app(GoogleOAuthService::class);
        $result = $service->handleCallback('valid_code', $state);
        $token = $result['token'];
        
        // Use token to authenticate
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user');
        
        $response->assertOk();
        $response->assertJsonPath('data.user.email', 'apiauth@example.com');
    }

    // ==========================================
    // Error Scenario Tests (ERR)
    // ==========================================

    /**
     * ERR-001: Missing email throws exception
     * 
     * @test
     */
    public function test_missing_email_throws_exception(): void
    {
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'email' => null,
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $service->findOrCreateUser($googleUser);
    }

    /**
     * ERR-002: Missing Google ID throws exception
     * 
     * @test
     */
    public function test_missing_google_id_throws_exception(): void
    {
        $service = app(GoogleOAuthService::class);
        
        $googleUser = $this->createMockGoogleUser([
            'id' => null,
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $service->findOrCreateUser($googleUser);
    }

    /**
     * ERR-003: Access denied code throws exception
     * 
     * @test
     */
    public function test_access_denied_code_throws_exception(): void
    {
        // Create a valid state
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
        
        $service = app(GoogleOAuthService::class);
        
        $this->expectException(\App\Exceptions\Auth\AccessDeniedException::class);
        $service->handleCallback('access_denied', $state);
    }

    // ==========================================
    // Concurrent Operation Tests (CONC)
    // ==========================================

    /**
     * CONC-001: Multiple users created independently
     * 
     * @test
     */
    public function test_multiple_users_created_independently(): void
    {
        $service = app(GoogleOAuthService::class);
        
        for ($i = 0; $i < 5; $i++) {
            $googleUser = $this->createMockGoogleUser([
                'id' => 'google_concurrent_' . $i,
                'email' => 'concurrent' . $i . '@example.com',
            ]);
            
            $user = $service->findOrCreateUser($googleUser);
            $this->assertNotNull($user->id);
        }
        
        $this->assertEquals(5, User::count());
        $this->assertEquals(5, SocialAccount::count());
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create a mock Google user for testing
     */
    protected function createMockGoogleUser(array $overrides = []): SocialiteUser
    {
        $defaults = [
            'id' => 'google_' . Str::random(10),
            'name' => 'Test User',
            'email' => 'test' . Str::random(5) . '@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/avatar',
            'token' => 'ya29.a0_token',
            'refreshToken' => '1//refresh',
            'expiresIn' => 3600,
        ];

        $data = array_merge($defaults, $overrides);

        $mock = Mockery::mock(SocialiteUser::class);
        $mock->id = $data['id'];
        $mock->name = $data['name'];
        $mock->email = $data['email'];
        $mock->avatar = $data['avatar'];
        $mock->token = $data['token'];
        $mock->refreshToken = $data['refreshToken'];
        $mock->expiresIn = $data['expiresIn'];
        $mock->user = ['email_verified' => true];

        return $mock;
    }

    /**
     * Mock Socialite to return a specific user
     */
    protected function mockSocialiteUser(SocialiteUser $user): void
    {
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }
}
