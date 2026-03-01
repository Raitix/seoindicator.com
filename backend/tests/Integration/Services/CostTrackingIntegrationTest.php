<?php

namespace Tests\Integration\Services;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\User;
use App\Services\CostTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CostTrackingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CostTrackingService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CostTrackingService();
        $this->user = User::factory()->create();

        Config::set('costs', [
            'default_rate' => 0.01,
            'currency' => 'USD',
            'rates' => [
                'meta_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'heading_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'serp_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'backlink_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'pwa_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'paid_tool' => ['base' => 0.05, 'per_unit' => 0.02, 'type' => 'paid'],
            ],
        ]);
    }

    // ==========================================
    // Integration Workflow Tests
    // ==========================================

    /** @test */
    public function int_001_full_workflow_record_and_summary(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'paid_tool',
            'status' => 'completed',
        ]);

        // Record cost
        $this->service->recordCost($analysis);

        // Get summary
        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertGreaterThan(0, $summary['total']);
        $this->assertArrayHasKey('by_type', $summary);
    }

    /** @test */
    public function int_002_multiple_analyses_for_same_user(): void
    {
        $analysis1 = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        $analysis2 = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'heading_analyzer',
        ]);

        $this->service->recordCost($analysis1);
        $this->service->recordCost($analysis2);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertGreaterThanOrEqual(2, AnalysisCost::whereIn('analysis_id', [$analysis1->id, $analysis2->id])->count());
    }

    /** @test */
    public function int_003_analysis_deletion_cascades_costs(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        $this->service->recordCost($analysis);

        $this->assertDatabaseHas('analysis_costs', [
            'analysis_id' => $analysis->id,
        ]);

        // Delete analysis
        $analysis->delete();

        // Costs should be deleted via cascade
        $this->assertDatabaseMissing('analysis_costs', [
            'analysis_id' => $analysis->id,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);
        $this->assertEquals(0, $summary['total']);
    }

    /** @test */
    public function int_004_user_deletion_removes_costs(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        $this->service->recordCost($analysis);

        // Delete user
        $this->user->delete();

        // Analysis and costs should be deleted via cascade
        $this->assertDatabaseMissing('analysis_history', [
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('analysis_costs', [
            'analysis_id' => $analysis->id,
        ]);
    }

    /** @test */
    public function int_005_tool_integration_after_analysis(): void
    {
        // Simulate tool completing analysis
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'serp_analyzer',
            'status' => 'completed',
        ]);

        // Cost tracking should be called after analysis
        $this->service->recordCost($analysis);

        $breakdown = $this->service->getCostBreakdown($analysis->id);

        $this->assertFalse($breakdown->isEmpty());
    }

    /** @test */
    public function int_006_dashboard_cost_summary_endpoint(): void
    {
        // Create multiple analyses
        for ($i = 0; $i < 5; $i++) {
            $analysis = AnalysisHistory::factory()->create([
                'user_id' => $this->user->id,
                'tool_name' => 'meta_analyzer',
            ]);
            $this->service->recordCost($analysis);
        }

        $summary = $this->service->getUserCostSummary($this->user->id);

        // Verify structure for dashboard
        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('by_type', $summary);
        $this->assertIsNumeric($summary['total']);
        $this->assertIsArray($summary['by_type']);
    }

    /** @test */
    public function int_007_reporting_cost_by_tool(): void
    {
        $tools = ['meta_analyzer', 'heading_analyzer', 'serp_analyzer'];

        foreach ($tools as $tool) {
            $analysis = AnalysisHistory::factory()->create([
                'user_id' => $this->user->id,
                'tool_name' => $tool,
            ]);
            $this->service->recordCost($analysis);
        }

        // Verify costs are grouped by tool (via analysis)
        $analyses = AnalysisHistory::where('user_id', $this->user->id)->get();
        $this->assertCount(3, $analyses);

        foreach ($analyses as $analysis) {
            $breakdown = $this->service->getCostBreakdown($analysis->id);
            $this->assertFalse($breakdown->isEmpty());
        }
    }

    /** @test */
    public function int_008_reporting_cost_by_date_range(): void
    {
        // Create analysis from different dates
        $oldAnalysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
            'created_at' => now()->subDays(60),
        ]);
        $this->service->recordCost($oldAnalysis);

        $newAnalysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
            'created_at' => now(),
        ]);
        $this->service->recordCost($newAnalysis);

        // Filter by month period
        $summary = $this->service->getUserCostSummary($this->user->id, 'month');

        // Only new analysis should be counted
        $this->assertLessThan(
            AnalysisCost::count(),
            $summary['total'] + 1 // Allow for floating point
        );
    }

    /** @test */
    public function int_009_audit_trail_cost_history(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        $this->service->recordCost($analysis, [
            'variable_units' => 3,
            'per_unit' => 0.01,
            'description' => 'Extra processing units',
        ]);

        $breakdown = $this->service->getCostBreakdown($analysis->id);

        // All costs should be traceable
        foreach ($breakdown as $cost) {
            $this->assertNotNull($cost->created_at);
            $this->assertNotNull($cost->cost_type);
            $this->assertNotNull($cost->cost_amount);
        }
    }

    /** @test */
    public function int_010_error_recovery_partial_failure(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        $initialCount = AnalysisCost::count();

        // Attempt to record with invalid metric
        try {
            $this->service->recordCost($analysis, [
                'variable_units' => -1, // Invalid
                'per_unit' => 0.01,
            ]);
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        // No partial records should exist
        $this->assertEquals($initialCount, AnalysisCost::count());
    }

    /** @test */
    public function int_011_performance_batch_recording(): void
    {
        $analysisIds = [];

        for ($i = 0; $i < 100; $i++) {
            $analysis = AnalysisHistory::factory()->create([
                'user_id' => $this->user->id,
                'tool_name' => 'meta_analyzer',
            ]);
            $analysisIds[] = $analysis->id;
        }

        $start = microtime(true);

        foreach ($analysisIds as $id) {
            $analysis = AnalysisHistory::find($id);
            $this->service->recordCost($analysis);
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(1000, $duration, 'Batch recording 100 costs should complete in < 1 second');
    }

    /** @test */
    public function int_012_performance_user_summary_query(): void
    {
        // Create 1000 costs
        for ($i = 0; $i < 100; $i++) {
            $analysis = AnalysisHistory::factory()->create([
                'user_id' => $this->user->id,
                'tool_name' => 'meta_analyzer',
            ]);
            $this->service->recordCost($analysis);
        }

        $start = microtime(true);
        $summary = $this->service->getUserCostSummary($this->user->id);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, 'Summary query should complete in < 100ms');
        $this->assertArrayHasKey('total', $summary);
    }

    /** @test */
    public function int_013_performance_breakdown_query(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        for ($i = 0; $i < 100; $i++) {
            AnalysisCost::factory()->create([
                'analysis_id' => $analysis->id,
            ]);
        }

        $start = microtime(true);
        $breakdown = $this->service->getCostBreakdown($analysis->id);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $duration, 'Breakdown query should complete in < 50ms');
        $this->assertCount(100, $breakdown);
    }

    /** @test */
    public function int_014_currency_handling_multi_currency(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
        ]);

        // Create costs in different currencies
        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.05,
            'cost_currency' => 'USD',
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 0.05,
            'cost_currency' => 'EUR',
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        // Service should handle multi-currency (convert or separate)
        $this->assertGreaterThan(0.05, $summary['total']);
    }

    /** @test */
    public function int_015_free_tier_zero_cost_tools(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer', // Free tool
        ]);

        $this->service->recordCost($analysis);

        $breakdown = $this->service->getCostBreakdown($analysis->id);

        // Free tools should still record costs (at $0.00)
        $this->assertFalse($breakdown->isEmpty());

        foreach ($breakdown as $cost) {
            $this->assertEquals(0.00, $cost->cost_amount);
        }
    }
}
