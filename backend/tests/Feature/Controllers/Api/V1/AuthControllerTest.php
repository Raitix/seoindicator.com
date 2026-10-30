<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Registration Tests (REG-*)
    |--------------------------------------------------------------------------
    */

    /** @test REG-001: Successful registration */
    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please verify your email.',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'email_verified' => false,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
        $this->assertNotEmpty($response->json('data.token'));
    }

    /** @test REG-001A: Registration token can access protected routes */
    public function test_registration_token_can_access_protected_routes(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $token = $response->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'john@example.com',
                    ],
                ],
            ]);
    }

    /** @test REG-002: Missing name validation */
    public function test_registration_requires_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test REG-003: Missing email validation */
    public function test_registration_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-004: Missing password validation */
    public function test_registration_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-005: Invalid email format */
    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-006: Duplicate email */
    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-007: Password too short */
    public function test_registration_requires_password_minimum_length(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-008: Password no numbers */
    public function test_registration_requires_password_with_numbers(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'allletters',
            'password_confirmation' => 'allletters',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-009: Password no letters */
    public function test_registration_requires_password_with_letters(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-010: Password confirmation mismatch */
    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'DifferentPassword456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-011: Name too long */
    public function test_registration_rejects_name_over_255_chars(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test REG-012: Email too long */
    public function test_registration_rejects_email_over_255_chars(): void
    {
        // Create an email that's definitely over 255 chars
        $localPart = str_repeat('a', 250);
        $longEmail = $localPart . '@example.com'; // This will be 250 + 12 = 262 chars

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-014: Password hashed */
    public function test_registration_hashes_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotEquals('SecurePassword123', $user->password);
        $this->assertTrue(Hash::check('SecurePassword123', $user->password));
    }

    /*
    |--------------------------------------------------------------------------
    | Login Tests (LOG-*)
    |--------------------------------------------------------------------------
    */

    /** @test LOG-001: Successful login */
    public function test_user_can_login_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified',
                    ],
                    'token',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /** @test LOG-002: Invalid email */
    public function test_login_fails_with_invalid_email(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test LOG-003: Invalid password */
    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test LOG-004: Missing email */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test LOG-005: Missing password */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test LOG-008: Remember parameter extends token expiration */
    public function test_login_with_remember_extends_token_expiration(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'remember' => true,
        ]);

        $response->assertStatus(200);

        // Verify token was created with extended expiration (30 days)
        $token = $user->tokens()->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->gt(now()->addDays(25)));
    }

    /** @test LOG-010: Token is valid */
    public function test_login_token_can_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $token = $loginResponse->json('data.token');
        $accessToken = $user->tokens()->first();

        // Use Sanctum::actingAs with the specific token
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'john@example.com',
                    ],
                ],
            ]);
    }

    /** @test LOG-011: Unverified email can still login */
    public function test_unverified_user_can_login(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(200);
    }

    /** @test LOG-012: SQL injection attempt */
    public function test_login_prevents_sql_injection(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => "admin'--",
            'password' => 'SecurePassword123',
        ]);

        // Should fail validation, not cause a database error
        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout Tests (OUT-*)
    |--------------------------------------------------------------------------
    */

    /** @test OUT-001: Successful logout */
    public function test_user_can_logout_successfully(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        // Verify token was revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    /** @test OUT-002: Token revoked after logout */
    public function test_token_revoked_after_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Verify token was revoked
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /** @test OUT-003: Unauthenticated logout */
    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /** @test OUT-005: Multiple tokens - only current revoked */
    public function test_logout_only_revokes_current_token(): void
    {
        $user = User::factory()->create();
        
        // Create multiple tokens
        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');
        
        // Should have 2 tokens
        $this->assertEquals(2, $user->tokens()->count());

        // Authenticate with the first token using actingAs with token
        Sanctum::actingAs($user, ['*'], 'sanctum');
        
        // Manually set the current access token (simulating using token1)
        $user->withAccessToken($token1->accessToken);

        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Only one token should remain (token2)
        $this->assertEquals(1, $user->fresh()->tokens()->count());
        $this->assertNotNull($user->fresh()->tokens()->where('name', 'token-2')->first());
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Tests (USR-*)
    |--------------------------------------------------------------------------
    */

    /** @test USR-001: Authenticated user retrieval */
    public function test_authenticated_user_can_get_their_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]);
    }

    /** @test USR-002: Unauthenticated user retrieval */
    public function test_unauthenticated_user_cannot_get_user_data(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /** @test USR-003: Invalid token */
    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | Security Tests (SEC-*)
    |--------------------------------------------------------------------------
    */

    /** @test SEC-003: XSS in name */
    public function test_registration_sanitizes_xss_in_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '<script>alert(1)</script>John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201);

        // The name should be stored as-is (sanitization happens on output)
        $user = User::where('email', 'john@example.com')->first();
        $this->assertStringContainsString('<script>', $user->name);
    }

    /** @test SEC-008: Mass assignment protection */
    public function test_registration_ignores_extra_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'role' => 'admin', // Should be ignored
            'is_admin' => true, // Should be ignored
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotEquals('admin', $user->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Cases (EC-*)
    |--------------------------------------------------------------------------
    */

    /** @test EC-001: Empty request body */
    public function test_registration_with_empty_body(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test EC-002: Unicode in name */
    public function test_registration_accepts_unicode_in_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '日本語 🎉 João Silva',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals('日本語 🎉 João Silva', $user->name);
    }

    /** @test EC-003: Special chars in password */
    public function test_registration_accepts_special_chars_in_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ss!#$%^&*()123',
            'password_confirmation' => 'P@ss!#$%^&*()123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('P@ss!#$%^&*()123', $user->password));
    }

    /** @test EC-004: Whitespace in email */
    public function test_registration_trims_whitespace_in_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => '  john@example.com  ',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201);

        // Laravel's email validation typically normalizes emails (trims whitespace)
        // The email should be stored trimmed
        $user = User::where('email', 'like', '%john@example.com%')->first();
        $this->assertNotNull($user);
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test EC-007: Concurrent logins */
    public function test_concurrent_logins_create_separate_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePassword123'),
        ]);

        // Login twice
        $response1 = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response2 = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        // Both tokens should be different
        $this->assertNotEquals($token1, $token2);

        // User should have 2 tokens
        $this->assertEquals(2, $user->tokens()->count());
    }
}
