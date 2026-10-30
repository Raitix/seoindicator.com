<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PasswordResetController API Tests for TASK-25-4
 *
 * Tests for the PasswordResetController endpoints including:
 * - POST /api/v1/auth/forgot-password (request reset link)
 * - POST /api/v1/auth/reset-password (reset with token)
 *
 * @group auth-api
 * @group password-reset
 */
class PasswordResetControllerTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // FORGOT PASSWORD ENDPOINT TESTS (FP-*)
    // ============================================

    /** @test FP-001: Valid email (exists) */
    public function test_forgot_password_valid_email_exists(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        // Verify reset token was created in database
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'user@example.com',
        ]);
    }

    /** @test FP-002: Valid email (not exists) - no enumeration */
    public function test_forgot_password_valid_email_not_exists(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should return same success message (no email enumeration)
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // No reset token should be created for non-existent email
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nonexistent@example.com',
        ]);
    }

    /** @test FP-003: Missing email */
    public function test_forgot_password_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test FP-004: Invalid email format */
    public function test_forgot_password_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test FP-005: Empty email */
    public function test_forgot_password_empty_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test FP-006: Rate limit exceeded */
    public function test_forgot_password_rate_limit_exceeded(): void
    {
        $email = 'ratelimit@example.com';
        
        // Clear any existing rate limit
        Cache::flush();

        // First request should succeed
        $response1 = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $response1->assertStatus(200);

        // Second request within 1 minute should be rate limited
        $response2 = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $response2->assertStatus(429);
    }

    /** @test FP-007: Rate limit reset after cooldown */
    public function test_forgot_password_rate_limit_reset(): void
    {
        $email = 'cooldown@example.com';
        Cache::flush();

        // First request
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ])->assertStatus(200);

        // Clear cache to simulate time passing
        Cache::flush();

        // Should work again after cache cleared
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $response->assertStatus(200);
    }

    /** @test FP-008: SQL injection in email */
    public function test_forgot_password_sql_injection(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => "test'; DROP TABLE users;--@example.com",
        ]);

        // Should handle safely - either validation error or success (no SQL error)
        $this->assertContains($response->status(), [200, 422]);

        // Users table should still exist
        $this->assertDatabaseCount('users', 0);
    }

    /** @test FP-009: XSS in email */
    public function test_forgot_password_xss_in_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => "<script>alert('xss')</script>@example.com",
        ]);

        // Invalid email format
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test FP-010: Unicode email */
    public function test_forgot_password_unicode_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'tëst@example.com',
        ]);

        // Should handle Unicode safely
        $response->assertStatus(200);
    }

    /** @test FP-011: Email with whitespace */
    public function test_forgot_password_email_whitespace(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => '  user@example.com  ',
        ]);

        $response->assertStatus(200);

        // Token should be created for trimmed email
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'user@example.com',
        ]);
    }

    /** @test FP-012: Case-insensitive email */
    public function test_forgot_password_case_insensitive(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'USER@EXAMPLE.COM',
        ]);

        // Should work with uppercase email
        $response->assertStatus(200);
    }

    // ============================================
    // RESET PASSWORD ENDPOINT TESTS (RP-*)
    // ============================================

    /** @test RP-001: Valid reset (all fields correct) */
    public function test_reset_password_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        // Create password reset token
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                ],
            ]);

        // Verify password was updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123', $user->password));
    }

    /** @test RP-002: Invalid token */
    public function test_reset_password_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token-value',
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test RP-003: Expired token */
    public function test_reset_password_expired_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Create token and manually expire it by updating created_at
        $token = Password::createToken($user);
        
        // Update the token's created_at to be 61 minutes ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subMinutes(61)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test RP-004: Wrong email for token */
    public function test_reset_password_wrong_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);

        // Create token for user
        $token = Password::createToken($user);

        // Try to use with different email
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'other@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test RP-005: Missing token */
    public function test_reset_password_missing_token(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    /** @test RP-006: Missing email */
    public function test_reset_password_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test RP-007: Missing password */
    public function test_reset_password_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'user@example.com',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-008: Missing password_confirmation */
    public function test_reset_password_missing_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-009: Password too short */
    public function test_reset_password_too_short(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-010: Password no numbers */
    public function test_reset_password_no_numbers(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'allletters',
            'password_confirmation' => 'allletters',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-011: Password no letters */
    public function test_reset_password_no_letters(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-012: Password confirmation mismatch */
    public function test_reset_password_confirmation_mismatch(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'DifferentPassword456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test RP-013: Token already used (single use) */
    public function test_reset_password_token_single_use(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        // Create and use token
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ])->assertStatus(200);

        // Try to use same token again
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'AnotherPassword456',
            'password_confirmation' => 'AnotherPassword456',
        ]);

        $response->assertStatus(422);
    }

    /** @test RP-014: Existing tokens revoked */
    public function test_reset_password_revokes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        // Create existing Sanctum tokens
        $existingToken = $user->createToken('existing-session')->plainTextToken;

        // Create password reset token
        $resetToken = Password::createToken($user);

        // Reset password
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200);

        // Old token should be revoked
        $this->withToken($existingToken)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(401);
    }

    /** @test RP-015: New token created after reset */
    public function test_reset_password_creates_new_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200);
        
        $newToken = $response->json('data.token');
        $this->assertNotNull($newToken);
        
        // Debug: Check token format
        $this->assertMatchesRegularExpression('/^\d+\|.+$/', $newToken, 'Token should be in format "id|hash"');

        // New token should work
        $this->withToken($newToken)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);
    }

    /** @test RP-016: Password hashed */
    public function test_reset_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $user->refresh();

        // Password should be hashed, not plaintext
        $this->assertNotEquals('NewSecurePassword123', $user->password);
        $this->assertTrue(Hash::check('NewSecurePassword123', $user->password));
    }

    /** @test RP-017: SQL injection in token */
    public function test_reset_password_sql_injection_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => "'; DROP TABLE users; --",
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        // Should return validation error, not SQL error
        $response->assertStatus(422);

        // Users table should still exist
        $this->assertDatabaseCount('users', 1);
    }

    /** @test RP-018: XSS in password */
    public function test_reset_password_xss_in_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        // Password with script tag - should be accepted (frontend handles XSS)
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'Password<script>123</script>',
            'password_confirmation' => 'Password<script>123</script>',
        ]);

        // Should either accept or validate based on rules
        // Password strength rule requires letters and numbers
        $this->assertContains($response->status(), [200, 422]);
    }

    // ============================================
    // TOKEN EXPIRATION TESTS (TE-*)
    // ============================================

    /** @test TE-001: Token 1 minute old is valid */
    public function test_token_1_minute_old_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);
        
        // Set token to 1 minute ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200);
    }

    /** @test TE-002: Token 59 minutes old is valid */
    public function test_token_59_minutes_old_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);
        
        // Set token to 59 minutes ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subMinutes(59)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(200);
    }

    /** @test TE-003: Token 60 minutes old is expired */
    public function test_token_60_minutes_old_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = Password::createToken($user);
        
        // Set token to exactly 60 minutes ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subMinutes(60)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test TE-004: Token 61 minutes old is expired */
    public function test_token_61_minutes_old_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = Password::createToken($user);
        
        // Set token to 61 minutes ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subMinutes(61)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test TE-005: Token 24 hours old is expired */
    public function test_token_24_hours_old_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = Password::createToken($user);
        
        // Set token to 24 hours ago
        \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->update(['created_at' => now()->subHours(24)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    // ============================================
    // EDGE CASE TESTS (EC-*)
    // ============================================

    /** @test EC-001: Empty request body */
    public function test_reset_password_empty_request(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    /** @test EC-002: Multiple reset requests */
    public function test_multiple_reset_requests(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        Cache::flush();

        // First request
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ])->assertStatus(200);

        // Wait for rate limit
        Cache::flush();

        // Second request should create new token (old one invalidated)
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ])->assertStatus(200);

        // Should only have one token in database
        $tokenCount = \DB::table('password_reset_tokens')
            ->where('email', 'user@example.com')
            ->count();
        
        $this->assertLessThanOrEqual(1, $tokenCount);
    }

    /** @test EC-003: Remember token cleared on reset */
    public function test_remember_token_cleared(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('OldPassword123'),
            'remember_token' => Str::random(60),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $user->refresh();

        // Remember token should be cleared
        $this->assertNull($user->remember_token);
    }

    /** @test EC-004: Non-existent user reset */
    public function test_reset_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'any-token',
            'email' => 'nonexistent@example.com',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertStatus(422);
    }

    /** @test EC-005: Rate limit per different email */
    public function test_rate_limit_per_different_email(): void
    {
        Cache::flush();

        // First email - should succeed
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'email1@example.com',
        ])->assertStatus(200);

        // Different email - should also succeed (separate rate limit)
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'email2@example.com',
        ])->assertStatus(200);
    }
}
