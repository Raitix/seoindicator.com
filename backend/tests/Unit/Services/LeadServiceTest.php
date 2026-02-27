<?php

namespace Tests\Unit\Services;

use App\Models\ToolLead;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the LeadService.
 *
 * Tests business logic for lead storage, duplicate detection, and statistics.
 *
 * @see TASK-16-6
 * @see TEST-16-6
 * @see FEAT-016
 */
class LeadServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadService $service;
    private LeadRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(LeadRepositoryInterface::class);
        $this->service = app(LeadService::class);
    }

    // =========================================================================
    // STORE TESTS
    // =========================================================================

    /**
     * Test store creates new lead.
     */
    public function test_store_creates_new_lead(): void
    {
        $data = [
            'email' => 'newuser@example.com',
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        $result = $this->service->store($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('leadId', $result);
        $this->assertDatabaseHas('tool_leads', [
            'email' => 'newuser@example.com',
            'tool' => 'meta-tag-analyzer',
        ]);
    }

    /**
     * Test store handles duplicate gracefully.
     */
    public function test_store_handles_duplicate_gracefully(): void
    {
        // Create existing lead within 24h
        ToolLead::factory()->create([
            'email' => 'duplicate@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(12),
        ]);

        $data = [
            'email' => 'duplicate@example.com',
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'ip_address' => '192.168.1.1',
        ];

        $result = $this->service->store($data);

        // Should return success without creating duplicate
        $this->assertTrue($result['success']);
        $this->assertEquals(1, ToolLead::where('email', 'duplicate@example.com')->count());
    }

    /**
     * Test store allows same email for different tools.
     */
    public function test_store_allows_same_email_different_tools(): void
    {
        ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
        ]);

        $data = [
            'email' => 'user@example.com',
            'tool' => 'keyword-density',
            'ip_address' => '192.168.1.1',
        ];

        $result = $this->service->store($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, ToolLead::where('email', 'user@example.com')->count());
    }

    // =========================================================================
    // DUPLICATE DETECTION TESTS
    // =========================================================================

    /**
     * Test isDuplicate returns true for duplicate.
     */
    public function test_is_duplicate_returns_true_for_duplicate(): void
    {
        ToolLead::factory()->create([
            'email' => 'dup@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(12),
        ]);

        $isDuplicate = $this->service->isDuplicate('dup@example.com', 'meta-tag-analyzer');

        $this->assertTrue($isDuplicate);
    }

    /**
     * Test isDuplicate returns false for non-duplicate.
     */
    public function test_is_duplicate_returns_false_for_non_duplicate(): void
    {
        ToolLead::factory()->create([
            'email' => 'old@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(48),
        ]);

        $isDuplicate = $this->service->isDuplicate('old@example.com', 'meta-tag-analyzer');

        $this->assertFalse($isDuplicate);
    }

    /**
     * Test isDuplicate is case insensitive.
     */
    public function test_is_duplicate_case_insensitive(): void
    {
        ToolLead::factory()->create([
            'email' => 'case@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(12),
        ]);

        $isDuplicate = $this->service->isDuplicate('CASE@EXAMPLE.COM', 'meta-tag-analyzer');

        $this->assertTrue($isDuplicate);
    }

    /**
     * Test duplicate window is configurable.
     */
    public function test_duplicate_window_configurable(): void
    {
        // Create lead 48 hours ago
        ToolLead::factory()->create([
            'email' => 'window@example.com',
            'tool' => 'meta-tag-analyzer',
            'created_at' => now()->subHours(48),
        ]);

        // With 24h window, should not be duplicate
        $isDuplicate24h = $this->service->isDuplicate('window@example.com', 'meta-tag-analyzer', 24);
        $this->assertFalse($isDuplicate24h);

        // With 72h window, should be duplicate
        $isDuplicate72h = $this->service->isDuplicate('window@example.com', 'meta-tag-analyzer', 72);
        $this->assertTrue($isDuplicate72h);
    }

    // =========================================================================
    // PAGINATION TESTS
    // =========================================================================

    /**
     * Test getPaginated returns paginated results.
     */
    public function test_get_paginated_returns_paginated_results(): void
    {
        ToolLead::factory()->count(25)->create();

        $result = $this->service->getPaginated(10);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['meta']['total']);
    }

    /**
     * Test getPaginated with filters.
     */
    public function test_get_paginated_with_filters(): void
    {
        ToolLead::factory()->count(5)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(3)->keywordDensity()->create();

        $result = $this->service->getPaginated(10, ['tool' => 'meta-tag-analyzer']);

        $this->assertCount(5, $result['data']);
    }

    /**
     * Test getPaginated excludes soft deleted by default.
     */
    public function test_get_paginated_excludes_soft_deleted(): void
    {
        ToolLead::factory()->create(['email' => 'active@example.com']);
        ToolLead::factory()->softDeleted()->create(['email' => 'deleted@example.com']);

        $result = $this->service->getPaginated(10);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('active@example.com', $result['data'][0]['email']);
    }

    // =========================================================================
    // STATISTICS TESTS
    // =========================================================================

    /**
     * Test getStatistics returns correct counts.
     */
    public function test_get_statistics_returns_correct_counts(): void
    {
        ToolLead::factory()->count(10)->metaTagAnalyzer()->create();
        ToolLead::factory()->count(5)->keywordDensity()->create();

        $stats = $this->service->getStatistics();

        $this->assertEquals(15, $stats['total']);
        $this->assertEquals(10, $stats['byTool']['meta-tag-analyzer']);
        $this->assertEquals(5, $stats['byTool']['keyword-density']);
    }

    /**
     * Test getStatistics calculates thisWeek correctly.
     */
    public function test_get_statistics_calculates_this_week(): void
    {
        ToolLead::factory()->create(['created_at' => now()->subDays(3)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(3)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(10)]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['thisWeek']);
    }

    /**
     * Test getStatistics calculates thisMonth correctly.
     */
    public function test_get_statistics_calculates_this_month(): void
    {
        ToolLead::factory()->create(['created_at' => now()->subDays(15)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(15)]);
        ToolLead::factory()->create(['created_at' => now()->subDays(45)]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['thisMonth']);
    }

    /**
     * Test getStatistics with date filter.
     */
    public function test_get_statistics_with_date_filter(): void
    {
        ToolLead::factory()->create(['created_at' => '2026-02-15']);
        ToolLead::factory()->create(['created_at' => '2026-02-20']);
        ToolLead::factory()->create(['created_at' => '2026-03-01']);

        $stats = $this->service->getStatistics([
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]);

        $this->assertEquals(2, $stats['total']);
    }

    // =========================================================================
    // EXPORT CSV TESTS
    // =========================================================================

    /**
     * Test exportCSV returns valid CSV string.
     */
    public function test_export_csv_returns_valid_csv(): void
    {
        ToolLead::factory()->create([
            'email' => 'export@example.com',
            'tool' => 'meta-tag-analyzer',
        ]);

        $csv = $this->service->exportCSV();

        $this->assertStringContainsString('email', $csv);
        $this->assertStringContainsString('export@example.com', $csv);
    }

    /**
     * Test exportCSV with filters.
     */
    public function test_export_csv_with_filters(): void
    {
        ToolLead::factory()->create(['email' => 'include@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'exclude@example.com', 'tool' => 'keyword-density']);

        $csv = $this->service->exportCSV(['tool' => 'meta-tag-analyzer']);

        $this->assertStringContainsString('include@example.com', $csv);
        $this->assertStringNotContainsString('exclude@example.com', $csv);
    }

    // =========================================================================
    // SOFT DELETE TESTS
    // =========================================================================

    /**
     * Test softDelete marks lead as deleted.
     */
    public function test_soft_delete_marks_lead_as_deleted(): void
    {
        $lead = ToolLead::factory()->create();

        $result = $this->service->softDelete($lead->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test restore recovers soft deleted lead.
     */
    public function test_restore_recovers_soft_deleted_lead(): void
    {
        $lead = ToolLead::factory()->softDeleted()->create();

        $result = $this->service->restore($lead->id);

        $this->assertTrue($result);
        $this->assertNull($lead->fresh()->deleted_at);
    }
}
