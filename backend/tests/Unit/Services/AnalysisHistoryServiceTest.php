<?php

namespace Tests\Unit\Services;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use App\Models\Site;
use App\Models\User;
use App\Repositories\AnalysisHistoryRepository;
use App\Services\AnalysisHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AnalysisHistoryService.
 * 
 * Tests the service layer for managing analysis history records,
 * including creation, updates, duration calculation, and user isolation.
 * 
 * @covers TASK-25-14
 */
class AnalysisHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisHistoryService $service;
    protected AnalysisHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(AnalysisHistoryRepository::class);
        $this->service = new AnalysisHistoryService($this->repository);
    }

    /**
     * US-001: Create Analysis Record
     */
    public function test_create_analysis_record(): void
    {
        $user = User::factory()->create();

        $analysis = $this->service->createAnalysis([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);

        $this->assertInstanceOf(AnalysisHistory::class, $analysis);
        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertEquals('meta-tag-analyzer', $analysis->tool_name);
        $this->assertEquals('https://example.com', $analysis->input_url);
        $this->assertEquals('completed', $analysis->status);
    }

    /**
     * US-002: Complete Analysis
     */
    public function test_complete_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'status' => 'pending',
        ]);

        $this->service->completeAnalysis($analysis->id, [
            'title' => 'Test Title',
            'description' => 'Test Description',
        ], 1234);

        $analysis->refresh();

        $this->assertEquals('completed', $analysis->status);
        $this->assertEquals(1234, $analysis->analysis_duration_ms);
        $this->assertNotNull($analysis->results);
        $this->assertEquals('Test Title', $analysis->results['title']);
    }

    /**
     * US-003: Fail Analysis
     */
    public function test_fail_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'status' => 'pending',
        ]);

        $this->service->failAnalysis($analysis->id, 'Connection timeout');

        $analysis->refresh();

        $this->assertEquals('failed', $analysis->status);
        $this->assertEquals('Connection timeout', $analysis->error_message);
    }

    /**
     * US-004: Get User Analyses
     */
    public function test_get_user_analyses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create analyses for both users
        AnalysisHistory::factory()->count(3)->create(['user_id' => $user->id]);
        AnalysisHistory::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $result = $this->service->getUserAnalyses($user->id);

        $this->assertEquals(3, $result->total());
        $result->each(fn($a) => $this->assertEquals($user->id, $a->user_id));
    }

    /**
     * US-005: Get Analysis by ID
     */
    public function test_get_analysis_by_id(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        $found = $this->service->getAnalysis($analysis->id);

        $this->assertNotNull($found);
        $this->assertEquals($analysis->id, $found->id);
    }

    /**
     * US-006: Delete Analysis
     */
    public function test_delete_analysis(): void
    {
        $user = User::factory()->create();
        $analysis = AnalysisHistory::factory()->create(['user_id' => $user->id]);

        $result = $this->service->deleteAnalysis($analysis->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('analysis_history', ['id' => $analysis->id]);
    }

    /**
     * US-007: Calculate Duration
     */
    public function test_calculate_duration(): void
    {
        $startTime = microtime(true) - 1.234; // 1.234 seconds ago

        $duration = $this->service->calculateDuration($startTime);

        $this->assertIsInt($duration);
        $this->assertGreaterThanOrEqual(1234, $duration);
        $this->assertLessThan(1250, $duration); // Allow some variance
    }

    /**
     * US-008: Associate Site
     */
    public function test_associate_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'site_id' => null,
        ]);

        $this->service->associateSite($analysis->id, $site->id);

        $analysis->refresh();
        $this->assertEquals($site->id, $analysis->site_id);
    }

    /**
     * US-009: Get Stats for User
     */
    public function test_get_stats_for_user(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'failed', 'tool_name' => 'keyword-density-checker']);

        $stats = $this->service->getUserStats($user->id);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['by_status']['completed']);
        $this->assertEquals(1, $stats['by_status']['failed']);
        $this->assertEquals(2, $stats['by_tool']['meta-tag-analyzer']);
        $this->assertEquals(1, $stats['by_tool']['keyword-density-checker']);
    }

    /**
     * US-010: Filter by Tool
     */
    public function test_filter_by_tool(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->count(3)->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
        ]);
        AnalysisHistory::factory()->count(2)->create([
            'user_id' => $user->id,
            'tool_name' => 'keyword-density-checker',
        ]);

        $result = $this->service->getUserAnalyses($user->id, ['tool_name' => 'meta-tag-analyzer']);

        $this->assertEquals(3, $result->total());
        $result->each(fn($a) => $this->assertEquals('meta-tag-analyzer', $a->tool_name));
    }

    /**
     * US-011: Filter by Date Range
     */
    public function test_filter_by_date_range(): void
    {
        $user = User::factory()->create();

        // Create analyses with different dates
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now()->subDays(10),
        ]);
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now()->subDays(5),
        ]);
        AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now(),
        ]);

        $result = $this->service->getUserAnalyses($user->id, [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $this->assertEquals(2, $result->total());
    }

    /**
     * US-012: Transaction Support
     */
    public function test_transaction_support(): void
    {
        $user = User::factory()->create();

        \DB::beginTransaction();
        $analysis = $this->service->createAnalysis([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);
        \DB::rollBack();

        $this->assertDatabaseMissing('analysis_history', ['id' => $analysis->id]);
    }

    /**
     * US-013: User Isolation
     */
    public function test_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->count(3)->create(['user_id' => $user1->id]);
        AnalysisHistory::factory()->count(2)->create(['user_id' => $user2->id]);

        $user1Analyses = $this->service->getUserAnalyses($user1->id);
        $user2Analyses = $this->service->getUserAnalyses($user2->id);

        $this->assertEquals(3, $user1Analyses->total());
        $this->assertEquals(2, $user2Analyses->total());
    }

    /**
     * US-014: Null User Handling
     */
    public function test_null_user_handling(): void
    {
        $analysis = $this->service->createAnalysis([
            'user_id' => null,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
        ]);

        $this->assertNull($analysis->user_id);
        $this->assertEquals('completed', $analysis->status);
    }

    /**
     * US-015: Large Results Handling
     */
    public function test_large_results_handling(): void
    {
        $user = User::factory()->create();

        // Create a large results array
        $largeResults = [
            'keywords' => array_fill(0, 1000, [
                'keyword' => 'test keyword ' . str_repeat('x', 50),
                'density' => 0.01,
                'count' => 10,
            ]),
        ];

        $analysis = $this->service->createAnalysis([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
            'results' => $largeResults,
        ]);

        $this->assertNotNull($analysis->results);
        $this->assertCount(1000, $analysis->results['keywords']);
    }

    /**
     * US-016: Error Message Truncation
     */
    public function test_error_message_truncation(): void
    {
        $user = User::factory()->create();
        $longError = str_repeat('Error message. ', 10000); // ~150KB

        $analysis = $this->service->createAnalysis([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'failed',
            'error_message' => $longError,
        ]);

        $this->assertNotNull($analysis->error_message);
        // TEXT column can hold 65535 bytes
        $this->assertLessThanOrEqual(65535, strlen($analysis->error_message));
    }

    /**
     * US-017: Concurrent Creation
     */
    public function test_concurrent_creation(): void
    {
        $user = User::factory()->create();

        $analyses = collect(range(1, 10))->map(function ($i) use ($user) {
            return $this->service->createAnalysis([
                'user_id' => $user->id,
                'tool_name' => 'meta-tag-analyzer',
                'input_url' => "https://example{$i}.com",
                'status' => 'completed',
            ]);
        });

        $uniqueIds = $analyses->pluck('id')->unique();
        $this->assertEquals(10, $uniqueIds->count());
    }

    /**
     * US-018: Duration Precision
     */
    public function test_duration_precision(): void
    {
        // Test with various floating point values
        $testCases = [
            [0.001, 1],     // 1ms
            [0.5, 500],     // 500ms
            [1.234, 1234],  // 1234ms
            [2.999, 2999],  // 2999ms (floor)
        ];

        foreach ($testCases as [$elapsedSeconds, $expectedMs]) {
            $startTime = microtime(true) - $elapsedSeconds;
            $duration = $this->service->calculateDuration($startTime);
            
            // Allow for small variance due to execution time
            $this->assertEqualsWithDelta($expectedMs, $duration, 10);
        }
    }
}
