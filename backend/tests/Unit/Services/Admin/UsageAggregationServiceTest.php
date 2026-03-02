<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\UsageAggregationService;
use App\Models\AnalysisJob;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for UsageAggregationService
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group unit
 * @group services
 */
class UsageAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UsageAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UsageAggregationService::class);
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(UsageAggregationService::class);
        $this->assertInstanceOf(UsageAggregationService::class, $service);
    }

    // =========================================================================
    // getStats() Tests
    // =========================================================================

    /** @test */
    public function get_stats_returns_array_with_required_keys(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('requests', $stats);
        $this->assertArrayHasKey('tokens', $stats);
        $this->assertArrayHasKey('analyses', $stats);
    }

    /** @test */
    public function get_stats_returns_zero_for_empty_database(): void
    {
        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['requests']['total']);
        $this->assertEquals(0, $stats['tokens']['total']);
        $this->assertEquals(0, $stats['analyses']['total']);
    }

    // =========================================================================
    // Request Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_counts_total_requests(): void
    {
        // Create analysis jobs as proxy for requests
        AnalysisJob::factory()->count(150)->create();

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total', $stats['requests']);
        $this->assertGreaterThanOrEqual(150, $stats['requests']['total']);
    }

    /** @test */
    public function get_stats_counts_requests_today(): void
    {
        AnalysisJob::factory()->count(25)->create([
            'created_at' => now(),
        ]);
        
        AnalysisJob::factory()->count(10)->create([
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(25, $stats['requests']['today']);
    }

    /** @test */
    public function get_stats_counts_requests_this_week(): void
    {
        AnalysisJob::factory()->count(50)->create([
            'created_at' => now()->startOfWeek()->addDays(2),
        ]);
        
        AnalysisJob::factory()->count(30)->create([
            'created_at' => now()->subWeeks(2),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(50, $stats['requests']['thisWeek']);
    }

    /** @test */
    public function get_stats_counts_requests_this_month(): void
    {
        AnalysisJob::factory()->count(100)->create([
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        
        AnalysisJob::factory()->count(50)->create([
            'created_at' => now()->subMonth(),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(100, $stats['requests']['thisMonth']);
    }

    // =========================================================================
    // Token Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_counts_total_tokens(): void
    {
        AnalysisJob::factory()->count(10)->create([
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);

        $stats = $this->service->getStats();

        // 10 jobs * (1000 + 500) = 15000 tokens
        $this->assertEquals(15000, $stats['tokens']['total']);
    }

    /** @test */
    public function get_stats_counts_tokens_by_model(): void
    {
        AnalysisJob::factory()->count(5)->create([
            'model' => 'gpt-4',
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);
        
        AnalysisJob::factory()->count(3)->create([
            'model' => 'gpt-3.5-turbo',
            'input_tokens' => 2000,
            'output_tokens' => 1000,
        ]);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('byModel', $stats['tokens']);
        $this->assertEquals(7500, $stats['tokens']['byModel']['gpt-4']); // 5 * 1500
        $this->assertEquals(9000, $stats['tokens']['byModel']['gpt-3.5-turbo']); // 3 * 3000
    }

    /** @test */
    public function get_stats_handles_missing_token_data(): void
    {
        AnalysisJob::factory()->count(5)->create([
            'input_tokens' => null,
            'output_tokens' => null,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(0, $stats['tokens']['total']);
    }

    /** @test */
    public function get_stats_aggregates_token_data_by_time_period(): void
    {
        AnalysisJob::factory()->create([
            'created_at' => now(),
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);
        
        AnalysisJob::factory()->create([
            'created_at' => now()->subWeek(),
            'input_tokens' => 2000,
            'output_tokens' => 1000,
        ]);
        
        AnalysisJob::factory()->create([
            'created_at' => now()->subMonth(),
            'input_tokens' => 3000,
            'output_tokens' => 1500,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(1500, $stats['tokens']['today']);
        $this->assertGreaterThanOrEqual(1500, $stats['tokens']['thisWeek']);
        $this->assertGreaterThanOrEqual(1500, $stats['tokens']['thisMonth']);
    }

    // =========================================================================
    // Analysis Stats Tests
    // =========================================================================

    /** @test */
    public function get_stats_counts_total_analyses(): void
    {
        AnalysisJob::factory()->count(75)->create();

        $stats = $this->service->getStats();

        $this->assertEquals(75, $stats['analyses']['total']);
    }

    /** @test */
    public function get_stats_counts_analyses_by_status(): void
    {
        AnalysisJob::factory()->count(10)->create(['status' => 'completed']);
        AnalysisJob::factory()->count(5)->create(['status' => 'pending']);
        AnalysisJob::factory()->count(3)->create(['status' => 'failed']);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('byStatus', $stats['analyses']);
        $this->assertEquals(10, $stats['analyses']['byStatus']['completed']);
        $this->assertEquals(5, $stats['analyses']['byStatus']['pending']);
        $this->assertEquals(3, $stats['analyses']['byStatus']['failed']);
    }

    /** @test */
    public function get_stats_counts_analyses_by_type(): void
    {
        AnalysisJob::factory()->count(20)->create(['type' => 'full_analysis']);
        AnalysisJob::factory()->count(15)->create(['type' => 'quick_check']);

        $stats = $this->service->getStats();

        $this->assertArrayHasKey('byType', $stats['analyses']);
        $this->assertEquals(20, $stats['analyses']['byType']['full_analysis']);
        $this->assertEquals(15, $stats['analyses']['byType']['quick_check']);
    }

    // =========================================================================
    // getRequestsByEndpoint() Tests
    // =========================================================================

    /** @test */
    public function get_requests_by_endpoint_returns_endpoint_stats(): void
    {
        // Create analysis jobs with different endpoints
        AnalysisJob::factory()->count(50)->create(['endpoint' => '/api/v1/analyze']);
        AnalysisJob::factory()->count(30)->create(['endpoint' => '/api/v1/batch']);

        $result = $this->service->getRequestsByEndpoint();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('/api/v1/analyze', $result);
        $this->assertEquals(50, $result['/api/v1/analyze']);
    }

    /** @test */
    public function get_requests_by_endpoint_orders_by_count_desc(): void
    {
        AnalysisJob::factory()->count(10)->create(['endpoint' => '/api/v1/analyze']);
        AnalysisJob::factory()->count(50)->create(['endpoint' => '/api/v1/batch']);
        AnalysisJob::factory()->count(25)->create(['endpoint' => '/api/v1/health']);

        $result = $this->service->getRequestsByEndpoint();

        $keys = array_keys($result);
        $this->assertEquals('/api/v1/batch', $keys[0]); // 50 requests
        $this->assertEquals('/api/v1/health', $keys[1]); // 25 requests
        $this->assertEquals('/api/v1/analyze', $keys[2]); // 10 requests
    }

    // =========================================================================
    // getTokenTrends() Tests
    // =========================================================================

    /** @test */
    public function get_token_trends_returns_daily_data(): void
    {
        // Create jobs for last 7 days
        for ($i = 0; $i < 7; $i++) {
            AnalysisJob::factory()->count(5)->create([
                'created_at' => now()->subDays($i),
                'input_tokens' => 1000,
                'output_tokens' => 500,
            ]);
        }

        $trends = $this->service->getTokenTrends(7);

        $this->assertCount(7, $trends);
        $this->assertArrayHasKey('date', $trends[0]);
        $this->assertArrayHasKey('tokens', $trends[0]);
    }

    /** @test */
    public function get_token_trends_limits_to_specified_days(): void
    {
        // Create jobs for last 14 days
        for ($i = 0; $i < 14; $i++) {
            AnalysisJob::factory()->create([
                'created_at' => now()->subDays($i),
                'input_tokens' => 1000,
                'output_tokens' => 500,
            ]);
        }

        $trends = $this->service->getTokenTrends(7);

        $this->assertCount(7, $trends);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_stats_completes_under_100ms(): void
    {
        AnalysisJob::factory()->count(1000)->create();

        $start = microtime(true);
        $this->service->getStats();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Stats query should complete under 100ms');
    }

    /** @test */
    public function get_stats_uses_efficient_queries(): void
    {
        AnalysisJob::factory()->count(100)->create();

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
    public function get_stats_handles_timezone_correctly(): void
    {
        AnalysisJob::factory()->create([
            'created_at' => now()->startOfDay(),
        ]);

        $stats = $this->service->getStats();

        $this->assertGreaterThanOrEqual(1, $stats['requests']['today']);
    }

    /** @test */
    public function get_stats_handles_large_token_counts(): void
    {
        AnalysisJob::factory()->create([
            'input_tokens' => 1000000,
            'output_tokens' => 500000,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(1500000, $stats['tokens']['total']);
    }

    /** @test */
    public function get_stats_handles_null_model(): void
    {
        AnalysisJob::factory()->count(5)->create([
            'model' => null,
            'input_tokens' => 1000,
            'output_tokens' => 500,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(7500, $stats['tokens']['total']);
    }

    // =========================================================================
    // Data Validation Tests
    // =========================================================================

    /** @test */
    public function get_stats_validates_numeric_types(): void
    {
        AnalysisJob::factory()->count(10)->create();

        $stats = $this->service->getStats();

        $this->assertIsInt($stats['requests']['total']);
        $this->assertIsInt($stats['tokens']['total']);
        $this->assertIsInt($stats['analyses']['total']);
    }

    /** @test */
    public function get_stats_does_not_expose_user_data(): void
    {
        AnalysisJob::factory()->create([
            'user_id' => 123,
            'url' => 'https://secret-site.com',
        ]);

        $stats = $this->service->getStats();

        $jsonStats = json_encode($stats);
        $this->assertStringNotContainsString('secret-site.com', $jsonStats);
    }
}
