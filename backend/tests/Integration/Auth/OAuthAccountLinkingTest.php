<?php

namespace Tests\Integration\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * OAuth Account Linking Integration Tests
 * 
 * Tests for email-based account linking during OAuth flow.
 * Validates that existing users are correctly linked with Google accounts.
 * 
 * @see TASK-25-23
 * @see TEST-25-23
 */
class OAuthAccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    // ==========================================
    // Account Linking Tests (LINK)
    // ==========================================

    /**
     * LINK-001: Existing email links account
     */
    public function test_link001_existing_email_links_account(): void
    {
        // Create existing user with password
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => bcrypt('password123'),
        ]);

        // Get redirect URL
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        // Mock Google user with same email
        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_new_user' => false,
                    'user' => [
                        'id' => $existingUser->id,
                        'email' => 'john@example.com',
                    ],
                ],
            ]);

        // Verify social account was created
        $this->assertEquals(1, SocialAccount::where('user_id', $existingUser->id)->count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => '123456789',
        ]);
    }

    /**
     * LINK-002: Password preserved after link
     */
    public function test_link002_password_preserved_after_link(): void
    {
        // Create existing user with password
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ])->assertOk();

        // Reload user
        $existingUser->refresh();

        // Password should still be set
        $this->assertNotNull($existingUser->password);
        $this->assertTrue(password_verify('password123', $existingUser->password));

        // User should be able to login with password
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $response->assertOk();
    }

    /**
     * LINK-003: Avatar updated if missing
     */
    public function test_link003_avatar_updated_if_missing(): void
    {
        // Create existing user without avatar
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'avatar' => null,
        ]);

        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'avatar' => 'https://lh3.googleusercontent.com/new_avatar',
        ]);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Avatar should be updated
        $response->assertJson([
            'data' => [
                'user' => [
                    'avatar' => 'https://lh3.googleusercontent.com/new_avatar',
                ],
            ],
        ]);

        $existingUser->refresh();
        $this->assertEquals('https://lh3.googleusercontent.com/new_avatar', $existingUser->avatar);
    }

    /**
     * LINK-004: Avatar not overwritten
     */
    public function test_link004_avatar_not_overwritten(): void
    {
        // Create existing user with avatar
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/existing_avatar.jpg',
        ]);

        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'avatar' => 'https://lh3.googleusercontent.com/google_avatar',
        ]);
        $this->mockSocialiteUser($googleUser);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ])->assertOk();

        // Avatar should NOT be overwritten
        $existingUser->refresh();
        $this->assertEquals('https://example.com/existing_avatar.jpg', $existingUser->avatar);
    }

    /**
     * LINK-005: Email auto-verified
     */
    public function test_link005_email_auto_verified(): void
    {
        // Create existing user with unverified email
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        // Complete OAuth flow with verified Google email
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser();
        $googleUser->user = ['email_verified' => true];
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Email should be verified
        $response->assertJson([
            'data' => [
                'user' => ['email_verified' => true],
            ],
        ]);

        $existingUser->refresh();
        $this->assertNotNull($existingUser->email_verified_at);
    }

    /**
     * LINK-006: Email case-insensitive match
     */
    public function test_link006_email_case_insensitive_match(): void
    {
        // Create existing user with mixed case email
        $existingUser = User::factory()->create([
            'email' => 'John@Example.COM',
        ]);

        // Complete OAuth flow with lowercase email
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'email' => 'john@example.com',
        ]);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Should link to existing user
        $response->assertJson([
            'data' => [
                'is_new_user' => false,
                'user' => ['id' => $existingUser->id],
            ],
        ]);
    }

    /**
     * LINK-007: Multiple providers allowed
     */
    public function test_link007_multiple_providers_allowed(): void
    {
        // Create user with GitHub social account
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);
        SocialAccount::factory()->forUser($user)->github()->create([
            'provider_id' => 'github_123',
        ]);

        // Complete OAuth flow with Google
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Should link to existing user
        $response->assertJson([
            'data' => [
                'is_new_user' => false,
                'user' => ['id' => $user->id],
            ],
        ]);

        // Should have 2 social accounts (GitHub + Google)
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

    /**
     * LINK-008: No duplicate social accounts
     */
    public function test_link008_no_duplicate_social_accounts(): void
    {
        // Create user with Google social account
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);
        SocialAccount::factory()->forUser($user)->google()->create([
            'provider_id' => '123456789',
        ]);

        // Complete OAuth flow again with same Google account
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser(['id' => '123456789']);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Should succeed
        $response->assertOk();

        // Should still have only 1 Google social account
        $this->assertEquals(1, SocialAccount::where([
            'user_id' => $user->id,
            'provider' => 'google',
        ])->count());
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    /**
     * User can login with both methods after linking
     */
    public function test_user_can_login_with_both_methods_after_linking(): void
    {
        // Create existing user with password
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Link Google account
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $oauthResponse = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);
        $oauthToken = $oauthResponse->json('data.token');

        // Login with password
        $passwordResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $passwordToken = $passwordResponse->json('data.token');

        // Both tokens should work
        $this->withToken($oauthToken, 'bearer')
            ->getJson('/api/v1/auth/user')
            ->assertOk();

        $this->withToken($passwordToken, 'bearer')
            ->getJson('/api/v1/auth/user')
            ->assertOk();
    }

    /**
     * Email with whitespace is trimmed
     */
    public function test_email_with_whitespace_is_trimmed(): void
    {
        // Create existing user with trimmed email
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        // Complete OAuth flow with whitespace in email
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'email' => '  john@example.com  ',
        ]);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Should link to existing user
        $response->assertJson([
            'data' => [
                'is_new_user' => false,
                'user' => ['id' => $existingUser->id],
            ],
        ]);
    }

    /**
     * Account linking creates social account record
     */
    public function test_account_linking_creates_social_account_record(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        // Verify no social account exists
        $this->assertEquals(0, SocialAccount::where('user_id', $existingUser->id)->count());

        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'id' => 'google_unique_id',
            'token' => 'access_token_123',
            'refreshToken' => 'refresh_token_456',
        ]);
        $this->mockSocialiteUser($googleUser);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ])->assertOk();

        // Verify social account was created with correct data
        $socialAccount = SocialAccount::where('user_id', $existingUser->id)->first();
        $this->assertNotNull($socialAccount);
        $this->assertEquals('google', $socialAccount->provider);
        $this->assertEquals('google_unique_id', $socialAccount->provider_id);
        $this->assertEquals('access_token_123', $socialAccount->provider_token);
        $this->assertEquals('refresh_token_456', $socialAccount->provider_refresh_token);
    }

    /**
     * Existing social account tokens are updated
     */
    public function test_existing_social_account_tokens_are_updated(): void
    {
        // Create user with existing Google account
        $user = User::factory()->create(['email' => 'john@example.com']);
        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create([
            'provider_id' => '123456789',
            'provider_token' => 'old_token',
            'provider_refresh_token' => 'old_refresh_token',
        ]);

        // Complete OAuth flow again
        $response = $this->postJson('/api/v1/auth/google');
        $state = $this->extractStateFromRedirectUrl($response->json('redirect_url'));

        $googleUser = $this->createMockGoogleUser([
            'id' => '123456789',
            'token' => 'new_token',
            'refreshToken' => 'new_refresh_token',
        ]);
        $this->mockSocialiteUser($googleUser);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ])->assertOk();

        // Tokens should be updated
        $socialAccount->refresh();
        $this->assertEquals('new_token', $socialAccount->provider_token);
        $this->assertEquals('new_refresh_token', $socialAccount->provider_refresh_token);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Extract state from redirect URL
     */
    protected function extractStateFromRedirectUrl(string $url): string
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
        return $params['state'] ?? '';
    }

    /**
     * Create a mock Google user
     */
    protected function createMockGoogleUser(array $overrides = []): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->id = $overrides['id'] ?? '123456789';
        $user->name = $overrides['name'] ?? 'John Doe';
        $user->email = $overrides['email'] ?? 'john@example.com';
        $user->avatar = $overrides['avatar'] ?? 'https://lh3.googleusercontent.com/avatar';
        $user->token = $overrides['token'] ?? 'ya29.a0_token';
        $user->refreshToken = $overrides['refreshToken'] ?? '1//refresh_token';
        $user->expiresIn = $overrides['expiresIn'] ?? 3600;
        $user->user = $overrides['user'] ?? ['email_verified' => true];
        
        return $user;
    }

    /**
     * Mock Socialite to return a specific user
     */
    protected function mockSocialiteUser(SocialiteUser $user): void
    {
        $mockProvider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }
}
