<?php

namespace Tests\Unit\Repositories;

use App\Models\ToolLead;
use App\Repositories\LeadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for the LeadRepository.
 *
 * Tests CRUD operations, filtering, and statistics.
 *
 * @see TASK-16-6
 * @see TEST-16-6
 * @see FEAT-016
 */
class LeadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private LeadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(LeadRepository::class);
    }

    // =========================================================================
    // CREATE TESTS
    // =========================================================================

    /**
     * Test create method stores a lead.
     * REPO-001
     */
    public function test_create_stores_lead(): void
    {
        $data = [
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        $lead = $this->repository->create($data);

        $this->assertInstanceOf(ToolLead::class, $lead);
        $this->assertEquals('user@example.com', $lead->email);
        $this->assertEquals('meta-tag-analyzer', $lead->tool);
        $this->assertDatabaseHas('tool_leads', [
            'email' => 'user@example.com',
        ]);
    }

    // =========================================================================
    // FIND TESTS
    // =========================================================================

    /**
     * Test find returns lead by ID.
     * REPO-002
     */
    public function test_find_returns_lead_by_id(): void
    {
        $lead = ToolLead::factory()->create();

        $found = $this->repository->find($lead->id);

        $this->assertNotNull($found);
        $this->assertEquals($lead->id, $found->id);
    }

    /**
     * Test find returns null for non-existent ID.
     */
    public function test_find_returns_null_for_nonexistent(): void
    {
        $found = $this->repository->find(99999);

        $this->assertNull($found);
    }

    /**
     * Test findByEmail returns collection.
     * REPO-003
     */
    public function test_find_by_email_returns_collection(): void
    {
        ToolLead::factory()->create(['email' => 'user@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'user@example.com', 'tool' => 'keyword-density']);
        ToolLead::factory()->create(['email' => 'other@example.com']);

        $results = $this->repository->findByEmail('user@example.com');

        $this->assertCount(2, $results);
    }

    /**
     * Test findByTool returns collection.
     * REPO-004
     */
    public function test_find_by_tool_returns_collection(): void
    {
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['tool' => 'keyword-density']);

        $results = $this->repository->findByTool('meta-tag-analyzer');

        $this->assertCount(2, $results);
    }

    // =========================================================================
    // PAGINATION TESTS
    // =========================================================================

    /**
     * Test paginate returns paginated results.
     * REPO-005
     */
    public function test_paginate_returns_paginated_results(): void
    {
        ToolLead::factory()->count(25)->create();

        $results = $this->repository->paginate(10);

        $this->assertCount(10, $results->items());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    /**
     * Test paginate with filters.
     */
    public function test_paginate_with_filters(): void
    {
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer', 'email' => 'user1@example.com']);
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer', 'email' => 'user2@example.com']);
        ToolLead::factory()->create(['tool' => 'keyword-density']);

        $results = $this->repository->paginate(10, ['tool' => 'meta-tag-analyzer']);

        $this->assertCount(2, $results->items());
    }

    // =========================================================================
    // UPDATE TESTS
    // =========================================================================

    /**
     * Test update modifies lead.
     * REPO-006
     */
    public function test_update_modifies_lead(): void
    {
        $lead = ToolLead::factory()->create(['url' => 'https://old.com']);

        $updated = $this->repository->update($lead->id, ['url' => 'https://new.com']);

        $this->assertEquals('https://new.com', $updated->url);
    }

    // =========================================================================
    // DELETE TESTS
    // =========================================================================

    /**
     * Test delete removes lead.
     * REPO-007
     */
    public function test_delete_removes_lead(): void
    {
        $lead = ToolLead::factory()->create();

        $result = $this->repository->delete($lead->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test softDelete sets deleted_at.
     * REPO-008
     */
    public function test_soft_delete_sets_deleted_at(): void
    {
        $lead = ToolLead::factory()->create();

        $this->repository->softDelete($lead->id);

        $this->assertSoftDeleted('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test restore clears deleted_at.
     * REPO-009
     */
    public function test_restore_clears_deleted_at(): void
    {
        $lead = ToolLead::factory()->softDeleted()->create();

        $this->repository->restore($lead->id);

        $this->assertNull($lead->fresh()->deleted_at);
    }

    /**
     * Test forceDelete permanently removes lead.
     * REPO-010
     */
    public function test_force_delete_removes_permanently(): void
    {
        $lead = ToolLead::factory()->create();

        $this->repository->forceDelete($lead->id);

        $this->assertDatabaseMissing('tool_leads', ['id' => $lead->id]);
    }

    // =========================================================================
    // COUNT TESTS
    // =========================================================================

    /**
     * Test countByTool returns correct count.
     * REPO-011
     */
    public function test_count_by_tool(): void
    {
        ToolLead::factory()->count(5)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(3)->keywordDensity()->create();

        $count = $this->repository->countByTool('meta-tag-analyzer');

        $this->assertEquals(5, $count);
    }

    /**
     * Test countByDateRange returns correct count.
     * REPO-012
     */
    public function test_count_by_date_range(): void
    {
        // Create leads with specific dates
        ToolLead::factory()->create(['created_at' => '2026-02-01 10:00:00']);
        ToolLead::factory()->create(['created_at' => '2026-02-15 10:00:00']);
        ToolLead::factory()->create(['created_at' => '2026-02-25 10:00:00']);
        ToolLead::factory()->create(['created_at' => '2026-03-01 10:00:00']);

        $count = $this->repository->countByDateRange('2026-02-01', '2026-02-28');

        $this->assertEquals(3, $count);
    }

    // =========================================================================
    // DUPLICATE DETECTION TESTS
    // =========================================================================

    /**
     * Test exists returns true for duplicate.
     * REPO-013
     */
    public function test_exists_returns_true_for_duplicate(): void
    {
        ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(12),
        ]);

        $exists = $this->repository->exists('user@example.com', 'meta-tag-analyzer', 24);

        $this->assertTrue($exists);
    }

    /**
     * Test exists returns false after window.
     */
    public function test_exists_returns_false_after_window(): void
    {
        ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(48), // More than 24h window
        ]);

        $exists = $this->repository->exists('user@example.com', 'meta-tag-analyzer', 24);

        $this->assertFalse($exists);
    }

    /**
     * Test exists is case insensitive for email.
     */
    public function test_exists_case_insensitive_email(): void
    {
        ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(12),
        ]);

        $exists = $this->repository->exists('USER@EXAMPLE.COM', 'meta-tag-analyzer', 24);

        $this->assertTrue($exists);
    }

    /**
     * Test getDuplicates returns collection.
     * REPO-014
     */
    public function test_get_duplicates_returns_collection(): void
    {
        ToolLead::factory()->create(['email' => 'user@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'user@example.com', 'tool' => 'keyword-density']);
        ToolLead::factory()->create(['email' => 'other@example.com']);

        $duplicates = $this->repository->getDuplicates('user@example.com');

        $this->assertCount(2, $duplicates);
    }

    // =========================================================================
    // EXPORT TESTS
    // =========================================================================

    /**
     * Test exportToCSV returns valid CSV.
     * REPO-015
     */
    public function test_export_to_csv_returns_valid_csv(): void
    {
        ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'ip_address' => '192.168.1.1',
        ]);

        $csv = $this->repository->exportToCSV();

        $this->assertStringContainsString('id,email,tool,url,ip_address,user_agent,created_at', $csv);
        $this->assertStringContainsString('user@example.com', $csv);
        $this->assertStringContainsString('meta-tag-analyzer', $csv);
    }

    /**
     * Test exportToCSV with filters.
     */
    public function test_export_to_csv_with_filters(): void
    {
        ToolLead::factory()->create(['email' => 'user@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'other@example.com', 'tool' => 'keyword-density']);

        $csv = $this->repository->exportToCSV(['tool' => 'meta-tag-analyzer']);

        $this->assertStringContainsString('user@example.com', $csv);
        $this->assertStringNotContainsString('other@example.com', $csv);
    }

    // =========================================================================
    // STATISTICS TESTS
    // =========================================================================

    /**
     * Test getStatistics returns correct data.
     */
    public function test_get_statistics_returns_correct_data(): void
    {
        ToolLead::factory()->count(10)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(5)->keywordDensity()->create();

        $stats = $this->repository->getStatistics();

        $this->assertEquals(15, $stats['total']);
        $this->assertEquals(10, $stats['byTool']['meta-tag-analyzer']);
        $this->assertEquals(5, $stats['byTool']['keyword-density']);
    }

    /**
     * Test getStatistics excludes soft deleted by default.
     */
    public function test_get_statistics_excludes_soft_deleted(): void
    {
        ToolLead::factory()->create();
        ToolLead::factory()->softDeleted()->create();

        $stats = $this->repository->getStatistics();

        $this->assertEquals(1, $stats['total']);
    }
}
