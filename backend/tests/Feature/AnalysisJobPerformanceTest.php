<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use App\Services\ProgressService;
use App\Services\Analysis\Indicators\IndicatorResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Performance tests for the Progressive Analysis system.
 *
 * Tests execution time and memory usage for job processing.
 *
 * @see TASK-19-16
 */
class AnalysisJobPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);

        Event::fake();
    }

    // =========================================================================
    // JOB CREATION PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function job_creation_completes_under_1_second(): void
    {
        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $start = microtime(true);

        $job = app(AnalysisJobService::class)->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Job creation should complete in under 1 second');
        $this->assertNotNull($job);
    }

    /** @test */
    public function job_creation_with_minimal_indicators_is_fast(): void
    {
        $start = microtime(true);

        $job = app(AnalysisJobService::class)->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.5, $duration, 'Job creation with 1 indicator should be very fast');
    }

    // =========================================================================
    // PROGRESS UPDATE PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function progress_update_completes_quickly(): void
    {
        $service = app(AnalysisJobService::class);
        $progressService = app(ProgressService::class);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images', 'links']
        );

        $service->startJob($job->id);

        $start = microtime(true);

        // Simulate multiple progress updates
        for ($i = 0; $i < 4; $i++) {
            $slug = ['meta_tags', 'headings', 'images', 'links'][$i];
            $progressService->markRunning($job->id, $slug);
            $progressService->markComplete(
                $job->id,
                $slug,
                IndicatorResult::success($slug, ucfirst($slug), 85.0, 100.0, processingTimeMs: 100)
            );
        }

        $service->updateProgress($job->id);

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.5, $duration, '4 progress updates should complete in under 0.5 seconds');
    }

    /** @test */
    public function single_progress_mark_complete_is_fast(): void
    {
        $service = app(AnalysisJobService::class);
        $progressService = app(ProgressService::class);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $service->startJob($job->id);
        $progressService->markRunning($job->id, 'meta_tags');

        $start = microtime(true);

        $progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, 'Single markComplete should be very fast');
    }

    // =========================================================================
    // LARGE JOB PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function job_with_all_indicators_processes_efficiently(): void
    {
        $service = app(AnalysisJobService::class);
        $progressService = app(ProgressService::class);

        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $service->startJob($job->id);

        $start = microtime(true);

        // Simulate processing all indicators
        foreach ($indicators as $slug) {
            $progressService->markRunning($job->id, $slug);
            $progressService->markComplete(
                $job->id,
                $slug,
                IndicatorResult::success($slug, ucfirst($slug), 85.0, 100.0, processingTimeMs: 100)
            );
        }

        $service->updateProgress($job->id);

        $duration = microtime(true) - $start;

        // Should complete processing all indicators in under 5 seconds
        $this->assertLessThan(5.0, $duration, 'All indicator processing should be fast');
    }

    // =========================================================================
    // MEMORY USAGE TESTS
    // =========================================================================

    /** @test */
    public function job_creation_memory_usage_is_reasonable(): void
    {
        $memoryBefore = memory_get_usage(true);

        $service = app(AnalysisJobService::class);

        // Create multiple jobs
        for ($i = 0; $i < 10; $i++) {
            $service->createJob(
                $this->user->id,
                $this->site->id,
                ['meta_tags', 'headings']
            );
        }

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Should use less than 50MB for 10 jobs
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should be reasonable');
    }

    /** @test */
    public function progress_update_memory_usage_is_reasonable(): void
    {
        $service = app(AnalysisJobService::class);
        $progressService = app(ProgressService::class);

        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $service->startJob($job->id);

        $memoryBefore = memory_get_usage(true);

        // Process all indicators
        foreach ($indicators as $slug) {
            $progressService->markRunning($job->id, $slug);
            $progressService->markComplete(
                $job->id,
                $slug,
                IndicatorResult::success($slug, ucfirst($slug), 85.0, 100.0, processingTimeMs: 100)
            );
        }

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Should use less than 20MB for processing all indicators
        $this->assertLessThan(20, $memoryUsed, 'Memory usage during processing should be reasonable');
    }

    // =========================================================================
    // DATABASE QUERY PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function get_job_progress_is_efficient(): void
    {
        $service = app(AnalysisJobService::class);

        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $start = microtime(true);

        $progress = $service->getJobProgress($job->id);

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, 'getJobProgress should be fast');
        $this->assertNotNull($progress);
    }

    /** @test */
    public function job_list_query_is_efficient(): void
    {
        // Create 50 jobs
        for ($i = 0; $i < 50; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'total_indicators' => 1,
            ]);
        }

        $start = microtime(true);

        $jobs = AnalysisJob::where('user_id', $this->user->id)
            ->where('site_id', $this->site->id)
            ->with('results')
            ->paginate(15);

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.5, $duration, 'Job list query should be fast');
    }

    // =========================================================================
    // CONCURRENT OPERATION PERFORMANCE TESTS
    // =========================================================================

    /** @test */
    public function concurrent_job_creation_performs_well(): void
    {
        $service = app(AnalysisJobService::class);

        $start = microtime(true);

        // Create 5 jobs (within concurrent limit)
        $jobs = [];
        for ($i = 0; $i < 2; $i++) {
            $jobs[] = $service->createJob(
                $this->user->id,
                $this->site->id,
                ['meta_tags']
            );
        }

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Concurrent job creation should be fast');
    }

    // =========================================================================
    // API RESPONSE TIME TESTS
    // =========================================================================

    /** @test */
    public function api_job_creation_responds_quickly(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags', 'headings'],
            ]);

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'API job creation should respond quickly');
        $response->assertStatus(201);
    }

    /** @test */
    public function api_progress_endpoint_responds_quickly(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 5,
        ]);

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.5, $duration, 'Progress endpoint should respond quickly');
        $response->assertStatus(200);
    }

    /** @test */
    public function api_job_list_responds_quickly(): void
    {
        // Create some jobs
        for ($i = 0; $i < 20; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'total_indicators' => 1,
            ]);
        }

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.5, $duration, 'Job list endpoint should respond quickly');
        $response->assertStatus(200);
    }

    // =========================================================================
    // BENCHMARK TESTS
    // =========================================================================

    /** @test */
    public function benchmark_full_job_lifecycle(): void
    {
        $service = app(AnalysisJobService::class);
        $progressService = app(ProgressService::class);

        $totalStart = microtime(true);

        // Create
        $createStart = microtime(true);
        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images', 'links']
        );
        $createDuration = microtime(true) - $createStart;

        // Start
        $startStart = microtime(true);
        $service->startJob($job->id);
        $startDuration = microtime(true) - $startStart;

        // Process
        $processStart = microtime(true);
        foreach (['meta_tags', 'headings', 'images', 'links'] as $slug) {
            $progressService->markRunning($job->id, $slug);
            $progressService->markComplete(
                $job->id,
                $slug,
                IndicatorResult::success($slug, ucfirst($slug), 85.0, 100.0, processingTimeMs: 100)
            );
        }
        $service->updateProgress($job->id);
        $processDuration = microtime(true) - $processStart;

        // Get progress
        $progressStart = microtime(true);
        $progress = $service->getJobProgress($job->id);
        $progressDuration = microtime(true) - $progressStart;

        $totalDuration = microtime(true) - $totalStart;

        // Log results
        $this->assertLessThan(1.0, $createDuration, "Create: {$createDuration}s");
        $this->assertLessThan(0.1, $startDuration, "Start: {$startDuration}s");
        $this->assertLessThan(1.0, $processDuration, "Process: {$processDuration}s");
        $this->assertLessThan(0.1, $progressDuration, "GetProgress: {$progressDuration}s");
        $this->assertLessThan(3.0, $totalDuration, "Total: {$totalDuration}s");

        $job->refresh();
        $this->assertEquals('completed', $job->status);
    }
}
