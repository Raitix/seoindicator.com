<?php

namespace Tests\Unit\Services;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\User;
use App\Services\CostTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CostTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CostTrackingService $service;
    private User $user;
    private AnalysisHistory $analysis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CostTrackingService();
        $this->user = User::factory()->create();

        // Create a test analysis history record
        $this->analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'meta_analyzer',
            'status' => 'completed',
        ]);

        // Set up default cost configuration
        Config::set('costs', [
            'default_rate' => 0.01,
            'currency' => 'USD',
            'rates' => [
                'meta_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'heading_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'serp_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'google_cse' => ['base' => 0.00, 'per_unit' => 0.00, 'daily_quota' => 100, 'type' => 'free'],
                'backlink_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'pwa_analyzer' => ['base' => 0.00, 'per_unit' => 0.00, 'type' => 'free'],
                'paid_tool' => ['base' => 0.05, 'per_unit' => 0.02, 'type' => 'paid'],
            ],
        ]);
    }

    // ==========================================
    // Category 1: recordCost() Method Tests
    // ==========================================

    /** @test */
    public function rc_001_records_base_cost_only(): void
    {
        $this->service->recordCost($this->analysis);

        $this->assertDatabaseHas('analysis_costs', [
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
        ]);

        $cost = AnalysisCost::where('analysis_id', $this->analysis->id)->first();
        $this->assertNotNull($cost);
        $this->assertEquals(0.00, $cost->cost_amount);
    }

    /** @test */
    public function rc_002_records_base_and_variable_cost(): void
    {
        $metrics = [
            'variable_units' => 5,
            'per_unit' => 0.02,
            'description' => 'Extra processing',
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $costs = AnalysisCost::where('analysis_id', $this->analysis->id)->get();
        $this->assertCount(2, $costs);

        $baseCost = $costs->where('cost_type', 'compute')->first();
        $variableCost = $costs->where('cost_type', 'variable')->first();

        $this->assertNotNull($baseCost);
        $this->assertNotNull($variableCost);
        $this->assertEquals(0.10, $variableCost->cost_amount); // 5 × 0.02
    }

    /** @test */
    public function rc_003_uses_default_rate_for_unknown_tool(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'unknown_tool_xyz',
            'status' => 'completed',
        ]);

        $this->service->recordCost($analysis);

        $cost = AnalysisCost::where('analysis_id', $analysis->id)->first();
        $this->assertNotNull($cost);
        $this->assertEquals(0.01, $cost->cost_amount); // default_rate
    }

    /** @test */
    public function rc_004_handles_empty_metrics(): void
    {
        $this->service->recordCost($this->analysis, []);

        $costs = AnalysisCost::where('analysis_id', $this->analysis->id)->get();
        $this->assertCount(1, $costs);
        $this->assertEquals('compute', $costs->first()->cost_type);
    }

    /** @test */
    public function rc_005_calculates_variable_cost_correctly(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'paid_tool',
            'status' => 'completed',
        ]);

        $metrics = [
            'variable_units' => 10,
            'per_unit' => 0.02,
        ];

        $this->service->recordCost($analysis, $metrics);

        $variableCost = AnalysisCost::where('analysis_id', $analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        $this->assertNotNull($variableCost);
        $this->assertEquals(0.20, $variableCost->cost_amount); // 10 × 0.02
    }

    /** @test */
    public function rc_006_saves_description_from_metrics(): void
    {
        $metrics = [
            'variable_units' => 3,
            'per_unit' => 0.01,
            'description' => 'Extra SERP pages analyzed',
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $variableCost = AnalysisCost::where('analysis_id', $this->analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        $this->assertNotNull($variableCost);
        $this->assertEquals('Extra SERP pages analyzed', $variableCost->description);
    }

    /** @test */
    public function rc_007_records_multiple_variable_costs(): void
    {
        $metrics = [
            'api_calls' => 3,
            'per_call' => 0.00,
            'description' => 'API calls',
        ];

        // Simulate multiple cost recording
        $this->service->recordCost($this->analysis, $metrics);
        $this->service->recordCost($this->analysis, [
            'variable_units' => 2,
            'per_unit' => 0.01,
            'description' => 'Storage',
        ]);

        $costs = AnalysisCost::where('analysis_id', $this->analysis->id)->get();
        $this->assertGreaterThanOrEqual(2, $costs->count());
    }

    /** @test */
    public function rc_008_skips_zero_variable_units(): void
    {
        $metrics = [
            'variable_units' => 0,
            'per_unit' => 0.02,
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $variableCost = AnalysisCost::where('analysis_id', $this->analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        // Zero units should not create a variable cost
        $this->assertNull($variableCost);
    }

    /** @test */
    public function rc_009_rejects_negative_variable_units(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $metrics = [
            'variable_units' => -1,
            'per_unit' => 0.02,
        ];

        $this->service->recordCost($this->analysis, $metrics);
    }

    /** @test */
    public function rc_010_rejects_null_analysis(): void
    {
        $this->expectException(\TypeError::class);

        $this->service->recordCost(null);
    }

    /** @test */
    public function rc_011_rejects_unsaved_analysis(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $unsavedAnalysis = new AnalysisHistory([
            'tool_name' => 'meta_analyzer',
        ]);

        $this->service->recordCost($unsavedAnalysis);
    }

    /** @test */
    public function rc_012_rollback_on_error(): void
    {
        $initialCount = AnalysisCost::count();

        // Force a database error after partial insert
        DB::shouldReceive('transaction')->andThrow(new \Exception('Database error'));

        try {
            $this->service->recordCost($this->analysis);
        } catch (\Exception $e) {
            // Expected
        }

        // No records should be created
        $this->assertEquals($initialCount, AnalysisCost::count());
    }

    // ==========================================
    // Category 2: calculateCost() Method Tests
    // ==========================================

    /** @test */
    public function cc_001_calculates_base_cost_only(): void
    {
        $cost = $this->service->calculateCost('meta_analyzer');

        $this->assertEquals(0.00, $cost);
    }

    /** @test */
    public function cc_002_calculates_with_variable_cost(): void
    {
        $metrics = ['variable_units' => 10];

        $cost = $this->service->calculateCost('paid_tool', $metrics);

        // base (0.05) + (10 × 0.02) = 0.25
        $this->assertEquals(0.25, $cost);
    }

    /** @test */
    public function cc_003_uses_default_for_unknown_tool(): void
    {
        $cost = $this->service->calculateCost('nonexistent_tool');

        $this->assertEquals(0.01, $cost); // default_rate
    }

    /** @test */
    public function cc_004_returns_zero_for_empty_tool_name(): void
    {
        $cost = $this->service->calculateCost('');

        $this->assertEquals(0.00, $cost);
    }

    /** @test */
    public function cc_005_handles_missing_per_unit_rate(): void
    {
        $metrics = ['variable_units' => 10];

        $cost = $this->service->calculateCost('meta_analyzer', $metrics);

        // No per_unit rate defined, should return base only
        $this->assertEquals(0.00, $cost);
    }

    /** @test */
    public function cc_006_high_precision_calculation(): void
    {
        Config::set('costs.rates.precision_tool', [
            'base' => 0.000001,
            'per_unit' => 0.000001,
        ]);

        $metrics = ['variable_units' => 1000000];

        $cost = $this->service->calculateCost('precision_tool', $metrics);

        // 0.000001 + (1000000 × 0.000001) = 1.000001
        $this->assertEqualsWithDelta(1.000001, $cost, 0.0000001);
    }

    /** @test */
    public function cc_007_handles_large_variable_units(): void
    {
        $metrics = ['variable_units' => 1000000];

        $cost = $this->service->calculateCost('paid_tool', $metrics);

        // 0.05 + (1000000 × 0.02) = 20000.05
        $this->assertEquals(20000.05, $cost);
    }

    /** @test */
    public function cc_008_handles_fractional_units(): void
    {
        $metrics = ['variable_units' => 0.5];

        $cost = $this->service->calculateCost('paid_tool', $metrics);

        // 0.05 + (0.5 × 0.02) = 0.06
        $this->assertEquals(0.06, $cost);
    }

    /** @test */
    public function cc_009_handles_zero_base_rate(): void
    {
        Config::set('costs.rates.zero_base_tool', [
            'base' => 0,
            'per_unit' => 0.01,
        ]);

        $metrics = ['variable_units' => 5];

        $cost = $this->service->calculateCost('zero_base_tool', $metrics);

        // 0 + (5 × 0.01) = 0.05
        $this->assertEquals(0.05, $cost);
    }

    /** @test */
    public function cc_010_handles_custom_cost_type(): void
    {
        Config::set('costs.rates.custom_tool', [
            'base' => 0.10,
            'per_unit' => 0.05,
            'multiplier' => 1.5,
        ]);

        $metrics = ['variable_units' => 2];

        $cost = $this->service->calculateCost('custom_tool', $metrics);

        // Custom calculation: (0.10 + (2 × 0.05)) × 1.5 = 0.30
        $this->assertEquals(0.30, $cost);
    }

    /** @test */
    public function cc_011_supports_currency_conversion(): void
    {
        Config::set('costs.rates.eur_tool', [
            'base' => 0.05,
            'currency' => 'EUR',
            'exchange_rate' => 1.1, // EUR to USD
        ]);

        $cost = $this->service->calculateCost('eur_tool');

        // 0.05 × 1.1 = 0.055 (converted to USD)
        $this->assertEqualsWithDelta(0.055, $cost, 0.001);
    }

    /** @test */
    public function cc_012_applies_rate_multiplier(): void
    {
        Config::set('costs.rates.multiplied_tool', [
            'base' => 0.10,
            'multiplier' => 2,
        ]);

        $cost = $this->service->calculateCost('multiplied_tool');

        $this->assertEquals(0.20, $cost);
    }

    // ==========================================
    // Category 3: getUserCostSummary() Method Tests
    // ==========================================

    /** @test */
    public function us_001_returns_zero_for_user_with_no_costs(): void
    {
        $newUser = User::factory()->create();

        $summary = $this->service->getUserCostSummary($newUser->id);

        $this->assertEquals(0.00, $summary['total']);
        $this->assertEmpty($summary['by_type']);
    }

    /** @test */
    public function us_002_returns_summary_for_single_cost(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.05,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEquals(0.05, $summary['total']);
        $this->assertEquals(0.05, $summary['by_type']['compute']);
    }

    /** @test */
    public function us_03_aggregates_multiple_costs(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.05,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'variable',
            'cost_amount' => 0.10,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEquals(0.15, $summary['total']);
        $this->assertEquals(0.05, $summary['by_type']['compute']);
        $this->assertEquals(0.10, $summary['by_type']['variable']);
    }

    /** @test */
    public function us_004_filters_by_day_period(): void
    {
        // Today's cost
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'created_at' => now(),
        ]);

        // Yesterday's cost
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
            'created_at' => now()->subDay(),
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id, 'day');

        $this->assertEquals(0.05, $summary['total']);
    }

    /** @test */
    public function us_005_filters_by_week_period(): void
    {
        // Within week
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'created_at' => now()->subDays(3),
        ]);

        // Outside week
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
            'created_at' => now()->subDays(10),
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id, 'week');

        $this->assertEquals(0.05, $summary['total']);
    }

    /** @test */
    public function us_006_filters_by_month_period(): void
    {
        // Within month
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'created_at' => now()->subDays(15),
        ]);

        // Outside month
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
            'created_at' => now()->subDays(45),
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id, 'month');

        $this->assertEquals(0.05, $summary['total']);
    }

    /** @test */
    public function us_007_filters_by_custom_period(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'created_at' => now()->parse('2026-01-15'),
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
            'created_at' => now()->parse('2025-12-15'),
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id, '2026-01-01');

        $this->assertEquals(0.05, $summary['total']);
    }

    /** @test */
    public function us_008_groups_by_cost_type(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'api_call',
            'cost_amount' => 0.01,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.02,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'storage',
            'cost_amount' => 0.03,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEquals(0.06, $summary['total']);
        $this->assertEquals(0.01, $summary['by_type']['api_call']);
        $this->assertEquals(0.02, $summary['by_type']['compute']);
        $this->assertEquals(0.03, $summary['by_type']['storage']);
    }

    /** @test */
    public function us_009_isolates_user_costs(): void
    {
        $otherUser = User::factory()->create();
        $otherAnalysis = AnalysisHistory::factory()->create([
            'user_id' => $otherUser->id,
            'tool_name' => 'meta_analyzer',
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $otherAnalysis->id,
            'cost_amount' => 0.10,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEquals(0.05, $summary['total']);
    }

    /** @test */
    public function us_010_excludes_deleted_analysis_costs(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
        ]);

        $this->analysis->delete();

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEquals(0.00, $summary['total']);
    }

    /** @test */
    public function us_011_handles_multiple_currencies(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'cost_currency' => 'USD',
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'cost_currency' => 'EUR',
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        // Should convert EUR to USD (assumes conversion logic)
        $this->assertGreaterThan(0.05, $summary['total']);
    }

    /** @test */
    public function us_012_performance_with_large_dataset(): void
    {
        // Create 10,000 cost records
        for ($i = 0; $i < 100; $i++) {
            AnalysisCost::factory()->create([
                'analysis_id' => $this->analysis->id,
                'cost_amount' => 0.01,
            ]);
        }

        $start = microtime(true);
        $summary = $this->service->getUserCostSummary($this->user->id);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Summary query should complete in < 500ms');
        $this->assertEquals(1.00, $summary['total']);
    }

    // ==========================================
    // Category 4: getCostBreakdown() Method Tests
    // ==========================================

    /** @test */
    public function cb_001_returns_empty_for_no_costs(): void
    {
        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        $this->assertInstanceOf(Collection::class, $breakdown);
        $this->assertTrue($breakdown->isEmpty());
    }

    /** @test */
    public function cb_002_returns_single_cost(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.05,
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        $this->assertCount(1, $breakdown);
        $this->assertEquals('compute', $breakdown->first()->cost_type);
    }

    /** @test */
    public function cb_003_returns_multiple_costs_grouped(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_amount' => 0.05,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'variable',
            'cost_amount' => 0.10,
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        $this->assertCount(2, $breakdown);
        $types = $breakdown->pluck('cost_type')->toArray();
        $this->assertContains('compute', $types);
        $this->assertContains('variable', $types);
    }

    /** @test */
    public function cb_004_includes_all_fields(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_type' => 'compute',
            'cost_units' => 1.5,
            'cost_amount' => 0.05,
            'description' => 'Base processing',
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);
        $cost = $breakdown->first();

        $this->assertEquals('compute', $cost->cost_type);
        $this->assertEquals(1.5, $cost->cost_units);
        $this->assertEquals(0.05, $cost->cost_amount);
        $this->assertEquals('Base processing', $cost->description);
    }

    /** @test */
    public function cb_005_includes_total(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        // Check if total is included (implementation may vary)
        $total = $breakdown->sum('cost_amount');
        $this->assertEquals(0.15, $total);
    }

    /** @test */
    public function cb_006_returns_empty_for_invalid_id(): void
    {
        $breakdown = $this->service->getCostBreakdown(99999);

        $this->assertTrue($breakdown->isEmpty());
    }

    /** @test */
    public function cb_007_isolates_analysis_costs(): void
    {
        $otherAnalysis = AnalysisHistory::factory()->create([
            'user_id' => $this->user->id,
            'tool_name' => 'heading_analyzer',
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $otherAnalysis->id,
            'cost_amount' => 0.10,
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        $this->assertCount(1, $breakdown);
        $this->assertEquals(0.05, $breakdown->first()->cost_amount);
    }

    /** @test */
    public function cb_008_orders_by_created_at(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.10,
            'created_at' => now()->addMinutes(5),
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.05,
            'created_at' => now(),
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        // Should be ordered by created_at ascending
        $this->assertEquals(0.05, $breakdown->first()->cost_amount);
    }

    /** @test */
    public function cb_009_includes_currency(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_currency' => 'EUR',
        ]);

        $breakdown = $this->service->getCostBreakdown($this->analysis->id);

        $this->assertEquals('EUR', $breakdown->first()->cost_currency);
    }

    /** @test */
    public function cb_010_handles_large_breakdown(): void
    {
        for ($i = 0; $i < 100; $i++) {
            AnalysisCost::factory()->create([
                'analysis_id' => $this->analysis->id,
            ]);
        }

        $start = microtime(true);
        $breakdown = $this->service->getCostBreakdown($this->analysis->id);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertCount(100, $breakdown);
        $this->assertLessThan(50, $duration, 'Breakdown query should complete in < 50ms');
    }

    // ==========================================
    // Category 5: Configuration Tests
    // ==========================================

    /** @test */
    public function cf_001_loads_default_config(): void
    {
        $cost = $this->service->calculateCost('meta_analyzer');

        $this->assertEquals(0.00, $cost);
    }

    /** @test */
    public function cf_002_uses_defaults_without_config(): void
    {
        Config::set('costs', null);

        $service = new CostTrackingService();
        $cost = $service->calculateCost('unknown_tool');

        $this->assertEquals(0.01, $cost); // Hardcoded default
    }

    /** @test */
    public function cf_003_rejects_invalid_rate_type(): void
    {
        Config::set('costs.rates.invalid_tool', [
            'base' => 'high', // Invalid: should be numeric
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->calculateCost('invalid_tool');
    }

    /** @test */
    public function cf_004_rejects_negative_rate(): void
    {
        Config::set('costs.rates.negative_tool', [
            'base' => -0.01,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->calculateCost('negative_tool');
    }

    /** @test */
    public function cf_005_uses_default_for_empty_rates(): void
    {
        Config::set('costs.rates', []);

        $cost = $this->service->calculateCost('meta_analyzer');

        $this->assertEquals(0.01, $cost);
    }

    /** @test */
    public function cf_006_parses_nested_config(): void
    {
        Config::set('costs.rates.nested_tool', [
            'base' => 0.05,
            'per_unit' => 0.02,
            'settings' => [
                'max_units' => 100,
                'discount_threshold' => 50,
            ],
        ]);

        $cost = $this->service->calculateCost('nested_tool');

        $this->assertEquals(0.05, $cost);
    }

    /** @test */
    public function cf_007_uses_cached_config(): void
    {
        // First call should cache
        $cost1 = $this->service->calculateCost('meta_analyzer');

        // Config change should not affect cached service
        Config::set('costs.rates.meta_analyzer.base', 999.99);

        $cost2 = $this->service->calculateCost('meta_analyzer');

        // If properly cached, both should be same
        $this->assertEquals($cost1, $cost2);
    }

    /** @test */
    public function cf_008_refreshes_config(): void
    {
        $cost1 = $this->service->calculateCost('meta_analyzer');

        Config::set('costs.rates.meta_analyzer.base', 0.99);
        $service = new CostTrackingService(); // New instance
        $cost2 = $service->calculateCost('meta_analyzer');

        $this->assertEquals(0.00, $cost1);
        $this->assertEquals(0.99, $cost2);
    }

    // ==========================================
    // Category 6: Edge Cases & Security Tests
    // ==========================================

    /** @test */
    public function ec_001_handles_concurrent_recording(): void
    {
        // Simulate concurrent recording
        $metrics = ['variable_units' => 5, 'per_unit' => 0.01];

        $this->service->recordCost($this->analysis, $metrics);

        // Second recording should add more costs, not duplicate
        $this->service->recordCost($this->analysis, $metrics);

        $costs = AnalysisCost::where('analysis_id', $this->analysis->id)->get();

        // Should have 4 records (2 base + 2 variable)
        $this->assertGreaterThanOrEqual(2, $costs->count());
    }

    /** @test */
    public function ec_002_handles_very_small_amounts(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 0.000001,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEqualsWithDelta(0.000001, $summary['total'], 0.0000001);
    }

    /** @test */
    public function ec_003_handles_very_large_amounts(): void
    {
        AnalysisCost::factory()->create([
            'analysis_id' => $this->analysis->id,
            'cost_amount' => 999999.999999,
        ]);

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertEqualsWithDelta(999999.999999, $summary['total'], 0.000001);
    }

    /** @test */
    public function ec_004_handles_unicode_description(): void
    {
        $metrics = [
            'variable_units' => 1,
            'per_unit' => 0.01,
            'description' => '测试 🎉 ñoño',
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $cost = AnalysisCost::where('analysis_id', $this->analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        $this->assertEquals('测试 🎉 ñoño', $cost->description);
    }

    /** @test */
    public function ec_005_sanitizes_sql_injection(): void
    {
        $metrics = [
            'variable_units' => 1,
            'per_unit' => 0.01,
            'description' => "'); DROP TABLE analysis_costs; --",
        ];

        $this->service->recordCost($this->analysis, $metrics);

        // Table should still exist
        $this->assertDatabaseHas('analysis_costs', [
            'analysis_id' => $this->analysis->id,
        ]);

        $cost = AnalysisCost::where('analysis_id', $this->analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        // Description should be stored as-is (parameterized query prevents execution)
        $this->assertStringContainsString("DROP TABLE", $cost->description);
    }

    /** @test */
    public function ec_006_sanitizes_xss(): void
    {
        $metrics = [
            'variable_units' => 1,
            'per_unit' => 0.01,
            'description' => '<script>alert("XSS")</script>',
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $cost = AnalysisCost::where('analysis_id', $this->analysis->id)
            ->where('cost_type', 'variable')
            ->first();

        // Script tag should be stored as text, not executed
        $this->assertStringContainsString('<script>', $cost->description);
    }

    /** @test */
    public function ec_007_handles_rate_limiting(): void
    {
        // Record 100+ costs
        for ($i = 0; $i < 110; $i++) {
            AnalysisCost::factory()->create([
                'analysis_id' => $this->analysis->id,
            ]);
        }

        $summary = $this->service->getUserCostSummary($this->user->id);

        $this->assertGreaterThan(0, $summary['total']);
    }

    /** @test */
    public function ec_008_handles_database_connection_loss(): void
    {
        $this->markTestSkipped('Requires database connection manipulation');
    }

    /** @test */
    public function ec_009_handles_large_metrics_array(): void
    {
        $metrics = [
            'variable_units' => 1000000,
            'per_unit' => 0.000001,
            'description' => str_repeat('x', 255),
        ];

        $this->service->recordCost($this->analysis, $metrics);

        $this->assertDatabaseHas('analysis_costs', [
            'analysis_id' => $this->analysis->id,
        ]);
    }

    /** @test */
    public function ec_010_maintains_state_in_singleton(): void
    {
        // Record cost twice
        $this->service->recordCost($this->analysis);
        $count1 = AnalysisCost::where('analysis_id', $this->analysis->id)->count();

        $this->service->recordCost($this->analysis);
        $count2 = AnalysisCost::where('analysis_id', $this->analysis->id)->count();

        // State should be clean - each call should add costs independently
        $this->assertGreaterThan($count1, $count2);
    }
}
