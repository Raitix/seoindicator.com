<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\DetectContentDecayForSiteJob;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for DetectContentDecayForSiteJob.
 *
 * @see TASK-36-9
 * @see TEST-36-9
 */
class DetectContentDecayForSiteJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // DJ-INST: Job Instantiation Tests
    // ==========================================

    /** @test */
    public function job_class_exists(): void
    {
        $this->assertTrue(class_exists(DetectContentDecayForSiteJob::class));
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function job_implements_should_be_unique(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test */
    public function job_has_correct_tries(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test */
    public function job_has_correct_timeout(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(600, $job->timeout);
    }

    /** @test */
    public function job_has_correct_queue(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals('content-decay', $job->queue);
    }

    /** @test */
    public function job_stores_site_id(): void
    {
        $job = new DetectContentDecayForSiteJob(123);
        $this->assertEquals(123, $job->siteId);
    }

    /** @test */
    public function job_has_handle_method(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test */
    public function job_has_failed_method(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // DJ-DISPATCH: Job Dispatch Tests
    // ==========================================

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1);

        Queue::assertPushed(DetectContentDecayForSiteJob::class);
    }

    /** @test */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        DetectContentDecayForSiteJob::dispatch(1);

        Queue::assertPushedOn('content-decay', DetectContentDecayForSiteJob::class);
    }

    /** @test */
    public function job_unique_id_based_on_site_id(): void
    {
        $job1 = new DetectContentDecayForSiteJob(1);
        $job2 = new DetectContentDecayForSiteJob(1);
        $job3 = new DetectContentDecayForSiteJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('1', $job2->uniqueId());
        $this->assertEquals('2', $job3->uniqueId());
    }

    /** @test */
    public function job_unique_for_correct_duration(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    /** @test */
    public function job_has_correct_backoff(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $this->assertEquals([60, 300, 900], $job->backoff());
    }

    /** @test */
    public function job_has_correct_tags(): void
    {
        $job = new DetectContentDecayForSiteJob(123);
        $tags = $job->tags();

        $this->assertContains('site:123', $tags);
        $this->assertContains('content-decay:detection', $tags);
    }

    // ==========================================
    // DJ-HANDLE: Job Handle Method Tests
    // ==========================================

    /** @test */
    public function handle_loads_site_and_calls_service(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->once()
            ->andReturn(['candidates_created' => 1]);

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);

        $project->refresh();
        $this->assertNotNull($project->last_scanned_at);
    }

    /** @test */
    public function handle_returns_early_if_site_not_found(): void
    {
        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(99999);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_project_not_found(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_detection_disabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => false,
        ]);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob($site->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_validates_site_id(): void
    {
        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldNotReceive('detectForSite');

        $job = new DetectContentDecayForSiteJob(-1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_throws_on_service_exception(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('detectForSite')
            ->andThrow(new \Exception('Detection failed'));

        $job = new DetectContentDecayForSiteJob($site->id);

        $this->expectException(\Exception::class);
        $job->handle($mockService);
    }

    // ==========================================
    // DJ-FAIL: Job Failure Handling Tests
    // ==========================================

    /** @test */
    public function failed_method_exists(): void
    {
        $job = new DetectContentDecayForSiteJob(1);
        $job->failed(new \Exception('Test'));

        $this->assertTrue(true);
    }
}
