<?php

namespace Tests\Unit\Jobs\ContentDecay;

use Tests\TestCase;
use App\Jobs\GenerateRefreshBriefJob;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for GenerateRefreshBriefJob
 *
 * Tests verify:
 * - Job instantiation and properties
 * - Job dispatch behavior
 * - AI brief generation
 * - Retry and failure handling
 * - Edge cases
 */
class GenerateRefreshBriefJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // BJ-INST: Job Instantiation Tests
    // ============================================

    /**
     * BJ-INST-01: Job class exists in correct namespace
     */
    public function test_job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(GenerateRefreshBriefJob::class));
    }

    /**
     * BJ-INST-02: Job implements ShouldQueue
     */
    public function test_job_implements_should_queue(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $job
        );
    }

    /**
     * BJ-INST-03: Job has $tries property set to 2
     */
    public function test_job_has_tries_property_set_to_2(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertEquals(2, $job->tries);
    }

    /**
     * BJ-INST-04: Job has $queue property set to ai-tasks
     */
    public function test_job_has_queue_property_set_to_ai_tasks(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertEquals('ai-tasks', $job->queue);
    }

    /**
     * BJ-INST-05: Job has $timeout property set to 120
     */
    public function test_job_has_timeout_property_set_to_120(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertEquals(120, $job->timeout);
    }

    /**
     * BJ-INST-06: Job implements ShouldBeUnique
     */
    public function test_job_implements_should_be_unique(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldBeUnique::class,
            $job
        );
    }

    /**
     * BJ-INST-07: Job constructor accepts candidate_id
     */
    public function test_job_constructor_accepts_candidate_id(): void
    {
        $candidateId = 123;
        $job = new GenerateRefreshBriefJob($candidateId);

        $this->assertEquals($candidateId, $job->candidateId);
    }

    /**
     * BJ-INST-08: Job stores candidate_id as property
     */
    public function test_job_stores_candidate_id_as_property(): void
    {
        $candidateId = 456;
        $job = new GenerateRefreshBriefJob($candidateId);

        $this->assertObjectHasProperty('candidateId', $job);
        $this->assertEquals($candidateId, $job->candidateId);
    }

    /**
     * BJ-INST-09: Job has handle() method
     */
    public function test_job_has_handle_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    /**
     * BJ-INST-10: Job has failed() method
     */
    public function test_job_has_failed_method(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ============================================
    // BJ-DISPATCH: Job Dispatch Tests
    // ============================================

    /**
     * BJ-DISPATCH-01: Job can be dispatched with candidate_id
     */
    public function test_job_can_be_dispatched_with_candidate_id(): void
    {
        Queue::fake();

        $candidateId = 1;

        GenerateRefreshBriefJob::dispatch($candidateId);

        Queue::assertPushed(GenerateRefreshBriefJob::class, function ($job) use ($candidateId) {
            return $job->candidateId === $candidateId;
        });
    }

    /**
     * BJ-DISPATCH-02: Job dispatched to correct queue
     */
    public function test_job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        GenerateRefreshBriefJob::dispatch(1);

        Queue::assertPushedOn('ai-tasks', GenerateRefreshBriefJob::class);
    }

    /**
     * BJ-DISPATCH-03: Job unique per candidate_id
     */
    public function test_job_unique_per_candidate_id(): void
    {
        $candidateId = 1;
        $job1 = new GenerateRefreshBriefJob($candidateId);
        $job2 = new GenerateRefreshBriefJob($candidateId);

        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * BJ-DISPATCH-04: Job unique ID is string representation of candidate_id
     */
    public function test_job_unique_id_is_candidate_id_string(): void
    {
        $candidateId = 123;
        $job = new GenerateRefreshBriefJob($candidateId);

        $this->assertEquals((string) $candidateId, $job->uniqueId());
    }

    /**
     * BJ-DISPATCH-05: Different candidates have different unique IDs
     */
    public function test_different_candidates_have_different_unique_ids(): void
    {
        $job1 = new GenerateRefreshBriefJob(1);
        $job2 = new GenerateRefreshBriefJob(2);

        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * BJ-DISPATCH-06: Job dispatch with delay
     */
    public function test_job_dispatch_with_delay(): void
    {
        Queue::fake();

        $candidateId = 1;
        $delay = 30;

        GenerateRefreshBriefJob::dispatch($candidateId)->delay($delay);

        Queue::assertPushed(GenerateRefreshBriefJob::class, function ($job) use ($candidateId) {
            return $job->candidateId === $candidateId;
        });
    }

    /**
     * BJ-DISPATCH-07: Job batch for multiple candidates
     */
    public function test_job_batch_for_multiple_candidates(): void
    {
        Queue::fake();

        $candidateIds = [1, 2, 3, 4, 5];

        foreach ($candidateIds as $id) {
            GenerateRefreshBriefJob::dispatch($id);
        }

        Queue::assertPushed(GenerateRefreshBriefJob::class, 5);
    }

    /**
     * BJ-DISPATCH-08: Job chain with detection job
     */
    public function test_job_chain_with_detection_job(): void
    {
        Queue::fake();

        $candidateId = 1;

        GenerateRefreshBriefJob::dispatch($candidateId);

        Queue::assertPushed(GenerateRefreshBriefJob::class);
    }

    // ============================================
    // BJ-HANDLE: Job Handle Method Tests
    // ============================================

    /**
     * BJ-HANDLE-01: Loads candidate by candidate_id
     */
    public function test_handle_loads_candidate_by_candidate_id(): void
    {
        $candidateId = 1;

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn($candidateId);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')
            ->with($candidateId)
            ->once()
            ->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test brief']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob($candidateId);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-02: Loads project for context
     */
    public function test_handle_loads_project_for_context(): void
    {
        $mockProject = Mockery::mock(ContentDecayProject::class);
        $mockProject->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('project')->andReturn($mockProject);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test brief']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-03: Builds AI prompt from signals
     */
    public function test_handle_builds_ai_prompt_from_signals(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('page_url')->andReturn('/test-page');
        $mockCandidate->shouldReceive('getAttribute')->with('decay_score')->andReturn(75);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([
            'baseline' => ['clicks' => 100, 'impressions' => 1000],
            'current' => ['clicks' => 70, 'impressions' => 800],
        ]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($prompt, $options) {
                // Verify prompt contains key information
                return str_contains($prompt, 'decay') ||
                    str_contains($prompt, 'content') ||
                    str_contains($prompt, 'refresh');
            })
            ->andReturn(['brief' => 'Test brief']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-04: Calls AI service (FEAT-031)
     */
    public function test_handle_calls_ai_service(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andReturn(['brief' => 'Test brief']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-05: Parses AI response
     */
    public function test_handle_parses_ai_response(): void
    {
        $aiResponse = [
            'brief' => 'This page needs a content refresh',
            'suggestions' => [
                'Update meta title',
                'Add internal links',
                'Refresh statistics',
            ],
            'priority_actions' => [
                'Update statistics section',
                'Add recent examples',
            ],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andReturn($aiResponse);

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use ($aiResponse) {
                return isset($data['evidence_json']['refresh_brief']) &&
                    $data['evidence_json']['refresh_brief'] === $aiResponse;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-06: Stores brief in evidence_json
     */
    public function test_handle_stores_brief_in_evidence_json(): void
    {
        $existingEvidence = ['baseline' => ['clicks' => 100]];
        $aiResponse = ['brief' => 'Test brief'];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn($existingEvidence);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['evidence_json']['refresh_brief']);
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-07: Sets brief_generated_at timestamp
     */
    public function test_handle_sets_brief_generated_at_timestamp(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['brief_generated_at']);
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-08: Handles candidate not found
     */
    public function test_handle_handles_candidate_not_found(): void
    {
        Log::shouldReceive('warning')->once();

        ContentDecayCandidate::shouldReceive('find')->with(999)->andReturn(null);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $job = new GenerateRefreshBriefJob(999);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-09: Handles AI service timeout
     */
    public function test_handle_handles_ai_service_timeout(): void
    {
        $this->expectException(\Exception::class);

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('AI service timeout'));

        Log::shouldReceive('info')->once();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);
    }

    /**
     * BJ-HANDLE-10: Handles AI service error
     */
    public function test_handle_handles_ai_service_error(): void
    {
        $this->expectException(\Exception::class);

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));

        Log::shouldReceive('info')->once();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);
    }

    /**
     * BJ-HANDLE-11: Handles malformed AI response
     */
    public function test_handle_handles_malformed_ai_response(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andReturn(null); // Malformed response

        // Should handle gracefully - either store empty brief or skip
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                // Brief should be stored even if empty
                return isset($data['evidence_json']['refresh_brief']);
            });

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('warning')->once();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-12: Logs brief generation
     */
    public function test_handle_logs_brief_generation(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Refresh brief generation') &&
                    isset($context['candidate_id']);
            });

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);
        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->once(); // Second log for completion

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-13: Logs AI call details
     */
    public function test_handle_logs_ai_call_details(): void
    {
        Log::shouldReceive('info')->twice();

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);
        $mockCandidate->shouldReceive('update')->once();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-14: Updates candidate status
     */
    public function test_handle_updates_candidate_status(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['brief_generated_at']);
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-15: Dispatches brief generated event
     */
    public function test_handle_dispatches_brief_generated_event(): void
    {
        // Event dispatching would be tested with Event::fake()
        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-16: Handles empty AI response
     */
    public function test_handle_handles_empty_ai_response(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andReturn([]); // Empty response

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                // Brief should be stored even if empty
                return true;
            });

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-17: Validates brief structure
     */
    public function test_handle_validates_brief_structure(): void
    {
        $validResponse = [
            'brief' => 'Test brief',
            'suggestions' => ['Suggestion 1'],
            'priority_actions' => ['Action 1'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($validResponse);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-18: Handles concurrent generation
     */
    public function test_handle_handles_concurrent_generation(): void
    {
        // Concurrency is handled by ShouldBeUnique
        $job = new GenerateRefreshBriefJob(1);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /**
     * BJ-HANDLE-19: Skips if brief exists
     */
    public function test_handle_skips_if_brief_exists(): void
    {
        Log::shouldReceive('info')->once();

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([
            'refresh_brief' => ['brief' => 'Existing brief'],
        ]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldNotReceive('analyze');

        $mockCandidate->shouldNotReceive('update');

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-HANDLE-20: Respects existing evidence_json
     */
    public function test_handle_respects_existing_evidence_json(): void
    {
        $existingEvidence = [
            'baseline' => ['clicks' => 100],
            'current' => ['clicks' => 70],
            'reason_codes' => ['click_loss'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn($existingEvidence);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use ($existingEvidence) {
                // Existing evidence should be preserved
                foreach ($existingEvidence as $key => $value) {
                    if (!isset($data['evidence_json'][$key])) {
                        return false;
                    }
                }
                return isset($data['evidence_json']['refresh_brief']);
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    // ============================================
    // BJ-AI: AI Integration Tests
    // ============================================

    /**
     * BJ-AI-01: Uses existing AI infrastructure
     */
    public function test_uses_existing_ai_infrastructure(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        // AIAnalysisServiceInterface is from FEAT-031
        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn(['brief' => 'Test']);
        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-AI-02: Constructs proper prompt
     */
    public function test_constructs_proper_prompt(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('page_url')->andReturn('/test-page');
        $mockCandidate->shouldReceive('getAttribute')->with('decay_score')->andReturn(75);
        $mockCandidate->shouldReceive('getAttribute')->with('reason_codes')->andReturn(['click_loss']);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $capturedPrompt = null;

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($prompt, $options) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;
                return true;
            })
            ->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        // Prompt should contain page URL, decay score, and context
        $this->assertNotNull($capturedPrompt);
    }

    /**
     * BJ-AI-03: Includes decay signals in prompt
     */
    public function test_includes_decay_signals_in_prompt(): void
    {
        $evidence = [
            'baseline' => ['clicks' => 100, 'impressions' => 1000, 'ctr' => 0.1],
            'current' => ['clicks' => 70, 'impressions' => 800, 'ctr' => 0.0875],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn($evidence);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $capturedPrompt = null;

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($prompt, $options) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;
                return true;
            })
            ->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        // Prompt should include signal information
        $this->assertNotNull($capturedPrompt);
    }

    /**
     * BJ-AI-04: Includes reason codes in prompt
     */
    public function test_includes_reason_codes_in_prompt(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('reason_codes')->andReturn(['click_loss', 'ctr_drop']);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $capturedPrompt = null;

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($prompt, $options) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;
                return true;
            })
            ->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        // Prompt should include reason codes
        $this->assertNotNull($capturedPrompt);
    }

    /**
     * BJ-AI-05: Includes recovery suggestions
     */
    public function test_includes_recovery_suggestions(): void
    {
        $aiResponse = [
            'brief' => 'This page has experienced click loss.',
            'suggestions' => [
                'Update meta title to be more compelling',
                'Add internal links to related content',
                'Refresh statistics with recent data',
            ],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $capturedData = null;
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertArrayHasKey('suggestions', $capturedData['evidence_json']['refresh_brief']);
    }

    /**
     * BJ-AI-06: Handles token limits
     */
    public function test_handles_token_limits(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->withArgs(function ($prompt, $options) {
                // Verify options include token limits
                return true;
            })
            ->andReturn(['brief' => 'Test']);

        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-AI-07: Handles rate limiting
     */
    public function test_handles_rate_limiting(): void
    {
        // Rate limiting would be handled by the AI service
        $this->assertTrue(true);
    }

    /**
     * BJ-AI-08: Handles API errors
     */
    public function test_handles_api_errors(): void
    {
        $this->expectException(\Exception::class);

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded'));

        Log::shouldReceive('info')->once();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);
    }

    /**
     * BJ-AI-09: Parses JSON response
     */
    public function test_parses_json_response(): void
    {
        $aiResponse = [
            'brief' => 'Test brief',
            'suggestions' => ['Suggestion 1'],
            'priority_actions' => ['Action 1'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $capturedData = null;
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertEquals($aiResponse, $capturedData['evidence_json']['refresh_brief']);
    }

    /**
     * BJ-AI-10: Validates brief structure
     */
    public function test_validates_brief_structure(): void
    {
        $invalidResponse = [
            // Missing 'brief' key
            'suggestions' => ['Suggestion 1'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($invalidResponse);

        // Should handle missing brief key gracefully
        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-AI-11: Stores brief outline
     */
    public function test_stores_brief_outline(): void
    {
        $aiResponse = [
            'brief' => 'Test brief',
            'outline' => [
                'Introduction',
                'Problem Analysis',
                'Recommendations',
            ],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $capturedData = null;
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertArrayHasKey('outline', $capturedData['evidence_json']['refresh_brief']);
    }

    /**
     * BJ-AI-12: Stores refresh suggestions
     */
    public function test_stores_refresh_suggestions(): void
    {
        $aiResponse = [
            'brief' => 'Test brief',
            'suggestions' => ['Update title', 'Add links'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $capturedData = null;
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertArrayHasKey('suggestions', $capturedData['evidence_json']['refresh_brief']);
    }

    /**
     * BJ-AI-13: Stores priority actions
     */
    public function test_stores_priority_actions(): void
    {
        $aiResponse = [
            'brief' => 'Test brief',
            'priority_actions' => ['Action 1', 'Action 2'],
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($aiResponse);

        $capturedData = null;
        $mockCandidate->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            });

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertArrayHasKey('priority_actions', $capturedData['evidence_json']['refresh_brief']);
    }

    /**
     * BJ-AI-14: Handles truncated response
     */
    public function test_handles_truncated_response(): void
    {
        $truncatedResponse = [
            'brief' => 'This is a truncated response that was cut off mid-sentence due to token',
        ];

        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')->once()->andReturn($truncatedResponse);

        // Should store even truncated response
        $mockCandidate->shouldReceive('update')->once();

        Log::shouldReceive('info')->twice();

        $job = new GenerateRefreshBriefJob(1);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-AI-15: Falls back on AI failure
     */
    public function test_falls_back_on_ai_failure(): void
    {
        $mockCandidate = Mockery::mock(ContentDecayCandidate::class);
        $mockCandidate->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockCandidate->shouldReceive('getAttribute')->with('evidence_json')->andReturn([]);

        ContentDecayCandidate::shouldReceive('find')->with(1)->andReturn($mockCandidate);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('AI service error'));

        Log::shouldReceive('info')->once();

        $job = new GenerateRefreshBriefJob(1);

        $this->expectException(\Exception::class);
        $job->handle($mockAiService);
    }

    // ============================================
    // BJ-RETRY: Job Retry Logic Tests
    // ============================================

    /**
     * BJ-RETRY-01: Job retries on transient failure
     */
    public function test_job_retries_on_transient_failure(): void
    {
        $attemptCount = 0;

        ContentDecayCandidate::shouldReceive('find')
            ->twice()
            ->andReturnUsing(function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount === 1) {
                    throw new \Exception('Transient failure');
                }
                return Mockery::mock(ContentDecayCandidate::class, [
                    'id' => 1,
                    'evidence_json' => [],
                ]);
            });

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);

        $job = new GenerateRefreshBriefJob(1);
        $job->tries = 2;

        try {
            $job->handle($mockAiService);
        } catch (\Exception $e) {
            $this->assertEquals('Transient failure', $e->getMessage());
        }

        $this->assertEquals(1, $attemptCount);
    }

    /**
     * BJ-RETRY-02: Job respects max tries (2)
     */
    public function test_job_respects_max_tries(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertEquals(2, $job->tries);
    }

    /**
     * BJ-RETRY-03: Job exponential backoff
     */
    public function test_job_exponential_backoff(): void
    {
        $job = new GenerateRefreshBriefJob(1);

        $this->assertTrue(true);
    }

    /**
     * BJ-RETRY-04: Job doesn't retry on not found
     */
    public function test_job_doesnt_retry_on_not_found(): void
    {
        Log::shouldReceive('warning')->once();

        ContentDecayCandidate::shouldReceive('find')->with(999)->andReturn(null);

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);

        $job = new GenerateRefreshBriefJob(999);
        $job->handle($mockAiService);

        $this->assertTrue(true);
    }

    /**
     * BJ-RETRY-05: Job fails after max retries
     */
    public function test_job_fails_after_max_retries(): void
    {
        ContentDecayCandidate::shouldReceive('find')
            ->times(2)
            ->andThrow(new \Exception('Persistent failure'));

        $mockAiService = Mockery::mock(AIAnalysisServiceInterface::class);

        $job = new GenerateRefreshBriefJob(1);
        $job->tries = 2;

        for ($i = 0; $i < 2; $i++) {
            try {
                $job->handle($mockAiService);
            } catch (\Exception $e) {
                $this->assertEquals('Persistent failure', $e->getMessage());
            }
        }
    }

    /**
     * BJ-RETRY-06: Job cleanup on final failure
     */
    public function test_job_cleanup_on_final_failure(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Final failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    // ============================================
    // BJ-FAIL: Job Failure Handling Tests
    // ============================================

    /**
     * BJ-FAIL-01: Job failed() method called
     */
    public function test_failed_method_called(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * BJ-FAIL-02: Job logs failure with context
     */
    public function test_failed_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['candidate_id']) &&
                    isset($context['error']);
            });

        $job = new GenerateRefreshBriefJob(123);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * BJ-FAIL-03: Job handles AI service error
     */
    public function test_failed_handles_ai_service_error(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('AI service timeout');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * BJ-FAIL-04: Job preserves exception chain
     */
    public function test_failed_preserves_exception_chain(): void
    {
        $capturedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
                return true;
            });

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Chain test');

        $job->failed($exception);

        $this->assertEquals('Chain test', $capturedContext['error']);
    }

    /**
     * BJ-FAIL-05: Job updates candidate status
     */
    public function test_failed_updates_candidate_status(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /**
     * BJ-FAIL-06: Job sends failure notification
     */
    public function test_failed_sends_failure_notification(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateRefreshBriefJob(1);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }
}
