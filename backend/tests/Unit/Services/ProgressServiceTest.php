<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\ProgressService;
use App\Services\Analysis\Indicators\IndicatorResult;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for ProgressService.
 *
 * @see TASK-19-16
 * @see \App\Services\ProgressService
 */
class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected AnalysisJob $job;
    protected ProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 3,
            'started_at' => now(),
        ]);

        $this->service = app(ProgressService::class);

        Event::fake();
    }

    protected function createResult(string $slug, string $status = 'pending'): AnalysisJobResult
    {
        return AnalysisJobResult::create([
            'job_id' => $this->job->id,
            'indicator_slug' => $slug,
            'indicator_name' => ucfirst(str_replace('_', ' ', $slug)),
            'status' => $status,
        ]);
    }

    // =========================================================================
    // MARK COMPLETE TESTS
    // =========================================================================

    /** @test */
    public function mark_complete_updates_result_status(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            data: ['title' => 'Test'],
            recommendations: [],
            processingTimeMs: 150,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals('completed', $result->status);
    }

    /** @test */
    public function mark_complete_updates_result_scores(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 75.5,
            maxScore: 100.0,
            data: ['title' => 'Test'],
            recommendations: [],
            processingTimeMs: 200,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals(75.5, (float) $result->score);
        $this->assertEquals(100.0, (float) $result->max_score);
        $this->assertEquals(76, $result->percentage);
    }

    /** @test */
    public function mark_complete_updates_result_data(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $data = ['title' => 'Test Page', 'description' => 'Test description'];
        $recommendations = [
            ['priority' => 'high', 'message' => 'Add meta description'],
        ];

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            data: $data,
            recommendations: $recommendations,
            processingTimeMs: 150,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals($data, $result->data);
        $this->assertEquals($recommendations, $result->recommendations);
    }

    /** @test */
    public function mark_complete_updates_job_progress(): void
    {
        $this->createResult('meta_tags', 'running');
        $this->createResult('headings', 'pending');

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $this->job->refresh();
        $this->assertEquals(1, $this->job->completed_indicators);
        $this->assertEquals(33, $this->job->progress_percent); // 1/3
    }

    /** @test */
    public function mark_complete_sets_completed_at(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 150,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertNotNull($result->completed_at);
    }

    /** @test */
    public function mark_complete_stores_processing_time(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 500,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals(500, $result->processing_time_ms);
    }

    /** @test */
    public function mark_complete_broadcasts_events(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        Event::assertDispatched(\App\Events\AnalysisIndicatorComplete::class);
        Event::assertDispatched(\App\Events\AnalysisProgressUpdated::class);
    }

    /** @test */
    public function mark_complete_handles_nonexistent_result(): void
    {
        Log::shouldReceive('warning')->once();

        $indicatorResult = IndicatorResult::success(
            slug: 'nonexistent',
            name: 'Nonexistent',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        // Should not throw exception
        $this->service->markComplete($this->job->id, 'nonexistent', $indicatorResult);

        // Verify no events were dispatched
        Event::assertNotDispatched(\App\Events\AnalysisIndicatorComplete::class);
    }

    // =========================================================================
    // MARK FAILED TESTS
    // =========================================================================

    /** @test */
    public function mark_failed_updates_result_status(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $this->service->markFailed($this->job->id, 'meta_tags', 'Connection timeout');

        $result->refresh();
        $this->assertEquals('failed', $result->status);
    }

    /** @test */
    public function mark_failed_updates_error_message(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $this->service->markFailed($this->job->id, 'meta_tags', 'Connection timeout');

        $result->refresh();
        $this->assertEquals('Connection timeout', $result->error_message);
    }

    /** @test */
    public function mark_failed_updates_job_progress(): void
    {
        $this->createResult('meta_tags', 'running');
        $this->createResult('headings', 'pending');

        $this->service->markFailed($this->job->id, 'meta_tags', 'Error');

        $this->job->refresh();
        $this->assertEquals(0, $this->job->completed_indicators);
        $this->assertEquals(1, $this->job->failed_indicators);
        $this->assertEquals(33, $this->job->progress_percent); // 1/3 (failed counts)
    }

    /** @test */
    public function mark_failed_broadcasts_events(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $this->service->markFailed($this->job->id, 'meta_tags', 'Error');

        Event::assertDispatched(\App\Events\AnalysisIndicatorComplete::class);
        Event::assertDispatched(\App\Events\AnalysisProgressUpdated::class);
    }

    /** @test */
    public function mark_failed_handles_nonexistent_result(): void
    {
        Log::shouldReceive('warning')->once();

        // Should not throw exception
        $this->service->markFailed($this->job->id, 'nonexistent', 'Error');

        Event::assertNotDispatched(\App\Events\AnalysisIndicatorComplete::class);
    }

    // =========================================================================
    // MARK RUNNING TESTS
    // =========================================================================

    /** @test */
    public function mark_running_updates_status(): void
    {
        $result = $this->createResult('meta_tags', 'pending');

        $this->service->markRunning($this->job->id, 'meta_tags');

        $result->refresh();
        $this->assertEquals('running', $result->status);
    }

    /** @test */
    public function mark_running_sets_started_at(): void
    {
        $result = $this->createResult('meta_tags', 'pending');

        $this->service->markRunning($this->job->id, 'meta_tags');

        $result->refresh();
        $this->assertNotNull($result->started_at);
    }

    // =========================================================================
    // MARK SKIPPED TESTS
    // =========================================================================

    /** @test */
    public function mark_skipped_updates_status(): void
    {
        $result = $this->createResult('meta_tags', 'pending');

        $this->service->markSkipped($this->job->id, 'meta_tags', 'Dependency failed');

        $result->refresh();
        $this->assertEquals('skipped', $result->status);
    }

    /** @test */
    public function mark_skipped_stores_reason(): void
    {
        $result = $this->createResult('meta_tags', 'pending');

        $this->service->markSkipped($this->job->id, 'meta_tags', 'Dependency failed');

        $result->refresh();
        $this->assertEquals('Dependency failed', $result->error_message);
    }

    /** @test */
    public function mark_skipped_updates_job_progress(): void
    {
        $this->createResult('meta_tags', 'pending');
        $this->createResult('headings', 'pending');
        $this->createResult('images', 'pending');

        $this->service->markSkipped($this->job->id, 'meta_tags', 'Skipped');

        $this->job->refresh();
        // Note: Skipped indicators don't count toward progress_percent
        // Progress is calculated as (completed + failed) / total
        $this->assertEquals(0, $this->job->progress_percent);
    }

    /** @test */
    public function mark_skipped_broadcasts_events(): void
    {
        $result = $this->createResult('meta_tags', 'pending');

        $this->service->markSkipped($this->job->id, 'meta_tags', 'Reason');

        Event::assertDispatched(\App\Events\AnalysisIndicatorComplete::class);
        Event::assertDispatched(\App\Events\AnalysisProgressUpdated::class);
    }

    // =========================================================================
    // IS JOB CANCELLED TESTS
    // =========================================================================

    /** @test */
    public function is_job_cancelled_returns_false_for_running_job(): void
    {
        $this->assertFalse($this->service->isJobCancelled($this->job->id));
    }

    /** @test */
    public function is_job_cancelled_returns_true_for_cancelled_job(): void
    {
        $this->job->update(['status' => 'cancelled']);

        $this->assertTrue($this->service->isJobCancelled($this->job->id));
    }

    /** @test */
    public function is_job_cancelled_returns_false_for_nonexistent_job(): void
    {
        $this->assertFalse($this->service->isJobCancelled(99999));
    }

    // =========================================================================
    // UPDATE PROGRESS TESTS
    // =========================================================================

    /** @test */
    public function update_progress_recalculates_percent(): void
    {
        // Set up completed indicators manually
        $this->createResult('meta_tags', 'completed');
        $this->createResult('headings', 'completed');
        $this->createResult('images', 'pending');

        // Manually update job counters
        $this->job->update([
            'completed_indicators' => 2,
            'failed_indicators' => 0,
        ]);

        $this->service->updateProgress($this->job->id, 'headings', 85.0);

        $this->job->refresh();
        $this->assertEquals(67, $this->job->progress_percent); // 2/3
    }

    /** @test */
    public function update_progress_handles_nonexistent_job(): void
    {
        Log::shouldReceive('warning')->once();

        // Should not throw
        $this->service->updateProgress(99999, 'meta_tags', 85.0);
    }

    /** @test */
    public function update_progress_works_for_running_jobs(): void
    {
        $this->createResult('meta_tags', 'completed');
        $this->createResult('headings', 'pending');

        // Manually update job counters
        $this->job->update([
            'completed_indicators' => 1,
            'failed_indicators' => 0,
        ]);

        $this->service->updateProgress($this->job->id, 'meta_tags', 85.0);

        // Progress should be updated
        $this->job->refresh();
        $this->assertEquals(33, $this->job->progress_percent);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function full_progress_tracking_lifecycle(): void
    {
        // Create 3 indicators
        $result1 = $this->createResult('meta_tags', 'pending');
        $result2 = $this->createResult('headings', 'pending');
        $result3 = $this->createResult('images', 'pending');

        // Start first indicator
        $this->service->markRunning($this->job->id, 'meta_tags');
        $result1->refresh();
        $this->assertEquals('running', $result1->status);

        // Complete first indicator
        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );
        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $this->job->refresh();
        $this->assertEquals(1, $this->job->completed_indicators);
        $this->assertEquals(33, $this->job->progress_percent);

        // Fail second indicator
        $this->service->markRunning($this->job->id, 'headings');
        $this->service->markFailed($this->job->id, 'headings', 'Error');

        $this->job->refresh();
        $this->assertEquals(1, $this->job->completed_indicators);
        $this->assertEquals(1, $this->job->failed_indicators);
        $this->assertEquals(67, $this->job->progress_percent); // (1 + 1) / 3

        // Skip third indicator
        $this->service->markSkipped($this->job->id, 'images', 'Dependency failed');

        $this->job->refresh();
        // Progress is (completed + failed) / total = (1 + 1) / 3 = 67%
        // Skipped doesn't count toward progress in calculateProgress
        $this->assertEquals(67, $this->job->progress_percent);
    }

    /** @test */
    public function mixed_completed_failed_progress_calculation(): void
    {
        // Create results with specific statuses
        $result1 = $this->createResult('meta_tags', 'completed');
        $result1->update(['score' => 85.0, 'max_score' => 100.0, 'percentage' => 85]);
        
        $result2 = $this->createResult('headings', 'failed');
        $result2->update(['error_message' => 'Error']);
        
        $result3 = $this->createResult('images', 'pending');

        // Manually update job counters to simulate partial processing
        $this->job->update([
            'completed_indicators' => 1,
            'failed_indicators' => 1,
        ]);

        // Now complete the third indicator
        $result3->update(['status' => 'running', 'started_at' => now()]);
        
        $indicatorResult = IndicatorResult::success(
            slug: 'images',
            name: 'Images',
            score: 90.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'images', $indicatorResult);

        $this->job->refresh();
        $this->assertEquals(2, $this->job->completed_indicators);
        $this->assertEquals(1, $this->job->failed_indicators);
        $this->assertEquals(100, $this->job->progress_percent);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function handles_long_error_messages(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $longError = str_repeat('Error message. ', 100);

        $this->service->markFailed($this->job->id, 'meta_tags', $longError);

        $result->refresh();
        $this->assertEquals($longError, $result->error_message);
    }

    /** @test */
    public function handles_unicode_in_data(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 85.0,
            maxScore: 100.0,
            data: ['title' => '日本語タイトル 🎉'],
            recommendations: [],
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals('日本語タイトル 🎉', $result->data['title']);
    }

    /** @test */
    public function handles_zero_score(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 0.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals(0.0, (float) $result->score);
        $this->assertEquals(0, $result->percentage);
    }

    /** @test */
    public function handles_perfect_score(): void
    {
        $result = $this->createResult('meta_tags', 'running');
        $result->update(['started_at' => now()]);

        $indicatorResult = IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 100.0,
            maxScore: 100.0,
            processingTimeMs: 100,
        );

        $this->service->markComplete($this->job->id, 'meta_tags', $indicatorResult);

        $result->refresh();
        $this->assertEquals(100.0, (float) $result->score);
        $this->assertEquals(100, $result->percentage);
    }
}
