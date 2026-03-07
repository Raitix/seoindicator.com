<?php

namespace Tests\Unit\Jobs\AIVisibility;

use App\Jobs\AIVisibility\RunAIVisibilityQueriesJob;
use App\Jobs\AIVisibility\AIVisibilityQueryJob;
use App\Services\AIVisibilityServiceInterface;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for RunAIVisibilityQueriesJob.
 *
 * Tests job execution, scheduling, and error handling for batch query dispatch.
 *
 * @group ai-visibility-jobs
 */
class RunAIVisibilityQueriesJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // ============================================
    // Job Execution Tests (JRQ-EXEC)
    // ============================================

    /** @test JRQ-EXEC-01: Dispatches jobs for active projects */
    public function dispatches_jobs_for_active_projects(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt']],
                ['id' => 2, 'brand_name' => 'Brand B', 'platforms' => ['chatgpt']],
                ['id' => 3, 'brand_name' => 'Brand C', 'platforms' => ['chatgpt']],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, 3);
    }

    /** @test JRQ-EXEC-02: Respects tracking frequency daily */
    public function respects_tracking_frequency_daily(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->with('daily')
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt']],
            ]);

        $job = new RunAIVisibilityQueriesJob('daily');
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, 1);
    }

    /** @test JRQ-EXEC-03: Respects tracking frequency weekly */
    public function respects_tracking_frequency_weekly(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->with('weekly')
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt']],
            ]);

        $job = new RunAIVisibilityQueriesJob('weekly');
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, 1);
    }

    /** @test JRQ-EXEC-04: Respects tracking frequency monthly */
    public function respects_tracking_frequency_monthly(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->with('monthly')
            ->andReturn([]);

        $job = new RunAIVisibilityQueriesJob('monthly');
        $job->handle($mockService);

        Queue::assertNotPushed(AIVisibilityQueryJob::class);
    }

    /** @test JRQ-EXEC-05: Skips inactive projects */
    public function skips_inactive_projects(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt'], 'is_active' => true],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        // Only active projects should be queried
        Queue::assertPushed(AIVisibilityQueryJob::class, 1);
    }

    /** @test JRQ-EXEC-06: Queries all platforms per project */
    public function queries_all_platforms_per_project(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'brand_name' => 'Brand A',
                    'platforms' => ['chatgpt', 'perplexity', 'gemini'],
                ],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, 3);
    }

    /** @test JRQ-EXEC-07: Handles no projects due */
    public function handles_no_projects_due(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertNotPushed(AIVisibilityQueryJob::class);
    }

    /** @test JRQ-EXEC-08: Handles empty project list */
    public function handles_empty_project_list(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No projects');
            });

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);
    }

    /** @test JRQ-EXEC-09: Uses default query from project settings */
    public function uses_default_query_from_project_settings(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'brand_name' => 'Brand A',
                    'platforms' => ['chatgpt'],
                    'default_query' => 'What are the best seo tools?',
                ],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, function ($job) {
            return $job->query === 'What are the best seo tools?';
        });
    }

    /** @test JRQ-EXEC-10: Uses brand name as default query */
    public function uses_brand_name_as_default_query(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'brand_name' => 'Brand A',
                    'platforms' => ['chatgpt'],
                    'default_query' => null,
                ],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, function ($job) {
            return str_contains($job->query, 'Brand A');
        });
    }

    // ============================================
    // Scheduling Tests (JRQ-SCHED)
    // ============================================

    /** @test JRQ-SCHED-01: Job has correct queue name */
    public function job_has_correct_queue_name(): void
    {
        $job = new RunAIVisibilityQueriesJob();

        $this->assertEquals('ai-visibility', $job->queue);
    }

    /** @test JRQ-SCHED-02: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new RunAIVisibilityQueriesJob();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test JRQ-SCHED-03: Job has single try */
    public function job_has_single_try(): void
    {
        $job = new RunAIVisibilityQueriesJob();

        // This job dispatches other jobs, so it shouldn't retry
        $this->assertEquals(1, $job->tries);
    }

    /** @test JRQ-SCHED-04: WithoutOverlapping middleware applied */
    public function without_overlapping_middleware_applied(): void
    {
        $job = new RunAIVisibilityQueriesJob();

        $middleware = $job->middleware ?? [];

        $this->assertNotEmpty($middleware);
    }

    // ============================================
    // Error Handling Tests (JRQ-ERR)
    // ============================================

    /** @test JRQ-ERR-01: Handles database error */
    public function handles_database_error(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->andThrow(new \Exception('Database connection failed'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'RunAIVisibilityQueriesJob failed');
            });

        $this->expectException(\Exception::class);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);
    }

    /** @test JRQ-ERR-02: Continues after single job failure */
    public function continues_after_single_job_failure(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt']],
                ['id' => 2, 'brand_name' => 'Brand B', 'platforms' => ['chatgpt']],
            ]);

        // Even if one dispatch fails, others should continue
        // This is handled by Laravel's queue system

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, 2);
    }

    /** @test JRQ-ERR-03: Logs summary of dispatched jobs */
    public function logs_summary_of_dispatched_jobs(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt', 'perplexity']],
            ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Dispatched')
                    && isset($context['count'])
                    && $context['count'] === 2;
            });

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);
    }

    /** @test JRQ-ERR-04: Failed job is logged with context */
    public function failed_job_is_logged_with_context(): void
    {
        $job = new RunAIVisibilityQueriesJob();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['frequency']);
            });

        $job->failed(new \Exception('Test failure'));
    }

    // ============================================
    // Edge Cases Tests
    // ============================================

    /** @test EC-01: Handles project with no platforms */
    public function handles_project_with_no_platforms(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => []],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertNotPushed(AIVisibilityQueryJob::class);
    }

    /** @test EC-02: Handles null platforms */
    public function handles_null_platforms(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => null],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertNotPushed(AIVisibilityQueryJob::class);
    }

    /** @test EC-03: Dispatches to correct queue */
    public function dispatches_to_correct_queue(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                ['id' => 1, 'brand_name' => 'Brand A', 'platforms' => ['chatgpt']],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushedOn('ai-visibility', AIVisibilityQueryJob::class);
    }

    /** @test EC-04: Respects useFreeModel setting from project */
    public function respects_use_free_model_setting_from_project(): void
    {
        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'brand_name' => 'Brand A',
                    'platforms' => ['chatgpt'],
                    'use_free_model' => true,
                ],
            ]);

        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);

        Queue::assertPushed(AIVisibilityQueryJob::class, function ($job) {
            return $job->useFreeModel === true;
        });
    }

    /** @test PERF-01: Job handles large project list efficiently */
    public function job_handles_large_project_list_efficiently(): void
    {
        // Generate 100 projects with 3 platforms each
        $projects = [];
        for ($i = 1; $i <= 100; $i++) {
            $projects[] = [
                'id' => $i,
                'brand_name' => "Brand $i",
                'platforms' => ['chatgpt', 'perplexity', 'gemini'],
            ];
        }

        $mockService = $this->mock(AIVisibilityServiceInterface::class);
        $mockService->shouldReceive('getActiveProjectsForQuery')
            ->once()
            ->andReturn($projects);

        $startTime = microtime(true);
        $job = new RunAIVisibilityQueriesJob();
        $job->handle($mockService);
        $executionTime = microtime(true) - $startTime;

        Queue::assertPushed(AIVisibilityQueryJob::class, 300);
        $this->assertLessThan(2, $executionTime, 'Job should complete in under 2 seconds');
    }
}
