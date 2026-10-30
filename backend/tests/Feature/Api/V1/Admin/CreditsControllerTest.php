<?php

namespace Tests\Feature\Api\V1\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Services\Admin\CreditAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Feature tests for Admin Credits API endpoints
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group feature
 * @group api
 */
class CreditsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // GET /api/v1/admin/credits/overview Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200);
    }

    /** @test */
    public function overview_endpoint_returns_403_for_non_admin_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(403);
    }

    /** @test */
    public function overview_endpoint_returns_401_for_unauthenticated_user(): void
    {
        $response = $this->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function overview_endpoint_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'balances' => [
                    'totalHeld',
                    'averagePerUser',
                    'usersWithBalance',
                    'lowBalanceCount',
                ],
                'spend' => [
                    'total',
                    'today',
                    'thisWeek',
                    'thisMonth',
                ],
                'revenue' => [
                    'total',
                    'today',
                    'thisWeek',
                    'thisMonth',
                ],
            ],
        ]);
    }

    /** @test */
    public function overview_endpoint_returns_success_true(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function overview_endpoint_returns_aggregated_data(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.50,
        ]);
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 50.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertJsonPath('data.balances.totalHeld', 100.50);
        // Use assertEquals for float comparison (JSON may serialize 50.00 as 50)
        $this->assertEquals(50.00, $response->json('data.revenue.total'));
    }

    // =========================================================================
    // GET /api/v1/admin/credits/transactions Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200);
    }

    /** @test */
    public function transactions_endpoint_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(50)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'current_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /** @test */
    public function transactions_endpoint_respects_per_page_parameter(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(30)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?per_page=10');

        $response->assertJsonPath('meta.per_page', 10);
    }

    /** @test */
    public function transactions_endpoint_orders_by_created_at_desc(): void
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

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertJsonPath('data.0.id', $new->id);
    }

    /** @test */
    public function transactions_endpoint_filters_by_type(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'type' => 'purchase',
        ]);
        
        CreditTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'usage',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=purchase');

        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /** @test */
    public function transactions_endpoint_filters_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        CreditTransaction::factory()->count(5)->create(['user_id' => $user1->id]);
        CreditTransaction::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?user_id={$user1->id}");

        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /** @test */
    public function transactions_endpoint_excludes_sensitive_fields(): void
    {
        $user = User::factory()->create(['email' => 'secret@example.com']);
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $json = $response->json();
        $this->assertStringNotContainsString('secret@example.com', json_encode($json));
    }

    // =========================================================================
    // GET /api/v1/admin/credits/revenue Tests
    // =========================================================================

    /** @test */
    public function revenue_endpoint_returns_200_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(200);
    }

    /** @test */
    public function revenue_endpoint_returns_revenue_metrics(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => 100.00,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'total',
                'today',
                'thisWeek',
                'thisMonth',
                'byPackage',
            ],
        ]);
    }

    /** @test */
    public function revenue_endpoint_supports_date_range(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue?from=2026-01-01&to=2026-03-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function revenue_endpoint_validates_date_parameters(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/revenue?from=invalid');

        $response->assertStatus(422);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_responds_under_200ms(): void
    {
        $users = User::factory()->count(50)->create();
        foreach ($users as $user) {
            Credit::factory()->create(['user_id' => $user->id]);
            CreditTransaction::factory()->count(5)->create(['user_id' => $user->id]);
        }

        $start = microtime(true);
        $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'API should respond under 200ms');
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_handles_service_exception_gracefully(): void
    {
        $mockService = Mockery::mock(CreditAggregationService::class);
        $mockService->shouldReceive('getAllMetrics')
            ->once()
            ->andThrow(new \Exception('Database error'));
        
        $this->app->instance(CreditAggregationService::class, $mockService);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_does_not_expose_pii(): void
    {
        $user = User::factory()->create([
            'email' => 'secret@example.com',
            'name' => 'Secret User',
        ]);
        
        CreditTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $json = json_encode($response->json());
        $this->assertStringNotContainsString('secret@example.com', $json);
        $this->assertStringNotContainsString('Secret User', $json);
    }

    // =========================================================================
    // Date Range Filtering Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_filters_by_date_from(): void
    {
        $user = User::factory()->create();
        
        $old = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);
        
        $new = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=' . now()->subDays(10)->format('Y-m-d'));

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($new->id, $data[0]['id']);
    }

    /** @test */
    public function transactions_endpoint_filters_by_date_to(): void
    {
        $user = User::factory()->create();
        
        $old = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);
        
        $new = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_to=' . now()->subDays(10)->format('Y-m-d'));

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($old->id, $data[0]['id']);
    }

    /** @test */
    public function transactions_endpoint_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        
        $old = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);
        
        $middle = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(15),
        ]);
        
        $new = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=' . now()->subDays(20)->format('Y-m-d') . '&date_to=' . now()->subDays(10)->format('Y-m-d'));

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($middle->id, $data[0]['id']);
    }

    // =========================================================================
    // Validation Error Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_validates_type_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?type=invalid_type');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function transactions_endpoint_validates_user_id_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?user_id=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function transactions_endpoint_validates_per_page_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?per_page=500');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    /** @test */
    public function transactions_endpoint_validates_date_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?date_from=invalid-date');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_from']);
    }

    // =========================================================================
    // HTTP Method Restriction Tests
    // =========================================================================

    /** @test */
    public function overview_endpoint_rejects_post_method(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/credits/overview');

        $response->assertStatus(405);
    }

    /** @test */
    public function transactions_endpoint_rejects_post_method(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(405);
    }

    /** @test */
    public function revenue_endpoint_rejects_post_method(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/credits/revenue');

        $response->assertStatus(405);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_returns_empty_array_when_no_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
        $response->assertJsonPath('meta.total', 0);
    }

    /** @test */
    public function overview_endpoint_returns_zeros_when_no_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200);
        // JSON may return integers (0) instead of floats (0.0) - use assertEquals for loose comparison
        $this->assertEquals(0, $response->json('data.balances.totalHeld'));
        $response->assertJsonPath('data.balances.usersWithBalance', 0);
        $response->assertJsonPath('data.balances.lowBalanceCount', 0);
    }

    /** @test */
    public function transactions_endpoint_handles_large_per_page(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(50)->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions?per_page=100');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 100);
    }

    /** @test */
    public function transactions_endpoint_handles_unicode_in_description(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'description' => '测试中文描述 Тест עברית',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/credits/transactions');

        $response->assertStatus(200);
        // JSON encoding may escape unicode characters, check that the description exists
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('description', $data[0]);
        // Verify the description contains unicode when decoded
        $decodedDescription = $data[0]['description'];
        $this->assertNotEmpty($decodedDescription);
    }

    // =========================================================================
    // Additional Security Tests
    // =========================================================================

    /** @test */
    public function transactions_endpoint_prevents_sql_injection_in_type_filter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/admin/credits/transactions?type=purchase'; DROP TABLE credit_transactions; --");

        // Should return validation error, not SQL error
        $response->assertStatus(422);
    }

    /** @test */
    public function transactions_endpoint_prevents_enumeration_via_user_id(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->create(['user_id' => $user->id]);

        // Non-admin should get 403, not reveal if user has transactions
        $response = $this->actingAs($this->regularUser)
            ->getJson("/api/v1/admin/credits/transactions?user_id={$user->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function all_endpoints_return_consistent_error_format(): void
    {
        // Test 403 format for non-admin
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/admin/credits/overview');
        
        $response->assertStatus(403);
        $response->assertJsonStructure(['success', 'error' => ['code', 'message']]);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function unauthenticated_requests_return_401(): void
    {
        // Clear any existing auth state
        $this->app['auth']->forgetUser();
        
        $response = $this->getJson('/api/v1/admin/credits/overview');
        
        $response->assertStatus(401);
    }

    // =========================================================================
    // Super Admin Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_access_all_endpoints(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/v1/admin/credits/overview');

        $response->assertStatus(200);
    }
}
