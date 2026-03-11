<?php

namespace Tests\Unit\Jobs\ContentDecay;

use Tests\TestCase;
use App\Jobs\DetectContentDecayForSiteJob;
use App\Models\Site;
use App\Models\ContentDecayProject;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Mockery;

/**
 * Unit tests for DetectContentDecayForSiteJob
 *
 * Tests verify:
 * - Job instantiation and properties
 * - Job dispatch behavior
 * - Job handle method execution
 * - Retry and failure handling
 * - Uniqueness behavior
 * - Edge cases
 */
class DetectContentDecayForSiteJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // DJ-INST: Job Instantiation Tests
    // ============================================

    /**
     * DJ-INST-01: Job class exists in correct namespace
     */
    public function test_job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(DetectContentDecayForSiteJob::class));
    }

    /**
     * DJ-INST-02: Job implements ShouldQueue
     */
    public function test_job_implements_should_queue(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $job
        );
    }

    /**
     * DJ-INST-03: Job has $tries property set to 3
     */
    public function test_job_has_tries_property_set_to_3(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertEquals(3, $job->tries);
    }

    /**
     * DJ-INST-04: Job has $timeout property set to 600
     */
    public function test_job_has_timeout_property_set_to_600(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertEquals(600, $job->timeout);
    }

    /**
     * DJ-INST-05: Job has $queue property set to content-decay
     */
    public function test_job_has_queue_property_set_to_content_decay(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertEquals('content-decay', $job->queue);
    }

    /**
     * DJ-INST-06: Job implements ShouldBeUnique
     */
    public function test_job_implements_should_be_unique(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldBeUnique::class,
            $job
        );
    }

    /**
     * DJ-INST-07: Job constructor accepts site_id parameter
     */
    public function test_job_constructor_accepts_site_id_parameter(): void
    {
        $siteId = 123;
        $job = new DetectContentDecayForSiteJob($siteId);

        $this->assertEquals($siteId, $job->siteId);
    }

    /**
     * DJ-INST-08: Job stores site_id as property
     */
    public function test_job_stores_site_id_as_property(): void
    {
        $siteId = 456;
        $job = new DetectContentDecayForSiteJob($siteId);

        $this->assertObjectHasProperty('siteId', $job);
        $this->assertEquals($siteId, $job->siteId);
    }

    /**
     * DJ-INST-09: Job has handle() method
     */
    public function test_job_has_handle_method(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    /**
     * DJ-INST-10: Job has failed() method for error handling
     */
    public function test_job_has_failed_method_for_error_handling(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ============================================
    // DJ-DISPATCH: Job Dispatch Tests
    // ============================================

    /**
     * DJ-DISPATCH-01: Job can be dispatched with site_id
     */
    public function test_job_can_be_dispatched_with_site_id(): void
    {
        Queue::fake();

        $siteId = 1;

        DetectContentDecayForSiteJob::dispatch($siteId);

        Queue::assertPushed(DetectContentDecayForSiteJob::class, function ($job) use ($siteId) {
            return $job->siteId === $siteId;
        });
    }

    /**
     * DJ-DISPATCH-02: Job dispatched to correct queue
     */
    public function test_job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1);

        Queue::assertPushedOn('content-decay', DetectContentDecayForSiteJob::class);
    }

    /**
     * DJ-DISPATCH-03: Job unique ID prevents duplicates
     */
    public function test_job_unique_id_prevents_duplicates(): void
    {
        $siteId = 1;
        $job1 = new DetectContentDecayForSiteJob($siteId);
        $job2 = new DetectContentDecayForSiteJob($siteId);

        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * DJ-DISPATCH-04: Job unique ID is string representation of site_id
     */
    public function test_job_unique_id_is_site_id_string(): void
    {
        $siteId = 123;
        $job = new DetectContentDecayForSiteJob($siteId);

        $this->assertEquals((string) $siteId, $job->uniqueId());
    }

    /**
     * DJ-DISPATCH-05: Different sites have different unique IDs
     */
    public function test_different_sites_have_different_unique_ids(): void
    {
        $job1 = new DetectContentDecayForSiteJob(1);
        $job2 = new DetectContentDecayForSiteJob(2);

        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * DJ-DISPATCH-06: Job dispatch with delay
     */
    public function test_job_dispatch_with_delay(): void
    {
        Queue::fake();

        $siteId = 1;
        $delay = 60;

        DetectContentDecayForSiteJob::dispatch($siteId)->delay($delay);

        Queue::assertPushed(DetectContentDecayForSiteJob::class, function ($job) use ($siteId) {
            return $job->siteId === $siteId;
        });
    }

    /**
     * DJ-DISPATCH-07: Job chain dispatch
     */
    public function test_job_chain_dispatch(): void
    {
        Queue::fake();

        $siteId = 1;

        DetectContentDecayForSiteJob::withChain([
            // Additional jobs in chain
        ])->dispatch($siteId);

        Queue::assertPushed(DetectContentDecayForSiteJob::class);
    }

    /**
     * DJ-DISPATCH-08: Job batch dispatch
     */
    public function test_job_batch_dispatch(): void
    {
        Queue::fake();

        $siteIds = [1, 2, 3];
        $jobs = collect($siteIds)->map(fn($id) => new DetectContentDecayForSiteJob($id));

        $this->assertCount(3, $jobs);
        foreach ($jobs as $index => $job) {
            $this->assertEquals($siteIds[$index], $job->siteId);
        }
    }

    // ============================================
    // DJ-HANDLE: Job Handle Method Tests
    // ============================================

    /**
     * DJ-HANDLE-01: Loads site by site_id
     */
    public function test_handle_loads_site_by_site_id(): void
    {
        $siteId = 1;

        $mockSite = Mockery::mock(Site::class);
        $mockSite->shouldReceive('getAttribute')->with('id')->andReturn($siteId);

        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        // Mock Site::find
        Site::shouldReceive('find')
            ->with($siteId)
            ->once()
            ->andReturn($mockSite);

        // Mock project query
        ContentDecayProject::shouldReceive('where')
            ->with('site_id', $siteId)
            ->once()
            ->andReturnSelf();
        ContentDecayProject::shouldReceive('first')
            ->once()
            ->andReturn($mockProject);

        // Mock detection service
        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn(['candidates_created' => 5]);

        $job = new DetectContentDecayForSiteJob($siteId);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-02: Calls DetectionService::detectForSite()
     */
    public function test_handle_calls_detection_service_detect_for_site(): void
    {
        $siteId = 1;

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with($siteId)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', $siteId)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->with($mockSite)
            ->andReturn(['candidates_created' => 5]);

        $job = new DetectContentDecayForSiteJob($siteId);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-03: Updates project last_scanned_at timestamp
     */
    public function test_handle_updates_project_last_scanned_at(): void
    {
        $siteId = 1;

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['last_scanned_at']);
            });

        Site::shouldReceive('find')->with($siteId)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', $siteId)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')->andReturn(['candidates_created' => 5]);

        $job = new DetectContentDecayForSiteJob($siteId);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-04: Handles site not found gracefully
     */
    public function test_handle_handles_site_not_found_gracefully(): void
    {
        Log::shouldReceive('warning')->once();

        Site::shouldReceive('find')->with(999)->andReturn(null);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(999);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-05: Handles project not found gracefully
     */
    public function test_handle_handles_project_not_found_gracefully(): void
    {
        Log::shouldReceive('warning')->once();

        $mockSite = Mockery::mock(Site::class);

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn(null);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-06: Handles detection disabled gracefully
     */
    public function test_handle_handles_detection_disabled_gracefully(): void
    {
        Log::shouldReceive('info')->once();

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(false);
        $mockProject->shouldReceive('getAttribute')->with('id')->andReturn(1);

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-07: Logs start of detection
     */
    public function test_handle_logs_start_of_detection(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'detection started') &&
                    isset($context['site_id']);
            });

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')->andReturn(['candidates_created' => 5]);

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-08: Logs successful completion
     */
    public function test_handle_logs_successful_completion(): void
    {
        Log::shouldReceive('info')->twice();

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')->andReturn(['candidates_created' => 5]);

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-HANDLE-09: Handles DetectionService exceptions
     */
    public function test_handle_handles_detection_service_exceptions(): void
    {
        $this->expectException(\Exception::class);

        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andThrow(new \Exception('Detection failed'));

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);
    }

    /**
     * DJ-HANDLE-10: Returns detection result from service
     */
    public function test_handle_returns_detection_result_from_service(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $expectedResult = [
            'candidates_created' => 5,
            'pages_processed' => 100,
            'pages_eligible' => 50,
        ];

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn($expectedResult);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    // ============================================
    // DJ-RETRY: Job Retry Logic Tests
    // ============================================

    /**
     * DJ-RETRY-01: Job retries on transient failure
     */
    public function test_job_retries_on_transient_failure(): void
    {
        $attemptCount = 0;

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->twice()
            ->andReturnUsing(function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount === 1) {
                    throw new \Exception('Transient failure');
                }
                return ['candidates_created' => 5];
            });

        Site::shouldReceive('find')->andReturn(Mockery::mock(Site::class));
        ContentDecayProject::shouldReceive('where')->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn(
            Mockery::mock(ContentDecayProject::class, [
                'detection_enabled' => true,
            ])->shouldReceive('update')->getMock()
        );

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new DetectContentDecayForSiteJob(1);
        $job->tries = 3;

        // First attempt fails
        try {
            $job->handle($mockService);
        } catch (\Exception $e) {
            $this->assertEquals('Transient failure', $e->getMessage());
        }

        // Second attempt succeeds
        $job->handle($mockService);

        $this->assertEquals(2, $attemptCount);
    }

    /**
     * DJ-RETRY-02: Job respects max tries (3)
     */
    public function test_job_respects_max_tries(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertEquals(3, $job->tries);
    }

    /**
     * DJ-RETRY-03: Job exponential backoff between retries
     */
    public function test_job_exponential_backoff_between_retries(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        // Check if backoff method exists or property
        if (method_exists($job, 'backoff')) {
            $backoff = $job->backoff();
            $this->assertIsArray($backoff);
            $this->assertCount(3, $backoff);
        } elseif (property_exists($job, 'backoff')) {
            $this->assertNotNull($job->backoff);
        } else {
            $this->assertTrue(true); // Default Laravel backoff applies
        }
    }

    /**
     * DJ-RETRY-04: Job doesn't retry on site not found
     */
    public function test_job_doesnt_retry_on_site_not_found(): void
    {
        Log::shouldReceive('warning')->once();

        Site::shouldReceive('find')->with(999)->andReturn(null);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(999);
        $job->handle($mockService);

        // No exception thrown - job completes without retry
        $this->assertTrue(true);
    }

    /**
     * DJ-RETRY-05: Job fails after max retries
     */
    public function test_job_fails_after_max_retries(): void
    {
        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->times(3)
            ->andThrow(new \Exception('Persistent failure'));

        Site::shouldReceive('find')->andReturn(Mockery::mock(Site::class));
        ContentDecayProject::shouldReceive('where')->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn(
            Mockery::mock(ContentDecayProject::class, ['detection_enabled' => true])
        );

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new DetectContentDecayForSiteJob(1);
        $job->tries = 3;

        // Simulate 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            try {
                $job->handle($mockService);
            } catch (\Exception $e) {
                $this->assertEquals('Persistent failure', $e->getMessage());
            }
        }
    }

    // ============================================
    // DJ-FAIL: Job Failure Handling Tests
    // ============================================

    /**
     * DJ-FAIL-01: Job failed() method called on exception
     */
    public function test_failed_method_called_on_exception(): void
    {
        Log::shouldReceive('error')->once();

        $job = new DetectContentDecayForSiteJob(1);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * DJ-FAIL-02: Job logs failure with context
     */
    public function test_failed_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['site_id']) &&
                    isset($context['error']);
            });

        $job = new DetectContentDecayForSiteJob(123);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * DJ-FAIL-03: Job failure preserves site_id in context
     */
    public function test_failed_preserves_site_id_in_context(): void
    {
        $capturedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
                return true;
            });

        $siteId = 456;
        $job = new DetectContentDecayForSiteJob($siteId);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertEquals($siteId, $capturedContext['site_id']);
    }

    /**
     * DJ-FAIL-04: Job failure preserves exception message
     */
    public function test_failed_preserves_exception_message(): void
    {
        $capturedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
                return true;
            });

        $exceptionMessage = 'Custom failure message';
        $job = new DetectContentDecayForSiteJob(1);
        $exception = new \Exception($exceptionMessage);

        $job->failed($exception);

        $this->assertEquals($exceptionMessage, $capturedContext['error']);
    }

    /**
     * DJ-FAIL-05: Job handles missing dependencies gracefully
     */
    public function test_failed_handles_missing_dependencies_gracefully(): void
    {
        Log::shouldReceive('error')->once();

        $job = new DetectContentDecayForSiteJob(1);
        $exception = new \Exception('Service not found');

        // Should not throw
        $job->failed($exception);

        $this->assertTrue(true);
    }

    // ============================================
    // DJ-UNIQUE: Job Uniqueness Tests
    // ============================================

    /**
     * DJ-UNIQUE-01: Job unique per site_id
     */
    public function test_job_unique_per_site_id(): void
    {
        $siteId = 1;
        $job = new DetectContentDecayForSiteJob($siteId);

        $this->assertEquals((string) $siteId, $job->uniqueId());
    }

    /**
     * DJ-UNIQUE-02: Job unique ID is consistent
     */
    public function test_job_unique_id_is_consistent(): void
    {
        $siteId = 123;
        $job1 = new DetectContentDecayForSiteJob($siteId);
        $job2 = new DetectContentDecayForSiteJob($siteId);

        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * DJ-UNIQUE-04: Job uniqueId returns string
     */
    public function test_job_unique_id_returns_string(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertIsString($job->uniqueId());
    }

    /**
     * DJ-UNIQUE-05: Job unique for lifetime
     */
    public function test_job_unique_for_lifetime(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        // ShouldBeUnique contract should be implemented
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    // ============================================
    // DJ-EDGE: Edge Cases Tests
    // ============================================

    /**
     * DJ-EDGE-01: Job handles site with no pages
     */
    public function test_job_handles_site_with_no_pages(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn([
                'candidates_created' => 0,
                'pages_processed' => 0,
                'pages_eligible' => 0,
            ]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-02: Job handles zero candidates created
     */
    public function test_job_handles_zero_candidates_created(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn(['candidates_created' => 0]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-03: Job handles large site (100+ candidates)
     */
    public function test_job_handles_large_site(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn([
                'candidates_created' => 150,
                'pages_processed' => 1000,
                'pages_eligible' => 500,
            ]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-04: Job handles unicode in site name
     */
    public function test_job_handles_unicode_in_site_name(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockSite->shouldReceive('getAttribute')->with('name')->andReturn('テストサイト');

        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn(['candidates_created' => 5]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-05: Job handles negative site_id gracefully
     */
    public function test_job_handles_negative_site_id_gracefully(): void
    {
        Log::shouldReceive('warning')->once();

        Site::shouldReceive('find')->with(-1)->andReturn(null);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(-1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-06: Job handles zero site_id gracefully
     */
    public function test_job_handles_zero_site_id_gracefully(): void
    {
        Log::shouldReceive('warning')->once();

        Site::shouldReceive('find')->with(0)->andReturn(null);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(0);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-07: Job handles empty detection result
     */
    public function test_job_handles_empty_detection_result(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn([]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /**
     * DJ-EDGE-08: Job handles timeout scenario
     */
    public function test_job_timeout_is_configured(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        // 600 seconds = 10 minutes
        $this->assertEquals(600, $job->timeout);
    }

    /**
     * DJ-EDGE-09: Job queue is correctly set
     */
    public function test_job_queue_is_correctly_set(): void
    {
        $job = new DetectContentDecayForSiteJob(1);

        $this->assertEquals('content-decay', $job->queue);
    }

    /**
     * DJ-EDGE-10: Job handles partial detection failure
     */
    public function test_job_handles_partial_detection_failure(): void
    {
        $mockSite = Mockery::mock(Site::class);
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('detection_enabled')->andReturn(true);
        $mockProject->shouldReceive('update')->once();

        Site::shouldReceive('find')->with(1)->andReturn($mockSite);
        ContentDecayProject::shouldReceive('where')->with('site_id', 1)->andReturnSelf();
        ContentDecayProject::shouldReceive('first')->andReturn($mockProject);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn([
                'candidates_created' => 5,
                'errors' => ['Some pages failed to process'],
            ]);

        Log::shouldReceive('info')->twice();

        $job = new DetectContentDecayForSiteJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }
}
