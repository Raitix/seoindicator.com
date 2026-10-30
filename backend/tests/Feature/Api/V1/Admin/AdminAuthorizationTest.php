<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

/**
 * Security tests for Admin API Authorization
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group feature
 * @group api
 * @group security
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected User $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
        $this->superAdminUser = User::factory()->create(['role' => 'super_admin']);
    }

    // =========================================================================
    // Non-Admin Access Denied Tests
    // =========================================================================

    /** @test */
    public function non_admin_cannot_access_admin_users_stats(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_access_admin_usage_overview(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_access_admin_credits_overview(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_access_admin_health_overview(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_receives_forbidden_error_message(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertJson([
            'success' => false,
            'error' => 'Unauthorized',
        ]);
    }

    // =========================================================================
    // Unauthenticated Access Denied Tests
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_admin_users_stats(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_usage_overview(): void
    {
        $response = $this->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_credits_overview(): void
    {
        $response = $this->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_health_overview(): void
    {
        $response = $this->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_receives_unauthenticated_error(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertJson([
            'success' => false,
            'error' => 'Unauthenticated',
        ]);
    }

    // =========================================================================
    // Admin Access Allowed Tests
    // =========================================================================

    /** @test */
    public function admin_can_access_admin_users_stats(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_usage_overview(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/usage/overview');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_credits_overview(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_health_overview(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/health/overview');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Super Admin Access Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_access_admin_users_stats(): void
    {
        $response = $this->actingAs($this->superAdminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_all_admin_endpoints(): void
    {
        $endpoints = [
            '/api/v1/admin/users/stats',
            '/api/v1/admin/usage/overview',
            '/api/v1/admin/credits/overview',
            '/api/v1/admin/health/overview',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->superAdminUser)
                ->getJson($endpoint);

            $response->assertStatus(200);
        }
    }

    // =========================================================================
    // Activity Logging Tests
    // =========================================================================

    /** @test */
    public function admin_access_is_logged(): void
    {
        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $this->adminUser->id,
            'action' => 'admin.users.stats.view',
        ]);
    }

    /** @test */
    public function unauthorized_access_attempt_is_logged(): void
    {
        $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/users/stats');

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $this->regularUser->id,
            'action' => 'admin.unauthorized_access',
        ]);
    }

    /** @test */
    public function activity_log_includes_ip_address(): void
    {
        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $log = AdminActivityLog::where('admin_user_id', $this->adminUser->id)->first();

        $this->assertNotNull($log->ip_address);
    }

    /** @test */
    public function activity_log_includes_user_agent(): void
    {
        $this->actingAs($this->adminUser)
            ->withHeaders(['User-Agent' => 'TestAgent/1.0'])
            ->getJson('/api/v1/admin/users/stats');

        $log = AdminActivityLog::where('admin_user_id', $this->adminUser->id)->first();

        $this->assertNotNull($log->user_agent);
    }

    // =========================================================================
    // Token/Session Tests
    // =========================================================================

    /** @test */
    public function invalid_token_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function expired_token_is_rejected(): void
    {
        // This test assumes token expiration is implemented
        $response = $this->withHeaders([
            'Authorization' => 'Bearer expired-token',
        ])->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    // =========================================================================
    // Role-Based Access Control Tests
    // =========================================================================

    /** @test */
    public function middleware_checks_isAdmin_method(): void
    {
        // User model should have isAdmin() method
        $this->assertTrue(method_exists($this->adminUser, 'isAdmin'));
    }

    /** @test */
    public function admin_user_isAdmin_returns_true(): void
    {
        $this->assertTrue($this->adminUser->isAdmin());
    }

    /** @test */
    public function regular_user_isAdmin_returns_false(): void
    {
        $this->assertFalse($this->regularUser->isAdmin());
    }

    /** @test */
    public function super_admin_isAdmin_returns_true(): void
    {
        $this->assertTrue($this->superAdminUser->isAdmin());
    }

    // =========================================================================
    // Security Header Tests
    // =========================================================================

    /** @test */
    public function admin_responses_include_security_headers(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    /** @test */
    public function admin_responses_do_not_expose_server_info(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertHeaderMissing('X-Powered-By');
        $response->assertHeaderMissing('Server');
    }

    // =========================================================================
    // Input Validation Security Tests
    // =========================================================================

    /** @test */
    public function sql_injection_is_prevented(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/users/recent?per_page=1; DROP TABLE users;");

        $response->assertStatus(422);
    }

    /** @test */
    public function xss_is_prevented_in_error_messages(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/users/recent?per_page=<script>alert('xss')</script>");

        $response->assertStatus(422);
        $response->assertDontSee('<script>');
    }

    // =========================================================================
    // Concurrent Access Tests
    // =========================================================================

    /** @test */
    public function multiple_admin_requests_are_handled_correctly(): void
    {
        $responses = collect(range(1, 5))->map(function () {
            return $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/users/stats');
        });

        $responses->each(function ($response) {
            $response->assertStatus(200);
        });
    }

    // =========================================================================
    // Rate Limit Security Tests
    // =========================================================================

    /** @test */
    public function rate_limiting_prevents_abuse(): void
    {
        for ($i = 0; $i < 65; $i++) {
            $response = $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/users/stats');
        }

        $response->assertStatus(429);
    }

    /** @test */
    public function rate_limit_does_not_affect_other_users(): void
    {
        // Exhaust rate limit for first admin
        for ($i = 0; $i < 65; $i++) {
            $this->actingAs($this->adminUser)
                ->getJson('/api/v1/admin/users/stats');
        }

        // Second admin should still be able to access
        $secondAdmin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($secondAdmin)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);
    }
}
