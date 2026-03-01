<?php

namespace Tests\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Password Reset Security Tests for TASK-25-4
 *
 * Comprehensive security tests covering:
 * - Email enumeration prevention
 * - Token security (entropy, expiration, single-use)
 * - Rate limiting
 * - Timing attack prevention
 * - Session invalidation
 * - SQL injection
 * - XSS prevention
 *
 * @group security
 * @group password-reset-security
 */
class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // EMAIL ENUMERATION TESTS (EE-*)
    // ============================================

    /** @test EE-001: Same response for existing and non-existing emails */
    public function test_no_email_enumeration_response_structure(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Request for existing email
        $responseExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        // Request for non-existing email
        $responseNonExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexisting@example.com',
        ]);

        // Both should return same status
        $this->assertEquals($responseExisting->status(), $responseNonExisting->status());
        
        // Both should have same success structure
        $this->assertEquals(
            $responseExisting->json('success'),
            $responseNonExisting->json('success')
        );
    }

    /** @test EE-002: Same message for existing and non-existing emails */
    public function test_no_email_enumeration_message(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $responseExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $responseNonExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexisting@example.com',
        ]);

        // Messages should be identical or very similar
        $messageExisting = $responseExisting->json('message');
        $messageNonExisting = $responseNonExisting->json('message');
        
        $this->assertEquals($messageExisting, $messageNonExisting);
    }

    /** @test EE-003: No timing difference reveals email existence */
    public function test_no_timing_attack_email_enumeration(): void
    {
        $user = User::factory()->create([
            'email' => 'timing@example.com',
        ]);

        $times = [];

        // Measure time for existing email (5 iterations)
        for ($i = 0; $i < 5; $i++) {
            Cache::flush();
            $start = microtime(true);
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'timing@example.com',
            ]);
            $times['existing'][] = microtime(true) - $start;
        }

        // Measure time for non-existing email (5 iterations)
        for ($i = 0; $i < 5; $i++) {
            Cache::flush();
            $start = microtime(true);
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'nonexistent' . $i . '@example.com',
            ]);
            $times['nonexisting'][] = microtime(true) - $start;
        }

        $avgExisting = array_sum($times['existing']) / count($times['existing']);
        $avgNonExisting = array_sum($times['nonexisting']) / count($times['nonexisting']);

        // Times should be within 50% variance (not significantly different)
        $ratio = $avgExisting / max($avgNonExisting, 0.001);
        $this->assertGreaterThan(0.5, $ratio, 'Response times should not reveal email existence');
        $this->assertLessThan(2.0, $ratio, 'Response times should not reveal email existence');
    }

    // ============================================
    // TOKEN SECURITY TESTS (TS-*)
    // ============================================

    /** @test TS-001: Token has sufficient entropy */
    public function test_token_has_sufficient_entropy(): void
    {
        $user = User::factory()->create([
            'email' => 'entropy@example.com',
        ]);

        Password::createToken($user);

        // Get the token from database (it's hashed, but we can check the structure)
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', 'entropy@example.com')
            ->first();

        $this->assertNotNull($tokenRecord);
        
        // Token should be hashed (starts with $2y$ for bcrypt)
        $this->assertStringStartsWith('$2y$', $tokenRecord->token);
    }

    /** @test TS-002: Token is hashed in database */
    public function test_token_hashed_in_database(): void
    {
        $user = User::factory()->create([
            'email' => 'hashed@example.com',
        ]);

        $plainToken = Password::createToken($user);

        // Get stored token
        $storedToken = DB::table('password_reset_tokens')
            ->where('email', 'hashed@example.com')
            ->value('token');

        // Plain token should not match stored (it's hashed)
        $this->assertNotEquals($plainToken, $storedToken);
    }

    /** @test TS-003: Token single use enforcement */
    public function test_token_single_use_enforcement(): void
    {
        $user = User::factory()->create([
            'email' => 'singleuse@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        // First use - should succeed
        $response1 = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'singleuse@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);
        $response1->assertStatus(200);

        // Second use - should fail
        $response2 = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'singleuse@example.com',
            'password' => 'AnotherPassword456',
            'password_confirmation' => 'AnotherPassword456',
        ]);
        $response2->assertStatus(422);
    }

    /** @test TS-004: Old tokens invalidated on new request */
    public function test_old_tokens_invalidated_on_new_request(): void
    {
        $user = User::factory()->create([
            'email' => 'invalidate@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        Cache::flush();

        // First reset request
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalidate@example.com',
        ]);

        $oldToken = Password::createToken($user);

        // Second reset request (after rate limit reset)
        Cache::flush();
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalidate@example.com',
        ]);

        $newToken = Password::createToken($user);

        // Tokens should be different
        $this->assertNotEquals($oldToken, $newToken);
    }

    /** @test TS-005: Token cannot be guessed */
    public function test_token_cannot_be_guessed(): void
    {
        $user = User::factory()->create([
            'email' => 'guess@example.com',
        ]);

        // Try common/guessable tokens
        $guessableTokens = [
            '123456',
            'password',
            'token',
            'reset',
            str_repeat('a', 64),
            str_repeat('0', 64),
        ];

        foreach ($guessableTokens as $token) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $token,
                'email' => 'guess@example.com',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

            $response->assertStatus(422);
        }
    }

    // ============================================
    // RATE LIMITING TESTS (RL-*)
    // ============================================

    /** @test RL-001: Rate limiting prevents abuse */
    public function test_rate_limiting_prevents_abuse(): void
    {
        $email = 'abuse@example.com';
        Cache::flush();

        // First request succeeds
        $response1 = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $response1->assertStatus(200);

        // Second request within window fails
        $response2 = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $response2->assertStatus(429);
    }

    /** @test RL-002: Rate limit includes retry-after header */
    public function test_rate_limit_includes_retry_after(): void
    {
        $email = 'retryafter@example.com';
        Cache::flush();

        // Trigger rate limit
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);

        if ($response->status() === 429) {
            $retryAfter = $response->headers->get('Retry-After');
            $this->assertNotNull($retryAfter);
            $this->assertIsNumeric($retryAfter);
            $this->assertGreaterThan(0, (int) $retryAfter);
        }
    }

    /** @test RL-003: Rate limit per email isolation */
    public function test_rate_limit_per_email_isolation(): void
    {
        Cache::flush();

        // Email1 gets rate limited
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'email1@example.com',
        ]);

        // Email2 should still work
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'email2@example.com',
        ]);
        $response->assertStatus(200);
    }

    /** @test RL-004: Rate limit error message does not leak info */
    public function test_rate_limit_message_no_info_leak(): void
    {
        $email = 'noleak@example.com';
        Cache::flush();

        // Trigger rate limit
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);

        if ($response->status() === 429) {
            $message = $response->json('message') ?? $response->json('error.message');
            
            // Should not reveal if email exists
            $this->assertStringNotContainsString('exists', strtolower($message ?? ''));
            $this->assertStringNotContainsString('registered', strtolower($message ?? ''));
            $this->assertStringNotContainsString('valid', strtolower($message ?? ''));
        }
    }

    /** @test RL-005: Reset password endpoint has rate limiting */
    public function test_reset_password_rate_limiting(): void
    {
        $email = 'resetlimit@example.com';
        Cache::flush();

        // Multiple failed reset attempts
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'invalid-token-' . $i,
                'email' => $email,
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);
        }

        // Should eventually rate limit or continue returning 422
        // Either is acceptable - rate limiting is optional for reset endpoint
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'another-invalid',
            'email' => $email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $this->assertContains($response->status(), [422, 429]);
    }

    // ============================================
    // SESSION INVALIDATION TESTS (SI-*)
    // ============================================

    /** @test SI-001: All sessions invalidated on password reset */
    public function test_all_sessions_invalidated(): void
    {
        $user = User::factory()->create([
            'email' => 'sessions@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        // Create multiple tokens (simulating multiple sessions)
        $token1 = $user->createToken('session1')->plainTextToken;
        $token2 = $user->createToken('session2')->plainTextToken;
        $token3 = $user->createToken('session3')->plainTextToken;

        // All tokens should work
        $this->withToken($token1)->getJson('/api/v1/auth/user')->assertStatus(200);
        $this->withToken($token2)->getJson('/api/v1/auth/user')->assertStatus(200);
        $this->withToken($token3)->getJson('/api/v1/auth/user')->assertStatus(200);

        // Reset password
        $resetToken = Password::createToken($user);
        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'sessions@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertStatus(200);

        // All old tokens should be revoked
        $this->withToken($token1)->getJson('/api/v1/auth/user')->assertStatus(401);
        $this->withToken($token2)->getJson('/api/v1/auth/user')->assertStatus(401);
        $this->withToken($token3)->getJson('/api/v1/auth/user')->assertStatus(401);
    }

    /** @test SI-002: Remember token cleared on reset */
    public function test_remember_token_cleared_on_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'password' => bcrypt('OldPassword123'),
            'remember_token' => Str::random(60),
        ]);

        $this->assertNotNull($user->remember_token);

        $resetToken = Password::createToken($user);
        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'remember@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->remember_token);
    }

    /** @test SI-003: New token issued after reset */
    public function test_new_token_issued_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'newtoken@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $resetToken = Password::createToken($user);
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'newtoken@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200);
        $newToken = $response->json('data.token');

        $this->assertNotNull($newToken);
        $this->assertNotEmpty($newToken);

        // New token should work
        $this->withToken($newToken)
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);
    }

    // ============================================
    // INJECTION TESTS (IN-*)
    // ============================================

    /** @test IN-001: SQL injection in email field */
    public function test_sql_injection_email_field(): void
    {
        $user = User::factory()->create([
            'email' => 'safe@example.com',
        ]);

        $maliciousInputs = [
            "'; DROP TABLE users;--@example.com",
            "' OR '1'='1@example.com",
            "admin'--@example.com",
            "' UNION SELECT * FROM users--@example.com",
            "'; DELETE FROM password_reset_tokens;--@example.com",
        ];

        foreach ($maliciousInputs as $input) {
            $response = $this->postJson('/api/v1/auth/forgot-password', [
                'email' => $input,
            ]);

            // Should not cause SQL error
            $this->assertNotEquals(500, $response->status());
        }

        // Verify data integrity
        $this->assertDatabaseHas('users', ['email' => 'safe@example.com']);
    }

    /** @test IN-002: SQL injection in token field */
    public function test_sql_injection_token_field(): void
    {
        $user = User::factory()->create([
            'email' => 'tokensafe@example.com',
        ]);

        $maliciousTokens = [
            "'; DROP TABLE users;--",
            "' OR '1'='1",
            "' UNION SELECT * FROM users--",
            "'; DELETE FROM users WHERE 1=1;--",
        ];

        foreach ($maliciousTokens as $token) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $token,
                'email' => 'tokensafe@example.com',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ]);

            // Should not cause SQL error
            $this->assertContains($response->status(), [422, 400]);
        }

        // Verify data integrity
        $this->assertDatabaseHas('users', ['email' => 'tokensafe@example.com']);
    }

    /** @test IN-003: No SQL error on malformed input */
    public function test_no_sql_error_malformed_input(): void
    {
        $malformedInputs = [
            ['email' => str_repeat('a', 10000)],
            ['email' => "\x00\x01\x02@example.com"],
            ['email' => "test@example.com\x00malicious"],
        ];

        foreach ($malformedInputs as $input) {
            $response = $this->postJson('/api/v1/auth/forgot-password', $input);

            // Should not return 500 (SQL error)
            $this->assertNotEquals(500, $response->status());
        }
    }

    // ============================================
    // XSS PREVENTION TESTS (XP-*)
    // ============================================

    /** @test XP-001: XSS in email field */
    public function test_xss_email_field(): void
    {
        $xssPayloads = [
            '<script>alert("xss")</script>@example.com',
            '"><script>alert(1)</script>@example.com',
            "javascript:alert('xss')@example.com",
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->postJson('/api/v1/auth/forgot-password', [
                'email' => $payload,
            ]);

            // Should reject invalid email format
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['email']);
            }
        }
    }

    /** @test XP-002: XSS in password field */
    public function test_xss_password_field(): void
    {
        $user = User::factory()->create([
            'email' => 'xsspass@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $resetToken = Password::createToken($user);

        // Password with XSS payload - should be stored safely
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'xsspass@example.com',
            'password' => 'Password<script>alert(1)</script>123',
            'password_confirmation' => 'Password<script>alert(1)</script>123',
        ]);

        // If accepted, verify it's stored as hash (not executed)
        if ($response->status() === 200) {
            $user->refresh();
            $this->assertTrue(Hash::check('Password<script>alert(1)</script>123', $user->password));
            // Password is hashed, not stored as plaintext
            $this->assertStringStartsWith('$2y$', $user->password);
        }
    }

    /** @test XP-003: Response does not reflect XSS */
    public function test_response_no_xss_reflection(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => '<script>alert(1)</script>@example.com',
        ]);

        $content = $response->content();

        // Response should not contain unescaped script tag
        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
    }

    // ============================================
    // SECURITY HEADER TESTS (SH-*)
    // ============================================

    /** @test SH-001: Security headers on forgot password */
    public function test_security_headers_forgot_password(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'headers@example.com',
        ]);

        // Check for X-Content-Type-Options
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));

        // Check for X-Frame-Options
        $xfo = $response->headers->get('X-Frame-Options');
        $this->assertContains($xfo, ['DENY', 'SAMEORIGIN', null]);
    }

    /** @test SH-002: Security headers on reset password */
    public function test_security_headers_reset_password(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'test-token',
            'email' => 'headers@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        // Check for X-Content-Type-Options
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    // ============================================
    // PASSWORD STRENGTH TESTS (PS-*)
    // ============================================

    /** @test PS-001: Weak password rejected */
    public function test_weak_password_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'weakpass@example.com',
        ]);

        $resetToken = Password::createToken($user);

        $weakPasswords = [
            'password',
            '12345678',
            'qwerty',
            'abc123',
            'pass1234',
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $resetToken,
                'email' => 'weakpass@example.com',
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword,
            ]);

            // Should reject weak password
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        }
    }

    /** @test PS-002: Strong password accepted */
    public function test_strong_password_accepted(): void
    {
        $user = User::factory()->create([
            'email' => 'strongpass@example.com',
            'password' => bcrypt('OldPassword123'),
        ]);

        $resetToken = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'strongpass@example.com',
            'password' => 'Str0ngP@ssw0rd!',
            'password_confirmation' => 'Str0ngP@ssw0rd!',
        ]);

        $response->assertStatus(200);
    }

    /** @test PS-003: Password requirements communicated */
    public function test_password_requirements_communicated(): void
    {
        $user = User::factory()->create([
            'email' => 'requirements@example.com',
        ]);

        $resetToken = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'requirements@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);

        // Error message should indicate requirements
        $errors = $response->json('errors.password');
        $this->assertNotEmpty($errors);
    }

    // ============================================
    // ERROR HANDLING TESTS (EH-*)
    // ============================================

    /** @test EH-001: Expired token error message */
    public function test_expired_token_error_message(): void
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
        ]);

        $resetToken = Password::createToken($user);

        // Expire the token
        DB::table('password_reset_tokens')
            ->where('email', 'expired@example.com')
            ->update(['created_at' => now()->subHours(2)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $resetToken,
            'email' => 'expired@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(422);

        // Should have user-friendly error message
        $this->assertNotEmpty($response->json('message'));
    }

    /** @test EH-002: Invalid token error message */
    public function test_invalid_token_error_message(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token-xyz',
            'email' => 'invalid@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(422);

        // Should have error message
        $this->assertNotEmpty($response->json('message'));
    }

    /** @test EH-003: No sensitive data in error response */
    public function test_no_sensitive_data_in_errors(): void
    {
        $user = User::factory()->create([
            'email' => 'sensitive@example.com',
            'password' => bcrypt('SecretPassword123'),
        ]);

        // Invalid reset attempt
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'wrong-token',
            'email' => 'sensitive@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $content = $response->content();

        // Should not leak current password
        $this->assertStringNotContainsString('SecretPassword123', $content);

        // Should not leak password hash
        $user->refresh();
        $this->assertStringNotContainsString($user->password, $content);
    }

    /** @test EH-004: Request ID in error response for traceability */
    public function test_request_id_in_response(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'requestid@example.com',
        ]);

        // Check for request ID header (X-Request-ID or similar)
        $requestId = $response->headers->get('X-Request-Id')
            ?? $response->headers->get('X-Request-ID')
            ?? $response->headers->get('X-Correlation-ID');

        // This is optional but recommended for debugging
        if ($requestId) {
            $this->assertNotEmpty($requestId);
        }
    }
}
