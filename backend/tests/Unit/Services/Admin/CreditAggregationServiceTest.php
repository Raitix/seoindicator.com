<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\CreditAggregationService;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for CreditAggregationService
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group unit
 * @group services
 */
class CreditAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CreditAggregationService::class);
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(CreditAggregationService::class);
        $this->assertInstanceOf(CreditAggregationService::class, $service);
    }

    // =========================================================================
    // getStats() Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_array_with_required_keys(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('balances', $stats);
        $this->assertArrayHasKey('spend', $stats);
        $this->assertArrayHasKey('revenue', $stats);
    }

    /** @test */
    public function get_stats_returns_zero_for_empty_database(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['balances']['totalHeld']);
        $this->assertEquals(0, $stats['spend']['total']);
        $this->assertEquals(0, $stats['revenue']['total']);
    }

    // =========================================================================
    // Balance Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_calculates_total_held_balance(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Credit::factory()->create([
            'user_id' => $user1->id,
            'balance' => 100.50,
        ]);
        
        Credit::factory()->create([
            'user_id' => $user2->id,
            'balance' => 200.25,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(300.75, $stats['balances']['totalHeld']);
    }

    /** @test */
    public function get_stats_calculates_average_balance_per_user(): void
    {
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $index => $user) {
            Credit::factory()->create([
                'user_id' => $user->id,
                'balance' => ($index + 1) * 100, // 100, 200, 300
            ]);
        }

        $stats = $this->service->getStats();

        // Average: (100 + 200 + 300) / 3 = 200
        $this->assertEquals(200, $stats['balances']['averagePerUser']);
    }

    /** @test */
    public function get_stats_counts_users_with_balance(): void
    {
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $index => $user) {
            Credit::factory()->create([
                'user_id' => $user->id,
                'balance' => $index < 3 ? 50 : 0, // 3 users with balance, 2 with zero
            ]);
        }

        $stats = $this->service->getStats();

        $this->assertEquals(3, $stats['balances']['usersWithBalance']);
    }

    /** @test */
    public function get_stats_counts_low_balance_users(): void
    {
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $index => $user) {
            Credit::factory()->create([
                'user_id' => $user->id,
                'balance' => $index < 2 ? 5 : 50, // 2 low balance (< 10)
            ]);
        }

        $stats = $this->service->getStats();

        $this->assertEquals(2, $stats['balances']['lowBalanceCount']);
    }

    /** @test */
    public function get_stats_handles_users_without_credit_record(): void
    {
        User::factory()->count(5)->create();
        // No credit records created

        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['balances']['totalHeld']);
        $this->assertEquals(0, $stats['balances']['usersWithBalance']);
    }

    // =========================================================================
    // Spend Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_calculates_total_spend(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 100,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(300, $stats['spend']['total']);
    }

    /** @test */
    public function get_stats_calculates_spend_today(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 50,
            'created_at' => now(),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 100,
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(50, $stats['spend']['today']);
    }

    /** @test */
    public function get_stats_calculates_spend_this_week(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 75,
            'created_at' => now()->startOfWeek()->addDays(2),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 200,
            'created_at' => now()->subWeeks(2),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(75, $stats['spend']['thisWeek']);
    }

    /** @test */
    public function get_stats_calculates_spend_this_month(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 150,
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 300,
            'created_at' => now()->subMonth(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(150, $stats['spend']['thisMonth']);
    }

    /** @test */
    public function get_stats_excludes_non_usage_transactions_from_spend(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'bonus',
            'amount' => 50,
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => 25,
        ]);

        $stats = $this->service->getStats();

        // Only usage should count towards spend
        $this->assertEquals(25, $stats['spend']['total']);
    }

    // =========================================================================
    // Revenue Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_calculates_total_revenue(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(300, $stats['revenue']['total']);
    }

    /** @test */
    public function get_stats_calculates_revenue_this_month(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 200,
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 500,
            'created_at' => now()->subMonth(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(200, $stats['revenue']['thisMonth']);
    }

    /** @test */
    public function get_stats_calculates_revenue_this_week(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 150,
            'created_at' => now()->startOfWeek()->addDays(2),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 300,
            'created_at' => now()->subWeeks(2),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(150, $stats['revenue']['thisWeek']);
    }

    /** @test */
    public function get_stats_calculates_revenue_today(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 75,
            'created_at' => now(),
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(75, $stats['revenue']['today']);
    }

    /** @test */
    public function get_stats_excludes_non_purchase_transactions_from_revenue(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => 50,
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'bonus',
            'amount' => 25,
        ]);

        $stats = $this->service->getStats();

        // Only purchase should count towards revenue
        $this->assertEquals(100, $stats['revenue']['total']);
    }

    // =========================================================================
    // getRecentTransactions() Tests
    // =========================================================================

    /** @test */
    public function get_recent_transactions_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(50)->create(['user_id' => $user->id]);

        $result = $this->service->getRecentTransactions(15);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
    }

    /** @test */
    public function get_recent_transactions_orders_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        
        $old = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $new = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $result = $this->service->getRecentTransactions(10);

        $this->assertEquals($new->id, $result->first()->id);
    }

    /** @test */
    public function get_recent_transactions_includes_user_relationship(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $result = $this->service->getRecentTransactions(10);

        $this->assertTrue($result->first()->relationLoaded('user'));
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_stats_completes_under_100ms(): void
    {
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            Credit::factory()->create(['user_id' => $user->id]);
            CreditTransaction::factory()->count(5)->create(['user_id' => $user->id]);
        }

        $start = microtime(true);
        $this->service->getStats();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Stats query should complete under 100ms');
    }

    /** @test */
    public function get_stats_uses_efficient_queries(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);
        CreditTransaction::factory()->count(50)->create(['user_id' => $user->id]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->service->getStats();

        $this->assertLessThanOrEqual(10, count($queries), 'Should use efficient queries');
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    /** @test */
    public function get_stats_handles_negative_amounts(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => -50, // Refund or correction
        ]);

        $stats = $this->service->getStats();

        // Should handle negative amounts appropriately
        $this->assertLessThanOrEqual(0, $stats['spend']['total']);
    }

    /** @test */
    public function get_stats_handles_large_amounts(): void
    {
        $user = User::factory()->create();
        
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 999999.99,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(999999.99, $stats['balances']['totalHeld']);
    }

    /** @test */
    public function get_stats_handles_decimal_precision(): void
    {
        $user = User::factory()->create();
        
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.123456,
        ]);

        $stats = $this->service->getStats();

        // Should maintain appropriate precision
        $this->assertEqualsWithDelta(100.12, $stats['balances']['totalHeld'], 0.01);
    }

    /** @test */
    public function get_stats_handles_timezone_correctly(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100,
            'created_at' => now()->startOfDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(100, $stats['revenue']['today']);
    }

    // =========================================================================
    // Data Validation Tests
    // =========================================================================

    /** @test */
    public function get_stats_validates_numeric_types(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'type' => 'purchase', 'amount' => 50]);

        $stats = $this->service->getStats();

        $this->assertIsFloat($stats['balances']['totalHeld']);
        $this->assertIsFloat($stats['spend']['total']);
        $this->assertIsFloat($stats['revenue']['total']);
    }

    /** @test */
    public function get_stats_does_not_expose_pii(): void
    {
        $user = User::factory()->create([
            'email' => 'secret@example.com',
            'name' => 'Secret User',
        ]);
        
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $stats = $this->service->getStats();

        $jsonStats = json_encode($stats);
        $this->assertStringNotContainsString('secret@example.com', $jsonStats);
        $this->assertStringNotContainsString('Secret User', $jsonStats);
    }
}
