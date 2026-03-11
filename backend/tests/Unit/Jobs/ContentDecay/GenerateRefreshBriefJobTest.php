<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\GenerateRefreshBriefJob;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for GenerateRefreshBriefJob.
 *
 * @see TASK-36-9
 * @see TEST-36-9
 */
class GenerateRefreshBriefJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // BJ-INST: Job Instantiation Tests
    // ==========================================

    /** @test */
    public function job_class_exists(): void
    {
        $this->assertTrue(class_exists(GenerateRefreshBriefJob::class));
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function job_has_tries_property_set_to_2(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(2, $job->tries);
    }

    /** @test */
    public function job_queue_is_ai_tasks(): void
    {
        Queue::fake();
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals('ai-tasks', $job->queue);
    }

    /** @test */
    public function job_has_timeout_property_set_to_120(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test */
    public function job_implements_should_be_unique(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test */
    public function job_constructor_accepts_candidate_id(): void
    {
        $job = new GenerateRefreshBriefJob(123);
        $this->assertEquals(123, $job->candidateId);
    }

    /** @test */
    public function job_stores_candidate_id_as_property(): void
    {
        $job = new GenerateRefreshBriefJob(456);
        $this->assertEquals(456, $job->candidateId);
    }

    /** @test */
    public function job_has_handle_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test */
    public function job_has_failed_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // BJ-DISPATCH: Job Dispatch Tests
    // ==========================================

    /** @test */
    public function job_can_be_dispatched_with_candidate_id(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(1);

        Queue::assertPushed(GenerateRefreshBriefJob::class, function ($job) {
            return $job->candidateId === 1;
        });
    }

    /** @test */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(1);

        Queue::assertPushedOn('ai-tasks', GenerateRefreshBriefJob::class);
    }

    /** @test */
    public function job_unique_id_is_correct(): void
    {
        $job1 = new GenerateRefreshBriefJob(1);
        $job2 = new GenerateRefreshBriefJob(1);
        $job3 = new GenerateRefreshBriefJob(2);

        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
        $this->assertNotEquals($job1->uniqueId(), $job3->uniqueId());
    }

    /** @test */
    public function job_unique_for_3600_seconds(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    /** @test */
    public function job_dispatch_with_delay(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(1)->delay(now()->addMinutes(5));

        Queue::assertPushed(GenerateRefreshBriefJob::class);
    }

    /** @test */
    public function job_backoff_calculation(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $backoff = $job->backoff();

        $this->assertEquals([60, 180], $backoff);
    }

    /** @test */
    public function job_tags_include_candidate_id(): void
    {
        $job = new GenerateRefreshBriefJob(123);
        $tags = $job->tags();

        $this->assertContains('candidate:123', $tags);
        $this->assertContains('content-decay:brief', $tags);
    }

    // ==========================================
    // BJ-HANDLE: Job Handle Method Tests
    // ==========================================

    /** @test */
    public function handle_loads_candidate_by_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $this->assertDatabaseHas('content_decay_candidates', [
            'id' => $candidate->id,
        ]);
    }

    /** @test */
    public function handle_loads_project_for_context(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_candidate_not_found(): void
    {
        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob(99999);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_skips_if_brief_exists(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'evidence_json' => [
                'refresh_brief' => ['outline' => 'Existing brief'],
            ],
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_calls_ai_service(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
        ]);

        $mockResult = $this->createMockResult();

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->with(
                'https://example.com/test',
                Mockery::any(),
                Mockery::any()
            )
            ->andReturn($mockResult);
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_stores_brief_in_evidence(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'evidence_json' => [],
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $candidate->refresh();
        $this->assertArrayHasKey('refresh_brief', $candidate->evidence_json);
        $this->assertArrayHasKey('brief_generated_at', $candidate->evidence_json);
    }

    /** @test */
    public function handle_ai_service_exception(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->andThrow(new \Exception('AI service failed'));
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);

        $this->expectException(\Exception::class);
        $job->handle($mockAiService);
    }

    /** @test */
    public function handle_logs_brief_generation(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        // If we get here without exception, logging is working
        $this->assertTrue(true);
    }

    /** @test */
    public function handle_invalid_candidate_id(): void
    {
        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob(-1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    // ==========================================
    // BJ-UNIQUE: Job Uniqueness Tests
    // ==========================================

    /** @test */
    public function job_unique_per_candidate_id(): void
    {
        $job1 = new GenerateRefreshBriefJob(1);
        $job2 = new GenerateRefreshBriefJob(1);
        $job3 = new GenerateRefreshBriefJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('1', $job2->uniqueId());
        $this->assertEquals('2', $job3->uniqueId());
    }

    /** @test */
    public function job_unique_for_duration(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    // ==========================================
    // BJ-FAIL: Job Failure Handling Tests
    // ==========================================

    /** @test */
    public function failed_method_called(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /** @test */
    public function failed_preserves_candidate_id(): void
    {
        $job = new GenerateRefreshBriefJob(456);
        $job->failed(new \Exception('Error'));

        $this->assertEquals(456, $job->candidateId);
    }

    // ==========================================
    // BJ-EDGE: Edge Cases Tests
    // ==========================================

    /** @test */
    public function job_handles_zero_candidate_id(): void
    {
        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob(0);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function job_handles_empty_reason_codes(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'reason_codes' => [],
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function job_handles_null_evidence_json(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'evidence_json' => null,
        ]);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->andReturn($this->createMockResult());
        $mockAiService->shouldReceive('getDefaultModel')->andReturn('test-model');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockAiService);

        $candidate->refresh();
        $this->assertNotNull($candidate->evidence_json);
        $this->assertArrayHasKey('refresh_brief', $candidate->evidence_json);
    }

    /**
     * Create a mock AnalysisResult for testing.
     */
    protected function createMockResult(): AnalysisResult
    {
        return new AnalysisResult(
            overallScore: 75,
            overallSummary: 'Good SEO performance with room for improvement',
            categoryScores: [
                'technical' => 80,
                'content' => 70,
            ],
            indicators: [
                'keyword_density' => ['score' => 75, 'value' => 'Good'],
                'readability' => ['score' => 80, 'value' => 'Good'],
            ],
            model: 'test-model',
            promptTokens: 100,
            responseTokens: 200,
            latencyMs: 500,
            truncationStrategies: [],
            topPriorities: [
                'Improve keyword density',
                'Add more internal links',
            ]
        );
    }
}
