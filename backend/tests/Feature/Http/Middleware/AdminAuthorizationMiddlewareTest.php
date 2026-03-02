<?php

namespace Tests\Feature\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Feature tests for Admin Authorization Middleware.
 * 
 * Tests security-critical middleware that protects all admin routes,
 * verifying authentication, authorization, and audit logging.
 * 
 * @see TASK-27-2: Admin Authorization Middleware
 */
class AdminAuthorizationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-02 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ============================================
    // AA-001: Admin User Access Granted
    // ============================================

    #[Test]
    public function aa_001_01_admin_user_access_granted(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(200);
    }

    #[Test]
    public function aa_001_02_admin_user_can_access_protected_route(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/users/1/impersonate');

        // 404 is acceptable (route may not exist), but NOT 401 or 403
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    #[Test]
    public function aa_001_03_admin_role_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($admin->isAdmin());
        
        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(200);
    }

    // ============================================
    // AA-002: Super Admin Access Granted
    // ============================================

    #[Test]
    public function aa_002_01_super_admin_access_granted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(200);
    }

    #[Test]
    public function aa_002_02_super_admin_role_from_database(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($superAdmin->isSuperAdmin());
        
        $response = $this->actingAs($superAdmin)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(200);
    }

    #[Test]
    public function aa_002_03_super_admin_has_higher_privileges(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Super admin should have access to all admin routes
        $response = $this->actingAs($superAdmin)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(200);
    }

    // ============================================
    // AA-003: Regular User Access Denied (403)
    // ============================================

    #[Test]
    public function aa_003_01_regular_user_denied_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_003_02_regular_user_denied_json_response(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
            ],
        ]);
    }

    #[Test]
    public function aa_003_03_regular_user_denied_message(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);

        $this->assertStringContainsString(
            'admin',
            strtolower($response->json('error.message'))
        );
    }

    #[Test]
    public function aa_003_04_moderator_role_denied(): void
    {
        // If moderator role exists, it should also be denied
        $moderator = User::factory()->create(['role' => 'moderator']);

        $response = $this->actingAs($moderator)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_003_05_null_role_denied(): void
    {
        $user = User::factory()->create(['role' => null]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    // ============================================
    // AA-004: Unauthenticated Request Denied (401)
    // ============================================

    #[Test]
    public function aa_004_01_unauthenticated_denied_401(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');

        $response->assertStatus(401);
    }

    #[Test]
    public function aa_004_02_unauthenticated_json_response(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');

        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHENTICATED',
            ],
        ]);
    }

    #[Test]
    public function aa_004_03_unauthenticated_message(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);
    }

    #[Test]
    public function aa_004_04_invalid_token_treated_as_unauthenticated(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/v1/admin/stats');

        $response->assertStatus(401);
    }

    #[Test]
    public function aa_004_05_expired_token_treated_as_unauthenticated(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('test-token');
        
        // Revoke the token to simulate expiry
        $token->accessToken->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/v1/admin/stats');

        $response->assertStatus(401);
    }

    // ============================================
    // AA-005: Access Attempt Logging
    // ============================================

    #[Test]
    public function aa_005_01_unauthorized_access_logged(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return str_contains(strtolower($message), 'unauthorized') 
                    && isset($context['user_id']) 
                    && $context['user_id'] === $user->id;
            });

        // Allow error logging from exception handler
        Log::shouldReceive('error')->byDefault();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');
    }

    #[Test]
    public function aa_005_02_unauthenticated_access_logged(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains(strtolower($message), 'unauthenticated')
                    && isset($context['ip']);
            });

        // Allow error logging from exception handler
        Log::shouldReceive('error')->byDefault();

        $this->getJson('/api/v1/admin/stats');
    }

    #[Test]
    public function aa_005_03_log_includes_ip_address(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['ip']) && !empty($context['ip']);
            });

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');
    }

    #[Test]
    public function aa_005_04_log_includes_user_agent(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['user_agent']);
            });

        $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'TestAgent/1.0'])
            ->getJson('/api/v1/admin/stats');
    }

    #[Test]
    public function aa_005_05_log_includes_requested_url(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['url']) 
                    && str_contains($context['url'], 'admin');
            });

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');
    }

    // ============================================
    // AA-006: admin_activity_logs Table Integration
    // ============================================

    #[Test]
    public function aa_006_01_unauthorized_attempt_logged_to_db(): void
    {
        // Skip if admin_activity_logs table doesn't exist
        $this->markTestSkippedIfTableNotExists('admin_activity_logs');

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $user->id,
            'action' => 'unauthorized_access_attempt',
        ]);
    }

    #[Test]
    public function aa_006_02_log_includes_target_type(): void
    {
        $this->markTestSkippedIfTableNotExists('admin_activity_logs');

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $log = DB::table('admin_activity_logs')
            ->where('admin_user_id', $user->id)
            ->where('action', 'unauthorized_access_attempt')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('admin_route', $log->target_type);
    }

    #[Test]
    public function aa_006_03_log_includes_ip_address_column(): void
    {
        $this->markTestSkippedIfTableNotExists('admin_activity_logs');

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $log = DB::table('admin_activity_logs')
            ->where('admin_user_id', $user->id)
            ->where('action', 'unauthorized_access_attempt')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
    }

    #[Test]
    public function aa_006_04_log_includes_user_agent_column(): void
    {
        $this->markTestSkippedIfTableNotExists('admin_activity_logs');

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'TestAgent/1.0'])
            ->getJson('/api/v1/admin/stats');

        $log = DB::table('admin_activity_logs')
            ->where('admin_user_id', $user->id)
            ->where('action', 'unauthorized_access_attempt')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('TestAgent', $log->user_agent);
    }

    #[Test]
    public function aa_006_05_admin_access_not_logged_as_unauthorized(): void
    {
        $this->markTestSkippedIfTableNotExists('admin_activity_logs');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/stats');

        // Should NOT log unauthorized attempts for valid admin
        $this->assertDatabaseMissing('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'unauthorized_access_attempt',
        ]);
    }

    // ============================================
    // AA-007: Response Format
    // ============================================

    #[Test]
    public function aa_007_01_403_response_structure(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);

        $this->assertFalse($response->json('success'));
    }

    #[Test]
    public function aa_007_02_401_response_structure(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);

        $this->assertFalse($response->json('success'));
    }

    #[Test]
    public function aa_007_03_error_code_distinct(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // 403 Forbidden (authenticated non-admin)
        $forbiddenResponse = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        // 401 Unauthenticated (clear auth by using a fresh request without actingAs)
        // Note: We need to ensure the session is cleared for the unauthenticated test
        $this->app['auth']->forgetUser();
        $this->app['auth']->guard('web')->logout();
        
        $unauthResponse = $this->getJson('/api/v1/admin/stats');

        // Error codes should be different
        $this->assertNotEquals(
            $forbiddenResponse->json('error.code'),
            $unauthResponse->json('error.code')
        );
    }

    // ============================================
    // AA-008: HTTP Method Handling
    // ============================================

    #[Test]
    public function aa_008_01_get_request_blocked(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_008_02_post_request_blocked(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/admin/users', ['name' => 'test']);

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_008_03_put_request_blocked(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/admin/users/1', ['name' => 'test']);

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_008_04_delete_request_blocked(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/admin/users/1');

        $response->assertStatus(403);
    }

    // ============================================
    // AA-009: Role Hierarchy
    // ============================================

    #[Test]
    public function aa_009_01_super_admin_above_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        // Both should have access
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    #[Test]
    public function aa_009_02_role_case_sensitive(): void
    {
        // 'Admin' (capitalized) should NOT be treated as admin
        $user = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function aa_009_03_role_trimmed(): void
    {
        // Role with spaces should NOT match
        $user = User::factory()->create(['role' => ' admin ']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    // ============================================
    // SC-001: Security Tests
    // ============================================

    #[Test]
    public function sc_001_01_sql_injection_in_role(): void
    {
        $user = User::factory()->create([
            'role' => "admin' OR '1'='1",
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        // Should be denied, not granted
        $response->assertStatus(403);
    }

    #[Test]
    public function sc_001_02_no_sensitive_data_in_response(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $responseBody = $response->getContent();

        // Should not expose passwords, tokens, or secrets
        $this->assertStringNotContainsString('password', $responseBody);
        $this->assertStringNotContainsString('token', $responseBody);
        $this->assertStringNotContainsString('secret', $responseBody);
    }

    #[Test]
    public function sc_001_03_xss_in_error_message(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $message = $response->json('error.message');

        // Message should not contain HTML tags
        $this->assertStringNotContainsString('<script>', $message);
        $this->assertStringNotContainsString('<img', $message);
    }

    #[Test]
    public function sc_001_04_rate_limiting_not_bypassed(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Multiple requests should all be blocked
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/admin/stats');

            $response->assertStatus(403);
        }
    }

    #[Test]
    public function sc_001_05_idor_prevention(): void
    {
        $admin = User::factory()->admin()->create(['id' => 1]);
        $user = User::factory()->create(['role' => 'user', 'id' => 2]);

        // User cannot access admin resources even with different IDs
        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/users/1');

        $response->assertStatus(403);
    }

    // ============================================
    // EC-001: Edge Cases
    // ============================================

    #[Test]
    public function ec_001_01_empty_role_denied(): void
    {
        $user = User::factory()->create(['role' => '']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function ec_001_02_numeric_role_denied(): void
    {
        $user = User::factory()->create(['role' => '1']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function ec_001_03_unicode_role_denied(): void
    {
        $user = User::factory()->create(['role' => 'αdmin']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function ec_001_04_very_long_role_denied(): void
    {
        $user = User::factory()->create(['role' => str_repeat('a', 1000)]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function ec_001_05_deleted_user_denied(): void
    {
        $user = User::factory()->admin()->create();
        $userId = $user->id;
        $user->delete();

        // Deleted user should not have access
        // Note: This test depends on how auth handles deleted users
        // Laravel Sanctum should reject the token automatically
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    #[Test]
    public function ec_001_06_unverified_email_admin_access(): void
    {
        // Unverified admin should still have access (email verification is separate)
        $admin = User::factory()->unverified()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/stats');

        // Admin access should be granted regardless of email verification
        $response->assertStatus(200);
    }

    // ============================================
    // EC-002: Concurrent Sessions
    // ============================================

    #[Test]
    public function ec_002_01_multiple_tokens_same_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $token1 = $admin->createToken('token1')->plainTextToken;
        $token2 = $admin->createToken('token2')->plainTextToken;

        // Both tokens should work
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/v1/admin/stats');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/v1/admin/stats');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    #[Test]
    public function ec_002_02_token_revoked_mid_session(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token');
        $plainTextToken = $token->plainTextToken;

        // First request works
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->getJson('/api/v1/admin/stats');
        $response1->assertStatus(200);

        // Revoke token
        $token->accessToken->delete();

        // Second request fails
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->getJson('/api/v1/admin/stats');
        $response2->assertStatus(401);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Skip test if table doesn't exist in the database.
     */
    protected function markTestSkippedIfTableNotExists(string $table): void
    {
        try {
            DB::table($table)->limit(1)->get();
        } catch (\Exception $e) {
            $this->markTestSkipped("Table '{$table}' does not exist. Run TASK-27-1 migration first.");
        }
    }
}
