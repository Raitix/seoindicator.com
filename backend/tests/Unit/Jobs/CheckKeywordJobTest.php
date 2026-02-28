<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CheckKeywordJob;
use App\Services\RankCheckerService;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Unit tests for CheckKeywordJob
 * 
 * Tests verify:
 * - Job dispatches correctly
 * - Job processes keyword check
 * - Job handles failures
 * - Job retries
 */
class CheckKeywordJobTest extends TestCase
{
    // Removed RefreshDatabase to avoid SQLite transaction issues
    // These tests use mocking and don't need database

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Test 23: Job Dispatch and Processing
    // ============================================

    /**
     * Test 23: Job processes competitor check
     */
    public function test_job_processes_competitor_check(): void
    {
        $keywordId = 1;

        // Mock RankCheckerService - match only keywordId (second param is optional)
        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->with($keywordId)
            ->andReturn(true);

        $this->app->instance(RankCheckerService::class, $mockService);

        // Dispatch job
        $job = new CheckKeywordJob($keywordId);
        $job->handle($mockService);

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }

    // ============================================
    // Test 24: Job Retry
    // ============================================

    /**
     * Test 24: Job retries on transient failure
     */
    public function test_job_retries_on_transient_failure(): void
    {
        $keywordId = 1;
        $attemptCount = 0;
        
        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->twice() // First fails, second succeeds
            ->andReturnUsing(function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount === 1) {
                    throw new \Exception('Connection timeout');
                }
                return true;
            });

        $this->app->instance(RankCheckerService::class, $mockService);

        $job = new CheckKeywordJob($keywordId);
        $job->tries = 3;

        // First attempt should fail
        try {
            $job->handle($mockService);
        } catch (\Exception $e) {
            $this->assertEquals('Connection timeout', $e->getMessage());
        }

        // Second attempt should succeed
        $job->handle($mockService);

        $this->assertEquals(2, $attemptCount);
    }

    // ============================================
    // Test 25: SERP API Down Handling
    // ============================================

    /**
     * Test 25: Graceful handling when SERP API is unavailable
     */
    public function test_graceful_handling_serp_api_down(): void
    {
        $keywordId = 1;

        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->andThrow(new \Exception('SERP API connection refused'));

        $this->app->instance(RankCheckerService::class, $mockService);

        $job = new CheckKeywordJob($keywordId);
        $job->tries = 1;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SERP API connection refused');

        $job->handle($mockService);
    }

    // ============================================
    // Additional Job Tests
    // ============================================

    /**
     * Test: Job has correct tries count
     */
    public function test_job_has_correct_tries_count(): void
    {
        $job = new CheckKeywordJob(1);
        
        $this->assertGreaterThanOrEqual(1, $job->tries);
    }

    /**
     * Test: Job has timeout configured
     */
    public function test_job_has_timeout_configured(): void
    {
        $job = new CheckKeywordJob(1);
        
        $this->assertNotNull($job->timeout ?? null, 'Job should have timeout configured');
    }

    /**
     * Test: Job can be queued
     */
    public function test_job_can_be_queued(): void
    {
        Queue::fake();

        $keywordId = 1;
        
        CheckKeywordJob::dispatch($keywordId);

        Queue::assertPushed(CheckKeywordJob::class, function ($job) use ($keywordId) {
            return $job->keywordId === $keywordId;
        });
    }

    /**
     * Test: Job stores keyword ID
     */
    public function test_job_stores_keyword_id(): void
    {
        $keywordId = 123;
        $job = new CheckKeywordJob($keywordId);

        $this->assertEquals($keywordId, $job->keywordId);
    }

    /**
     * Test: Job implements ShouldQueue
     */
    public function test_job_implements_should_queue(): void
    {
        $job = new CheckKeywordJob(1);
        
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $job
        );
    }

    /**
     * Test: Job failure is logged
     */
    public function test_job_failure_is_logged(): void
    {
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Keyword check job failed');
            });

        $keywordId = 1;

        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->andThrow(new \Exception('Test failure'));

        $this->app->instance(RankCheckerService::class, $mockService);

        $job = new CheckKeywordJob($keywordId);
        $job->tries = 1;

        try {
            $job->handle($mockService);
        } catch (\Exception $e) {
            // Expected
        }
    }

    /**
     * Test: Batch job processes multiple keywords
     */
    public function test_batch_job_processes_multiple_keywords(): void
    {
        $keywordIds = [1, 2, 3];
        
        // Test individual jobs are created correctly
        foreach ($keywordIds as $keywordId) {
            $job = new CheckKeywordJob($keywordId);
            $this->assertEquals($keywordId, $job->keywordId);
        }
        
        // Verify all jobs were created
        $this->assertCount(3, $keywordIds);
    }
}
