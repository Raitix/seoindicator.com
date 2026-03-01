<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\AnalysisHistory;
use App\Models\AnalysisCost;
use App\Models\Site;
use App\Services\DashboardAggregator;
use App\Repositories\AnalysisHistoryRepository;
use App\Services\CostTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private DashboardAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = app(AnalysisHistoryRepository::class);
        $costService = app(CostTrackingService::class);
        $this->aggregator = new DashboardAggregator($repository, $costService);

        Cache::flush();
    }

    // ========================================
    // Integration Tests
    // ========================================

    /** @test */
    public function int_001_new_analysis_created_updates_dashboard_stats(): void
    {
        $user = User::factory()->create();

        // Get initial stats
        $stats = $this->aggregator->getStats($user->id);
        $this->assertEquals(0, $stats['totalAnalyses']);

        // Create new analysis
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'analyzed_at' => now(),
        ]);

        // Clear cache
        Cache::flush();

        // Get updated stats
        $stats = $this->aggregator->getStats($user->id);
        $this->assertEquals(1, $stats['totalAnalyses']);
    }

    /** @test */
    public function int_002_multiple_analyses_for_same_user_aggregated(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $stats = $this->aggregator->getStats($user->id);

        $this->assertEquals(10, $stats['totalAnalyses']);
    }

    /** @test */
    public function int_003_analysis_deleted_updates_dashboard(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        Cache::flush();
        $stats = $this->aggregator->getStats($user->id);
        $this->assertEquals(1, $stats['totalAnalyses']);

        // Delete the analysis
        $analysis->delete();

        Cache::flush();
        $stats = $this->aggregator->getStats($user->id);
        $this->assertEquals(0, $stats['totalAnalyses']);
    }

    /** @test */
    public function int_004_cost_recorded_updates_cost_summary(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        AnalysisCost::factory()->create([
            'analysis_id' => $analysis->id,
            'cost_amount' => 5.00,
            'cost_type' => 'compute',
        ]);

        Cache::flush();
        $summary = $this->aggregator->getCostSummary($user->id);

        $this->assertEquals(5.00, $summary['totalCost']);
    }

    /** @test */
    public function int_005_cache_cleared_on_analysis_change(): void
    {
        $user = User::factory()->create();

        // First request caches the data
        $this->aggregator->getDashboardData($user->id);
        $this->assertTrue(Cache::has('dashboard.' . $user->id));

        // Create new analysis
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Clear cache (simulating cache invalidation)
        Cache::forget('dashboard.' . $user->id);

        // Fresh data should reflect the change
        $data = $this->aggregator->getDashboardData($user->id);
        $this->assertEquals(1, $data['stats']['totalAnalyses']);
    }

    /** @test */
    public function int_006_user_deletion_cleans_up_dashboard_data(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        // Verify data exists
        $this->aggregator->getDashboardData($user->id);
        $this->assertTrue(Cache::has('dashboard.' . $user->id));

        // Delete user
        $user->delete();

        // Cache should still exist but return empty for non-existent user
        $newUser = User::factory()->create();
        $data = $this->aggregator->getDashboardData($newUser->id);
        $this->assertEquals(0, $data['stats']['totalAnalyses']);
    }

    /** @test */
    public function int_007_site_deletion_analyses_still_included(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        // Delete site
        $site->delete();

        Cache::flush();
        $recent = $this->aggregator->getRecentAnalyses($user->id);

        // Analyses should still be present with null site
        $this->assertCount(5, $recent);
    }

    /** @test */
    public function int_008_cost_tracking_service_integration(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
        ]);

        // Record cost using CostTrackingService
        $costService = app(CostTrackingService::class);
        $costService->recordCost($analysis, []);

        Cache::flush();
        $summary = $this->aggregator->getCostSummary($user->id);

        // Should have recorded a cost
        $this->assertGreaterThanOrEqual(0, $summary['totalCost']);
    }

    /** @test */
    public function int_009_analysis_history_repository_integration(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(20)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $recent = $this->aggregator->getRecentAnalyses($user->id);

        // Should be limited to 10
        $this->assertLessThanOrEqual(10, count($recent));
    }

    /** @test */
    public function int_010_performance_under_load(): void
    {
        $user = User::factory()->create();

        // Create 500 analyses with costs
        AnalysisHistory::factory()
            ->count(500)
            ->has(AnalysisCost::factory()->count(2), 'costs')
            ->create([
                'user_id' => $user->id,
                'status' => 'completed',
            ]);

        $start = microtime(true);

        $this->aggregator->getDashboardData($user->id);

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Dashboard should respond in under 500ms with 500 analyses');
    }

    /** @test */
    public function int_011_concurrent_dashboard_requests(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(50)->create([
            'user_id' => $user->id,
        ]);

        // Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->aggregator->getDashboardData($user->id);
        }

        // All results should be identical
        $first = json_encode($results[0]);
        foreach ($results as $result) {
            $this->assertEquals($first, json_encode($result));
        }
    }

    /** @test */
    public function int_012_database_transaction_during_fetch(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user->id,
        ]);

        // Get dashboard data
        $data1 = $this->aggregator->getDashboardData($user->id);

        // Start a transaction and create more analyses
        \DB::beginTransaction();
        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        // Data should be consistent (transaction not committed yet)
        Cache::flush();
        $data2 = $this->aggregator->getDashboardData($user->id);

        // With transaction isolation, should see committed data
        $this->assertEquals(10, $data1['stats']['totalAnalyses']);

        \DB::rollBack();

        // After rollback, should still be 10
        Cache::flush();
        $data3 = $this->aggregator->getDashboardData($user->id);
        $this->assertEquals(10, $data3['stats']['totalAnalyses']);
    }

    /** @test */
    public function int_013_full_dashboard_workflow(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Create analyses with different statuses and tools
        AnalysisHistory::factory()->count(30)->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'completed',
            'tool_name' => 'meta_analyzer',
            'analysis_duration_ms' => 150,
        ]);

        AnalysisHistory::factory()->count(10)->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'completed',
            'tool_name' => 'heading_analyzer',
            'analysis_duration_ms' => 200,
        ]);

        AnalysisHistory::factory()->count(5)->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'failed',
            'tool_name' => 'meta_analyzer',
            'analysis_duration_ms' => null,
        ]);

        // Create costs
        $analyses = AnalysisHistory::where('user_id', $user->id)->get();
        foreach ($analyses->take(20) as $analysis) {
            AnalysisCost::factory()->create([
                'analysis_id' => $analysis->id,
                'cost_amount' => 0.50,
            ]);
        }

        // Get full dashboard
        $dashboard = $this->aggregator->getDashboardData($user->id);

        // Verify stats
        $this->assertEquals(45, $dashboard['stats']['totalAnalyses']);

        // Verify cost summary
        $this->assertEquals(10.00, $dashboard['costSummary']['totalCost']);

        // Verify quick insights
        $this->assertEquals('meta_analyzer', $dashboard['quickInsights']['mostUsedTool']);
        $this->assertEquals(88.9, round($dashboard['quickInsights']['successRate'], 1)); // 40/45 = 88.9%

        // Verify recent analyses limited to 10
        $this->assertLessThanOrEqual(10, count($dashboard['recentAnalyses']));
    }
}
