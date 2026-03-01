<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AuthController API Tests
 *
 * Tests for the AuthController endpoints including:
 * - POST /api/v1/auth/register (user registration)
 * - POST /api/v1/auth/login (user login)
 * - POST /api/v1/auth/logout (user logout)
 * - GET /api/v1/auth/user (get authenticated user)
 *
 * @group auth-api
 */
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // REGISTRATION ENDPOINT TESTS (REG-*)
    // ============================================

    /** @test REG-001: Successful registration */
    public function test_register_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
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
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    /** @test REG-002: Missing name */
    public function test_register_missing_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test REG-003: Missing email */
    public function test_register_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-004: Missing password */
    public function test_register_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test REG-005: Invalid email format */
    public function test_register_invalid_email_format(): void
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
    public function test_register_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
        ]);

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
    public function test_register_password_too_short(): void
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
    public function test_register_password_no_numbers(): void
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
    public function test_register_password_no_letters(): void
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
    public function test_register_password_confirmation_mismatch(): void
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
    public function test_register_name_too_long(): void
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
    public function test_register_email_too_long(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => str_repeat('a', 250) . '@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test REG-014: Password hashed */
    public function test_register_password_hashed(): void
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

    // ============================================
    // LOGIN ENDPOINT TESTS (LOG-*)
    // ============================================

    /** @test LOG-001: Successful login */
    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ]);
    }

    /** @test LOG-002: Invalid email */
    public function test_login_invalid_email(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(401);
    }

    /** @test LOG-003: Invalid password */
    public function test_login_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(401);
    }

    /** @test LOG-004: Missing email */
    public function test_login_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test LOG-005: Missing password */
    public function test_login_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test LOG-006: Rate limiting exceeded */
    public function test_login_rate_limiting(): void
    {
        $email = 'john@example.com';

        // Clear any existing rate limit
        RateLimiter::clear('login:' . $email);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }

    /** @test LOG-010: Token is valid */
    public function test_login_token_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePassword123'),
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $token = $loginResponse->json('data.token');

        // Use token to access protected route
        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
    }

    /** @test LOG-011: Unverified email can still login */
    public function test_login_unverified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(200);
    }

    /** @test LOG-012: SQL injection attempt */
    public function test_login_sql_injection(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => "admin'--",
            'password' => 'anything',
        ]);

        // Should not cause SQL error, just treat as invalid credentials
        $response->assertStatus(401);
    }

    // ============================================
    // LOGOUT ENDPOINT TESTS (OUT-*)
    // ============================================

    /** @test OUT-001: Successful logout */
    public function test_logout_success(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test OUT-002: Token revoked */
    public function test_logout_token_revoked(): void
    {
        $user = User::factory()->create();
        
        // Login to get token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Logout
        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Try to use revoked token
        $response = $this->withToken($token)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /** @test OUT-003: Unauthenticated logout */
    public function test_logout_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /** @test OUT-005: Multiple tokens - only current revoked */
    public function test_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();
        
        // Create multiple tokens
        $token1 = $user->createToken('token1')->plainTextToken;
        $token2 = $user->createToken('token2')->plainTextToken;

        // Logout with token1
        $this->withToken($token1)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token1 should be revoked
        $this->withToken($token1)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(401);

        // Token2 should still work
        $this->withToken($token2)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);
    }

    // ============================================
    // GET USER ENDPOINT TESTS (USR-*)
    // ============================================

    /** @test USR-001: Authenticated user */
    public function test_get_user_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    /** @test USR-002: Unauthenticated user */
    public function test_get_user_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /** @test USR-003: Invalid token */
    public function test_get_user_invalid_token(): void
    {
        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    // ============================================
    // SECURITY TESTS (SEC-*)
    // ============================================

    /** @test SEC-001: SQL injection in email */
    public function test_sql_injection_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => "test'; DROP TABLE users;--@example.com",
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // Should either validate or store as-is (safe)
        $this->assertContains($response->status(), [201, 422]);

        // Users table should still exist
        $this->assertDatabaseCount('users', $response->status() === 201 ? 1 : 0);
    }

    /** @test SEC-003: XSS in name */
    public function test_xss_in_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '<script>alert("xss")</script>',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201);

        // Name should be stored as-is (XSS protection is frontend responsibility)
        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('<script>alert("xss")</script>', $user->name);
    }

    /** @test SEC-008: Mass assignment */
    public function test_mass_assignment_protection(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'is_admin' => true, // Attempt to set protected field
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        
        // is_admin should not be set (either ignored or column doesn't exist)
        $this->assertNotEquals(true, $user->is_admin ?? false);
    }

    // ============================================
    // EDGE CASE TESTS (EC-*)
    // ============================================

    /** @test EC-001: Empty request body */
    public function test_empty_request_body(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test EC-002: Unicode in name */
    public function test_unicode_in_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '日本語 テスト 🎉',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('日本語 テスト 🎉', $user->name);
    }

    /** @test EC-004: Whitespace in email */
    public function test_whitespace_in_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => '  test@example.com  ',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201);

        // Email should be trimmed
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
    }

    /** @test EC-005: Case-insensitive email */
    public function test_case_insensitive_email(): void
    {
        // Register with lowercase
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // Try to register with uppercase
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // Should detect duplicate
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test EC-007: Concurrent logins create separate tokens */
    public function test_concurrent_logins(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Password123'),
        ]);

        // First login
        $response1 = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]);

        // Second login
        $response2 = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123',
        ]);

        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        // Tokens should be different
        $this->assertNotEquals($token1, $token2);

        // Both tokens should work
        $this->withToken($token1)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);

        $this->withToken($token2)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);
    }
}
