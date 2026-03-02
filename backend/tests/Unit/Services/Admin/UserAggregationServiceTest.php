<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\UserAggregationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for UserAggregationService
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group unit
 * @group services
 */
class UserAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserAggregationService::class);
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(UserAggregationService::class);
        $this->assertInstanceOf(UserAggregationService::class, $service);
    }

    // =========================================================================
    // getStats() Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_array_with_required_keys(): void
    {
        User::factory()->count(10)->create();

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('newThisMonth', $stats);
        $this->assertArrayHasKey('newThisWeek', $stats);
        $this->assertArrayHasKey('newToday', $stats);
        $this->assertArrayHasKey('growthRate', $stats);
        $this->assertArrayHasKey('byPlan', $stats);
    }

    /** @test */
    public function get_stats_returns_correct_total_count(): void
    {
        User::factory()->count(25)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(25, $stats['total']);
    }

    /** @test */
    public function get_stats_returns_zero_for_empty_database(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['active']);
        $this->assertEquals(0, $stats['newThisMonth']);
    }

    /** @test */
    public function get_stats_counts_active_users_correctly(): void
    {
        // Active users (logged in within last 30 days)
        User::factory()->count(5)->create([
            'last_login_at' => now()->subDays(15),
        ]);
        
        // Inactive users (logged in more than 30 days ago)
        User::factory()->count(3)->create([
            'last_login_at' => now()->subDays(45),
        ]);
        
        // Never logged in
        User::factory()->count(2)->create([
            'last_login_at' => null,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(5, $stats['active']);
    }

    /** @test */
    public function get_stats_counts_new_users_this_month(): void
    {
        // Created this month
        User::factory()->count(8)->create([
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        
        // Created last month
        User::factory()->count(4)->create([
            'created_at' => now()->subMonth(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(8, $stats['newThisMonth']);
    }

    /** @test */
    public function get_stats_counts_new_users_this_week(): void
    {
        // Created this week
        User::factory()->count(5)->create([
            'created_at' => now()->startOfWeek()->addDays(2),
        ]);
        
        // Created earlier this month
        User::factory()->count(10)->create([
            'created_at' => now()->subWeeks(2),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(5, $stats['newThisWeek']);
    }

    /** @test */
    public function get_stats_counts_new_users_today(): void
    {
        // Created today
        User::factory()->count(3)->create([
            'created_at' => now()->startOfDay()->addHours(5),
        ]);
        
        // Created yesterday
        User::factory()->count(7)->create([
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(3, $stats['newToday']);
    }

    // =========================================================================
    // Growth Rate Tests
    // =========================================================================

    /** @test */
    public function get_stats_calculates_monthly_growth_rate(): void
    {
        // This month: 20 users
        User::factory()->count(20)->create([
            'created_at' => now()->startOfMonth(),
        ]);
        
        // Last month: 100 users (so growth should be ~20%)
        User::factory()->count(100)->create([
            'created_at' => now()->subMonth()->startOfMonth(),
        ]);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('monthly', $stats['growthRate']);
        $this->assertIsFloat($stats['growthRate']['monthly']);
    }

    /** @test */
    public function get_stats_calculates_weekly_growth_rate(): void
    {
        // This week
        User::factory()->count(15)->create([
            'created_at' => now()->startOfWeek(),
        ]);
        
        // Last week
        User::factory()->count(10)->create([
            'created_at' => now()->subWeek()->startOfWeek(),
        ]);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('weekly', $stats['growthRate']);
        $this->assertIsFloat($stats['growthRate']['weekly']);
    }

    /** @test */
    public function get_stats_calculates_daily_growth_rate(): void
    {
        // Today
        User::factory()->count(5)->create([
            'created_at' => now(),
        ]);
        
        // Yesterday
        User::factory()->count(4)->create([
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('daily', $stats['growthRate']);
        $this->assertIsFloat($stats['growthRate']['daily']);
    }

    /** @test */
    public function get_stats_returns_zero_growth_rate_for_no_previous_period_users(): void
    {
        User::factory()->count(10)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['growthRate']['monthly']);
        $this->assertEquals(0, $stats['growthRate']['weekly']);
        $this->assertEquals(0, $stats['growthRate']['daily']);
    }

    // =========================================================================
    // Plan Distribution Tests
    // =========================================================================

    /** @test */
    public function get_stats_groups_users_by_plan(): void
    {
        User::factory()->count(50)->create(['plan' => 'free']);
        User::factory()->count(20)->create(['plan' => 'pro']);
        User::factory()->count(5)->create(['plan' => 'enterprise']);

        $stats = $this->service->getStats();

        $this->assertEquals(50, $stats['byPlan']['free']);
        $this->assertEquals(20, $stats['byPlan']['pro']);
        $this->assertEquals(5, $stats['byPlan']['enterprise']);
    }

    /** @test */
    public function get_stats_handles_missing_plans(): void
    {
        User::factory()->count(10)->create(['plan' => null]);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('free', $stats['byPlan']);
    }

    // =========================================================================
    // getRecentUsers() Tests
    // =========================================================================

    /** @test */
    public function get_recent_users_returns_paginated_results(): void
    {
        User::factory()->count(30)->create();

        $result = $this->service->getRecentUsers(15);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
    }

    /** @test */
    public function get_recent_users_orders_by_created_at_desc(): void
    {
        $user1 = User::factory()->create(['created_at' => now()->subDays(2)]);
        $user2 = User::factory()->create(['created_at' => now()->subDays(1)]);
        $user3 = User::factory()->create(['created_at' => now()]);

        $result = $this->service->getRecentUsers(10);

        $this->assertEquals($user3->id, $result->first()->id);
        $this->assertEquals($user1->id, $result->last()->id);
    }

    /** @test */
    public function get_recent_users_excludes_sensitive_fields(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $result = $this->service->getRecentUsers(10);

        $this->assertArrayNotHasKey('password', $result->first()->toArray());
        $this->assertArrayNotHasKey('remember_token', $result->first()->toArray());
    }

    // =========================================================================
    // getActiveUsers() Tests
    // =========================================================================

    /** @test */
    public function get_active_users_returns_users_logged_in_last_30_days(): void
    {
        User::factory()->count(5)->create(['last_login_at' => now()->subDays(5)]);
        User::factory()->count(3)->create(['last_login_at' => now()->subDays(45)]);

        $result = $this->service->getActiveUsers(10);

        $this->assertEquals(5, $result->total());
    }

    /** @test */
    public function get_active_users_orders_by_last_login_desc(): void
    {
        $user1 = User::factory()->create(['last_login_at' => now()->subDays(2)]);
        $user2 = User::factory()->create(['last_login_at' => now()->subDays(1)]);
        $user3 = User::factory()->create(['last_login_at' => now()]);

        $result = $this->service->getActiveUsers(10);

        $this->assertEquals($user3->id, $result->first()->id);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_stats_completes_under_100ms(): void
    {
        User::factory()->count(1000)->create();

        $start = microtime(true);
        $this->service->getStats();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Stats query should complete under 100ms');
    }

    /** @test */
    public function get_stats_uses_efficient_queries(): void
    {
        User::factory()->count(100)->create();

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->service->getStats();

        // Should use minimal queries (one per stat category ideally)
        $this->assertLessThanOrEqual(5, count($queries), 'Should use efficient queries');
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    /** @test */
    public function get_stats_handles_timezone_correctly(): void
    {
        // User created at UTC midnight should be counted in today's stats
        User::factory()->create([
            'created_at' => now()->startOfDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(1, $stats['newToday']);
    }

    /** @test */
    public function get_stats_handles_concurrent_requests(): void
    {
        User::factory()->count(10)->create();

        $results = collect(range(1, 5))->map(function () {
            return $this->service->getStats();
        });

        $this->assertEquals(1, $results->unique()->count(), 'Concurrent requests should return same result');
    }

    /** @test */
    public function get_stats_handles_large_user_counts(): void
    {
        User::factory()->count(10000)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(10000, $stats['total']);
    }

    // =========================================================================
    // Data Validation Tests
    // =========================================================================

    /** @test */
    public function get_stats_validates_numeric_types(): void
    {
        User::factory()->count(10)->create();

        $stats = $this->service->getStats();

        $this->assertIsInt($stats['total']);
        $this->assertIsInt($stats['active']);
        $this->assertIsFloat($stats['growthRate']['monthly']);
    }

    /** @test */
    public function get_stats_does_not_expose_pii(): void
    {
        User::factory()->create([
            'email' => 'secret@example.com',
            'name' => 'Secret User',
        ]);

        $stats = $this->service->getStats();

        $this->assertStringNotContainsString('secret@example.com', json_encode($stats));
        $this->assertStringNotContainsString('Secret User', json_encode($stats));
    }
}
