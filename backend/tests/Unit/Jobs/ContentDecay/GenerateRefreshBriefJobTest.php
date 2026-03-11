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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for GenerateRefreshBriefJob.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class GenerateRefreshBriefJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function createTestCandidate(): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test-page',
            'page_url_hash' => hash('sha256', 'https://example.com/test-page'),
            'page_title' => 'Test Page Title',
            'page_type' => 'blog',
            'status' => 'backlog',
            'decay_score' => 50,
            'severity' => 'high',
            'primary_reason_code' => 'clicks_down',
            'reason_codes' => ['clicks_down', 'ctr_down'],
            'lost_click_estimate' => 100,
            'recovery_upside_estimate' => 500,
        ]);

        return [$candidate, $project];
    }

    // ==========================================
    // Job Instantiation Tests (BJ-INST)
    // ==========================================

    /** @test BJ-INST-01: Job class exists in correct namespace */
    public function job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(GenerateRefreshBriefJob::class));
    }

    /** @test BJ-INST-02: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test BJ-INST-03: Job has $tries property set to 2 */
    public function job_has_tries_property_set_to_2(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(2, $job->tries);
    }

    /** @test BJ-INST-04: Job queue is set to 'ai-tasks' */
    public function job_queue_is_set_to_ai_tasks(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        // Queue is set via onQueue() in constructor
        // We verify this through dispatch tests
        $this->assertTrue(true);
    }

    /** @test BJ-INST-05: Job has $timeout property set to 120 */
    public function job_has_timeout_property_set_to_120(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(120, $job->timeout);
    }

    /** @test BJ-INST-06: Job implements ShouldBeUnique */
    public function job_implements_should_be_unique(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test BJ-INST-07: Job constructor accepts candidate_id */
    public function job_constructor_accepts_candidate_id(): void
    {
        $job = new GenerateRefreshBriefJob(123);
        $this->assertEquals(123, $job->candidateId);
    }

    /** @test BJ-INST-08: Job stores candidate_id as property */
    public function job_stores_candidate_id_as_property(): void
    {
        $job = new GenerateRefreshBriefJob(456);
        $this->assertEquals(456, $job->candidateId);
    }

    /** @test BJ-INST-09: Job has handle() method */
    public function job_has_handle_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test BJ-INST-10: Job has failed() method */
    public function job_has_failed_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // Job Dispatch Tests (BJ-DISPATCH)
    // ==========================================

    /** @test BJ-DISPATCH-01: Job can be dispatched with candidate_id */
    public function job_can_be_dispatched_with_candidate_id(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(123);

        Queue::assertPushed(GenerateRefreshBriefJob::class, function ($job) {
            return $job->candidateId === 123;
        });
    }

    /** @test BJ-DISPATCH-02: Job dispatched to correct queue */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(1);

        Queue::assertPushedOn('ai-tasks', GenerateRefreshBriefJob::class);
    }

    /** @test BJ-DISPATCH-03: Job unique per candidate_id */
    public function job_unique_per_candidate_id(): void
    {
        $job1 = new GenerateRefreshBriefJob(1);
        $job2 = new GenerateRefreshBriefJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('2', $job2->uniqueId());
    }

    /** @test BJ-DISPATCH-04: Job unique for 3600 seconds */
    public function job_unique_for_3600_seconds(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    // ==========================================
    // Job Handle Method Tests (BJ-HANDLE)
    // ==========================================

    /** @test BJ-HANDLE-01: Loads candidate by candidate_id */
    public function loads_candidate_by_candidate_id(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 75,
            'indicators' => [],
            'suggestions' => ['Update content', 'Improve keywords'],
        ]);

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        $candidate->refresh();
        $this->assertArrayHasKey('refresh_brief', $candidate->evidence_json);
    }

    /** @test BJ-HANDLE-09: Handles candidate not found */
    public function handles_candidate_not_found(): void
    {
        Log::shouldReceive('warning')->once();

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob(999999);
        $job->handle($mockService);
    }

    /** @test BJ-HANDLE-08: Skips if brief already exists */
    public function skips_if_brief_already_exists(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        // Add existing brief
        $candidate->update([
            'evidence_json' => [
                'refresh_brief' => ['outline' => 'Existing brief'],
            ],
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        $candidate->refresh();
        // Should still have the existing brief
        $this->assertEquals('Existing brief', $candidate->evidence_json['refresh_brief']['outline']);
    }

    /** @test BJ-HANDLE-07: Stores brief in evidence_json */
    public function stores_brief_in_evidence_json(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 80,
            'indicators' => [
                'keyword_density' => ['score' => 70],
                'readability' => ['score' => 85],
            ],
            'suggestions' => ['Update keywords', 'Improve structure'],
        ]);

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        $candidate->refresh();
        $this->assertArrayHasKey('refresh_brief', $candidate->evidence_json);
        $this->assertArrayHasKey('outline', $candidate->evidence_json['refresh_brief']);
        $this->assertArrayHasKey('priority_actions', $candidate->evidence_json['refresh_brief']);
        $this->assertArrayHasKey('generated_at', $candidate->evidence_json['refresh_brief']);
    }

    /** @test BJ-HANDLE-08: Sets brief_generated_at timestamp */
    public function sets_brief_generated_at_timestamp(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 75,
            'indicators' => [],
            'suggestions' => [],
        ]);

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')->once()->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        $candidate->refresh();
        $this->assertArrayHasKey('brief_generated_at', $candidate->evidence_json);
    }

    // ==========================================
    // AI Integration Tests (BJ-AI)
    // ==========================================

    /** @test BJ-AI-01: Uses existing AI infrastructure */
    public function uses_existing_ai_infrastructure(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 75,
            'indicators' => [],
            'suggestions' => [],
        ]);

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($url, $html, $options) use ($candidate) {
                return $url === $candidate->page_url &&
                    is_string($html) &&
                    isset($options['model']) &&
                    isset($options['timeout']);
            })
            ->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);
    }

    /** @test BJ-AI-02: Constructs proper prompt */
    public function constructs_proper_prompt(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 75,
            'indicators' => [],
            'suggestions' => [],
        ]);

        $capturedHtml = null;

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($url, $html, $options) use (&$capturedHtml) {
                $capturedHtml = $html;
                return true;
            })
            ->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        // Verify prompt contains expected content
        $this->assertStringContainsString($candidate->page_url, $capturedHtml);
        $this->assertStringContainsString($candidate->page_title, $capturedHtml);
        $this->assertStringContainsString('clicks_down', $capturedHtml);
    }

    /** @test BJ-AI-10: Validates brief structure */
    public function validates_brief_structure(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockResult = AnalysisResult::fromArray([
            'overallScore' => 75,
            'indicators' => [
                'keyword_density' => ['score' => 60],
            ],
            'suggestions' => ['Update content'],
        ]);

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')->once()->andReturn($mockResult);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);
        $job->handle($mockService);

        $candidate->refresh();
        $brief = $candidate->evidence_json['refresh_brief'];

        $this->assertArrayHasKey('outline', $brief);
        $this->assertArrayHasKey('priority_actions', $brief);
        $this->assertArrayHasKey('suggestions', $brief);
        $this->assertArrayHasKey('generated_at', $brief);
        $this->assertArrayHasKey('model_used', $brief);
    }

    // ==========================================
    // Job Failure Handling Tests (BJ-FAIL)
    // ==========================================

    /** @test BJ-FAIL-01: Job failed() method called */
    public function job_failed_method_called(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /** @test BJ-FAIL-02: Job logs failure with context */
    public function job_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['candidate_id']) &&
                    isset($context['error']);
            });

        $job = new GenerateRefreshBriefJob(123);
        $job->failed(new \Exception('Test error'));
    }

    /** @test BJ-FAIL-05: Job updates candidate status on failure */
    public function job_updates_candidate_on_failure(): void
    {
        [$candidate, $project] = $this->createTestCandidate();

        $mockService = $this->mock(AIAnalysisServiceInterface::class);
        $mockService->shouldReceive('getDefaultModel')->andReturn('test-model');
        $mockService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('AI service failed'));

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob($candidate->id);

        try {
            $job->handle($mockService);
        } catch (\Exception $e) {
            // Expected
        }

        $candidate->refresh();
        $this->assertArrayHasKey('refresh_brief_error', $candidate->evidence_json);
    }

    // ==========================================
    // Job Tags Tests
    // ==========================================

    /** @test: Job has correct tags */
    public function job_has_correct_tags(): void
    {
        $job = new GenerateRefreshBriefJob(123);
        $tags = $job->tags();

        $this->assertContains('candidate:123', $tags);
        $this->assertContains('content-decay:brief', $tags);
    }

    // ==========================================
    // Job Backoff Tests
    // ==========================================

    /** @test: Job has backoff configuration */
    public function job_has_backoff_configuration(): void
    {
        $job = new GenerateRefreshBriefJob(1);
        $backoff = $job->backoff();

        $this->assertEquals([60, 180], $backoff);
    }
}
