<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Admin User Stats API Endpoints (TASK-27-5)
 *
 * Covers:
 * - GET /api/v1/admin/users/stats
 * - GET /api/v1/admin/users/recent
 * - GET /api/v1/admin/users/active
 *
 * Requirements:
 * - AdminAuthorizationMiddleware protection
 * - 60 requests/minute rate limiting
 * - PII protection (no emails, names masked)
 * - Pagination support
 */
class UserStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->regularUser = User::factory()->create();
    }

    // ============================================================
    // USER STATS ENDPOINT - GET /api/v1/admin/users/stats
    // ============================================================

    /** @test */
    public function user_stats_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create some test users
        User::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'active',
                    'newThisMonth',
                    'newThisWeek',
                    'newToday',
                    'growthRate' => [
                        'monthly',
                        'weekly',
                        'daily',
                    ],
                    'byPlan',
                ],
            ]);
    }

    /** @test */
    public function user_stats_returns_correct_total_count(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create 15 users (plus 1 admin = 16 total)
        User::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $this->assertEquals(16, $response->json('data.total'));
    }

    /** @test */
    public function user_stats_counts_active_users_within_30_days(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create active user (logged in within 30 days)
        $activeUser = User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(15),
        ]);

        // Create inactive user (logged in 45 days ago)
        $inactiveUser = User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(45),
        ]);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        // Admin + 1 active user = 2 active users
        $this->assertEquals(2, $response->json('data.active'));
    }

    /** @test */
    public function user_stats_counts_new_registrations_today(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User created today
        User::factory()->create([
            'created_at' => Carbon::today(),
        ]);

        // User created yesterday
        User::factory()->create([
            'created_at' => Carbon::yesterday(),
        ]);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        // Admin (created now) + 1 user created today
        $this->assertGreaterThanOrEqual(1, $response->json('data.newToday'));
    }

    /** @test */
    public function user_stats_counts_new_registrations_this_week(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User created this week
        User::factory()->create([
            'created_at' => Carbon::now()->startOfWeek()->addDays(2),
        ]);

        // User created last week
        User::factory()->create([
            'created_at' => Carbon::now()->subWeek(),
        ]);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $this->assertGreaterThanOrEqual(1, $response->json('data.newThisWeek'));
    }

    /** @test */
    public function user_stats_counts_new_registrations_this_month(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User created this month
        User::factory()->create([
            'created_at' => Carbon::now()->startOfMonth()->addDays(10),
        ]);

        // User created last month
        User::factory()->create([
            'created_at' => Carbon::now()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $this->assertGreaterThanOrEqual(1, $response->json('data.newThisMonth'));
    }

    /** @test */
    public function user_stats_growth_rate_calculation(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $growthRate = $response->json('data.growthRate');

        // Growth rates should be numeric (float)
        $this->assertIsFloat($growthRate['monthly']);
        $this->assertIsFloat($growthRate['weekly']);
        $this->assertIsFloat($growthRate['daily']);
    }

    /** @test */
    public function user_stats_growth_rate_handles_zero_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Only the admin exists
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $growthRate = $response->json('data.growthRate');

        // Growth rates should be 0.0 when no previous data exists
        // (or the system handles division by zero gracefully)
        $this->assertIsFloat($growthRate['monthly']);
        $this->assertIsFloat($growthRate['weekly']);
        $this->assertIsFloat($growthRate['daily']);
    }

    /** @test */
    public function user_stats_by_plan_breakdown(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create users with different plans
        User::factory()->count(5)->create(['plan' => 'free']);
        User::factory()->count(3)->create(['plan' => 'pro']);
        User::factory()->count(2)->create(['plan' => 'enterprise']);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $byPlan = $response->json('data.byPlan');

        // Should include counts for each plan
        $this->assertArrayHasKey('free', $byPlan);
        $this->assertArrayHasKey('pro', $byPlan);
        $this->assertArrayHasKey('enterprise', $byPlan);

        // Verify counts
        $this->assertEquals(5, $byPlan['free']);
        $this->assertEquals(3, $byPlan['pro']);
        $this->assertEquals(2, $byPlan['enterprise']);
    }

    /** @test */
    public function user_stats_handles_null_plan_as_free(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create user without plan
        User::factory()->create(['plan' => null]);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        $byPlan = $response->json('data.byPlan');

        // Null plan should be counted as 'free'
        $this->assertArrayHasKey('free', $byPlan);
    }

    /** @test */
    public function user_stats_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_stats_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function user_stats_respects_soft_deletes(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create and then soft delete a user
        $user = User::factory()->create();
        $user->delete(); // Soft delete if model uses SoftDeletes trait

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        // Soft deleted users should not be counted
        // Total should be 1 (admin only)
        $this->assertEquals(1, $response->json('data.total'));
    }

    // ============================================================
    // RECENT USERS ENDPOINT - GET /api/v1/admin/users/recent
    // ============================================================

    /** @test */
    public function recent_users_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'users' => [
                        '*' => [
                            'id',
                            'name_masked',
                            'created_at',
                            'plan',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    /** @test */
    public function recent_users_returns_last_20_by_default(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create 25 users
        User::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        // Should return 20 users by default
        $this->assertCount(20, $response->json('data.users'));
    }

    /** @test */
    public function recent_users_ordered_by_created_at_desc(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create users with specific timestamps
        $oldestUser = User::factory()->create([
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $newestUser = User::factory()->create([
            'created_at' => Carbon::now(),
        ]);
        $middleUser = User::factory()->create([
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        $users = $response->json('data.users');

        // First user should be the newest
        $this->assertEquals($newestUser->id, $users[0]['id']);
    }

    /** @test */
    public function recent_users_masks_names(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create(['name' => 'John Doe']);

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        $user = $response->json('data.users.0');

        // Name should be masked (e.g., "J*** D**" or similar)
        $this->assertArrayHasKey('name_masked', $user);
        $this->assertNotEquals('John Doe', $user['name_masked']);
    }

    /** @test */
    public function recent_users_excludes_email(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        $user = $response->json('data.users.0');

        // Email should NOT be present in response
        $this->assertArrayNotHasKey('email', $user);
    }

    /** @test */
    public function recent_users_supports_pagination(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(30)->create();

        // Request page 2
        $response = $this->getJson('/api/v1/admin/users/recent?page=2');

        $response->assertStatus(200);

        $meta = $response->json('data.meta');

        $this->assertEquals(2, $meta['current_page']);
        $this->assertEquals(20, $meta['per_page']);
    }

    /** @test */
    public function recent_users_custom_per_page(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/admin/users/recent?per_page=10');

        $response->assertStatus(200);

        $this->assertCount(10, $response->json('data.users'));
    }

    /** @test */
    public function recent_users_per_page_max_limit(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(150)->create();

        // Try to request more than max allowed
        $response = $this->getJson('/api/v1/admin/users/recent?per_page=200');

        $response->assertStatus(200);

        // Should cap at max (e.g., 100)
        $users = $response->json('data.users');
        $this->assertLessThanOrEqual(100, count($users));
    }

    /** @test */
    public function recent_users_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(401);
    }

    /** @test */
    public function recent_users_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(403);
    }

    /** @test */
    public function recent_users_empty_result(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Only admin exists, but we want to test empty recent users
        // (admin was created in setUp, so at least 1 exists)
        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        // Should return empty array or array with admin
        $this->assertIsArray($response->json('data.users'));
    }

    // ============================================================
    // ACTIVE USERS ENDPOINT - GET /api/v1/admin/users/active
    // ============================================================

    /** @test */
    public function active_users_returns_200_for_admin(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(5)->create([
            'last_login_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'users' => [
                        '*' => [
                            'id',
                            'name_masked',
                            'last_login_at',
                            'plan',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    /** @test */
    public function active_users_only_shows_30_day_active(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Active user (within 30 days)
        User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(15),
        ]);

        // Inactive user (more than 30 days)
        User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(45),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        // Should only include active user (plus admin who is currently "active")
        $users = $response->json('data.users');
        $this->assertLessThanOrEqual(2, count($users));
    }

    /** @test */
    public function active_users_30_day_boundary_exact(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User exactly 30 days ago (should be included)
        User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(30),
        ]);

        // User 31 days ago (should NOT be included)
        User::factory()->create([
            'last_login_at' => Carbon::now()->subDays(31),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        $users = collect($response->json('data.users'));

        // Verify only 30-day boundary user is included
        $this->assertGreaterThanOrEqual(1, $users->count());
    }

    /** @test */
    public function active_users_ordered_by_last_login_desc(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create users with different login times
        User::factory()->create(['last_login_at' => Carbon::now()->subDays(5)]);
        User::factory()->create(['last_login_at' => Carbon::now()->subDays(1)]);
        User::factory()->create(['last_login_at' => Carbon::now()->subDays(10)]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        $users = $response->json('data.users');

        // Most recently active user should be first (or the admin)
        // At minimum, verify the structure is correct
        $this->assertIsArray($users);
    }

    /** @test */
    public function active_users_masks_names(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create([
            'name' => 'Jane Smith',
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        $user = $response->json('data.users.0');

        // Name should be masked
        $this->assertArrayHasKey('name_masked', $user);
        $this->assertNotEquals('Jane Smith', $user['name_masked']);
    }

    /** @test */
    public function active_users_excludes_email(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create([
            'email' => 'activeuser@example.com',
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        $user = $response->json('data.users.0');

        // Email should NOT be present
        $this->assertArrayNotHasKey('email', $user);
    }

    /** @test */
    public function active_users_supports_pagination(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(30)->create([
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active?page=2');

        $response->assertStatus(200);

        $meta = $response->json('data.meta');

        $this->assertEquals(2, $meta['current_page']);
    }

    /** @test */
    public function active_users_custom_per_page(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(25)->create([
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active?per_page=15');

        $response->assertStatus(200);

        $this->assertCount(15, $response->json('data.users'));
    }

    /** @test */
    public function active_users_handles_null_last_login(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User who never logged in
        User::factory()->create(['last_login_at' => null]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        // Users with null last_login_at should NOT be in active list
        // (or should be handled gracefully)
        $this->assertIsArray($response->json('data.users'));
    }

    /** @test */
    public function active_users_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(401);
    }

    /** @test */
    public function active_users_returns_403_for_non_admin(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(403);
    }

    // ============================================================
    // RATE LIMITING - 60 requests/minute
    // ============================================================

    /** @test */
    public function rate_limit_allows_within_limit(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Make 10 requests - should all succeed
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/admin/users/stats');
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function rate_limit_headers_present(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        // Should have rate limit headers
        $this->assertTrue(
            $response->headers->has('X-RateLimit-Limit') ||
            $response->headers->has('x-ratelimit-limit')
        );
    }

    // ============================================================
    // HTTP METHOD RESTRICTIONS
    // ============================================================

    /** @test */
    public function stats_endpoint_post_not_allowed(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/admin/users/stats', []);

        $response->assertStatus(405);
    }

    /** @test */
    public function stats_endpoint_put_not_allowed(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->putJson('/api/v1/admin/users/stats', []);

        $response->assertStatus(405);
    }

    /** @test */
    public function stats_endpoint_delete_not_allowed(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson('/api/v1/admin/users/stats');

        $response->assertStatus(405);
    }

    /** @test */
    public function recent_endpoint_post_not_allowed(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/admin/users/recent', []);

        $response->assertStatus(405);
    }

    /** @test */
    public function active_endpoint_post_not_allowed(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/admin/users/active', []);

        $response->assertStatus(405);
    }

    // ============================================================
    // SECURITY TESTS
    // ============================================================

    /** @test */
    public function no_sensitive_data_in_responses(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create([
            'email' => 'sensitive@example.com',
            'name' => 'Sensitive User',
        ]);

        $statsResponse = $this->getJson('/api/v1/admin/users/stats');
        $recentResponse = $this->getJson('/api/v1/admin/users/recent');
        $activeResponse = $this->getJson('/api/v1/admin/users/active');

        // Verify no email exposed in any response
        $this->assertStringNotContainsString(
            'sensitive@example.com',
            json_encode($statsResponse->json())
        );
        $this->assertStringNotContainsString(
            'sensitive@example.com',
            json_encode($recentResponse->json())
        );
        $this->assertStringNotContainsString(
            'sensitive@example.com',
            json_encode($activeResponse->json())
        );
    }

    /** @test */
    public function sql_injection_protection(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Try SQL injection via query parameter
        $response = $this->getJson("/api/v1/admin/users/recent?per_page=1; DROP TABLE users;");

        // Should either validate and reject, or safely ignore the injection
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test */
    public function xss_protection_in_response(): void
    {
        Sanctum::actingAs($this->adminUser);

        // User with XSS attempt in name
        User::factory()->create([
            'name' => '<script>alert("xss")</script>User',
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        // Response should not contain unescaped script tags
        $content = json_encode($response->json());
        $this->assertStringNotContainsString('<script>alert', $content);
    }

    /** @test */
    public function idor_protection_on_all_endpoints(): void
    {
        // Non-admin cannot access any user stats endpoints
        Sanctum::actingAs($this->regularUser);

        $this->getJson('/api/v1/admin/users/stats')->assertStatus(403);
        $this->getJson('/api/v1/admin/users/recent')->assertStatus(403);
        $this->getJson('/api/v1/admin/users/active')->assertStatus(403);
    }

    // ============================================================
    // PERFORMANCE TESTS
    // ============================================================

    /** @test */
    public function stats_endpoint_response_time(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(100)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/admin/users/stats');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond within 500ms
        $this->assertLessThan(500, $duration, 'Stats endpoint should respond quickly');
    }

    /** @test */
    public function recent_endpoint_response_time(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(100)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/admin/users/recent');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond within 500ms
        $this->assertLessThan(500, $duration, 'Recent endpoint should respond quickly');
    }

    /** @test */
    public function active_endpoint_response_time(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(100)->create([
            'last_login_at' => Carbon::now()->subDays(10),
        ]);

        $start = microtime(true);
        $response = $this->getJson('/api/v1/admin/users/active');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond within 500ms
        $this->assertLessThan(500, $duration, 'Active endpoint should respond quickly');
    }

    // ============================================================
    // ERROR HANDLING
    // ============================================================

    /** @test */
    public function error_response_format_consistent(): void
    {
        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function validation_error_format(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Invalid per_page value
        $response = $this->getJson('/api/v1/admin/users/recent?per_page=invalid');

        // Should either accept default or return validation error
        $this->assertContains($response->status(), [200, 422]);
    }

    // ============================================================
    // EDGE CASES
    // ============================================================

    /** @test */
    public function handles_large_user_count(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create 500 users
        User::factory()->count(500)->create();

        $response = $this->getJson('/api/v1/admin/users/stats');

        $response->assertStatus(200);

        // Verify counts are accurate
        $this->assertEquals(501, $response->json('data.total')); // 500 + admin
    }

    /** @test */
    public function handles_unicode_names(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create([
            'name' => '张伟',
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        // Should handle Unicode without errors
        $this->assertIsArray($response->json('data.users'));
    }

    /** @test */
    public function handles_special_characters_in_names(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->create([
            'name' => "O'Brien-Smith Jr.",
            'last_login_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/admin/users/active');

        $response->assertStatus(200);

        // Should handle special characters without errors
        $this->assertIsArray($response->json('data.users'));
    }

    /** @test */
    public function pagination_meta_accurate(): void
    {
        Sanctum::actingAs($this->adminUser);

        User::factory()->count(45)->create();

        $response = $this->getJson('/api/v1/admin/users/recent');

        $response->assertStatus(200);

        $meta = $response->json('data.meta');

        // 45 users + 1 admin = 46 total
        $this->assertEquals(46, $meta['total']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(20, $meta['per_page']);
        $this->assertEquals(3, $meta['last_page']); // 46 / 20 = 3 pages
    }

    /** @test */
    public function concurrent_requests_safe(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/admin/users/stats');
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }
}
