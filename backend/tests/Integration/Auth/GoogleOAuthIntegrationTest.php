<?php

namespace Tests\Integration\Auth;

use App\Models\User;
use App\Models\SocialAccount;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Integration Tests (INT)
    // Tests for end-to-end OAuth flow
    // ==========================================

    /** @test */
    public function test_int001_full_oauth_flow(): void
    {
        // Step 1: Get redirect URL
        $response = $this->postJson('/api/v1/auth/google');
        
        $response->assertOk()
            ->assertJsonStructure(['redirect_url']);
        
        $redirectUrl = $response->json('redirect_url');
        $this->assertStringContainsString('accounts.google.com', $redirectUrl);
        
        // Extract state from redirect URL
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'] ?? null;
        $this->assertNotNull($state);

        // Step 2: Simulate callback with valid code and state
        $googleUser = $this->createMockGoogleUser();
        
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_authorization_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'email_verified'],
                    'token',
                    'is_new_user',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function test_int002_existing_user_login(): void
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        // Get redirect URL and extract state
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'];

        // Mock Google user with same email
        $googleUser = $this->createMockGoogleUser([
            'email' => 'existing@example.com',
            'name' => 'Existing User Updated',
        ]);
        
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_new_user' => false,
                ],
            ]);

        // Verify no duplicate user created
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
    }

    /** @test */
    public function test_int003_new_user_registration(): void
    {
        // Get redirect URL and extract state
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'];

        // Mock Google user
        $googleUser = $this->createMockGoogleUser([
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
        
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_new_user' => true,
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    /** @test */
    public function test_int004_token_is_valid(): void
    {
        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'];

        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $token = $response->json('data.token');

        // Use token to access protected route
        $response = $this->withToken($token, 'bearer')
            ->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonStructure(['id', 'name', 'email']);
    }

    /** @test */
    public function test_int005_user_can_logout(): void
    {
        // Create user with token
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Logout
        $response = $this->withToken($token, 'bearer')
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();

        // Token should be invalidated
        $response = $this->withToken($token, 'bearer')
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_int006_avatar_url_stored(): void
    {
        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'];

        $googleUser = $this->createMockGoogleUser([
            'avatar' => 'https://lh3.googleusercontent.com/test-avatar',
        ]);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        // Verify avatar in response
        $response->assertJson([
            'data' => [
                'user' => [
                    'avatar' => 'https://lh3.googleusercontent.com/test-avatar',
                ],
            ],
        ]);

        // Verify avatar in database
        $this->assertDatabaseHas('users', [
            'avatar' => 'https://lh3.googleusercontent.com/test-avatar',
        ]);
    }

    /** @test */
    public function test_int007_multiple_oauth_sessions(): void
    {
        // First OAuth login
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state1 = $params['state'];

        $googleUser = $this->createMockGoogleUser();
        $this->mockSocialiteUser($googleUser);

        $response1 = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code1',
            'state' => $state1,
        ]);

        $token1 = $response1->json('data.token');
        $userId = $response1->json('data.user.id');

        // Second OAuth login (same user)
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state2 = $params['state'];

        $this->mockSocialiteUser($googleUser);

        $response2 = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code2',
            'state' => $state2,
        ]);

        $token2 = $response2->json('data.token');

        // Same user, different tokens
        $this->assertEquals($userId, $response2->json('data.user.id'));
        $this->assertNotEquals($token1, $token2);

        // Both tokens should work
        $this->withToken($token1, 'bearer')
            ->getJson('/api/v1/auth/user')
            ->assertOk();

        $this->withToken($token2, 'bearer')
            ->getJson('/api/v1/auth/user')
            ->assertOk();
    }

    /** @test */
    public function test_int008_response_includes_all_user_fields(): void
    {
        // Complete OAuth flow
        $response = $this->postJson('/api/v1/auth/google');
        $redirectUrl = $response->json('redirect_url');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $state = $params['state'];

        $googleUser = $this->createMockGoogleUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
        $this->mockSocialiteUser($googleUser);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified',
                        'avatar',
                    ],
                    'token',
                    'is_new_user',
                ],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'email_verified' => true,
                        'avatar' => 'https://example.com/avatar.jpg',
                    ],
                ],
            ]);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function createMockGoogleUser(array $overrides = []): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->id = $overrides['id'] ?? '123456789';
        $user->name = $overrides['name'] ?? 'John Doe';
        $user->email = $overrides['email'] ?? 'john@example.com';
        $user->avatar = $overrides['avatar'] ?? 'https://lh3.googleusercontent.com/avatar';
        $user->token = $overrides['token'] ?? 'ya29.a0_token';
        $user->refreshToken = $overrides['refreshToken'] ?? '1//refresh_token';
        $user->expiresIn = $overrides['expiresIn'] ?? 3600;
        
        return $user;
    }

    private function mockSocialiteUser(SocialiteUser $user): void
    {
        $mockProvider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $mockProvider->shouldReceive('stateless')
            ->andReturnSelf();
        $mockProvider->shouldReceive('user')
            ->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }
}
