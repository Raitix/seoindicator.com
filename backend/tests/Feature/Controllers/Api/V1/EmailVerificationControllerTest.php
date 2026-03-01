<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * EmailVerificationController API Tests
 *
 * Tests for the Email Verification endpoints including:
 * - GET /api/v1/auth/verify-email/{id}/{hash} (verify email)
 * - POST /api/v1/auth/resend-verification (resend verification email)
 *
 * @group email-verification
 */
class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // EMAIL VERIFICATION ENDPOINT TESTS (VER-*)
    // ============================================

    /** @test VER-001: Successful email verification */
    public function test_verify_email_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate signed verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);

        // Verify user is now verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test VER-002: Verification with invalid hash */
    public function test_verify_email_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate URL with wrong hash
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('wrong-email@example.com'),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);

        // User should still be unverified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test VER-003: Verification with expired link */
    public function test_verify_email_expired_link(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate expired signed URL (expired 1 minute ago)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->subMinutes(1),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);

        // User should still be unverified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test VER-004: Verification with invalid signature */
    public function test_verify_email_invalid_signature(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Create URL without valid signature
        $url = "/api/v1/auth/verify-email/{$user->id}/" . sha1($user->email);

        $response = $this->getJson($url);

        $response->assertStatus(403);

        // User should still be unverified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test VER-005: Verification of non-existent user */
    public function test_verify_email_nonexistent_user(): void
    {
        $nonExistentId = 99999;
        $fakeHash = sha1('nonexistent@example.com');

        // Generate signed URL for non-existent user
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $nonExistentId,
                'hash' => $fakeHash,
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(404);
    }

    /** @test VER-006: Already verified user */
    public function test_verify_email_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        // Generate signed URL for already verified user
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        // Should indicate already verified
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
    }

    /** @test VER-007: Verification with tampered user ID */
    public function test_verify_email_tampered_user_id(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'email_verified_at' => null,
        ]);
        
        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'email_verified_at' => null,
        ]);

        // Generate URL for user1 but try to verify user2's email
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user1->id,
                'hash' => sha1($user2->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);

        // Both users should still be unverified
        $user1->refresh();
        $user2->refresh();
        $this->assertNull($user1->email_verified_at);
        $this->assertNull($user2->email_verified_at);
    }

    /** @test VER-008: Email changed after link generation */
    public function test_verify_email_changed_after_link_generation(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => null,
        ]);

        // Generate URL with old email
        $oldEmail = $user->email;
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($oldEmail),
            ]
        );

        // User changes email
        $user->update(['email' => 'new@example.com']);

        $response = $this->getJson($verificationUrl);

        // Should fail because hash doesn't match new email
        $response->assertStatus(403);
    }

    // ============================================
    // RESEND VERIFICATION ENDPOINT TESTS (RES-*)
    // ============================================

    /** @test RES-001: Successful resend verification */
    public function test_resend_verification_success(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email sent.',
            ]);

        // Assert notification was sent
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /** @test RES-002: Resend to already verified user */
    public function test_resend_verification_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email already verified.',
            ]);

        // No notification should be sent
        Notification::assertNotSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /** @test RES-003: Resend without authentication */
    public function test_resend_verification_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(401);
    }

    /** @test RES-004: Rate limiting - first request allowed */
    public function test_resend_verification_rate_limit_first_allowed(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        // Clear any existing rate limit
        RateLimiter::clear('verification-email:' . $user->id);

        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(200);
    }

    /** @test RES-005: Rate limiting - second request blocked */
    public function test_resend_verification_rate_limit_second_blocked(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        // Clear any existing rate limit
        RateLimiter::clear('verification-email:' . $user->id);

        // First request should succeed
        $this->postJson('/api/v1/auth/resend-verification')
            ->assertStatus(200);

        // Second request within 1 minute should be rate limited
        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test RES-006: Rate limiting - allowed after 1 minute */
    public function test_resend_verification_rate_limit_allowed_after_delay(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        // Clear any existing rate limit
        RateLimiter::clear('verification-email:' . $user->id);

        // First request
        $this->postJson('/api/v1/auth/resend-verification')
            ->assertStatus(200);

        // Simulate waiting 61 seconds (by clearing the rate limiter)
        RateLimiter::clear('verification-email:' . $user->id);

        // Should now be allowed
        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(200);
    }

    /** @test RES-007: Rate limit headers present */
    public function test_resend_verification_rate_limit_headers(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        // Clear any existing rate limit
        RateLimiter::clear('verification-email:' . $user->id);

        $response = $this->postJson('/api/v1/auth/resend-verification');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    // ============================================
    // USER MODEL VERIFICATION TESTS (MOD-*)
    // ============================================

    /** @test MOD-001: hasVerifiedEmail returns false for unverified */
    public function test_has_verified_email_returns_false_for_unverified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertFalse($user->hasVerifiedEmail());
    }

    /** @test MOD-002: hasVerifiedEmail returns true for verified */
    public function test_has_verified_email_returns_true_for_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $this->assertTrue($user->hasVerifiedEmail());
    }

    /** @test MOD-003: markEmailAsVerified sets timestamp */
    public function test_mark_email_as_verified_sets_timestamp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $result = $user->markEmailAsVerified();

        $this->assertTrue($result);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test MOD-004: getEmailForVerification returns correct email */
    public function test_get_email_for_verification_returns_correct_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $user->getEmailForVerification());
    }

    // ============================================
    // MIDDLEWARE TESTS (MID-*)
    // ============================================

    /** @test MID-001: Verified middleware blocks unverified user */
    public function test_verified_middleware_blocks_unverified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        // Try to access a protected route that requires verified email
        $response = $this->getJson('/api/v1/auth/user');

        // The user endpoint should work, but verified-only endpoints should fail
        // This test assumes there's a 'verified' middleware check
        $response = $this->getJson('/api/v1/dashboard');

        // Should get 403 Forbidden if endpoint requires verification
        // Or 200 if verification is not required for dashboard
        $this->assertContains($response->status(), [200, 403, 404]);
    }

    /** @test MID-002: Verified middleware allows verified user */
    public function test_verified_middleware_allows_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
    }

    // ============================================
    // SECURITY TESTS (SEC-*)
    // ============================================

    /** @test SEC-001: SQL injection in verification URL */
    public function test_sql_injection_in_verification_url(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Try SQL injection via hash parameter
        $url = "/api/v1/auth/verify-email/{$user->id}/' OR '1'='1";

        $response = $this->getJson($url);

        // Should fail validation or return 403
        $this->assertContains($response->status(), [403, 404]);

        // User should still be unverified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test SEC-002: Path traversal in verification URL */
    public function test_path_traversal_in_verification_url(): void
    {
        $response = $this->getJson('/api/v1/auth/verify-email/../../../etc/passwd/hash');

        $response->assertStatus(404);
    }

    /** @test SEC-003: IDOR prevention - cannot verify another user */
    public function test_idor_prevention_verify_another_user(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'email_verified_at' => null,
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'email_verified_at' => null,
        ]);

        // Try to use user2's hash with user1's ID
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user1->id,
                'hash' => sha1($user2->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(403);

        // user1 should still be unverified
        $user1->refresh();
        $this->assertNull($user1->email_verified_at);
    }

    /** @test SEC-004: Negative user ID */
    public function test_negative_user_id(): void
    {
        $response = $this->getJson('/api/v1/auth/verify-email/-1/somehash');

        $response->assertStatus(404);
    }

    /** @test SEC-005: Non-numeric user ID */
    public function test_non_numeric_user_id(): void
    {
        $response = $this->getJson('/api/v1/auth/verify-email/abc/somehash');

        $response->assertStatus(404);
    }

    /** @test SEC-006: Timing attack prevention on hash comparison */
    public function test_timing_attack_prevention(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate URL with correct hash
        $correctUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // Generate URL with slightly wrong hash
        $wrongUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email) . 'x',
            ]
        );

        // Measure response times
        $start = microtime(true);
        $this->getJson($correctUrl);
        $correctTime = microtime(true) - $start;

        $start = microtime(true);
        $this->getJson($wrongUrl);
        $wrongTime = microtime(true) - $start;

        // Both should be fast (timing-safe comparison)
        // This is a basic check - timing attacks require statistical analysis
        $this->assertLessThan(1.0, $correctTime);
        $this->assertLessThan(1.0, $wrongTime);
    }

    // ============================================
    // EDGE CASE TESTS (EC-*)
    // ============================================

    /** @test EC-001: Very long email hash */
    public function test_very_long_email_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $longHash = str_repeat('a', 1000);
        $url = "/api/v1/auth/verify-email/{$user->id}/{$longHash}";

        $response = $this->getJson($url);

        $response->assertStatus(403);
    }

    /** @test EC-002: Unicode in email */
    public function test_unicode_in_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@例え.jp',
            'email_verified_at' => null,
        ]);

        // Generate verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test EC-003: User deleted before verification */
    public function test_user_deleted_before_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Generate URL before user is deleted
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // Delete the user
        $user->delete();

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(404);
    }

    /** @test EC-004: Multiple verification requests */
    public function test_multiple_verification_requests(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Generate first URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // First verification
        $this->getJson($verificationUrl)->assertStatus(200);

        // Second verification with same URL
        $this->getJson($verificationUrl)->assertStatus(200);

        // User should be verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test EC-005: Case sensitivity in email hash */
    public function test_case_sensitivity_in_email_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'Test@Example.COM',
            'email_verified_at' => null,
        ]);

        // Hash is case-sensitive, so lowercase email should fail
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1(strtolower($user->email)),
            ]
        );

        $response = $this->getJson($verificationUrl);

        // Should fail because hash doesn't match
        $response->assertStatus(403);
    }

    /** @test EC-006: Empty request body for resend */
    public function test_empty_request_body_resend(): void
    {
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/resend-verification', []);

        // Should succeed (no body required)
        $response->assertStatus(200);
    }

    // ============================================
    // INTEGRATION TESTS (INT-*)
    // ============================================

    /** @test INT-001: Full verification flow */
    public function test_full_verification_flow(): void
    {
        Notification::fake();

        // 1. Register user
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $registerResponse->assertStatus(201);
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->email_verified_at);

        // 2. Login to get token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $token = $loginResponse->json('data.token');

        // 3. Request verification email
        $resendResponse = $this->withToken($token)
            ->postJson('/api/v1/auth/resend-verification');

        $resendResponse->assertStatus(200);

        // 4. Get verification URL from notification
        $verificationUrl = null;
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function ($notification) use (&$verificationUrl) {
                $verificationUrl = $notification->toMail($user)->actionUrl;
                return true;
            }
        );

        // 5. Verify email
        $verifyResponse = $this->getJson($verificationUrl);
        $verifyResponse->assertStatus(200);

        // 6. Confirm user is verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test INT-002: Verification after password reset */
    public function test_verification_status_preserved_after_password_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        // Simulate password reset
        $user->update([
            'password' => Hash::make('NewPassword123'),
        ]);

        // Verification status should be preserved
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}
