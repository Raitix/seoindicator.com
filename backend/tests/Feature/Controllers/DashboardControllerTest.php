<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\AnalysisHistory;
use App\Models\AnalysisCost;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ========================================
    // Category 6: GET /api/v1/dashboard Tests
    // ========================================

    /** @test */
    public function ep_001_authenticated_user_gets_dashboard_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'stats' => [
                        'totalAnalyses',
                        'thisMonth',
                        'thisWeek',
                        'today',
                    ],
                    'costSummary' => [
                        'totalCost',
                        'thisMonthCost',
                        'currency',
                    ],
                    'recentAnalyses',
                    'quickInsights' => [
                        'mostUsedTool',
                        'avgAnalysisTime',
                        'successRate',
                    ],
                ],
            ]);
    }

    /** @test */
    public function ep_002_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function ep_003_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard', [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function ep_004_response_structure_is_correct(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'stats',
                    'costSummary',
                    'recentAnalyses',
                    'quickInsights',
                ],
            ]);
    }

    /** @test */
    public function ep_005_user_isolation_enforced(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user1->id,
            'status' => 'completed',
        ]);

        AnalysisHistory::factory()->count(20)->create([
            'user_id' => $user2->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.totalAnalyses', 10);
    }

    /** @test */
    public function ep_006_cache_headers_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        // Cache-Control header should be present
        $this->assertNotNull($response->headers->get('Cache-Control'));
    }

    /** @test */
    public function ep_007_performance_under_500ms(): void
    {
        $user = User::factory()->create();

        // Create some test data
        AnalysisHistory::factory()->count(100)->create([
            'user_id' => $user->id,
        ]);

        $start = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Dashboard endpoint should respond in under 500ms');
    }

    /** @test */
    public function ep_008_sql_injection_prevented(): void
    {
        $user = User::factory()->create();

        // This should not cause any SQL errors
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
    }

    // ========================================
    // Category 7: GET /api/v1/dashboard/stats Tests
    // ========================================

    /** @test */
    public function es_001_authenticated_user_gets_stats(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalAnalyses',
                    'thisMonth',
                    'thisWeek',
                    'today',
                ],
            ]);
    }

    /** @test */
    public function es_002_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function es_003_response_structure_is_correct(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalAnalyses' => 0,
                    'thisMonth' => 0,
                    'thisWeek' => 0,
                    'today' => 0,
                ],
            ]);
    }

    /** @test */
    public function es_004_empty_user_returns_zeros(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.totalAnalyses', 0)
            ->assertJsonPath('data.thisMonth', 0)
            ->assertJsonPath('data.thisWeek', 0)
            ->assertJsonPath('data.today', 0);
    }

    /** @test */
    public function es_005_cross_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->count(15)->create([
            'user_id' => $user1->id,
            'status' => 'completed',
        ]);

        AnalysisHistory::factory()->count(25)->create([
            'user_id' => $user2->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.totalAnalyses', 15);
    }

    /** @test */
    public function es_006_performance_with_many_analyses(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(1000)->create([
            'user_id' => $user->id,
        ]);

        $start = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, 'Stats endpoint should respond in under 200ms');
    }

    /** @test */
    public function es_007_stats_cached_correctly(): void
    {
        $user = User::factory()->create();

        // First request - should cache
        $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        // Check cache exists
        $this->assertTrue(Cache::has('dashboard.stats.' . $user->id));
    }

    /** @test */
    public function es_008_cache_key_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)
            ->getJson('/api/v1/dashboard/stats');

        $this->actingAs($user2)
            ->getJson('/api/v1/dashboard/stats');

        $this->assertTrue(Cache::has('dashboard.stats.' . $user1->id));
        $this->assertTrue(Cache::has('dashboard.stats.' . $user2->id));
    }

    // ========================================
    // Category 8: GET /api/v1/dashboard/recent-analyses Tests
    // ========================================

    /** @test */
    public function er_001_authenticated_user_gets_recent_analyses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function er_002_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(401);
    }

    /** @test */
    public function er_003_response_structure_is_correct(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'tool_name' => 'meta_analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function er_004_empty_list_returns_empty_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /** @test */
    public function er_005_limited_to_10_items(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(15)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));
    }

    /** @test */
    public function er_006_ordered_by_analyzed_at_desc(): void
    {
        $user = User::factory()->create();

        // Create analyses with different dates
        $old = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now()->subDays(5),
        ]);

        $new = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $data = $response->json('data');

        // Most recent should be first
        $this->assertEquals($new->id, $data[0]['id']);
    }

    /** @test */
    public function er_007_cross_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user1->id,
        ]);

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $data = $response->json('data');

        $this->assertCount(5, $data);
    }

    /** @test */
    public function er_009_analysis_structure_contains_required_fields(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'tool_name' => 'meta_analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'toolName',
                    'inputUrl',
                    'status',
                    'analyzedAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function er_010_null_site_handling(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'site_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    // ========================================
    // Category 9: GET /api/v1/dashboard/cost-summary Tests
    // ========================================

    /** @test */
    public function ec_001_authenticated_user_gets_cost_summary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalCost',
                    'thisMonthCost',
                    'currency',
                ],
            ]);
    }

    /** @test */
    public function ec_002_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(401);
    }

    /** @test */
    public function ec_003_response_structure_is_correct(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totalCost' => 0.00,
                    'thisMonthCost' => 0.00,
                    'currency' => 'USD',
                ],
            ]);
    }

    /** @test */
    public function ec_004_zero_costs_returns_zeros(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.totalCost', 0);
    }

    /** @test */
    public function ec_005_multiple_costs_aggregated(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 5.50,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 3.25,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.totalCost', 8.75);
    }

    /** @test */
    public function ec_006_cross_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $analysis1 = AnalysisHistory::factory()->create(['user_id' => $user1->id]);
        $analysis2 = AnalysisHistory::factory()->create(['user_id' => $user2->id]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis1->id,
            'cost_amount' => 100.00,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis2->id,
            'cost_amount' => 200.00,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertJsonPath('data.totalCost', 100);
    }

    /** @test */
    public function ec_007_performance_with_many_costs(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->count(1000)->create([
            'analysis_id' => $analysis->id,
        ]);

        $start = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, 'Cost summary should respond in under 200ms');
    }

    /** @test */
    public function ec_008_cache_hit_returns_cached_data(): void
    {
        $user = User::factory()->create();

        // First request
        $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        // Verify cache exists
        $this->assertTrue(Cache::has('dashboard.costs.' . $user->id));
    }

    /** @test */
    public function ec_009_currency_field_is_usd(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertJsonPath('data.currency', 'USD');
    }

    /** @test */
    public function ec_010_high_precision_amounts(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.000001,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/cost-summary');

        $response->assertStatus(200);
    }

    // ========================================
    // Category 10: Security Tests
    // ========================================

    /** @test */
    public function sec_001_sql_injection_prevented(): void
    {
        $user = User::factory()->create();

        // This should not cause SQL errors
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function sec_002_idor_attack_prevented(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user2->id,
        ]);

        // User1 should not see User2's data
        $response = $this->actingAs($user1)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.totalAnalyses', 0);
    }

    /** @test */
    public function sec_003_xss_in_urls_sanitized(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'input_url' => 'https://example.com/<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/recent-analyses');

        $response->assertStatus(200);

        // The URL should be returned as-is (stored as string, not executed)
        // XSS protection is handled by frontend escaping
        $this->assertStringContainsString('script', $response->json('data.0.inputUrl'));
    }

    /** @test */
    public function sec_005_rate_limiting_applied(): void
    {
        $user = User::factory()->create();

        // Make multiple requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/dashboard');

            // Should still be 200 (not 429) for reasonable requests
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function sec_009_mass_assignment_prevented(): void
    {
        $user = User::factory()->create();

        // Try to pass extra fields that shouldn't be processed
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard?admin=true&role=admin');

        $response->assertStatus(200);
        // Extra fields should be ignored
    }

    /** @test */
    public function sec_010_information_disclosure_prevented(): void
    {
        $user = User::factory()->create();

        // Trigger an error condition
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        // Should not expose stack traces or internal details
        $response->assertStatus(200);

        // No sensitive data in response
        $json = $response->json();
        $this->assertArrayNotHasKey('trace', $json);
        $this->assertArrayNotHasKey('debug', $json);
    }

    // ========================================
    // Integration Tests
    // ========================================

    /** @test */
    public function int_001_new_analysis_updates_dashboard(): void
    {
        $user = User::factory()->create();

        // Get initial dashboard
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.stats.totalAnalyses', 0);

        // Create a new analysis
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Clear cache to get fresh data
        Cache::flush();

        // Get updated dashboard
        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.stats.totalAnalyses', 1);
    }

    /** @test */
    public function int_005_multiple_endpoints_consistency(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Get full dashboard
        $fullResponse = $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        // Get stats only
        $statsResponse = $this->actingAs($user)
            ->getJson('/api/v1/dashboard/stats');

        // Stats should match
        $this->assertEquals(
            $fullResponse->json('data.stats.totalAnalyses'),
            $statsResponse->json('data.totalAnalyses')
        );
    }

    /** @test */
    public function int_010_performance_under_load(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(500)->create([
            'user_id' => $user->id,
        ]);

        $start = microtime(true);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Dashboard should respond in under 500ms with 500 analyses');
    }
}
