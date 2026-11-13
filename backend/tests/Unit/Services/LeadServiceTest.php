<?php

namespace Tests\Unit\Services;

use App\Models\ToolLead;
use App\Repositories\LeadRepository;
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
    private LeadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(LeadRepository::class);
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

        $result = $this->service->capture($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('lead', $result);
        $this->assertNotNull($result['lead']);
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

        $result = $this->service->capture($data);

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

        $result = $this->service->capture($data);

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

        $isDuplicate = $this->service->capture(['email' => 'dup@example.com', 'tool' => 'meta-tag-analyzer'])['is_duplicate'];

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

        $isDuplicate = $this->service->capture(['email' => 'old@example.com', 'tool' => 'meta-tag-analyzer'])['is_duplicate'];

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

        $isDuplicate = $this->service->capture(['email' => 'case@example.com', 'tool' => 'meta-tag-analyzer'])['is_duplicate'];

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
        $isDuplicate24h = $this->service->capture(['email' => 'window@example.com', 'tool' => 'meta-tag-analyzer'])['is_duplicate'];
        $this->assertFalse($isDuplicate24h);

        // With 72h window, should be duplicate
        $isDuplicate72h = $this->service->capture(['email' => 'window@example.com', 'tool' => 'meta-tag-analyzer'])['is_duplicate'];
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

        $result = $this->service->getLeads(10);

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

        $result = $this->service->getLeads(10, ['tool' => 'meta-tag-analyzer']);

        $this->assertCount(5, $result['data']);
    }

    /**
     * Test getPaginated excludes soft deleted by default.
     */
    public function test_get_paginated_excludes_soft_deleted(): void
    {
        ToolLead::factory()->create(['email' => 'active@example.com']);
        ToolLead::factory()->softDeleted()->create(['email' => 'deleted@example.com']);

        $result = $this->service->getLeads(10);

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
        // thisWeek is the CALENDAR week (startOfWeek..endOfWeek). "3 days
        // ago" can land in the previous week, so use a date that is
        // unambiguously inside the current one.
        $insideThisWeek = now()->startOfWeek()->addHours(2);
        $outsideThisWeek = now()->subWeeks(2)->startOfWeek()->addHours(2);

        ToolLead::factory()->create(['created_at' => $insideThisWeek]);
        ToolLead::factory()->create(['created_at' => $insideThisWeek]);
        ToolLead::factory()->create(['created_at' => $outsideThisWeek]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['thisWeek']);
    }

    /**
     * Test getStatistics calculates thisMonth correctly.
     */
    public function test_get_statistics_calculates_this_month(): void
    {
        // thisMonth is the CALENDAR month (startOfMonth..endOfMonth), the
        // same definition AnalysisHistoryRepository uses for the admin
        // dashboard. The old test used "15 days ago", which falls outside
        // the calendar month whenever the suite runs in the first half of
        // the month - so the test passed on some days and failed on others.
        $insideThisMonth = now()->startOfMonth()->addHours(2);
        $insideLastMonth = now()->subMonths(2)->startOfMonth()->addHours(2);

        ToolLead::factory()->create(['created_at' => $insideThisMonth]);
        ToolLead::factory()->create(['created_at' => $insideThisMonth]);
        ToolLead::factory()->create(['created_at' => $insideLastMonth]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['thisMonth']);
    }

    /**
     * Test getStatistics with date filter.
     */
    public function test_get_statistics_counts_every_lead(): void
    {
        // getStatistics() takes no filters: date filtering was removed
        // from the service. The old test passed a filter array that was
        // silently ignored and then asserted the filtered count, so it
        // only passed by accident.
        ToolLead::factory()->create(['created_at' => '2026-02-15']);
        ToolLead::factory()->create(['created_at' => '2026-02-20']);
        ToolLead::factory()->create(['created_at' => '2026-03-01']);

        $stats = $this->service->getStatistics();

        $this->assertEquals(3, $stats['total']);
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

        $csv = $this->service->exportCsv();

        // The export starts at the email column; there is no leading id
        // column.
        $this->assertStringContainsString('Email', $csv);
        $this->assertStringContainsString('export@example.com', $csv);
    }

    /**
     * Test exportCSV with filters.
     */
    public function test_export_csv_with_filters(): void
    {
        ToolLead::factory()->create(['email' => 'include@example.com', 'tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['email' => 'exclude@example.com', 'tool' => 'keyword-density']);

        $csv = $this->service->exportCsv(['tool' => 'meta-tag-analyzer']);

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

        $result = $this->service->delete($lead->id);

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
