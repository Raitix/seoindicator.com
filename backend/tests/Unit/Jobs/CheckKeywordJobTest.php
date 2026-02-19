<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CheckKeywordJob;
use App\Services\RankCheckerService;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

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
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $keyword = Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'seo tools',
        ]);

        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);

        // Mock RankCheckerService
        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->with($keyword->id, Mockery::any())
            ->andReturnUsing(function ($keywordId, $competitors = null) use ($competitor) {
                // Simulate storing competitor ranking
                CompetitorRanking::create([
                    'competitor_id' => $competitor->id,
                    'keyword_id' => $keywordId,
                    'position' => 8,
                    'url' => 'https://competitor.com/page',
                    'checked_at' => now(),
                ]);
                return true;
            });

        $this->app->instance(RankCheckerService::class, $mockService);

        // Dispatch job
        $job = new CheckKeywordJob($keyword->id);
        $job->handle($mockService);

        // Verify ranking stored
        $ranking = CompetitorRanking::where('keyword_id', $keyword->id)->first();
        $this->assertNotNull($ranking);
        $this->assertEquals(8, $ranking->position);
    }

    // ============================================
    // Test 24: Job Retry
    // ============================================

    /**
     * Test 24: Job retries on transient failure
     */
    public function test_job_retries_on_transient_failure(): void
    {
        $keyword = Keyword::create([
            'user_id' => 1,
            'keyword' => 'seo tools',
        ]);

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

        $job = new CheckKeywordJob($keyword->id);
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
        $keyword = Keyword::create([
            'user_id' => 1,
            'keyword' => 'seo tools',
        ]);

        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->andThrow(new \Exception('SERP API connection refused'));

        $this->app->instance(RankCheckerService::class, $mockService);

        $job = new CheckKeywordJob($keyword->id);
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
                return str_contains($message, 'keyword') || 
                       str_contains($message, 'failed');
            });

        $keyword = Keyword::create([
            'user_id' => 1,
            'keyword' => 'seo tools',
        ]);

        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->once()
            ->andThrow(new \Exception('Test failure'));

        $this->app->instance(RankCheckerService::class, $mockService);

        $job = new CheckKeywordJob($keyword->id);
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
        
        $mockService = Mockery::mock(RankCheckerService::class);
        $mockService->shouldReceive('checkAndStore')
            ->times(3);

        $this->app->instance(RankCheckerService::class, $mockService);

        // If there's a batch job class
        if (class_exists(\App\Jobs\CheckKeywordsBatchJob::class)) {
            $job = new \App\Jobs\CheckKeywordsBatchJob($keywordIds);
            $job->handle($mockService);
        } else {
            // Test individual jobs
            foreach ($keywordIds as $keywordId) {
                $job = new CheckKeywordJob($keywordId);
                // Would normally dispatch, just verify job created
                $this->assertEquals($keywordId, $job->keywordId);
            }
        }
    }
}
