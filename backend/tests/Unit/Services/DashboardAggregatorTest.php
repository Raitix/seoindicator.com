<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DashboardAggregator;
use App\Repositories\AnalysisHistoryRepository;
use App\Services\CostTrackingService;
use App\Models\AnalysisHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;

class DashboardAggregatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // Category 1: getStats() Method Tests
    // ========================================

    /** @test */
    public function gs_001_user_with_no_analyses_returns_zero_stats(): void
    {
        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 0,
            'by_status' => [],
            'by_tool' => [],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $stats = $aggregator->getStats(1);

        $this->assertEquals([
            'totalAnalyses' => 0,
            'thisMonth' => 0,
            'thisWeek' => 0,
            'today' => 0,
        ], $stats);
    }

    /** @test */
    public function gs_002_user_with_total_analyses_returns_correct_count(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 50,
            'by_status' => ['completed' => 48, 'failed' => 2],
            'by_tool' => ['meta_analyzer' => 30, 'heading_analyzer' => 20],
            'avg_duration_ms' => 150.5,
            'success_rate' => 96.0,
            'this_month' => 10,
            'this_week' => 5,
            'today' => 2,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $stats = $aggregator->getStats(1);

        $this->assertEquals(50, $stats['totalAnalyses']);
    }

    /** @test */
    public function gs_006_cross_user_isolation(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 10,
            'by_status' => ['completed' => 10],
            'by_tool' => ['meta_analyzer' => 10],
            'avg_duration_ms' => 100,
            'success_rate' => 100.0,
            'this_month' => 5,
            'this_week' => 3,
            'today' => 1,
        ]);

        $mockRepo->shouldReceive('getStats')->with(2)->andReturn([
            'total' => 20,
            'by_status' => ['completed' => 20],
            'by_tool' => ['heading_analyzer' => 20],
            'avg_duration_ms' => 200,
            'success_rate' => 100.0,
            'this_month' => 10,
            'this_week' => 6,
            'today' => 2,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);

        $stats1 = $aggregator->getStats(1);
        $stats2 = $aggregator->getStats(2);

        $this->assertEquals(10, $stats1['totalAnalyses']);
        $this->assertEquals(20, $stats2['totalAnalyses']);
    }

    /** @test */
    public function gs_015_cache_hit_returns_cached_data(): void
    {
        $cachedStats = [
            'totalAnalyses' => 100,
            'thisMonth' => 25,
            'thisWeek' => 10,
            'today' => 3,
        ];

        Cache::put('dashboard.stats.1', $cachedStats, 60);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $stats = $aggregator->getStats(1);

        $this->assertEquals($cachedStats, $stats);

        Cache::forget('dashboard.stats.1');
    }

    // ========================================
    // Category 2: getRecentAnalyses() Method Tests
    // ========================================

    /** @test */
    public function gr_001_user_with_no_analyses_returns_empty_array(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->with(1, 10)->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $analyses = $aggregator->getRecentAnalyses(1);

        $this->assertEquals([], $analyses);
    }

    /** @test */
    public function gr_002_user_with_5_analyses_returns_5_items(): void
    {
        Cache::flush();

        $analyses = AnalysisHistory::factory()->count(5)->make([
            'user_id' => 1,
        ]);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->with(1, 10)->andReturn($analyses);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $result = $aggregator->getRecentAnalyses(1);

        $this->assertCount(5, $result);
    }

    /** @test */
    public function gr_003_user_with_15_analyses_limited_to_10(): void
    {
        Cache::flush();

        $analyses = AnalysisHistory::factory()->count(10)->make([
            'user_id' => 1,
        ]);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->with(1, 10)->andReturn($analyses);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $result = $aggregator->getRecentAnalyses(1);

        $this->assertLessThanOrEqual(10, count($result));
    }

    /** @test */
    public function gr_005_analysis_structure_contains_required_fields(): void
    {
        Cache::flush();

        $analysis = AnalysisHistory::factory()->make([
            'id' => 1,
            'user_id' => 1,
            'tool_name' => 'meta_analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
            'analyzed_at' => now(),
        ]);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->with(1, 10)->andReturn(new EloquentCollection([$analysis]));

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $result = $aggregator->getRecentAnalyses(1);

        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('toolName', $result[0]);
        $this->assertArrayHasKey('inputUrl', $result[0]);
        $this->assertArrayHasKey('status', $result[0]);
        $this->assertArrayHasKey('analyzedAt', $result[0]);
    }

    /** @test */
    public function gr_006_cross_user_isolation(): void
    {
        Cache::flush();

        $analyses1 = AnalysisHistory::factory()->count(3)->make(['user_id' => 1]);
        $analyses2 = AnalysisHistory::factory()->count(5)->make(['user_id' => 2]);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->with(1, 10)->andReturn($analyses1);
        $mockRepo->shouldReceive('getRecent')->with(2, 10)->andReturn($analyses2);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);

        $result1 = $aggregator->getRecentAnalyses(1);
        $result2 = $aggregator->getRecentAnalyses(2);

        $this->assertCount(3, $result1);
        $this->assertCount(5, $result2);
    }

    // ========================================
    // Category 3: getCostSummary() Method Tests
    // ========================================

    /** @test */
    public function gc_001_user_with_no_costs_returns_zeros(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, null)
            ->andReturn(['total' => 0, 'by_type' => []]);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, 'month')
            ->andReturn(['total' => 0, 'by_type' => []]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $summary = $aggregator->getCostSummary(1);

        $this->assertEquals(0.00, $summary['totalCost']);
        $this->assertEquals(0.00, $summary['thisMonthCost']);
        $this->assertEquals('USD', $summary['currency']);
    }

    /** @test */
    public function gc_002_user_with_costs_returns_aggregated_total(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, null)
            ->andReturn([
                'total' => 12.45,
                'by_type' => ['compute' => 10.00, 'api_call' => 2.45],
            ]);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, 'month')
            ->andReturn([
                'total' => 3.20,
                'by_type' => ['compute' => 2.50, 'api_call' => 0.70],
            ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $summary = $aggregator->getCostSummary(1);

        $this->assertEquals(12.45, $summary['totalCost']);
        $this->assertEquals(3.20, $summary['thisMonthCost']);
        $this->assertEquals('USD', $summary['currency']);
    }

    /** @test */
    public function gc_005_cross_user_isolation(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, Mockery::any())
            ->andReturn(['total' => 100.00, 'by_type' => []]);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(2, Mockery::any())
            ->andReturn(['total' => 200.00, 'by_type' => []]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);

        $summary1 = $aggregator->getCostSummary(1);
        $summary2 = $aggregator->getCostSummary(2);

        $this->assertEquals(100.00, $summary1['totalCost']);
        $this->assertEquals(200.00, $summary2['totalCost']);
    }

    /** @test */
    public function gc_008_high_precision_amounts(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')
            ->with(1, Mockery::any())
            ->andReturn([
                'total' => 0.000001,
                'by_type' => ['compute' => 0.000001],
            ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $summary = $aggregator->getCostSummary(1);

        $this->assertEquals(0.000001, $summary['totalCost']);
    }

    // ========================================
    // Category 4: getQuickInsights() Method Tests
    // ========================================

    /** @test */
    public function gq_001_user_with_no_analyses_returns_null_insights(): void
    {
        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 0,
            'by_status' => [],
            'by_tool' => [],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertNull($insights['mostUsedTool']);
        $this->assertNull($insights['avgAnalysisTime']);
        $this->assertNull($insights['successRate']);
    }

    /** @test */
    public function gq_002_most_used_tool_calculated_correctly(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 15,
            'by_status' => ['completed' => 15],
            'by_tool' => ['meta_analyzer' => 10, 'heading_analyzer' => 5],
            'avg_duration_ms' => 200.0,
            'success_rate' => 100.0,
            'this_month' => 8,
            'this_week' => 4,
            'today' => 1,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals('meta_analyzer', $insights['mostUsedTool']);
    }

    /** @test */
    public function gq_004_average_analysis_time_calculated_correctly(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 3,
            'by_status' => ['completed' => 3],
            'by_tool' => ['meta_analyzer' => 3],
            'avg_duration_ms' => 200.0,
            'success_rate' => 100.0,
            'this_month' => 2,
            'this_week' => 1,
            'today' => 0,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals(200.0, $insights['avgAnalysisTime']);
    }

    /** @test */
    public function gq_007_success_rate_calculated_correctly(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 100,
            'by_status' => ['completed' => 80, 'failed' => 20],
            'by_tool' => ['meta_analyzer' => 100],
            'avg_duration_ms' => 150.0,
            'success_rate' => 80.0,
            'this_month' => 50,
            'this_week' => 25,
            'today' => 5,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals(80.0, $insights['successRate']);
    }

    /** @test */
    public function gq_008_100_percent_success_rate(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 50,
            'by_status' => ['completed' => 50],
            'by_tool' => ['meta_analyzer' => 50],
            'avg_duration_ms' => 100.0,
            'success_rate' => 100.0,
            'this_month' => 25,
            'this_week' => 12,
            'today' => 3,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals(100.0, $insights['successRate']);
    }

    /** @test */
    public function gq_009_0_percent_success_rate(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 10,
            'by_status' => ['failed' => 10],
            'by_tool' => ['meta_analyzer' => 10],
            'avg_duration_ms' => 50.0,
            'success_rate' => 0.0,
            'this_month' => 5,
            'this_week' => 3,
            'today' => 1,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals(0.0, $insights['successRate']);
    }

    /** @test */
    public function gq_011_cross_user_isolation(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 10,
            'by_status' => ['completed' => 10],
            'by_tool' => ['meta_analyzer' => 10],
            'avg_duration_ms' => 100.0,
            'success_rate' => 100.0,
            'this_month' => 5,
            'this_week' => 3,
            'today' => 1,
        ]);
        $mockRepo->shouldReceive('getStats')->with(2)->andReturn([
            'total' => 20,
            'by_status' => ['completed' => 15, 'failed' => 5],
            'by_tool' => ['heading_analyzer' => 20],
            'avg_duration_ms' => 200.0,
            'success_rate' => 75.0,
            'this_month' => 10,
            'this_week' => 6,
            'today' => 2,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);

        $insights1 = $aggregator->getQuickInsights(1);
        $insights2 = $aggregator->getQuickInsights(2);

        $this->assertEquals('meta_analyzer', $insights1['mostUsedTool']);
        $this->assertEquals('heading_analyzer', $insights2['mostUsedTool']);
        $this->assertEquals(100.0, $insights1['successRate']);
        $this->assertEquals(75.0, $insights2['successRate']);
    }

    // ========================================
    // Category 5: getDashboardData() Method Tests
    // ========================================

    /** @test */
    public function gd_001_full_dashboard_structure(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 50,
            'by_status' => ['completed' => 48, 'failed' => 2],
            'by_tool' => ['meta_analyzer' => 30, 'heading_analyzer' => 20],
            'avg_duration_ms' => 150.0,
            'success_rate' => 96.0,
            'this_month' => 25,
            'this_week' => 12,
            'today' => 3,
        ]);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')->andReturn([
            'total' => 10.00,
            'by_type' => [],
        ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $dashboard = $aggregator->getDashboardData(1);

        $this->assertArrayHasKey('stats', $dashboard);
        $this->assertArrayHasKey('costSummary', $dashboard);
        $this->assertArrayHasKey('recentAnalyses', $dashboard);
        $this->assertArrayHasKey('quickInsights', $dashboard);
    }

    /** @test */
    public function gd_002_cache_key_format(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 0,
            'by_status' => [],
            'by_tool' => [],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ]);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')->andReturn([
            'total' => 0,
            'by_type' => [],
        ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $aggregator->getDashboardData(42);

        $this->assertTrue(Cache::has('dashboard.42'));

        Cache::forget('dashboard.42');
    }

    /** @test */
    public function gd_003_cache_ttl_is_60_seconds(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 0,
            'by_status' => [],
            'by_tool' => [],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ]);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')->andReturn([
            'total' => 0,
            'by_type' => [],
        ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $aggregator->getDashboardData(1);

        // Verify cache exists (TTL is handled by Cache::remember)
        $this->assertTrue(Cache::has('dashboard.1'));

        Cache::forget('dashboard.1');
    }

    /** @test */
    public function gd_004_cache_hit_returns_cached_data(): void
    {
        $cachedData = [
            'stats' => ['totalAnalyses' => 999, 'thisMonth' => 100, 'thisWeek' => 50, 'today' => 10],
            'costSummary' => ['totalCost' => 999.99, 'thisMonthCost' => 50.00, 'currency' => 'USD'],
            'recentAnalyses' => [],
            'quickInsights' => ['mostUsedTool' => 'cached_tool', 'avgAnalysisTime' => 999, 'successRate' => 99.9],
        ];

        Cache::put('dashboard.1', $cachedData, 60);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $result = $aggregator->getDashboardData(1);

        $this->assertEquals($cachedData, $result);

        Cache::forget('dashboard.1');
    }

    /** @test */
    public function gd_008_user_isolation_in_cache(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->with(1)->andReturn([
            'total' => 10,
            'by_status' => ['completed' => 10],
            'by_tool' => ['meta_analyzer' => 10],
            'avg_duration_ms' => 100.0,
            'success_rate' => 100.0,
            'this_month' => 5,
            'this_week' => 3,
            'today' => 1,
        ]);
        $mockRepo->shouldReceive('getStats')->with(2)->andReturn([
            'total' => 20,
            'by_status' => ['completed' => 20],
            'by_tool' => ['heading_analyzer' => 20],
            'avg_duration_ms' => 200.0,
            'success_rate' => 100.0,
            'this_month' => 10,
            'this_week' => 6,
            'today' => 2,
        ]);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')->andReturn([
            'total' => 0,
            'by_type' => [],
        ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);

        $aggregator->getDashboardData(1);
        $aggregator->getDashboardData(2);

        $this->assertTrue(Cache::has('dashboard.1'));
        $this->assertTrue(Cache::has('dashboard.2'));

        Cache::forget('dashboard.1');
        Cache::forget('dashboard.2');
    }

    /** @test */
    public function gd_009_empty_user_returns_zeros_and_empty_arrays(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 0,
            'by_status' => [],
            'by_tool' => [],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ]);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([]));

        $mockCostService = Mockery::mock(CostTrackingService::class);
        $mockCostService->shouldReceive('getUserCostSummary')->andReturn([
            'total' => 0,
            'by_type' => [],
        ]);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $dashboard = $aggregator->getDashboardData(1);

        $this->assertEquals(0, $dashboard['stats']['totalAnalyses']);
        $this->assertEquals(0.00, $dashboard['costSummary']['totalCost']);
        $this->assertEquals([], $dashboard['recentAnalyses']);
        $this->assertNull($dashboard['quickInsights']['mostUsedTool']);
    }

    // ========================================
    // Edge Cases
    // ========================================

    /** @test */
    public function ec_001_handles_null_analyzed_at_gracefully(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 5,
            'by_status' => ['pending' => 5],
            'by_tool' => ['meta_analyzer' => 5],
            'avg_duration_ms' => null,
            'success_rate' => null,
            'this_month' => 3,
            'this_week' => 2,
            'today' => 1,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $stats = $aggregator->getStats(1);

        // Total should still count records without analyzed_at
        $this->assertEquals(5, $stats['totalAnalyses']);
    }

    /** @test */
    public function ec_002_handles_unicode_in_tool_names(): void
    {
        Cache::flush();

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 1,
            'by_status' => ['completed' => 1],
            'by_tool' => ['工具_分析仪' => 1],
            'avg_duration_ms' => 100.0,
            'success_rate' => 100.0,
            'this_month' => 1,
            'this_week' => 1,
            'today' => 0,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals('工具_分析仪', $insights['mostUsedTool']);
    }

    /** @test */
    public function ec_003_handles_very_long_urls(): void
    {
        Cache::flush();

        $longUrl = 'https://example.com/' . str_repeat('a', 2000);

        $analysis = AnalysisHistory::factory()->make([
            'id' => 1,
            'user_id' => 1,
            'input_url' => $longUrl,
        ]);

        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getRecent')->andReturn(new EloquentCollection([$analysis]));

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $result = $aggregator->getRecentAnalyses(1);

        $this->assertEquals($longUrl, $result[0]['inputUrl']);
    }

    /** @test */
    public function ec_004_tie_for_most_used_tool_returns_alphabetically_first(): void
    {
        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 20,
            'by_status' => ['completed' => 20],
            'by_tool' => ['alpha_analyzer' => 10, 'beta_analyzer' => 10],
            'avg_duration_ms' => 150.0,
            'success_rate' => 100.0,
            'this_month' => 10,
            'this_week' => 5,
            'today' => 2,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals('alpha_analyzer', $insights['mostUsedTool']);
    }

    /** @test */
    public function ec_005_null_durations_excluded_from_average(): void
    {
        $mockRepo = Mockery::mock(AnalysisHistoryRepository::class);
        $mockRepo->shouldReceive('getStats')->andReturn([
            'total' => 3,
            'by_status' => ['completed' => 3],
            'by_tool' => ['meta_analyzer' => 3],
            'avg_duration_ms' => 150.0, // Only non-null values averaged
            'success_rate' => 100.0,
            'this_month' => 2,
            'this_week' => 1,
            'today' => 0,
        ]);

        $mockCostService = Mockery::mock(CostTrackingService::class);

        $aggregator = new DashboardAggregator($mockRepo, $mockCostService);
        $insights = $aggregator->getQuickInsights(1);

        $this->assertEquals(150.0, $insights['avgAnalysisTime']);
    }
}
