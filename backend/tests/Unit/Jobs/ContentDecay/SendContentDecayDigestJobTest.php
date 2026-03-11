<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\SendContentDecayDigestJob;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDigestServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for SendContentDecayDigestJob.
 *
 * @see TASK-36-9
 * @see TEST-36-9
 */
class SendContentDecayDigestJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SJ-INST: Job Instantiation Tests
    // ==========================================

    /** @test */
    public function job_class_exists(): void
    {
        $this->assertTrue(class_exists(SendContentDecayDigestJob::class));
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function job_implements_should_be_unique(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test */
    public function job_has_correct_tries(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test */
    public function job_has_correct_timeout(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test */
    public function job_has_correct_queue(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals('notifications', $job->queue);
    }

    /** @test */
    public function job_stores_project_id(): void
    {
        $job = new SendContentDecayDigestJob(123);
        $this->assertEquals(123, $job->projectId);
    }

    /** @test */
    public function job_has_handle_method(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test */
    public function job_has_failed_method(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // SJ-DISPATCH: Job Dispatch Tests
    // ==========================================

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();

        SendContentDecayDigestJob::dispatch(1);

        Queue::assertPushed(SendContentDecayDigestJob::class);
    }

    /** @test */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        SendContentDecayDigestJob::dispatch(1);

        Queue::assertPushedOn('notifications', SendContentDecayDigestJob::class);
    }

    /** @test */
    public function job_unique_id_based_on_project_id(): void
    {
        $job1 = new SendContentDecayDigestJob(1);
        $job2 = new SendContentDecayDigestJob(1);
        $job3 = new SendContentDecayDigestJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('1', $job2->uniqueId());
        $this->assertEquals('2', $job3->uniqueId());
    }

    /** @test */
    public function job_has_correct_backoff(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $this->assertEquals([60, 300, 900], $job->backoff());
    }

    /** @test */
    public function job_has_correct_tags(): void
    {
        $job = new SendContentDecayDigestJob(123);
        $tags = $job->tags();

        $this->assertContains('project:123', $tags);
        $this->assertContains('content-decay:digest', $tags);
    }

    // ==========================================
    // SJ-HANDLE: Job Handle Method Tests
    // ==========================================

    /** @test */
    public function handle_calls_digest_service(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->andReturn(true);
        $mockService->shouldReceive('generateDigest')->andReturn([
            'new_decay_count' => 1,
            'critical_count' => 0,
            'recovered_count' => 0,
        ]);
        $mockService->shouldReceive('sendDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);

        $project->refresh();
        $this->assertNotNull($project->last_digest_sent_at);
    }

    /** @test */
    public function handle_returns_early_if_project_not_found(): void
    {
        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldNotReceive('generateDigest');

        $job = new SendContentDecayDigestJob(99999);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_digest_disabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => false,
        ]);

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldNotReceive('generateDigest');

        $job = new SendContentDecayDigestJob(1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_should_send_false(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->andReturn(false);
        $mockService->shouldNotReceive('generateDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_owner_not_found(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Delete the user to simulate owner not found
        $user->delete();

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->andReturn(true);
        $mockService->shouldNotReceive('generateDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_skips_empty_digest(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->andReturn(true);
        $mockService->shouldReceive('generateDigest')->andReturn([
            'new_decay_count' => 0,
            'critical_count' => 0,
            'recovered_count' => 0,
        ]);
        $mockService->shouldNotReceive('sendDigest');

        $job = new SendContentDecayDigestJob($project->id);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_validates_project_id(): void
    {
        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldNotReceive('generateDigest');

        $job = new SendContentDecayDigestJob(-1);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_throws_on_service_exception(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $mockService = Mockery::mock(ContentDecayDigestServiceInterface::class);
        $mockService->shouldReceive('shouldSendDigest')->andReturn(true);
        $mockService->shouldReceive('generateDigest')
            ->andThrow(new \Exception('Digest failed'));

        $job = new SendContentDecayDigestJob($project->id);

        $this->expectException(\Exception::class);
        $job->handle($mockService);
    }

    // ==========================================
    // SJ-FAIL: Job Failure Handling Tests
    // ==========================================

    /** @test */
    public function failed_method_exists(): void
    {
        $job = new SendContentDecayDigestJob(1);
        $job->failed(new \Exception('Test'));

        $this->assertTrue(true);
    }
}
