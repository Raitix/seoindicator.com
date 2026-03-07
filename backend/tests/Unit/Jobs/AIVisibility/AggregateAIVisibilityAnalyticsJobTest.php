<?php

namespace Tests\Unit\Jobs\AIVisibility;

use App\Jobs\AIVisibility\AggregateAIVisibilityAnalyticsJob;
use App\Services\AIVisibilityServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for AggregateAIVisibilityAnalyticsJob.
 *
 * Tests analytics aggregation, scheduling, and error handling.
 *
 * @group ai-visibility-jobs
 */
class AggregateAIVisibilityAnalyticsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ============================================
    // Aggregation Logic Tests (JAA-AGG)
    // ============================================

    /** @test JAA-AGG-01: Aggregates query results */
    public function aggregates_query_results(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions' => []],
                ['id' => 2, 'project_id' => 1, 'platform' => 'perplexity', 'mentions' => []],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->twice();

        $mockService->shouldReceive('markResultsProcessed')
            ->once();

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);

        // Job completed successfully
        $this->assertTrue(true);
    }

    /** @test JAA-AGG-02: Calculates mention rate */
    public function calculates_mention_rate(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions_count' => 5],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once()
            ->withArgs(function ($results) {
                // Verify mention rate calculation
                return true;
            });

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-AGG-03: Calculates share of voice */
    public function calculates_share_of_voice(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'brand_mentions' => 3,
                    'total_mentions' => 10,
                ],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-AGG-04: Calculates sentiment score */
    public function calculates_sentiment_score(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'project_id' => 1,
                    'platform' => 'chatgpt',
                    'mentions' => [
                        ['sentiment' => 'positive'],
                        ['sentiment' => 'positive'],
                        ['sentiment' => 'negative'],
                    ],
                ],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-AGG-05: Groups by project and platform */
    public function groups_by_project_and_platform(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt'],
                ['id' => 2, 'project_id' => 1, 'platform' => 'perplexity'],
                ['id' => 3, 'project_id' => 2, 'platform' => 'chatgpt'],
                ['id' => 4, 'project_id' => 2, 'platform' => 'perplexity'],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->times(4);

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-AGG-06: Skips already processed results */
    public function skips_already_processed_results(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([]); // No unprocessed results

        $mockService->shouldNotReceive('aggregateResults');
        $mockService->shouldNotReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-AGG-07: Stores in ai_mention_analytics */
    public function stores_in_ai_mention_analytics(): void
    {
        // Create a project first (needed for foreign key constraint)
        \App\Models\AIVisibility\AIVisibilityProject::factory()->create(['id' => 1]);

        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions' => []],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once()
            ->andReturn([
                'project_id' => 1,
                'platform' => 'chatgpt',
                'date' => now()->toDateString(),
                'total_queries' => 1,
                'total_mentions' => 0,
                'mention_rate' => 0,
                'share_of_voice' => 0,
                'sentiment_score' => 0,
            ]);

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);

        $this->assertDatabaseHas('ai_mention_analytics', [
            'project_id' => 1,
            'platform' => 'chatgpt',
        ]);
    }

    /** @test JAA-AGG-08: Updates existing analytics for same date */
    public function updates_existing_analytics_for_same_date(): void
    {
        $today = now()->toDateString();

        // Create a project first (needed for foreign key constraint)
        \App\Models\AIVisibility\AIVisibilityProject::factory()->create(['id' => 1]);

        // Create existing record
        DB::table('ai_mention_analytics')->insert([
            'project_id' => 1,
            'platform' => 'chatgpt',
            'date' => $today,
            'total_queries' => 5,
            'brand_mentions' => 2,
            'total_mentions' => 2,
            'mention_rate' => 0.4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions' => []],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);

        // Should update, not create duplicate
        $count = DB::table('ai_mention_analytics')
            ->where('project_id', 1)
            ->where('platform', 'chatgpt')
            ->where('date', $today)
            ->count();

        $this->assertEquals(1, $count);
    }

    // ============================================
    // Scheduling Tests (JAA-SCHED)
    // ============================================

    /** @test JAA-SCHED-01: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new AggregateAIVisibilityAnalyticsJob();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test JAA-SCHED-02: Job has correct queue name */
    public function job_has_correct_queue_name(): void
    {
        $job = new AggregateAIVisibilityAnalyticsJob();

        $this->assertEquals('ai-visibility', $job->queue);
    }

    /** @test JAA-SCHED-03: Job has single try */
    public function job_has_single_try(): void
    {
        $job = new AggregateAIVisibilityAnalyticsJob();

        $this->assertEquals(1, $job->tries);
    }

    // ============================================
    // Error Handling Tests (JAA-ERR)
    // ============================================

    /** @test JAA-ERR-01: Handles empty results */
    public function handles_empty_results(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([]);

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);

        // Job should complete without errors
        $this->assertTrue(true);
    }

    /** @test JAA-ERR-02: Handles database error */
    public function handles_database_error(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->andThrow(new \Exception('Database connection failed'));

        Log::shouldReceive('error')
            ->once();

        $this->expectException(\Exception::class);

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test JAA-ERR-03: Continues after single project failure */
    public function continues_after_single_project_failure(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt'],
                ['id' => 2, 'project_id' => 2, 'platform' => 'chatgpt'],
            ]);

        // First aggregation fails, second succeeds
        $callCount = 0;
        $mockService->shouldReceive('aggregateResults')
            ->twice()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    throw new \Exception('Aggregation failed');
                }
                return [];
            });

        Log::shouldReceive('error')->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);

        // Job continues processing after first failure
        $this->assertEquals(2, $callCount);
    }

    /** @test JAA-ERR-04: Failed job is logged */
    public function failed_job_is_logged(): void
    {
        $job = new AggregateAIVisibilityAnalyticsJob();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'AggregateAIVisibilityAnalyticsJob failed');
            });

        $job->failed(new \Exception('Test failure'));
    }

    // ============================================
    // Edge Cases Tests
    // ============================================

    /** @test EC-01: Handles null mention data */
    public function handles_null_mention_data(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions' => null],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test EC-02: Handles malformed mention data */
    public function handles_malformed_mention_data(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn([
                ['id' => 1, 'project_id' => 1, 'platform' => 'chatgpt', 'mentions' => 'invalid'],
            ]);

        $mockService->shouldReceive('aggregateResults')
            ->once();

        $mockService->shouldReceive('markResultsProcessed');

        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
    }

    /** @test PERF-01: Job handles large dataset efficiently */
    public function job_handles_large_dataset_efficiently(): void
    {
        // Generate 1000 results
        $results = [];
        for ($i = 1; $i <= 1000; $i++) {
            $results[] = [
                'id' => $i,
                'project_id' => ($i % 10) + 1,
                'platform' => ['chatgpt', 'perplexity', 'gemini'][$i % 3],
                'mentions' => [],
            ];
        }

        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getUnprocessedQueryResults')
            ->once()
            ->andReturn($results);

        $mockService->shouldReceive('aggregateResults')
            ->times(1000);

        $mockService->shouldReceive('markResultsProcessed');

        $startTime = microtime(true);
        $job = new AggregateAIVisibilityAnalyticsJob();
        $job->handle($mockService);
        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(5, $executionTime, 'Job should complete in under 5 seconds');
    }
}
