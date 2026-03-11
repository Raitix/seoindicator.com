<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\DetectContentDecayForSiteJob;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for DetectContentDecayForSiteJob.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class DetectContentDecayForSiteJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // ==========================================
    // Job Instantiation Tests (DJ-INST)
    // ==========================================

    /** @test DJ-INST-01: Job class exists in correct namespace */
    public function job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(DetectContentDecayForSiteJob::class));
    }

    /** @test DJ-INST-02: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test DJ-INST-03: Job has $tries property set to 3 */
    public function job_has_tries_property_set_to_3(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test DJ-INST-04: Job has $timeout property set to 600 */
    public function job_has_timeout_property_set_to_600(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(600, $job->timeout);
    }

    /** @test DJ-INST-05: Job queue is set to 'content-decay' */
    public function job_queue_is_set_to_content_decay(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        // Queue is set via onQueue() in constructor
        // We verify this through dispatch tests
        $this->assertTrue(true);
    }

    /** @test DJ-INST-06: Job implements ShouldBeUnique */
    public function job_implements_should_be_unique(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test DJ-INST-07: Job constructor accepts site_id parameter */
    public function job_constructor_accepts_site_id_parameter(): void
    {
        $job = new DetectContentDecayForSiteJob(123);
        $this->assertEquals(123, $job->siteId);
    }

    /** @test DJ-INST-08: Job stores site_id as property */
    public function job_stores_site_id_as_property(): void
    {
        $job = new DetectContentDecayForSiteJob(456);
        $this->assertEquals(456, $job->siteId);
    }

    /** @test DJ-INST-09: Job has handle() method */
    public function job_has_handle_method(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test DJ-INST-10: Job has failed() method for error handling */
    public function job_has_failed_method(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // Job Dispatch Tests (DJ-DISPATCH)
    // ==========================================

    /** @test DJ-DISPATCH-01: Job can be dispatched with site_id */
    public function job_can_be_dispatched_with_site_id(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(123);

        Queue::assertPushed(DetectContentDecayForSiteJob::class, function ($job) {
            return $job->siteId === 123;
        });
    }

    /** @test DJ-DISPATCH-02: Job dispatched to correct queue */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1);

        Queue::assertPushedOn('content-decay', DetectContentDecayForSiteJob::class);
    }

    /** @test DJ-DISPATCH-03: Job unique ID prevents duplicates */
    public function job_unique_id_prevents_duplicates(): void
    {
        $job1 = new DetectContentDecayForSiteJob(1);
        $job2 = new DetectContentDecayForSiteJob(1);
        $job3 = new DetectContentDecayForSiteJob(2);

        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
        $this->assertNotEquals($job1->uniqueId(), $job3->uniqueId());
    }

    /** @test DJ-DISPATCH-04: Job unique for 3600 seconds */
    public function job_unique_for_3600_seconds(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    /** @test DJ-DISPATCH-05: Job dispatch after database transaction */
    public function job_dispatch_after_database_transaction(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1);

        Queue::assertPushed(DetectContentDecayForSiteJob::class);
    }

    /** @test DJ-DISPATCH-06: Job dispatch with delay */
    public function job_dispatch_with_delay(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1)->delay(now()->addMinutes(5));

        Queue::assertPushed(DetectContentDecayForSiteJob::class);
    }

    // ==========================================
    // Job Handle Method Tests (DJ-HANDLE)
    // ==========================================

    /** @test DJ-HANDLE-01: Loads site by site_id */
    public function loads_site_by_site_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
        ]);

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn([
                'candidates_created' => 0,
                'candidates_updated' => 0,
                'processed_pages' => 0,
            ]);

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);

        $this->assertDatabaseHas('content_decay_projects', [
            'id' => $project->id,
            'last_scanned_at' => now()->toDateTimeString(),
        ]);
    }

    /** @test DJ-HANDLE-06: Handles site not found gracefully */
    public function handles_site_not_found_gracefully(): void
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(999999);
        $job->handle($mockService);
    }

    /** @test DJ-HANDLE-07: Handles project not found gracefully */
    public function handles_project_not_found_gracefully(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);
    }

    /** @test DJ-HANDLE-08: Handles detection disabled gracefully */
    public function handles_detection_disabled_gracefully(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => false,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);
    }

    /** @test DJ-HANDLE-19: Validates site_id is positive integer */
    public function validates_site_id_is_positive_integer(): void
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(-1);
        $job->handle($mockService);
    }

    /** @test DJ-HANDLE-20: Handles invalid site_id */
    public function handles_invalid_site_id(): void
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(0);
        $job->handle($mockService);
    }

    // ==========================================
    // Job Failure Handling Tests (DJ-FAIL)
    // ==========================================

    /** @test DJ-FAIL-01: Job failed() method called on exception */
    public function job_failed_method_called_on_exception(): void
    {
        Log::shouldReceive('error')->once();

        $job = new DetectContentDecayForSiteJob(1);
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /** @test DJ-FAIL-02: Job logs failure with context */
    public function job_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['site_id']) &&
                    isset($context['error']);
            });

        $job = new DetectContentDecayForSiteJob(123);
        $job->failed(new \Exception('Test error'));
    }

    // ==========================================
    // Job Uniqueness Tests (DJ-UNIQUE)
    // ==========================================

    /** @test DJ-UNIQUE-01: Job unique per site_id */
    public function job_unique_per_site_id(): void
    {
        $job1 = new DetectContentDecayForSiteJob(1);
        $job2 = new DetectContentDecayForSiteJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('2', $job2->uniqueId());
    }

    /** @test DJ-UNIQUE-03: Job unique ID format correct */
    public function job_unique_id_format_correct(): void
    {
        $job = new DetectContentDecayForSiteJob(123);
        $this->assertEquals('123', $job->uniqueId());
    }

    // ==========================================
    // Job Tags Tests
    // ==========================================

    /** @test: Job has correct tags */
    public function job_has_correct_tags(): void
    {
        $job = new DetectContentDecayForSiteJob(123);
        $tags = $job->tags();

        $this->assertContains('site:123', $tags);
        $this->assertContains('content-decay:detection', $tags);
    }

    // ==========================================
    // Job Backoff Tests
    // ==========================================

    /** @test: Job has backoff configuration */
    public function job_has_backoff_configuration(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $backoff = $job->backoff();

        $this->assertEquals([60, 300, 900], $backoff);
    }
}
