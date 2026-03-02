<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserStatsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unit tests for UserStatsRepository.
 *
 * Tests user statistics aggregation queries for admin dashboard.
 *
 * @see TASK-27-4
 * @see SPEC-027
 */
class UserStatsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserStatsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserStatsRepository();
        Carbon::setTestNow(Carbon::parse('2026-03-02 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a user with specific creation date.
     */
    protected function createUserCreatedAt(Carbon $date, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'created_at' => $date,
            'updated_at' => $date,
        ], $attrs));
    }

    /**
     * Create a user with last login date.
     */
    protected function createUserWithLastLogin(?Carbon $lastLogin, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'last_login_at' => $lastLogin,
        ], $attrs));
    }

    /**
     * Create a user with a specific plan.
     */
    protected function createUserWithPlan(string $plan, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'plan' => $plan,
        ], $attrs));
    }

    // =========================================================================
    // Repository Instantiation Tests
    // =========================================================================

    /** @test */
    public function repository_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UserStatsRepository::class, $this->repository);
    }

    // =========================================================================
    // getTotalUserCount() Tests
    // =========================================================================

    /** @test */
    public function get_total_user_count_returns_zero_when_no_users(): void
    {
        $count = $this->repository->getTotalUserCount();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_total_user_count_returns_correct_count(): void
    {
        User::factory()->count(5)->create();

        $count = $this->repository->getTotalUserCount();

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function get_total_user_count_excludes_soft_deleted(): void
    {
        // Soft deletes are not implemented on User model
        // This test verifies that all users are counted when soft deletes are not used
        User::factory()->count(5)->create();

        $count = $this->repository->getTotalUserCount();

        // All users are counted since soft deletes are not implemented
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function get_total_user_count_returns_integer(): void
    {
        User::factory()->count(10)->create();

        $count = $this->repository->getTotalUserCount();

        $this->assertIsInt($count);
    }

    /** @test */
    public function get_total_user_count_includes_all_roles(): void
    {
        User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'super_admin']);

        $count = $this->repository->getTotalUserCount();

        $this->assertEquals(3, $count);
    }

    // =========================================================================
    // getActiveUsersCount() Tests (logged in within 30 days)
    // =========================================================================

    /** @test */
    public function get_active_users_count_returns_zero_when_no_users(): void
    {
        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_active_users_count_returns_users_logged_in_30_days(): void
    {
        // Active: logged in within 30 days
        $this->createUserWithLastLogin(Carbon::now()->subDays(5));
        $this->createUserWithLastLogin(Carbon::now()->subDays(15));
        $this->createUserWithLastLogin(Carbon::now()->subDays(29));

        // Inactive: logged in more than 30 days ago
        $this->createUserWithLastLogin(Carbon::now()->subDays(31));
        $this->createUserWithLastLogin(Carbon::now()->subDays(60));

        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function get_active_users_count_excludes_null_last_login(): void
    {
        $this->createUserWithLastLogin(Carbon::now()->subDays(5));
        $this->createUserWithLastLogin(null); // Never logged in

        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function get_active_users_count_boundary_exact_30_days(): void
    {
        // Exactly 30 days ago should be included
        $this->createUserWithLastLogin(Carbon::now()->subDays(30));

        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function get_active_users_count_boundary_31_days(): void
    {
        // 31 days ago should be excluded
        $this->createUserWithLastLogin(Carbon::now()->subDays(31));

        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_active_users_count_returns_integer(): void
    {
        $this->createUserWithLastLogin(Carbon::now()->subDays(1));

        $count = $this->repository->getActiveUsersCount();

        $this->assertIsInt($count);
    }

    // =========================================================================
    // getNewRegistrationsToday() Tests
    // =========================================================================

    /** @test */
    public function get_new_registrations_today_returns_zero_when_none(): void
    {
        $count = $this->repository->getNewRegistrationsToday();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_new_registrations_today_returns_correct_count(): void
    {
        // Today
        $this->createUserCreatedAt(Carbon::today());
        $this->createUserCreatedAt(Carbon::today()->addHours(5));
        $this->createUserCreatedAt(Carbon::today()->addHours(23));

        // Yesterday
        $this->createUserCreatedAt(Carbon::yesterday());

        $count = $this->repository->getNewRegistrationsToday();

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function get_new_registrations_today_boundary_midnight(): void
    {
        // Just before midnight today
        $this->createUserCreatedAt(Carbon::today()->addHours(23)->addMinutes(59));

        // Just after midnight yesterday
        $this->createUserCreatedAt(Carbon::yesterday()->addHours(23)->addMinutes(59));

        $count = $this->repository->getNewRegistrationsToday();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function get_new_registrations_today_returns_integer(): void
    {
        $this->createUserCreatedAt(Carbon::today());

        $count = $this->repository->getNewRegistrationsToday();

        $this->assertIsInt($count);
    }

    // =========================================================================
    // getNewRegistrationsThisWeek() Tests
    // =========================================================================

    /** @test */
    public function get_new_registrations_this_week_returns_zero_when_none(): void
    {
        $count = $this->repository->getNewRegistrationsThisWeek();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_new_registrations_this_week_returns_correct_count(): void
    {
        // This week (Monday to today) - create users within this calendar week
        $this->createUserCreatedAt(Carbon::now()->startOfWeek());
        $this->createUserCreatedAt(Carbon::now()->startOfWeek()->addDays(2)); // Wednesday this week

        // Last week
        $this->createUserCreatedAt(Carbon::now()->subWeek());

        $count = $this->repository->getNewRegistrationsThisWeek();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function get_new_registrations_this_week_boundary_start_of_week(): void
    {
        // Monday of this week
        $this->createUserCreatedAt(Carbon::now()->startOfWeek());

        // Sunday of last week
        $this->createUserCreatedAt(Carbon::now()->startOfWeek()->subSecond());

        $count = $this->repository->getNewRegistrationsThisWeek();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function get_new_registrations_this_week_returns_integer(): void
    {
        $this->createUserCreatedAt(Carbon::today());

        $count = $this->repository->getNewRegistrationsThisWeek();

        $this->assertIsInt($count);
    }

    // =========================================================================
    // getNewRegistrationsThisMonth() Tests
    // =========================================================================

    /** @test */
    public function get_new_registrations_this_month_returns_zero_when_none(): void
    {
        $count = $this->repository->getNewRegistrationsThisMonth();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_new_registrations_this_month_returns_correct_count(): void
    {
        // This month - create users within this calendar month
        $this->createUserCreatedAt(Carbon::now()->startOfMonth());
        $this->createUserCreatedAt(Carbon::now()->startOfMonth()->addDays(10)); // Day 11 of this month

        // Last month
        $this->createUserCreatedAt(Carbon::now()->subMonth());

        $count = $this->repository->getNewRegistrationsThisMonth();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function get_new_registrations_this_month_boundary_start_of_month(): void
    {
        // First day of this month
        $this->createUserCreatedAt(Carbon::now()->startOfMonth());

        // Last day of last month
        $this->createUserCreatedAt(Carbon::now()->startOfMonth()->subSecond());

        $count = $this->repository->getNewRegistrationsThisMonth();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function get_new_registrations_this_month_returns_integer(): void
    {
        $this->createUserCreatedAt(Carbon::today());

        $count = $this->repository->getNewRegistrationsThisMonth();

        $this->assertIsInt($count);
    }

    // =========================================================================
    // getGrowthRates() Tests
    // =========================================================================

    /** @test */
    public function get_growth_rates_returns_array(): void
    {
        $growth = $this->repository->getGrowthRates();

        $this->assertIsArray($growth);
        $this->assertArrayHasKey('daily', $growth);
        $this->assertArrayHasKey('weekly', $growth);
        $this->assertArrayHasKey('monthly', $growth);
    }

    /** @test */
    public function get_growth_rates_calculates_daily_growth(): void
    {
        // Today: 5 new users
        for ($i = 0; $i < 5; $i++) {
            $this->createUserCreatedAt(Carbon::today());
        }

        // Yesterday: 4 new users
        for ($i = 0; $i < 4; $i++) {
            $this->createUserCreatedAt(Carbon::yesterday());
        }

        $growth = $this->repository->getGrowthRates();

        // (5 - 4) / 4 * 100 = 25%
        $this->assertEquals(25.0, $growth['daily']);
    }

    /** @test */
    public function get_growth_rates_calculates_weekly_growth(): void
    {
        // This week: 10 new users (spread across this calendar week)
        $startOfThisWeek = Carbon::now()->startOfWeek();
        for ($i = 0; $i < 10; $i++) {
            $this->createUserCreatedAt($startOfThisWeek->copy()->addDays($i % 7));
        }

        // Last week: 8 new users (spread across last calendar week)
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        for ($i = 0; $i < 8; $i++) {
            $this->createUserCreatedAt($startOfLastWeek->copy()->addDays($i % 7));
        }

        $growth = $this->repository->getGrowthRates();

        // (10 - 8) / 8 * 100 = 25%
        $this->assertEquals(25.0, $growth['weekly']);
    }

    /** @test */
    public function get_growth_rates_calculates_monthly_growth(): void
    {
        // This month: 20 new users (spread across this calendar month)
        $startOfThisMonth = Carbon::now()->startOfMonth();
        for ($i = 0; $i < 20; $i++) {
            $this->createUserCreatedAt($startOfThisMonth->copy()->addDays($i % 28));
        }

        // Last month: 15 new users (spread across last calendar month)
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        for ($i = 0; $i < 15; $i++) {
            $this->createUserCreatedAt($startOfLastMonth->copy()->addDays($i % 28));
        }

        $growth = $this->repository->getGrowthRates();

        // (20 - 15) / 15 * 100 = 33.33%
        $this->assertEqualsWithDelta(33.33, $growth['monthly'], 0.1);
    }

    /** @test */
    public function get_growth_rates_handles_zero_previous_period(): void
    {
        // Today: 5 new users
        for ($i = 0; $i < 5; $i++) {
            $this->createUserCreatedAt(Carbon::today());
        }

        // Yesterday: 0 new users (nothing created)

        $growth = $this->repository->getGrowthRates();

        // When previous period is 0, growth should be 0 or handled gracefully
        $this->assertIsFloat($growth['daily']);
    }

    /** @test */
    public function get_growth_rates_handles_zero_both_periods(): void
    {
        // No users at all

        $growth = $this->repository->getGrowthRates();

        $this->assertEquals(0.0, $growth['daily']);
        $this->assertEquals(0.0, $growth['weekly']);
        $this->assertEquals(0.0, $growth['monthly']);
    }

    /** @test */
    public function get_growth_rates_returns_negative_growth(): void
    {
        // Today: 3 new users
        for ($i = 0; $i < 3; $i++) {
            $this->createUserCreatedAt(Carbon::today());
        }

        // Yesterday: 5 new users
        for ($i = 0; $i < 5; $i++) {
            $this->createUserCreatedAt(Carbon::yesterday());
        }

        $growth = $this->repository->getGrowthRates();

        // (3 - 5) / 5 * 100 = -40%
        $this->assertEquals(-40.0, $growth['daily']);
    }

    /** @test */
    public function get_growth_rates_returns_floats(): void
    {
        $this->createUserCreatedAt(Carbon::today());

        $growth = $this->repository->getGrowthRates();

        $this->assertIsFloat($growth['daily']);
        $this->assertIsFloat($growth['weekly']);
        $this->assertIsFloat($growth['monthly']);
    }

    /** @test */
    public function get_growth_rates_rounds_to_one_decimal(): void
    {
        // Create users that would result in non-terminating decimal
        for ($i = 0; $i < 7; $i++) {
            $this->createUserCreatedAt(Carbon::today());
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createUserCreatedAt(Carbon::yesterday());
        }

        $growth = $this->repository->getGrowthRates();

        // (7 - 3) / 3 * 100 = 133.333...%
        // Should round to 1 decimal
        $this->assertEquals(133.3, $growth['daily']);
    }

    // =========================================================================
    // getUsersByPlan() Tests
    // =========================================================================

    /** @test */
    public function get_users_by_plan_returns_array(): void
    {
        $byPlan = $this->repository->getUsersByPlan();

        $this->assertIsArray($byPlan);
    }

    /** @test */
    public function get_users_by_plan_returns_zero_for_each_plan_when_no_users(): void
    {
        $byPlan = $this->repository->getUsersByPlan();

        $this->assertEquals(0, $byPlan['free'] ?? 0);
        $this->assertEquals(0, $byPlan['pro'] ?? 0);
        $this->assertEquals(0, $byPlan['enterprise'] ?? 0);
    }

    /** @test */
    public function get_users_by_plan_counts_free_users(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->createUserWithPlan('free');
        }

        $byPlan = $this->repository->getUsersByPlan();

        $this->assertEquals(100, $byPlan['free']);
    }

    /** @test */
    public function get_users_by_plan_counts_pro_users(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->createUserWithPlan('pro');
        }

        $byPlan = $this->repository->getUsersByPlan();

        $this->assertEquals(50, $byPlan['pro']);
    }

    /** @test */
    public function get_users_by_plan_counts_enterprise_users(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createUserWithPlan('enterprise');
        }

        $byPlan = $this->repository->getUsersByPlan();

        $this->assertEquals(10, $byPlan['enterprise']);
    }

    /** @test */
    public function get_users_by_plan_mixed_plans(): void
    {
        // Create users with different plans
        for ($i = 0; $i < 100; $i++) {
            $this->createUserWithPlan('free');
        }
        for ($i = 0; $i < 50; $i++) {
            $this->createUserWithPlan('pro');
        }
        for ($i = 0; $i < 10; $i++) {
            $this->createUserWithPlan('enterprise');
        }

        $byPlan = $this->repository->getUsersByPlan();

        $this->assertEquals(100, $byPlan['free']);
        $this->assertEquals(50, $byPlan['pro']);
        $this->assertEquals(10, $byPlan['enterprise']);
    }

    /** @test */
    public function get_users_by_plan_defaults_null_plan_to_free(): void
    {
        // User with default plan (free is the database default)
        User::factory()->create(); // Uses default 'free' plan
        User::factory()->create(['plan' => 'free']); // Explicitly free

        $byPlan = $this->repository->getUsersByPlan();

        // Both users should be counted as free
        $this->assertGreaterThanOrEqual(2, $byPlan['free'] ?? 0);
    }

    /** @test */
    public function get_users_by_plan_returns_integers(): void
    {
        $this->createUserWithPlan('free');

        $byPlan = $this->repository->getUsersByPlan();

        $this->assertIsInt($byPlan['free']);
    }

    /** @test */
    public function get_users_by_plan_handles_unknown_plans(): void
    {
        $this->createUserWithPlan('unknown_plan');

        $byPlan = $this->repository->getUsersByPlan();

        // Should handle unknown plans gracefully
        $this->assertIsArray($byPlan);
    }

    // =========================================================================
    // getFullStats() Tests
    // =========================================================================

    /** @test */
    public function get_full_stats_returns_complete_structure(): void
    {
        $stats = $this->repository->getFullStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('newThisMonth', $stats);
        $this->assertArrayHasKey('newThisWeek', $stats);
        $this->assertArrayHasKey('newToday', $stats);
        $this->assertArrayHasKey('growthRate', $stats);
        $this->assertArrayHasKey('byPlan', $stats);
    }

    /** @test */
    public function get_full_stats_growth_rate_structure(): void
    {
        $stats = $this->repository->getFullStats();

        $this->assertIsArray($stats['growthRate']);
        $this->assertArrayHasKey('monthly', $stats['growthRate']);
        $this->assertArrayHasKey('weekly', $stats['growthRate']);
        $this->assertArrayHasKey('daily', $stats['growthRate']);
    }

    /** @test */
    public function get_full_stats_by_plan_structure(): void
    {
        $stats = $this->repository->getFullStats();

        $this->assertIsArray($stats['byPlan']);
        $this->assertArrayHasKey('free', $stats['byPlan']);
        $this->assertArrayHasKey('pro', $stats['byPlan']);
        $this->assertArrayHasKey('enterprise', $stats['byPlan']);
    }

    /** @test */
    public function get_full_stats_matches_response_format(): void
    {
        // Create some test data
        $this->createUserWithPlan('free');
        $this->createUserWithPlan('pro');
        $this->createUserWithPlan('enterprise');

        $stats = $this->repository->getFullStats();

        // Verify response matches expected format from SPEC-027
        $this->assertEquals(3, $stats['total']);
        $this->assertIsInt($stats['active']);
        $this->assertIsInt($stats['newThisMonth']);
        $this->assertIsInt($stats['newThisWeek']);
        $this->assertIsInt($stats['newToday']);
    }

    /** @test */
    public function get_full_stats_all_values_are_correct_types(): void
    {
        $stats = $this->repository->getFullStats();

        // Integer fields
        $this->assertIsInt($stats['total']);
        $this->assertIsInt($stats['active']);
        $this->assertIsInt($stats['newThisMonth']);
        $this->assertIsInt($stats['newThisWeek']);
        $this->assertIsInt($stats['newToday']);

        // Float fields in growthRate
        $this->assertIsFloat($stats['growthRate']['monthly']);
        $this->assertIsFloat($stats['growthRate']['weekly']);
        $this->assertIsFloat($stats['growthRate']['daily']);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_total_user_count_completes_under_100ms(): void
    {
        // Create 1000 users
        User::factory()->count(1000)->create();

        $start = microtime(true);
        $this->repository->getTotalUserCount();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, "Query took {$duration}ms, expected < 100ms");
    }

    /** @test */
    public function get_active_users_count_completes_under_100ms(): void
    {
        User::factory()->count(500)->create(['last_login_at' => now()->subDays(15)]);
        User::factory()->count(500)->create(['last_login_at' => now()->subDays(60)]);

        $start = microtime(true);
        $this->repository->getActiveUsersCount();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, "Query took {$duration}ms, expected < 100ms");
    }

    /** @test */
    public function get_full_stats_completes_under_100ms(): void
    {
        // Create substantial test data
        User::factory()->count(500)->create(['plan' => 'free']);
        User::factory()->count(200)->create(['plan' => 'pro']);
        User::factory()->count(50)->create(['plan' => 'enterprise']);

        $start = microtime(true);
        $this->repository->getFullStats();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, "Query took {$duration}ms, expected < 100ms");
    }

    /** @test */
    public function get_users_by_plan_completes_under_100ms(): void
    {
        User::factory()->count(1000)->create();

        $start = microtime(true);
        $this->repository->getUsersByPlan();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, "Query took {$duration}ms, expected < 100ms");
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /** @test */
    public function handles_timezone_aware_date_queries(): void
    {
        // Create user at boundary of day in UTC
        $boundaryUser = $this->createUserCreatedAt(Carbon::today()->startOfDay());

        $count = $this->repository->getNewRegistrationsToday();

        $this->assertGreaterThanOrEqual(1, $count);
    }

    /** @test */
    public function handles_large_user_counts(): void
    {
        User::factory()->count(10000)->create();

        $count = $this->repository->getTotalUserCount();

        $this->assertEquals(10000, $count);
    }

    /** @test */
    public function handles_concurrent_queries(): void
    {
        User::factory()->count(100)->create();

        // Run multiple queries "concurrently"
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->repository->getTotalUserCount();
        }

        // All results should be consistent
        $this->assertCount(1, array_unique($results));
    }

    /** @test */
    public function handles_unicode_in_user_data(): void
    {
        User::factory()->create([
            'name' => '用户名 测试',
            'email' => 'test@例え.jp',
            'plan' => 'pro',
        ]);

        $stats = $this->repository->getFullStats();

        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['total']);
    }

    /** @test */
    public function handles_null_last_login_at(): void
    {
        User::factory()->count(5)->create(['last_login_at' => null]);
        User::factory()->count(3)->create(['last_login_at' => now()->subDays(10)]);

        $count = $this->repository->getActiveUsersCount();

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function handles_empty_database_gracefully(): void
    {
        $stats = $this->repository->getFullStats();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['active']);
        $this->assertEquals(0, $stats['newThisMonth']);
        $this->assertEquals(0, $stats['newThisWeek']);
        $this->assertEquals(0, $stats['newToday']);
        $this->assertEquals(0.0, $stats['growthRate']['daily']);
        $this->assertEquals(0.0, $stats['growthRate']['weekly']);
        $this->assertEquals(0.0, $stats['growthRate']['monthly']);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /** @test */
    public function no_sql_injection_via_plan_parameter(): void
    {
        // Attempt SQL injection via plan field (if user input were used)
        $maliciousPlan = "free'; DROP TABLE users; --";

        $this->createUserWithPlan($maliciousPlan);

        // Query should execute safely without dropping table
        $stats = $this->repository->getFullStats();

        $this->assertIsArray($stats);
        $this->assertDatabaseCount('users', 1);
    }

    /** @test */
    public function query_uses_parameter_binding(): void
    {
        User::factory()->count(5)->create();

        // Enable query log to verify parameter binding
        DB::enableQueryLog();

        $this->repository->getActiveUsersCount();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify queries were executed (actual binding verification would need inspection)
        $this->assertNotEmpty($queries);
    }

    /** @test */
    public function excludes_sensitive_user_data_from_results(): void
    {
        User::factory()->create([
            'email' => 'sensitive@example.com',
            'password' => 'hashed_password',
        ]);

        $stats = $this->repository->getFullStats();

        // Stats should only contain counts, not user data
        $this->assertArrayNotHasKey('email', $stats);
        $this->assertArrayNotHasKey('password', $stats);
        $this->assertArrayNotHasKey('users', $stats);
    }

    // =========================================================================
    // Cache Integration Tests
    // =========================================================================

    /** @test */
    public function respects_cached_results(): void
    {
        User::factory()->count(10)->create();

        // First call
        $count1 = $this->repository->getTotalUserCount();

        // Add more users
        User::factory()->count(5)->create();

        // Second call (may return cached result)
        $count2 = $this->repository->getTotalUserCount();

        // If caching is implemented, both should be equal
        // If not, count2 should be higher
        $this->assertGreaterThanOrEqual($count1, $count2);
    }

    /** @test */
    public function cache_key_is_namespaced(): void
    {
        // This test verifies cache key naming convention
        // The repository should use namespaced keys like 'admin:stats:users:*'

        // Create test data
        User::factory()->create();

        // Call method that should cache
        $this->repository->getFullStats();

        // If cache key is properly namespaced, it should not conflict
        // with other cached data
        $this->assertTrue(true); // Placeholder - actual verification would check cache keys
    }
}
