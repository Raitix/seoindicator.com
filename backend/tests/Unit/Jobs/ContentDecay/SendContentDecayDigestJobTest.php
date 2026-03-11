<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\SendContentDecayDigestJob;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDigestServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for SendContentDecayDigestJob.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class SendContentDecayDigestJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // ==========================================
    // Job Instantiation Tests (SJ-INST)
    // ==========================================

    /** @test SJ-INST-01: Job class exists in correct namespace */
    public function job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(SendContentDecayDigestJob::class));
    }

    /** @test SJ-INST-02: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test SJ-INST-03: Job has $tries property set to 3 */
    public function job_has_tries_property_set_to_3(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test SJ-INST-04: Job queue is set to 'notifications' */
    public function job_queue_is_set_to_notifications(): void
    {
        $job = new SendContentDecayDigestJob(1);
        // Queue is set via onQueue() in constructor
        // We verify this through dispatch tests
        $this->assertTrue(true);
    }

    /** @test SJ-INST-05: Job has $timeout property set to 120 */
    public function job_has_timeout_property_set_to_120(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test SJ-INST-06: Job implements ShouldBeUnique */
    public function job_implements_should_be_unique(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test SJ-INST-07: Job constructor accepts project_id */
    public function job_constructor_accepts_project_id(): void
    {
        $job = new SendContentDecayDigestJob(123);
        $this->assertEquals(123, $job->projectId);
    }

    /** @test SJ-INST-08: Job stores project_id as property */
    public function job_stores_project_id_as_property(): void
    {
        $job = new SendContentDecayDigestJob(456);
        $this->assertEquals(456, $job->projectId);
    }

    /** @test SJ-INST-09: Job has handle() method */
    public function job_has_handle_method(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test SJ-INST-10: Job has failed() method */
    public function job_has_failed_method(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // Job Dispatch Tests (SJ-DISPATCH)
    // ==========================================

    /** @test SJ-DISPATCH-01: Job can be dispatched with project_id */
    public function job_can_be_dispatched_with_project_id(): void
    {
        Queue::fake();

        SendContentDecayDigestJob::dispatch(123);

        Queue::assertPushed(SendContentDecayDigestJob::class, function ($job) {
            return $job->projectId === 123;
        });
    }

    /** @test SJ-DISPATCH-02: Job dispatched to correct queue */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        SendContentDecayDigestJob::dispatch(1);

        Queue::assertPushedOn('notifications', SendContentDecayDigestJob::class);
    }

    /** @test SJ-DISPATCH-03: Job unique per project_id */
    public function job_unique_per_project_id(): void
    {
        $job1 = new SendContentDecayDigestJob(1);
        $job2 = new SendContentDecayDigestJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('2', $job2->uniqueId());
    }

    /** @test SJ-DISPATCH-04: Job unique for 3600 seconds */
    public function job_unique_for_3600_seconds(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    // ==========================================
    // Job Handle Method Tests (SJ-HANDLE)
    // ==========================================

    /** @test SJ-HANDLE-01: Loads project by project_id */
    public function loads_project_by_project_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')
            ->once()
            ->withArgs(function ($arg) use ($project) {
                return $arg->id === $project->id;
            })
            ->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->once()
            ->andReturn([
                'new_decay_count' => 5,
                'critical_count' => 2,
                'recovered_count' => 1,
            ]);
        $mockService->shouldReceive('sendDigest')
            ->once();

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    /** @test SJ-HANDLE-06: Handles project not found */
    public function handles_project_not_found(): void
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldNotReceive('generateDigest');
        $mockService->shouldNotReceive('sendDigest');

        $job = new SendContentDecayDigestJob(999999);
        $job->handle($mockService);
    }

    /** @test SJ-HANDLE-08: Handles digest disabled */
    public function handles_digest_disabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => false,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldNotReceive('generateDigest');
        $mockService->shouldNotReceive('sendDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    /** @test SJ-HANDLE-07: Handles owner not found */
    public function handles_owner_not_found(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        // Delete the user directly from database (bypassing model events)
        // This simulates a case where the project owner no longer exists
        \DB::table('users')->where('id', $user->id)->delete();

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        // shouldSendDigest may or may not be called depending on when user check happens
        $mockService->shouldReceive('shouldSendDigest')->zeroOrMoreTimes()->andReturn(true);
        $mockService->shouldNotReceive('generateDigest');
        $mockService->shouldNotReceive('sendDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    /** @test SJ-HANDLE-12: Handles empty digest */
    public function handles_empty_digest(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->once()->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->once()
            ->andReturn([
                'new_decay_count' => 0,
                'critical_count' => 0,
                'recovered_count' => 0,
            ]);
        $mockService->shouldNotReceive('sendDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    // ==========================================
    // Digest Content Tests (SJ-DIGEST)
    // ==========================================

    /** @test SJ-DIGEST-01: Digest contains new_decay_count */
    public function digest_contains_new_decay_count(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->once()->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->once()
            ->andReturn([
                'new_decay_count' => 10,
                'critical_count' => 2,
                'recovered_count' => 3,
                'total_lost_clicks' => 500,
                'total_upside' => 1000,
                'top_candidates' => [],
                'recovered_pages' => [],
            ]);
        $mockService->shouldReceive('sendDigest')
            ->once()
            ->withArgs(function ($user, $digest) {
                return isset($digest['new_decay_count']) && $digest['new_decay_count'] === 10;
            });

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    /** @test SJ-DIGEST-02: Digest contains critical_count */
    public function digest_contains_critical_count(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->once()->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->once()
            ->andReturn([
                'new_decay_count' => 5,
                'critical_count' => 3,
                'recovered_count' => 1,
            ]);
        $mockService->shouldReceive('sendDigest')
            ->once()
            ->withArgs(function ($user, $digest) {
                return isset($digest['critical_count']) && $digest['critical_count'] === 3;
            });

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    /** @test SJ-DIGEST-03: Digest contains recovered_count */
    public function digest_contains_recovered_count(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'digest_enabled' => true,
        ]);

        $mockService = $this->mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->once()->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->once()
            ->andReturn([
                'new_decay_count' => 2,
                'critical_count' => 1,
                'recovered_count' => 4,
            ]);
        $mockService->shouldReceive('sendDigest')
            ->once()
            ->withArgs(function ($user, $digest) {
                return isset($digest['recovered_count']) && $digest['recovered_count'] === 4;
            });

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);
    }

    // ==========================================
    // Job Failure Handling Tests (SJ-FAIL)
    // ==========================================

    /** @test SJ-FAIL-01: Job failed() method called */
    public function job_failed_method_called(): void
    {
        Log::shouldReceive('error')->once();

        $job = new SendContentDecayDigestJob(1);
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /** @test SJ-FAIL-02: Job logs failure with context */
    public function job_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['project_id']) &&
                    isset($context['error']);
            });

        $job = new SendContentDecayDigestJob(123);
        $job->failed(new \Exception('Test error'));
    }

    // ==========================================
    // Job Tags Tests
    // ==========================================

    /** @test: Job has correct tags */
    public function job_has_correct_tags(): void
    {
        $job = new SendContentDecayDigestJob(123);
        $tags = $job->tags();

        $this->assertContains('project:123', $tags);
        $this->assertContains('content-decay:digest', $tags);
    }

    // ==========================================
    // Job Backoff Tests
    // ==========================================

    /** @test: Job has backoff configuration */
    public function job_has_backoff_configuration(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $backoff = $job->backoff();

        $this->assertEquals([60, 300, 900], $backoff);
    }
}
