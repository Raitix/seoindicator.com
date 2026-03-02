<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for the CreditsController Admin API.
 *
 * Tests admin endpoints for credit/balance statistics, transactions,
 * and revenue metrics.
 *
 * @see TASK-27-10
 * @see TEST-27-10
 * @see SPEC-027
 */
class CreditsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::factory()->create(['is_admin' => true]);

        // Create regular user
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    // =========================================================================
    // AUTHORIZATION TESTS - OVERVIEW
    // =========================================================================

    /**
     * Test overview requires authentication.
     * AUTH-002
     */
    public function test_overview_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(401);
    }

    /**
     * Test overview requires admin role.
     * AUTH-003
     */
    public function test_overview_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(403);
    }

    /**
     * Test admin can access overview.
     * AUTH-001
     */
    public function test_admin_can_access_overview(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHORIZATION TESTS - TRANSACTIONS
    // =========================================================================

    /**
     * Test transactions requires authentication.
     * AUTH-005
     */
    public function test_transactions_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(401);
    }

    /**
     * Test transactions requires admin role.
     * AUTH-006
     */
    public function test_transactions_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(403);
    }

    /**
     * Test admin can access transactions.
     * AUTH-004
     */
    public function test_admin_can_access_transactions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHORIZATION TESTS - REVENUE
    // =========================================================================

    /**
     * Test revenue requires authentication.
     * AUTH-008
     */
    public function test_revenue_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(401);
    }

    /**
     * Test revenue requires admin role.
     * AUTH-009
     */
    public function test_revenue_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(403);
    }

    /**
     * Test admin can access revenue.
     * AUTH-007
     */
    public function test_admin_can_access_revenue(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200);
    }

    // =========================================================================
    // OVERVIEW FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test overview returns balances structure.
     * OVER-001
     */
    public function test_overview_returns_balances_structure(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'balances' => [
                        'totalHeld',
                        'averagePerUser',
                        'usersWithBalance',
                        'lowBalanceCount',
                    ],
                ],
            ]);
    }

    /**
     * Test overview returns spend structure.
     * OVER-002
     */
    public function test_overview_returns_spend_structure(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => -50.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'spend' => [
                        'total',
                        'today',
                        'thisWeek',
                        'thisMonth',
                    ],
                ],
            ]);
    }

    /**
     * Test overview returns revenue structure.
     * OVER-003
     */
    public function test_overview_returns_revenue_structure(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'revenue' => [
                        'total',
                        'thisMonth',
                        'thisWeek',
                        'today',
                    ],
                ],
            ]);
    }

    /**
     * Test overview calculates total held correctly.
     * OVER-004
     */
    public function test_overview_calculates_total_held_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Credit::factory()->create(['user_id' => $user1->id, 'balance' => 100.00]);
        Credit::factory()->create(['user_id' => $user2->id, 'balance' => 200.00]);
        Credit::factory()->create(['user_id' => $user3->id, 'balance' => 300.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.balances.totalHeld', 600.00);
    }

    /**
     * Test overview calculates average per user correctly.
     * OVER-005
     */
    public function test_overview_calculates_average_per_user_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Credit::factory()->create(['user_id' => $user1->id, 'balance' => 100.00]);
        Credit::factory()->create(['user_id' => $user2->id, 'balance' => 200.00]);
        Credit::factory()->create(['user_id' => $user3->id, 'balance' => 300.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.balances.averagePerUser', 200.00);
    }

    /**
     * Test overview counts users with balance.
     * OVER-006
     */
    public function test_overview_counts_users_with_balance(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Credit::factory()->create(['user_id' => $user1->id, 'balance' => 100.00]);
        Credit::factory()->create(['user_id' => $user2->id, 'balance' => 200.00]);
        Credit::factory()->create(['user_id' => $user3->id, 'balance' => 0.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.balances.usersWithBalance', 2);
    }

    /**
     * Test overview low balance count.
     * OVER-007
     */
    public function test_overview_low_balance_count(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Credit::factory()->create(['user_id' => $user1->id, 'balance' => 5.00]);
        Credit::factory()->create(['user_id' => $user2->id, 'balance' => 100.00]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.balances.lowBalanceCount', 1);
    }

    /**
     * Test overview empty data returns zeros.
     * OVER-008
     */
    public function test_overview_empty_data_returns_zeros(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.balances.totalHeld', 0)
            ->assertJsonPath('data.balances.usersWithBalance', 0)
            ->assertJsonPath('data.spend.total', 0)
            ->assertJsonPath('data.revenue.total', 0);
    }

    // =========================================================================
    // TRANSACTIONS FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test transactions returns paginated results.
     * TRANS-001
     */
    public function test_transactions_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?perPage=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.perPage', 10);
    }

    /**
     * Test transactions default pagination is 20.
     * TRANS-002
     */
    public function test_transactions_default_pagination_is_20(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 20);
    }

    /**
     * Test transactions filter by user_id.
     * TRANS-003
     */
    public function test_transactions_filter_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CreditTransaction::factory()->count(5)->create(['user_id' => $user1->id]);
        CreditTransaction::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?user_id={$user1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions filter by type=purchase.
     * TRANS-004
     */
    public function test_transactions_filter_by_type_purchase(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->purchase()->create(['user_id' => $user->id]);
        CreditTransaction::factory()->count(3)->usage()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=purchase');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions filter by type=usage.
     * TRANS-005
     */
    public function test_transactions_filter_by_type_usage(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->usage()->create(['user_id' => $user->id]);
        CreditTransaction::factory()->count(3)->purchase()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=usage');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions filter by type=refund.
     * TRANS-006
     */
    public function test_transactions_filter_by_type_refund(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->create(['user_id' => $user->id, 'type' => 'refund']);
        CreditTransaction::factory()->count(3)->purchase()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=refund');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions filter by type=bonus.
     * TRANS-007
     */
    public function test_transactions_filter_by_type_bonus(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->create(['user_id' => $user->id, 'type' => 'bonus']);
        CreditTransaction::factory()->count(3)->purchase()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=bonus');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions filter by date_from.
     * TRANS-008
     */
    public function test_transactions_filter_by_date_from(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-15']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-20']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=2026-02-20');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test transactions filter by date_to.
     * TRANS-009
     */
    public function test_transactions_filter_by_date_to(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-15']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-20']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test transactions filter by date range.
     * TRANS-010
     */
    public function test_transactions_filter_by_date_range(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-15']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-02-20']);
        CreditTransaction::factory()->create(['user_id' => $user->id, 'created_at' => '2026-03-01']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=2026-02-01&date_to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test transactions combined filters.
     * TRANS-011
     */
    public function test_transactions_combined_filters(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CreditTransaction::factory()->count(5)->purchase()->create(['user_id' => $user1->id]);
        CreditTransaction::factory()->count(3)->usage()->create(['user_id' => $user1->id]);
        CreditTransaction::factory()->count(2)->purchase()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?user_id={$user1->id}&type=purchase");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test transactions PII excluded from response.
     * TRANS-012
     */
    public function test_transactions_pii_excluded_from_response(): void
    {
        $user = User::factory()->create(['email' => 'secret@example.com']);
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonMissing(['email' => 'secret@example.com']);
    }

    /**
     * Test transactions sorted by date desc.
     * TRANS-014
     */
    public function test_transactions_sorted_by_date_desc(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => '2026-02-15',
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => '2026-03-01',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.created_at', '2026-03-01T00:00:00.000000Z');
    }

    /**
     * Test transactions empty results.
     * TRANS-015
     */
    public function test_transactions_empty_results(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // REVENUE FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * Test revenue returns structure.
     * REV-001
     */
    public function test_revenue_returns_structure(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'thisMonth',
                    'thisWeek',
                    'today',
                ],
            ]);
    }

    /**
     * Test revenue total correct.
     * REV-002
     */
    public function test_revenue_total_correct(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 1000.00,
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 500.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1500.00);
    }

    /**
     * Test revenue today correct.
     * REV-003
     */
    public function test_revenue_today_correct(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 50.00,
            'created_at' => now(),
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.today', 50.00);
    }

    /**
     * Test revenue this week correct.
     * REV-004
     */
    public function test_revenue_this_week_correct(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 200.00,
            'created_at' => now()->subDays(3),
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.thisWeek', 200.00);
    }

    /**
     * Test revenue this month correct.
     * REV-005
     */
    public function test_revenue_this_month_correct(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 500.00,
            'created_at' => now()->subDays(15),
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
            'created_at' => now()->subDays(45),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.thisMonth', 500.00);
    }

    /**
     * Test revenue excludes non-purchase.
     * REV-006
     */
    public function test_revenue_excludes_non_purchase(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
        ]);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'usage',
            'amount' => -50.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 100.00);
    }

    /**
     * Test revenue empty returns zeros.
     * REV-007
     */
    public function test_revenue_empty_returns_zeros(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.today', 0);
    }

    /**
     * Test revenue handles large numbers.
     * REV-008
     */
    public function test_revenue_handles_large_numbers(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 1000000.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1000000.00);
    }

    // =========================================================================
    // RATE LIMITING TESTS
    // =========================================================================

    /**
     * Test rate limit headers present.
     * RATE-001
     */
    public function test_rate_limit_headers_present(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit');
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * Test SQL injection in user_id.
     * SEC-001
     */
    public function test_sql_injection_in_user_id(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?user_id=1; DROP TABLE users; --");

        // Should not cause an error
        $response->assertStatus(200);
    }

    /**
     * Test SQL injection in type.
     * SEC-002
     */
    public function test_sql_injection_in_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?type='; DELETE FROM credit_transactions;--");

        // Should return validation error
        $response->assertStatus(422);
    }

    /**
     * Test XSS in date_from.
     * SEC-003
     */
    public function test_xss_in_date_from(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=<script>alert(1)</script>');

        $response->assertStatus(422);
    }

    /**
     * Test path traversal blocked.
     * SEC-004
     */
    public function test_path_traversal_blocked(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?user_id=../../../etc/passwd');

        $response->assertStatus(422);
    }

    /**
     * Test invalid type rejected.
     * SEC-005
     */
    public function test_invalid_type_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=invalid_type');

        $response->assertStatus(422);
    }

    /**
     * Test invalid date format.
     * SEC-006
     */
    public function test_invalid_date_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=not-a-date');

        $response->assertStatus(422);
    }

    /**
     * Test no sensitive data exposure.
     * SEC-007
     */
    public function test_no_sensitive_data_exposure(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'metadata' => ['internal_key' => 'secret_value'],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200)
            ->assertJsonMissing(['internal_key' => 'secret_value']);
    }

    /**
     * Test no user enumeration.
     * SEC-008
     */
    public function test_no_user_enumeration(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?user_id=99999');

        // Should return empty result, not 404
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test very large perPage capped.
     * EDGE-001
     */
    public function test_very_large_perpage_capped(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(150)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?perPage=1000');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 100);
    }

    /**
     * Test negative perPage uses default.
     * EDGE-002
     */
    public function test_negative_perpage_uses_default(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?perPage=-10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 20);
    }

    /**
     * Test zero perPage uses default.
     * EDGE-003
     */
    public function test_zero_perpage_uses_default(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?perPage=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 20);
    }

    /**
     * Test invalid page defaults to first.
     * EDGE-004
     */
    public function test_invalid_page_defaults_to_first(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?page=-1');

        $response->assertStatus(200)
            ->assertJsonPath('meta.currentPage', 1);
    }

    /**
     * Test future date filter returns empty.
     * EDGE-005
     */
    public function test_future_date_filter_returns_empty(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=2099-01-01');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test invalid date range (from > to).
     * EDGE-006
     */
    public function test_invalid_date_range(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=2026-03-01&date_to=2026-02-01');

        // Should return empty or handle gracefully
        $response->assertStatus(200);
    }

    // =========================================================================
    // HTTP METHOD TESTS
    // =========================================================================

    /**
     * Test POST not allowed on overview.
     * HTTP-001
     */
    public function test_post_not_allowed_on_overview(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/credits/overview');

        $response->assertStatus(405);
    }

    /**
     * Test PUT not allowed on transactions.
     * HTTP-002
     */
    public function test_put_not_allowed_on_transactions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(405);
    }

    /**
     * Test DELETE not allowed on revenue.
     * HTTP-003
     */
    public function test_delete_not_allowed_on_revenue(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(405);
    }
}
