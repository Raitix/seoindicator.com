<?php

namespace Tests\Unit\Repositories;

use App\Models\AnalysisHistory;
use App\Models\User;
use App\Models\Site;
use App\Repositories\AnalysisHistoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * Unit tests for AnalysisHistoryRepository.
 *
 * @see TASK-25-12
 * @see TEST-25-12
 */
class AnalysisHistoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(AnalysisHistoryRepository::class);
    }

    // ==========================================
    // CRUD Operations Tests (CRUD)
    // ==========================================

    /** @test CRUD-001: Create analysis with all fields */
    public function test_crud_001_create_analysis(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $data = [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com/page',
            'status' => 'completed',
            'results' => ['title' => 'Test', 'score' => 85],
            'error_message' => null,
            'analysis_duration_ms' => 1234,
            'analyzed_at' => now(),
        ];

        $analysis = $this->repository->create($data);

        $this->assertInstanceOf(AnalysisHistory::class, $analysis);
        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertEquals($site->id, $analysis->site_id);
        $this->assertEquals('meta-tag-analyzer', $analysis->tool_name);
        $this->assertEquals('completed', $analysis->status);
        $this->assertDatabaseHas('analysis_history', ['id' => $analysis->id]);
    }

    /** @test CRUD-002: Create with minimal fields */
    public function test_crud_002_create_minimal(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'keyword-density',
            'input_url' => 'https://example.com',
        ]);

        $this->assertInstanceOf(AnalysisHistory::class, $analysis);
        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertNotNull($analysis->id);
    }

    /** @test CRUD-003: FindById existing */
    public function test_crud_003_find_by_id_existing(): void
    {
        $analysis = AnalysisHistory::factory()->create();

        $found = $this->repository->findById($analysis->id);

        $this->assertInstanceOf(AnalysisHistory::class, $found);
        $this->assertEquals($analysis->id, $found->id);
    }

    /** @test CRUD-004: FindById non-existing */
    public function test_crud_004_find_by_id_non_existing(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /** @test CRUD-005: Update analysis */
    public function test_crud_005_update_analysis(): void
    {
        $analysis = AnalysisHistory::factory()->create([
            'status' => 'pending',
            'results' => null,
        ]);

        $updated = $this->repository->update($analysis->id, [
            'status' => 'completed',
            'results' => ['score' => 90],
        ]);

        $this->assertEquals('completed', $updated->status);
        $this->assertEquals(90, $updated->results['score']);
        $this->assertDatabaseHas('analysis_history', [
            'id' => $analysis->id,
            'status' => 'completed',
        ]);
    }

    /** @test CRUD-006: Update non-existing returns false */
    public function test_crud_006_update_non_existing(): void
    {
        $result = $this->repository->update(99999, ['status' => 'completed']);

        $this->assertFalse($result);
    }

    /** @test CRUD-007: Delete analysis */
    public function test_crud_007_delete_analysis(): void
    {
        $analysis = AnalysisHistory::factory()->create();

        $result = $this->repository->delete($analysis->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('analysis_history', ['id' => $analysis->id]);
    }

    /** @test CRUD-008: Delete non-existing returns false */
    public function test_crud_008_delete_non_existing(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    // ==========================================
    // Query Methods Tests (RQ)
    // ==========================================

    /** @test RQ-001: findByUser returns paginator */
    public function test_rq_001_find_by_user_returns_paginator(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(5)->create();

        $result = $this->repository->findByUser($user->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result->items());
    }

    /** @test RQ-002: findByUser with status filter */
    public function test_rq_002_find_by_user_with_status_filter(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['status' => 'failed']);

        $result = $this->repository->findByUser($user->id, ['status' => 'completed']);

        $this->assertCount(3, $result->items());
        foreach ($result->items() as $item) {
            $this->assertEquals('completed', $item->status);
        }
    }

    /** @test RQ-003: findByUser with tool filter */
    public function test_rq_003_find_by_user_with_tool_filter(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['tool_name' => 'keyword-density']);

        $result = $this->repository->findByUser($user->id, ['tool_name' => 'meta-tag-analyzer']);

        $this->assertCount(3, $result->items());
        foreach ($result->items() as $item) {
            $this->assertEquals('meta-tag-analyzer', $item->tool_name);
        }
    }

    /** @test RQ-004: findByUser with date range filter */
    public function test_rq_004_find_by_user_with_date_range(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(10),
        ]);
        $middle = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(5),
        ]);
        $recent = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now(),
        ]);

        $result = $this->repository->findByUser($user->id, [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->subDays(3)->toDateString(),
        ]);

        $this->assertCount(1, $result->items());
        $this->assertEquals($middle->id, $result->items()[0]->id);
    }

    /** @test RQ-005: findByUser ordered by analyzed_at DESC */
    public function test_rq_005_find_by_user_ordered_desc(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDays(2),
        ]);
        $middle = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now()->subDay(),
        ]);
        $recent = AnalysisHistory::factory()->forUser($user)->create([
            'analyzed_at' => now(),
        ]);

        $result = $this->repository->findByUser($user->id);

        $items = $result->items();
        $this->assertEquals($recent->id, $items[0]->id);
        $this->assertEquals($middle->id, $items[1]->id);
        $this->assertEquals($old->id, $items[2]->id);
    }

    /** @test RQ-006: getByTool returns collection */
    public function test_rq_006_get_by_tool_returns_collection(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(3)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['tool_name' => 'keyword-density']);

        $result = $this->repository->getByTool($user->id, 'meta-tag-analyzer');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $result->each(fn ($a) => $this->assertEquals('meta-tag-analyzer', $a->tool_name));
    }

    /** @test RQ-007: getByTool ordered by analyzed_at DESC */
    public function test_rq_007_get_by_tool_ordered_desc(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create([
            'tool_name' => 'meta-tag-analyzer',
            'analyzed_at' => now()->subDays(2),
        ]);
        $recent = AnalysisHistory::factory()->forUser($user)->create([
            'tool_name' => 'meta-tag-analyzer',
            'analyzed_at' => now(),
        ]);

        $result = $this->repository->getByTool($user->id, 'meta-tag-analyzer');

        $this->assertEquals($recent->id, $result->first()->id);
        $this->assertEquals($old->id, $result->last()->id);
    }

    /** @test RQ-008: getRecent returns limited collection */
    public function test_rq_008_get_recent_returns_limited(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(20)->create();

        $result = $this->repository->getRecent($user->id, 5);

        $this->assertCount(5, $result);
    }

    /** @test RQ-009: getRecent default limit is 10 */
    public function test_rq_009_get_recent_default_limit(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(20)->create();

        $result = $this->repository->getRecent($user->id);

        $this->assertCount(10, $result);
    }

    /** @test RQ-010: getRecent ordered by analyzed_at DESC */
    public function test_rq_010_get_recent_ordered_desc(): void
    {
        $user = User::factory()->create();

        $old = AnalysisHistory::factory()->forUser($user)->create(['analyzed_at' => now()->subDays(2)]);
        $recent = AnalysisHistory::factory()->forUser($user)->create(['analyzed_at' => now()]);

        $result = $this->repository->getRecent($user->id);

        $this->assertEquals($recent->id, $result->first()->id);
    }

    // ==========================================
    // Stats Tests (RS)
    // ==========================================

    /** @test RS-001: getStats returns array */
    public function test_rs_001_get_stats_returns_array(): void
    {
        $user = User::factory()->create();

        $stats = $this->repository->getStats($user->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_tool', $stats);
        $this->assertArrayHasKey('avg_duration_ms', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
    }

    /** @test RS-002: getStats total count */
    public function test_rs_002_get_stats_total_count(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(15)->create();

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(15, $stats['total']);
    }

    /** @test RS-003: getStats by status breakdown */
    public function test_rs_003_get_stats_by_status(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(5)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->count(3)->create(['status' => 'failed']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['status' => 'pending']);

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(5, $stats['by_status']['completed']);
        $this->assertEquals(3, $stats['by_status']['failed']);
        $this->assertEquals(2, $stats['by_status']['pending']);
    }

    /** @test RS-004: getStats by tool breakdown */
    public function test_rs_004_get_stats_by_tool(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(5)->create(['tool_name' => 'meta-tag-analyzer']);
        AnalysisHistory::factory()->forUser($user)->count(3)->create(['tool_name' => 'keyword-density']);

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(5, $stats['by_tool']['meta-tag-analyzer']);
        $this->assertEquals(3, $stats['by_tool']['keyword-density']);
    }

    /** @test RS-005: getStats avg duration */
    public function test_rs_005_get_stats_avg_duration(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->create(['analysis_duration_ms' => 1000]);
        AnalysisHistory::factory()->forUser($user)->create(['analysis_duration_ms' => 2000]);
        AnalysisHistory::factory()->forUser($user)->create(['analysis_duration_ms' => 3000]);

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(2000.0, $stats['avg_duration_ms']);
    }

    /** @test RS-006: getStats empty user */
    public function test_rs_006_get_stats_empty_user(): void
    {
        $user = User::factory()->create();

        $stats = $this->repository->getStats($user->id);

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['by_status']['completed']);
        $this->assertEquals(0, $stats['by_status']['failed']);
        $this->assertEquals(0, $stats['by_status']['pending']);
        $this->assertEquals(0, $stats['avg_duration_ms']);
        $this->assertEquals(0, $stats['success_rate']);
    }

    /** @test RS-007: getStats success rate */
    public function test_rs_007_get_stats_success_rate(): void
    {
        $user = User::factory()->create();

        AnalysisHistory::factory()->forUser($user)->count(8)->create(['status' => 'completed']);
        AnalysisHistory::factory()->forUser($user)->count(2)->create(['status' => 'failed']);

        $stats = $this->repository->getStats($user->id);

        // 8 completed out of 10 total = 80%
        $this->assertEquals(80.0, $stats['success_rate']);
    }

    // ==========================================
    // Pagination Tests (PG)
    // ==========================================

    /** @test PG-001: Default page size is 15 */
    public function test_pg_001_default_page_size(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(20)->create();

        $result = $this->repository->findByUser($user->id);

        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    /** @test PG-002: Custom page size */
    public function test_pg_002_custom_page_size(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(30)->create();

        $result = $this->repository->findByUser($user->id, ['per_page' => 10]);

        $this->assertEquals(10, $result->perPage());
    }

    /** @test PG-003: Page navigation */
    public function test_pg_003_page_navigation(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(25)->create();

        $page1 = $this->repository->findByUser($user->id, ['per_page' => 10, 'page' => 1]);
        $page2 = $this->repository->findByUser($user->id, ['per_page' => 10, 'page' => 2]);
        $page3 = $this->repository->findByUser($user->id, ['per_page' => 10, 'page' => 3]);

        $this->assertCount(10, $page1->items());
        $this->assertCount(10, $page2->items());
        $this->assertCount(5, $page3->items());
        $this->assertEquals(1, $page1->currentPage());
        $this->assertEquals(2, $page2->currentPage());
        $this->assertEquals(3, $page3->currentPage());
    }

    /** @test PG-004: Total count correct */
    public function test_pg_004_total_count_correct(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(37)->create();

        $result = $this->repository->findByUser($user->id, ['per_page' => 10]);

        $this->assertEquals(37, $result->total());
        $this->assertEquals(4, $result->lastPage());
    }

    // ==========================================
    // Edge Cases Tests (EC)
    // ==========================================

    /** @test EC-001: Large JSON in results */
    public function test_ec_001_large_json_in_results(): void
    {
        $user = User::factory()->create();

        // Create ~100KB JSON
        $largeResults = [
            'data' => str_repeat('A', 100000),
            'items' => array_fill(0, 1000, ['key' => 'value']),
        ];

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => $largeResults,
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertCount(1000, $analysis->results['items']);
    }

    /** @test EC-002: Unicode in input_url */
    public function test_ec_002_unicode_in_input_url(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com/путь/страница',
        ]);

        $this->assertEquals('https://example.com/путь/страница', $analysis->input_url);
    }

    /** @test EC-003: Unicode in results */
    public function test_ec_003_unicode_in_results(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [
                'title' => 'Заголовок',
                'symbols' => '€£¥',
            ],
        ]);

        $this->assertEquals('Заголовок', $analysis->results['title']);
        $this->assertEquals('€£¥', $analysis->results['symbols']);
    }

    /** @test EC-004: Cross-user isolation */
    public function test_ec_004_cross_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->forUser($user1)->count(5)->create();
        AnalysisHistory::factory()->forUser($user2)->count(3)->create();

        $result = $this->repository->findByUser($user1->id);

        $this->assertCount(5, $result->items());
        foreach ($result->items() as $item) {
            $this->assertEquals($user1->id, $item->user_id);
        }
    }

    /** @test EC-005: Empty results object */
    public function test_ec_005_empty_results_object(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [],
        ]);

        $this->assertIsArray($analysis->results);
        $this->assertEmpty($analysis->results);
    }

    /** @test EC-006: Null values in results */
    public function test_ec_006_null_values_in_results(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [
                'title' => 'Test',
                'description' => null,
                'keywords' => null,
            ],
        ]);

        $this->assertEquals('Test', $analysis->results['title']);
        $this->assertNull($analysis->results['description']);
        $this->assertNull($analysis->results['keywords']);
    }

    /** @test EC-007: Site filter in findByUser */
    public function test_ec_007_site_filter(): void
    {
        $user = User::factory()->create();
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        AnalysisHistory::factory()->forUser($user)->forSite($site1)->count(3)->create();
        AnalysisHistory::factory()->forUser($user)->forSite($site2)->count(2)->create();

        $result = $this->repository->findByUser($user->id, ['site_id' => $site1->id]);

        $this->assertCount(3, $result->items());
        foreach ($result->items() as $item) {
            $this->assertEquals($site1->id, $item->site_id);
        }
    }

    // ==========================================
    // Security Tests (SEC)
    // ==========================================

    /** @test SEC-001: SQL injection in input_url prevented */
    public function test_sec_001_sql_injection_in_url_prevented(): void
    {
        $user = User::factory()->create();

        $maliciousUrl = "https://example.com?id=1'; DROP TABLE analysis_history; --";

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => $maliciousUrl,
        ]);

        // Verify table still exists
        $this->assertDatabaseHas('analysis_history', ['id' => $analysis->id]);
    }

    /** @test SEC-002: SQL injection in tool_name prevented */
    public function test_sec_002_sql_injection_in_tool_prevented(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => "tool'; DELETE FROM analysis_history; --",
            'input_url' => 'https://example.com',
        ]);

        // Verify table still exists
        $this->assertDatabaseHas('analysis_history', ['id' => $analysis->id]);
    }

    /** @test SEC-003: XSS in error_message not executed */
    public function test_sec_003_xss_in_error_message(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'failed',
            'error_message' => '<script>alert("xss")</script>',
        ]);

        // Values stored as-is, output escaping at display layer
        $this->assertEquals('<script>alert("xss")</script>', $analysis->error_message);
    }

    /** @test SEC-004: XSS in results JSON not executed */
    public function test_sec_004_xss_in_results(): void
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => [
                'title' => '<script>alert("xss")</script>',
            ],
        ]);

        // Values stored as-is, output escaping at display layer
        $this->assertEquals('<script>alert("xss")</script>', $analysis->results['title']);
    }

    /** @test SEC-005: User isolation enforced in stats */
    public function test_sec_005_user_isolation_in_stats(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AnalysisHistory::factory()->forUser($user1)->count(10)->create();
        AnalysisHistory::factory()->forUser($user2)->count(5)->create();

        $stats1 = $this->repository->getStats($user1->id);
        $stats2 = $this->repository->getStats($user2->id);

        $this->assertEquals(10, $stats1['total']);
        $this->assertEquals(5, $stats2['total']);
    }

    // ==========================================
    // Performance Tests (PF)
    // ==========================================

    /** @test PF-001: Large results storage is fast */
    public function test_pf_001_large_results_storage(): void
    {
        $user = User::factory()->create();

        // Create ~50KB JSON
        $largeResults = [
            'data' => str_repeat('A', 50000),
            'items' => array_fill(0, 500, ['key' => 'value']),
        ];

        $start = microtime(true);
        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'results' => $largeResults,
        ]);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Create should complete in < 100ms');
        $this->assertNotNull($analysis->id);
    }

    /** @test PF-002: FindById performance */
    public function test_pf_002_find_by_id_performance(): void
    {
        $analysis = AnalysisHistory::factory()->create();

        $start = microtime(true);
        $found = $this->repository->findById($analysis->id);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(10, $elapsed, 'FindById should complete in < 10ms');
        $this->assertNotNull($found);
    }

    /** @test PF-003: findByUser with many records */
    public function test_pf_003_find_by_user_many_records(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(500)->create();

        $start = microtime(true);
        $result = $this->repository->findByUser($user->id);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'findByUser should complete in < 200ms');
        $this->assertEquals(15, count($result->items()));
    }

    /** @test PF-004: getStats with many records */
    public function test_pf_004_get_stats_many_records(): void
    {
        $user = User::factory()->create();
        AnalysisHistory::factory()->forUser($user)->count(1000)->create();

        $start = microtime(true);
        $stats = $this->repository->getStats($user->id);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'getStats should complete in < 500ms');
        $this->assertEquals(1000, $stats['total']);
    }

    // ==========================================
    // Integration Tests (INT)
    // ==========================================

    /** @test INT-001: Full CRUD workflow */
    public function test_int_001_full_crud_workflow(): void
    {
        $user = User::factory()->create();

        // Create
        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'pending',
        ]);

        $this->assertNotNull($analysis->id);

        // Read
        $found = $this->repository->findById($analysis->id);
        $this->assertEquals($analysis->id, $found->id);

        // Update
        $updated = $this->repository->update($analysis->id, [
            'status' => 'completed',
            'results' => ['score' => 85],
        ]);
        $this->assertEquals('completed', $updated->status);

        // Delete
        $deleted = $this->repository->delete($analysis->id);
        $this->assertTrue($deleted);

        // Verify deleted
        $notFound = $this->repository->findById($analysis->id);
        $this->assertNull($notFound);
    }

    /** @test INT-002: User relationship integrity */
    public function test_int_002_user_relationship_integrity(): void
    {
        $user = User::factory()->create();
        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
        ]);

        // Verify relationship loaded
        $found = $this->repository->findById($analysis->id);
        $this->assertEquals($user->id, $found->user_id);

        // Verify user can access analyses
        $user->refresh();
        $this->assertTrue($user->analysisHistory->contains($analysis));
    }
}
