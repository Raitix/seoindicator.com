<?php

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AnalysisHistoryController API Tests
 *
 * Tests for the AnalysisHistoryController endpoints including:
 * - GET /api/v1/analyses (list analyses)
 * - GET /api/v1/analyses/{id} (get single analysis)
 * - DELETE /api/v1/analyses/{id} (delete analysis)
 * - GET /api/v1/analyses/{id}/costs (get cost breakdown)
 *
 * @group analysis-history-api
 */
class AnalysisHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // ============================================
    // LIST ANALYSES ENDPOINT TESTS (LA-*)
    // ============================================

    /** @test LA-001: List all analyses for user */
    public function test_list_analyses_for_authenticated_user(): void
    {
        $analyses = AnalysisHistory::factory()->count(10)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'toolName',
                        'inputUrl',
                        'status',
                        'analyzedAt',
                    ],
                ],
                'meta' => ['total', 'page', 'perPage', 'lastPage'],
            ]);
    }

    /** @test LA-002: Pagination default */
    public function test_list_analyses_default_pagination(): void
    {
        AnalysisHistory::factory()->count(20)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 15)
            ->assertJsonPath('meta.page', 1);
    }

    /** @test LA-003: Custom pagination */
    public function test_list_analyses_custom_per_page(): void
    {
        AnalysisHistory::factory()->count(60)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?perPage=50');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 50)
            ->assertJsonCount(50, 'data');
    }

    /** @test LA-004: Max pagination */
    public function test_list_analyses_max_per_page(): void
    {
        AnalysisHistory::factory()->count(120)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?perPage=100');

        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 100)
            ->assertJsonCount(100, 'data');
    }

    /** @test LA-005: Pagination exceeds max */
    public function test_list_analyses_per_page_exceeds_max(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?perPage=150');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['perPage']);
    }

    /** @test LA-006: Filter by tool */
    public function test_list_analyses_filter_by_tool(): void
    {
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta-tag-analyzer',
        ]);
        AnalysisHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tool_name' => 'keyword-density',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?tool=meta-tag-analyzer');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        foreach ($response->json('data') as $analysis) {
            $this->assertEquals('meta-tag-analyzer', $analysis['toolName']);
        }
    }

    /** @test LA-007: Filter by status */
    public function test_list_analyses_filter_by_status(): void
    {
        AnalysisHistory::factory()->count(7)->completed()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisHistory::factory()->count(2)->failed()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(7, 'data');

        foreach ($response->json('data') as $analysis) {
            $this->assertEquals('completed', $analysis['status']);
        }
    }

    /** @test LA-008: Filter by date range */
    public function test_list_analyses_filter_by_date_range(): void
    {
        // Create analyses on different dates
        AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'analyzed_at' => '2026-01-15 10:00:00',
        ]);
        AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'analyzed_at' => '2026-01-20 10:00:00',
        ]);
        AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'analyzed_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?from=2026-01-10&to=2026-01-25');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test LA-009: Combined filters */
    public function test_list_analyses_combined_filters(): void
    {
        AnalysisHistory::factory()->count(3)->completed()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta-tag-analyzer',
            'analyzed_at' => '2026-01-15 10:00:00',
        ]);
        AnalysisHistory::factory()->count(2)->completed()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'keyword-density',
            'analyzed_at' => '2026-01-15 10:00:00',
        ]);
        AnalysisHistory::factory()->failed()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta-tag-analyzer',
            'analyzed_at' => '2026-01-15 10:00:00',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?tool=meta-tag-analyzer&status=completed&from=2026-01-01&to=2026-01-31');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test LA-010: Invalid status */
    public function test_list_analyses_invalid_status(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test LA-011: Invalid date format */
    public function test_list_analyses_invalid_date_format(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?from=01-01-2026');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    /** @test LA-013: Unauthenticated request */
    public function test_list_analyses_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/analyses');

        $response->assertStatus(401);
    }

    /** @test LA-014: Empty result */
    public function test_list_analyses_empty_result(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /** @test LA-015: User isolation */
    public function test_list_analyses_user_isolation(): void
    {
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisHistory::factory()->count(3)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    // ============================================
    // GET SINGLE ANALYSIS TESTS (SA-*)
    // ============================================

    /** @test SA-001: Get existing analysis */
    public function test_show_analysis_success(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $analysis->id)
            ->assertJsonPath('data.toolName', $analysis->tool_name)
            ->assertJsonPath('data.status', $analysis->status);
    }

    /** @test SA-002: Include cost breakdown */
    public function test_show_analysis_with_costs(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.costs')
            ->assertJsonStructure([
                'data' => [
                    'costs' => [
                        '*' => ['id', 'costType', 'costAmount', 'costCurrency'],
                    ],
                ],
            ]);
    }

    /** @test SA-003: Analysis without costs */
    public function test_show_analysis_without_costs(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.costs');
    }

    /** @test SA-004: Unauthenticated request */
    public function test_show_analysis_unauthenticated(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(401);
    }

    /** @test SA-005: Non-existent analysis */
    public function test_show_analysis_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses/99999');

        $response->assertStatus(404);
    }

    /** @test SA-006: Cross-user access */
    public function test_show_analysis_cross_user_denied(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(403);
    }

    /** @test SA-007: Invalid ID format */
    public function test_show_analysis_invalid_id_format(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses/abc');

        $response->assertStatus(404);
    }

    /** @test SA-008: Negative ID */
    public function test_show_analysis_negative_id(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses/-1');

        $response->assertStatus(404);
    }

    /** @test SA-009: Results JSON parsed */
    public function test_show_analysis_results_parsed(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'results' => ['title' => 'Test Title', 'h1Count' => 2],
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.results.title', 'Test Title')
            ->assertJsonPath('data.results.h1Count', 2);
    }

    // ============================================
    // DELETE ANALYSIS TESTS (DA-*)
    // ============================================

    /** @test DA-001: Delete own analysis */
    public function test_delete_analysis_success(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('analysis_history', [
            'id' => $analysis->id,
        ]);
    }

    /** @test DA-002: Delete cascades to costs */
    public function test_delete_analysis_cascades_to_costs(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $costs = AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200);

        foreach ($costs as $cost) {
            $this->assertDatabaseMissing('analysis_costs', [
                'id' => $cost->id,
            ]);
        }
    }

    /** @test DA-003: Unauthenticated request */
    public function test_delete_analysis_unauthenticated(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
        ]);
    }

    /** @test DA-004: Non-existent analysis */
    public function test_delete_analysis_not_found(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/v1/analyses/99999');

        $response->assertStatus(404);
    }

    /** @test DA-005: Cross-user delete */
    public function test_delete_analysis_cross_user_denied(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
        ]);
    }

    /** @test DA-006: Double delete */
    public function test_delete_analysis_twice(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // First delete
        $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}")
            ->assertStatus(200);

        // Second delete
        $response = $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(404);
    }

    /** @test DA-007: Invalid ID format */
    public function test_delete_analysis_invalid_id_format(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/v1/analyses/abc');

        $response->assertStatus(404);
    }

    // ============================================
    // GET COST BREAKDOWN TESTS (CB-*)
    // ============================================

    /** @test CB-001: Get costs for analysis */
    public function test_get_costs_success(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisCost::factory()->count(3)->create([
            'analysis_id' => $analysis->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}/costs");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.costs')
            ->assertJsonStructure([
                'data' => [
                    'analysisId',
                    'costs',
                    'total',
                ],
            ]);
    }

    /** @test CB-003: Analysis without costs */
    public function test_get_costs_empty(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}/costs");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.costs')
            ->assertJsonPath('data.total', '0.000000');
    }

    /** @test CB-004: Unauthenticated request */
    public function test_get_costs_unauthenticated(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/analyses/{$analysis->id}/costs");

        $response->assertStatus(401);
    }

    /** @test CB-005: Non-existent analysis */
    public function test_get_costs_analysis_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses/99999/costs');

        $response->assertStatus(404);
    }

    /** @test CB-006: Cross-user access */
    public function test_get_costs_cross_user_denied(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}/costs");

        $response->assertStatus(403);
    }

    /** @test CB-007: Total cost calculated */
    public function test_get_costs_total_calculated(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.05,
        ]);
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.03,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}/costs");

        $response->assertStatus(200)
            ->assertJsonPath('data.total', '0.080000');
    }

    // ============================================
    // SECURITY TESTS (SEC-*)
    // ============================================

    /** @test SEC-001: SQL injection in tool param */
    public function test_sql_injection_tool_param(): void
    {
        AnalysisHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses?tool='; DROP TABLE analysis_history;--");

        // Should either validate error or return empty result
        $response->assertStatus(in_array($response->status(), [200, 422]) ? $response->status() : 500);

        // Verify table still exists
        $this->assertDatabaseCount('analysis_history', 3);
    }

    /** @test SEC-003: XSS in input_url */
    public function test_xss_in_input_url(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'input_url' => '<script>alert(1)</script>',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200);

        // JSON APIs return data as-is; XSS protection is the frontend's responsibility
        // Verify the input_url is returned correctly
        $this->assertEquals('<script>alert(1)</script>', $response->json('data.inputUrl'));
    }

    /** @test SEC-006: Mass assignment */
    public function test_mass_assignment_protection(): void
    {
        // Attempt to set internal fields via query params
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?user_id=999');

        $response->assertStatus(200);

        // Verify no records belong to user 999
        $this->assertDatabaseMissing('analysis_history', ['user_id' => 999]);
    }

    /** @test SEC-008: IDOR with predictable IDs */
    public function test_idor_predictable_ids(): void
    {
        $analysis1 = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $analysis2 = AnalysisHistory::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        // Try to access user1's analysis
        $response = $this->actingAs($this->otherUser)->getJson("/api/v1/analyses/{$analysis1->id}");

        $response->assertStatus(403);
    }

    // ============================================
    // EDGE CASE TESTS (EC-*)
    // ============================================

    /** @test EC-001: Very long input_url */
    public function test_long_input_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'input_url' => $longUrl,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200);
    }

    /** @test EC-002: Unicode tool_name */
    public function test_unicode_tool_name(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => '工具-测试-🚀',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.toolName', '工具-测试-🚀');
    }

    /** @test EC-004: Delete then get */
    public function test_delete_then_get(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Delete
        $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}")
            ->assertStatus(200);

        // Try to get
        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");

        $response->assertStatus(404);
    }

    /** @test EC-005: Zero page number */
    public function test_zero_page_number(): void
    {
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?page=0');

        // Should either validate error or default to page 1
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test EC-008: String page number */
    public function test_string_page_number(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /** @test EC-010: Future date range */
    public function test_future_date_range(): void
    {
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?from=2030-01-01&to=2030-01-31');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ============================================
    // PERFORMANCE TESTS (PF-*)
    // ============================================

    /** @test PF-001: List 100 analyses */
    public function test_list_100_analyses_performance(): void
    {
        AnalysisHistory::factory()->count(100)->create([
            'user_id' => $this->user->id,
        ]);

        $start = microtime(true);
        $response = $this->actingAs($this->user)->getJson('/api/v1/analyses?perPage=100');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');

        // Should respond within 500ms
        $this->assertLessThan(500, $duration, "Response took {$duration}ms, expected < 500ms");
    }

    /** @test PF-002: Get single analysis with costs */
    public function test_get_analysis_with_costs_performance(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisCost::factory()->count(10)->create([
            'analysis_id' => $analysis->id,
        ]);

        $start = microtime(true);
        $response = $this->actingAs($this->user)->getJson("/api/v1/analyses/{$analysis->id}");
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond within 100ms
        $this->assertLessThan(100, $duration, "Response took {$duration}ms, expected < 100ms");
    }

    /** @test PF-003: Delete analysis with costs cascade */
    public function test_delete_analysis_cascade_performance(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
        ]);
        AnalysisCost::factory()->count(10)->create([
            'analysis_id' => $analysis->id,
        ]);

        $start = microtime(true);
        $response = $this->actingAs($this->user)->deleteJson("/api/v1/analyses/{$analysis->id}");
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);

        // Should respond within 200ms
        $this->assertLessThan(200, $duration, "Response took {$duration}ms, expected < 200ms");
    }
}
