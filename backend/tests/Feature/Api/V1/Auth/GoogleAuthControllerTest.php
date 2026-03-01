<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use App\Exceptions\Auth\InvalidOAuthStateException;
use App\Exceptions\Auth\OAuthFailedException;
use App\Exceptions\Auth\AccessDeniedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Redirect Endpoint Tests (RE)
    // ==========================================

    /** @test */
    public function test_re001_redirect_returns_redirect_url(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?client_id=test&redirect_uri=https://example.com/callback&state=abc123&scope=email+profile&response_type=code');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk()
            ->assertJsonStructure(['redirect_url']);
    }

    /** @test */
    public function test_re002_url_contains_state_parameter(): void
    {
        $redirectUrl = 'https://accounts.google.com/o/oauth2/auth?state=teststate123';
        
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn($redirectUrl);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk();
        $this->assertStringContainsString('state=', $response->json('redirect_url'));
    }

    /** @test */
    public function test_re003_url_contains_client_id(): void
    {
        $redirectUrl = 'https://accounts.google.com/o/oauth2/auth?client_id=test-client-id';
        
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn($redirectUrl);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk();
        $this->assertStringContainsString('client_id=', $response->json('redirect_url'));
    }

    /** @test */
    public function test_re004_url_contains_redirect_uri(): void
    {
        $redirectUrl = 'https://accounts.google.com/o/oauth2/auth?redirect_uri=https://example.com/callback';
        
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn($redirectUrl);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk();
        $this->assertStringContainsString('redirect_uri=', $response->json('redirect_url'));
    }

    /** @test */
    public function test_re005_url_contains_scope(): void
    {
        $redirectUrl = 'https://accounts.google.com/o/oauth2/auth?scope=email+profile';
        
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn($redirectUrl);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk();
        $this->assertStringContainsString('scope=', $response->json('redirect_url'));
    }

    /** @test */
    public function test_re006_works_without_authentication(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // No actingAs() - testing unauthenticated access
        $response = $this->postJson('/api/v1/auth/google');

        $response->assertOk();
    }

    /** @test */
    public function test_re007_rate_limiting_not_applied_to_redirect(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->times(10)
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        // Make 10 rapid requests - all should succeed
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/google');
            $response->assertOk();
        }
    }

    /** @test */
    public function test_re008_different_state_on_each_call(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->twice()
            ->andReturn(
                'https://accounts.google.com/o/oauth2/auth?state=state1',
                'https://accounts.google.com/o/oauth2/auth?state=state2'
            );
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response1 = $this->postJson('/api/v1/auth/google');
        $response2 = $this->postJson('/api/v1/auth/google');

        $this->assertNotEquals(
            $response1->json('redirect_url'),
            $response2->json('redirect_url')
        );
    }

    /** @test */
    public function test_re009_url_is_valid_google_oauth_url(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $this->assertStringStartsWith(
            'https://accounts.google.com',
            $response->json('redirect_url')
        );
    }

    /** @test */
    public function test_re010_response_structure(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertJsonStructure(['redirect_url']);
    }

    /** @test */
    public function test_re011_no_sensitive_data_in_response(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?client_id=test&state=abc');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');
        $content = json_encode($response->json());

        // Client secret should never be exposed
        $this->assertStringNotContainsString('client_secret', $content);
        $this->assertStringNotContainsString('GOCSPX', $content); // Google client secret prefix
    }

    /** @test */
    public function test_re012_works_with_authenticated_user(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/google');

        $response->assertOk();
    }

    // ==========================================
    // Callback Endpoint Tests (CB)
    // ==========================================

    /** @test */
    public function test_cb001_valid_callback_returns_user_data(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with('valid_code', 'valid_state')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|abc123def456',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                    'token' => '1|abc123def456',
                    'is_new_user' => false,
                ],
            ]);
    }

    /** @test */
    public function test_cb002_returns_is_new_user_true(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|new123',
                'is_new_user' => true,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'new_user_code',
            'state' => 'valid_state',
        ]);

        $response->assertJson(['data' => ['is_new_user' => true]]);
    }

    /** @test */
    public function test_cb003_returns_is_new_user_false(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|existing123',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'existing_user_code',
            'state' => 'valid_state',
        ]);

        $response->assertJson(['data' => ['is_new_user' => false]]);
    }

    /** @test */
    public function test_cb004_returns_sanctum_token(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|sanctum_token_here',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure(['data' => ['token']]);
        $token = $response->json('data.token');
        $this->assertMatchesRegularExpression('/^\d+\|[a-zA-Z0-9]+$/', $token);
    }

    /** @test */
    public function test_cb005_returns_user_avatar_url(): void
    {
        $user = User::factory()->create([
            'avatar' => 'https://lh3.googleusercontent.com/avatar123',
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token123',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJson([
            'data' => [
                'user' => [
                    'avatar' => 'https://lh3.googleusercontent.com/avatar123',
                ],
            ],
        ]);
    }

    /** @test */
    public function test_cb006_returns_user_object(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified',
                ],
            ],
        ]);
    }

    /** @test */
    public function test_cb007_email_verified_for_google_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJson([
            'data' => [
                'user' => [
                    'email_verified' => true,
                ],
            ],
        ]);
    }

    /** @test */
    public function test_cb008_missing_code_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'state' => 'valid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function test_cb009_missing_state_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    /** @test */
    public function test_cb010_invalid_state_returns_error(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with('valid_code', 'invalid_state')
            ->once()
            ->andThrow(new InvalidOAuthStateException('Invalid OAuth state'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'invalid_state',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'invalid_state',
                ],
            ]);
    }

    /** @test */
    public function test_cb011_invalid_code_returns_error(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with('invalid_code', 'valid_state')
            ->once()
            ->andThrow(new OAuthFailedException('Invalid authorization code'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'invalid_code',
            'state' => 'valid_state',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_cb012_success_response_structure(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'token',
                'is_new_user',
            ],
        ]);
    }

    // ==========================================
    // Validation Tests (VL)
    // ==========================================

    /** @test */
    public function test_vl001_code_required(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'state' => 'valid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function test_vl002_state_required(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    /** @test */
    public function test_vl003_code_must_be_string(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => ['array', 'value'],
            'state' => 'valid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function test_vl004_state_must_be_string(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 12345,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    /** @test */
    public function test_vl005_code_max_length(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => str_repeat('a', 1001),
            'state' => 'valid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function test_vl006_state_max_length(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => str_repeat('a', 101),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    /** @test */
    public function test_vl007_validation_error_format(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function test_vl008_empty_request_body(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'state']);
    }

    // ==========================================
    // Error Handling Tests (EH)
    // ==========================================

    /** @test */
    public function test_eh001_user_denied_access(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new AccessDeniedException('User denied access'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'denied_code',
            'state' => 'valid_state',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'access_denied',
                ],
            ]);
    }

    /** @test */
    public function test_eh002_invalid_state_parameter(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new InvalidOAuthStateException());
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'wrong_state',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'invalid_state',
                ],
            ]);
    }

    /** @test */
    public function test_eh003_oauth_service_error(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new OAuthFailedException('OAuth service unavailable'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_eh004_error_response_structure(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new OAuthFailedException('Test error'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);
    }

    // ==========================================
    // Response Structure Tests (RS)
    // ==========================================

    /** @test */
    public function test_rs001_redirect_response_structure(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('getRedirectUrl')
            ->once()
            ->andReturn('https://accounts.google.com/o/oauth2/auth?...');
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google');

        $response->assertJsonStructure(['redirect_url']);
    }

    /** @test */
    public function test_rs002_success_callback_structure(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'success',
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
        ]);
    }

    /** @test */
    public function test_rs003_user_object_structure(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified',
                    'avatar',
                ],
            ],
        ]);
    }

    /** @test */
    public function test_rs004_error_response_structure(): void
    {
        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new OAuthFailedException('Error'));
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);
    }

    /** @test */
    public function test_rs005_validation_error_structure(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    // ==========================================
    // Edge Cases Tests (EC)
    // ==========================================

    /** @test */
    public function test_ec001_very_long_authorization_code(): void
    {
        $longCode = str_repeat('a', 500);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with($longCode, 'valid_state')
            ->once()
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => $longCode,
            'state' => 'valid_state',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_ec002_unicode_in_state(): void
    {
        $unicodeState = '你好世界';

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with('valid_code', $unicodeState)
            ->once()
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $unicodeState,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_ec003_special_characters_in_code(): void
    {
        $specialCode = 'code!@#$%^&*()';

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->with($specialCode, 'valid_state')
            ->once()
            ->andReturn([
                'user' => User::factory()->create(),
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => $specialCode,
            'state' => 'valid_state',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function test_ec004_empty_string_code(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => '',
            'state' => 'valid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function test_ec005_whitespace_in_code(): void
    {
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => '   ',
            'state' => 'valid_state',
        ]);

        // Should fail validation (empty after trim)
        $response->assertStatus(422);
    }

    /** @test */
    public function test_ec006_missing_avatar(): void
    {
        $user = User::factory()->create([
            'avatar' => null,
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'user' => [
                        'avatar' => null,
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_ec007_unicode_in_user_name(): void
    {
        $user = User::factory()->create([
            'name' => '张伟 (Zhang Wei)',
        ]);

        $mockService = Mockery::mock(GoogleOAuthService::class);
        $mockService->shouldReceive('handleCallback')
            ->once()
            ->andReturn([
                'user' => $user,
                'token' => '1|token',
                'is_new_user' => false,
            ]);
        
        $this->app->instance(GoogleOAuthService::class, $mockService);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'user' => [
                        'name' => '张伟 (Zhang Wei)',
                    ],
                ],
            ]);
    }
}
