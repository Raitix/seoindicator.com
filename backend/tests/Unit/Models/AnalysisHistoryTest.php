<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AnalysisHistory;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Test suite for TASK-25-10: AnalysisHistory Model
 *
 * Tests the Eloquent model for the analysis_history table.
 *
 * Test Categories:
 * - M-001 to M-003: Model Attributes & Casts
 * - M-004 to M-006: Relationships
 * - M-007 to M-011: Scopes
 * - M-012 to M-015: Accessors & Helpers
 * - M-016 to M-018: Edge Cases
 *
 * @see TASK-25-10
 * @see SPEC-025
 * @see FEAT-025
 */
class AnalysisHistoryTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Category 1: Model Attributes & Casts (M-001 to M-003)
    // =========================================================================

    /**
     * M-001: Fillable attributes are correct.
     */
    public function test_m001_fillable_attributes(): void
    {
        $model = new AnalysisHistory();

        $expectedFillable = [
            'user_id',
            'site_id',
            'tool_name',
            'input_url',
            'status',
            'results',
            'error_message',
            'analysis_duration_ms',
            'analyzed_at',
        ];

        $this->assertEquals(
            sort($expectedFillable),
            sort($model->getFillable()),
            'Model should have correct fillable attributes'
        );
    }

    /**
     * M-002: results cast to array.
     */
    public function test_m002_results_cast_to_array(): void
    {
        $model = new AnalysisHistory();

        $casts = $model->getCasts();

        $this->assertArrayHasKey('results', $casts, 'results should have a cast');
        $this->assertEquals('array', $casts['results'], 'results should be cast to array');
    }

    /**
     * M-003: analyzed_at cast to datetime.
     */
    public function test_m003_analyzed_at_cast_to_datetime(): void
    {
        $model = new AnalysisHistory();

        $casts = $model->getCasts();

        $this->assertArrayHasKey('analyzed_at', $casts, 'analyzed_at should have a cast');
        $this->assertEquals('datetime', $casts['analyzed_at'], 'analyzed_at should be cast to datetime');
    }

    // =========================================================================
    // Category 2: Relationships (M-004 to M-006)
    // =========================================================================

    /**
     * M-004: BelongsTo user relationship.
     */
    public function test_m004_belongs_to_user_relationship(): void
    {
        $model = new AnalysisHistory();

        $relationship = $model->user();

        $this->assertInstanceOf(BelongsTo::class, $relationship, 'user relationship should be BelongsTo');
        $this->assertEquals(User::class, $relationship->getRelated()::class, 'user relationship should relate to User model');
    }

    /**
     * M-005: BelongsTo site relationship.
     */
    public function test_m005_belongs_to_site_relationship(): void
    {
        $model = new AnalysisHistory();

        $relationship = $model->site();

        $this->assertInstanceOf(BelongsTo::class, $relationship, 'site relationship should be BelongsTo');
        $this->assertEquals(Site::class, $relationship->getRelated()::class, 'site relationship should relate to Site model');
    }

    /**
     * M-006: Site relationship is nullable.
     */
    public function test_m006_site_relationship_is_nullable(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'site_id' => null,
        ]);

        $this->assertNull($analysis->site_id, 'site_id should allow null');
        $this->assertNull($analysis->site, 'site relationship should return null when site_id is null');
    }

    // =========================================================================
    // Category 3: Scopes (M-007 to M-011)
    // =========================================================================

    /**
     * M-007: Scope by status.
     */
    public function test_m007_scope_by_status(): void
    {
        $user = User::factory()->create();

        // Create analyses with different statuses
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'failed']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $completedCount = AnalysisHistory::byStatus('completed')->count();
        $failedCount = AnalysisHistory::byStatus('failed')->count();
        $pendingCount = AnalysisHistory::byStatus('pending')->count();

        $this->assertEquals(2, $completedCount, 'Should have 2 completed analyses');
        $this->assertEquals(1, $failedCount, 'Should have 1 failed analysis');
        $this->assertEquals(1, $pendingCount, 'Should have 1 pending analysis');
    }

    /**
     * M-008: Scope by tool_name.
     */
    public function test_m008_scope_by_tool_name(): void
    {
        $user = User::factory()->create();

        // Create analyses with different tools
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->create(['user_id' => $user->id, 'tool_name' => 'keyword-density']);

        $metaTagCount = AnalysisHistory::byTool('meta-tag-analyzer')->count();
        $keywordCount = AnalysisHistory::byTool('keyword-density')->count();

        $this->assertEquals(2, $metaTagCount, 'Should have 2 meta-tag-analyzer analyses');
        $this->assertEquals(1, $keywordCount, 'Should have 1 keyword-density analysis');
    }

    /**
     * M-009: Scope by user.
     */
    public function test_m009_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create analyses for different users
        AnalysisHistory::factory()->create(['user_id' => $user1->id]);
        AnalysisHistory::factory()->create(['user_id' => $user1->id]);
        AnalysisHistory::factory()->create(['user_id' => $user2->id]);

        $user1Count = AnalysisHistory::byUser($user1->id)->count();
        $user2Count = AnalysisHistory::byUser($user2->id)->count();

        $this->assertEquals(2, $user1Count, 'User 1 should have 2 analyses');
        $this->assertEquals(1, $user2Count, 'User 2 should have 1 analysis');
    }

    /**
     * M-010: Order by analyzed_at descending.
     */
    public function test_m010_order_by_analyzed_at_desc(): void
    {
        $user = User::factory()->create();

        $analysis1 = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now()->subDays(2),
        ]);
        $analysis2 = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now()->subDay(),
        ]);
        $analysis3 = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analyzed_at' => now(),
        ]);

        $ordered = AnalysisHistory::latestFirst()->get();

        $this->assertEquals($analysis3->id, $ordered[0]->id, 'Most recent should be first');
        $this->assertEquals($analysis2->id, $ordered[1]->id, 'Second most recent should be second');
        $this->assertEquals($analysis1->id, $ordered[2]->id, 'Oldest should be last');
    }

    /**
     * M-011: Scope for date range.
     */
    public function test_m011_scope_for_date_range(): void
    {
        $user = User::factory()->create();

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

        $lastWeekCount = AnalysisHistory::forDateRange(
            now()->subDays(7),
            now()
        )->count();

        $this->assertEquals(2, $lastWeekCount, 'Should have 2 analyses in last week');
    }

    // =========================================================================
    // Category 4: Accessors & Helpers (M-012 to M-015)
    // =========================================================================

    /**
     * M-012: Duration formatting helper.
     */
    public function test_m012_duration_formatting_helper(): void
    {
        $user = User::factory()->create();

        // Test milliseconds
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analysis_duration_ms' => 500,
        ]);
        $this->assertEquals('500ms', $analysis->formatted_duration);

        // Test seconds
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analysis_duration_ms' => 2500,
        ]);
        $this->assertEquals('2.50s', $analysis->formatted_duration);

        // Test null
        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'analysis_duration_ms' => null,
        ]);
        $this->assertNull($analysis->formatted_duration);
    }

    /**
     * M-013: Is completed check.
     */
    public function test_m013_is_completed_check(): void
    {
        $user = User::factory()->create();

        $completed = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        $failed = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
        $pending = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($failed->isCompleted());
        $this->assertFalse($pending->isCompleted());
    }

    /**
     * M-014: Is failed check.
     */
    public function test_m014_is_failed_check(): void
    {
        $user = User::factory()->create();

        $completed = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        $failed = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
        ]);

        $this->assertFalse($completed->isFailed());
        $this->assertTrue($failed->isFailed());
    }

    /**
     * M-015: Is pending check.
     */
    public function test_m015_is_pending_check(): void
    {
        $user = User::factory()->create();

        $completed = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        $pending = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertFalse($completed->isPending());
        $this->assertTrue($pending->isPending());
    }

    // =========================================================================
    // Category 5: Edge Cases (M-016 to M-018)
    // =========================================================================

    /**
     * M-016: Empty results handled correctly.
     */
    public function test_m016_empty_results_handled_correctly(): void
    {
        $user = User::factory()->create();

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'results' => [],
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertEmpty($analysis->results);
    }

    /**
     * M-017: Large results handled correctly.
     */
    public function test_m017_large_results_handled_correctly(): void
    {
        $user = User::factory()->create();

        // Create a large results array
        $largeResults = [
            'items' => array_fill(0, 1000, [
                'url' => 'https://example.com/page',
                'title' => 'Example Page',
                'data' => str_repeat('x', 100),
            ]),
        ];

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'results' => $largeResults,
        ]);

        $analysis->refresh();

        $this->assertCount(1000, $analysis->results['items']);
    }

    /**
     * M-018: Unicode in results handled correctly.
     */
    public function test_m018_unicode_in_results_handled_correctly(): void
    {
        $user = User::factory()->create();

        $results = [
            'title' => '标题 — 中文测试',
            'emoji' => '🎉🎊🎁',
            'special' => '«»€£¥§¶',
        ];

        $analysis = AnalysisHistory::factory()->create([
            'user_id' => $user->id,
            'results' => $results,
        ]);

        $analysis->refresh();

        $this->assertEquals('标题 — 中文测试', $analysis->results['title']);
        $this->assertEquals('🎉🎊🎁', $analysis->results['emoji']);
    }
}
