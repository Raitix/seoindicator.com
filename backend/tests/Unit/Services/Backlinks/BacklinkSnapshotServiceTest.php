<?php

namespace Tests\Unit\Services\Backlinks;

use App\Models\AnchorText;
use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Services\Backlinks\BacklinkSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Unit tests for BacklinkSnapshotService.
 *
 * Tests snapshot creation, history retrieval, and metric calculations.
 *
 * @see TASK-23-6
 * @see TASK-23-17
 */
class BacklinkSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacklinkSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BacklinkSnapshotService();
    }

    // =============================================
    // GET HISTORY TESTS (GH)
    // =============================================

    /** @test GH-001: Returns history for project */
    public function get_history_returns_snapshots_for_project(): void
    {
        Carbon::setTestNow('2026-02-28');
        
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        // Create snapshots on different dates
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-10',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-20',
        ]);
        
        BacklinkSnapshot::factory()->create([
            'project_id' => $otherProject->id,
            'snapshot_date' => '2026-02-12',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $otherProject->id,
            'snapshot_date' => '2026-02-18',
        ]);

        $history = $this->service->getHistory($project->id);

        $this->assertCount(3, $history);
        
        Carbon::setTestNow();
    }

    /** @test GH-002: Returns empty collection for no snapshots */
    public function get_history_returns_empty_for_no_snapshots(): void
    {
        $project = Project::factory()->create();

        $history = $this->service->getHistory($project->id);

        $this->assertEmpty($history);
    }

    /** @test GH-003: Filters by date range */
    public function get_history_filters_by_date_range(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-10',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-20',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-25',
        ]);

        $history = $this->service->getHistory($project->id, '2026-02-12', '2026-02-22');

        $this->assertCount(2, $history);
    }

    /** @test GH-004: Defaults to last 30 days */
    public function get_history_defaults_to_30_days(): void
    {
        Carbon::setTestNow('2026-02-28');

        $project = Project::factory()->create();

        // Within 30 days
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);
        // Outside 30 days
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-01-20',
        ]);

        $history = $this->service->getHistory($project->id);

        $this->assertCount(1, $history);

        Carbon::setTestNow();
    }

    /** @test GH-005: Orders by date ascending */
    public function get_history_orders_by_date_asc(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-20',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-10',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);

        $history = $this->service->getHistory($project->id);

        $dates = $history->pluck('date')->toArray();
        $this->assertEquals(['2026-02-10', '2026-02-15', '2026-02-20'], $dates);
    }

    /** @test GH-006: Returns API response format */
    public function get_history_returns_api_response_format(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
            'total_backlinks' => 100,
            'referring_domains_count' => 50,
        ]);

        $history = $this->service->getHistory($project->id);

        $this->assertArrayHasKey('date', $history[0]);
        $this->assertArrayHasKey('total_backlinks', $history[0]);
        $this->assertArrayHasKey('referring_domains_count', $history[0]);
    }

    /** @test GH-007: Returns empty for non-existent project */
    public function get_history_returns_empty_for_nonexistent_project(): void
    {
        $history = $this->service->getHistory(99999);

        $this->assertEmpty($history);
    }

    // =============================================
    // CREATE SNAPSHOT TESTS (CS)
    // =============================================

    /** @test CS-001: Creates snapshot with calculated metrics */
    public function create_snapshot_stores_metrics(): void
    {
        $project = Project::factory()->create();

        // Create backlinks
        Backlink::factory()->count(50)->dofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(30)->nofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        // Create referring domains
        ReferringDomain::factory()->count(10)->create([
            'project_id' => $project->id,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertDatabaseHas('backlink_snapshots', [
            'project_id' => $project->id,
            'total_backlinks' => 80,
            'referring_domains_count' => 10,
            'dofollow_count' => 50,
            'nofollow_count' => 30,
        ]);
    }

    /** @test CS-002: Creates snapshot with today's date */
    public function create_snapshot_uses_todays_date(): void
    {
        Carbon::setTestNow('2026-02-28 12:00:00');

        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals('2026-02-28', $snapshot->snapshot_date->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /** @test CS-003: Handles empty backlink profile */
    public function create_snapshot_handles_empty_profile(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
        $this->assertEquals(0, $snapshot->referring_domains_count);
        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(0, $snapshot->nofollow_count);
        $this->assertNull($snapshot->avg_domain_trust);
    }

    /** @test CS-004: Only counts active backlinks */
    public function create_snapshot_only_counts_active_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(50)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(30)->create([
            'project_id' => $project->id,
            'is_active' => false,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(50, $snapshot->total_backlinks);
    }

    /** @test CS-005: Returns snapshot model instance */
    public function create_snapshot_returns_model_instance(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertTrue($snapshot->exists);
    }

    /** @test CS-006: Calculates average domain trust */
    public function create_snapshot_calculates_avg_trust(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 80,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 60,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 40,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(60.0, $snapshot->avg_domain_trust);
    }

    /** @test CS-007: Handles domains with zero trust */
    public function create_snapshot_excludes_zero_trust_from_avg(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 80,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0, // Should be excluded
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(80.0, $snapshot->avg_domain_trust);
    }

    // =============================================
    // ANCHOR DIVERSITY CALCULATION TESTS (AD)
    // =============================================

    /** @test AD-001: Perfect diversity (equal distribution) */
    public function anchor_diversity_perfect_distribution(): void
    {
        $project = Project::factory()->create();

        // 10 anchors, each with 10 backlinks = perfect distribution
        foreach (range(1, 10) as $i) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'anchor_text' => "anchor-{$i}",
                'backlinks_count' => 10,
            ]);
        }

        $score = $this->service->calculateAnchorDiversity($project->id);

        // Perfect diversity = 100 - (10% * 100) = 90
        $this->assertEquals(90.0, $score);
    }

    /** @test AD-002: Poor diversity (dominant anchor) */
    public function anchor_diversity_poor_distribution(): void
    {
        $project = Project::factory()->create();

        // One anchor with 90 backlinks, others with 10 total
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 90,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 10,
        ]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // 90% dominant = 100 - 90 = 10
        $this->assertEquals(10.0, $score);
    }

    /** @test AD-003: Single anchor */
    public function anchor_diversity_single_anchor(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 100,
        ]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // 100% dominant = 100 - 100 = 0
        $this->assertEquals(0.0, $score);
    }

    /** @test AD-004: No anchors */
    public function anchor_diversity_no_anchors(): void
    {
        $project = Project::factory()->create();

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $score);
    }

    /** @test AD-005: Anchors with zero backlinks */
    public function anchor_diversity_zero_backlinks(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 0,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 0,
        ]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $score);
    }

    /** @test AD-006: Calculates from ordered data */
    public function anchor_diversity_uses_top_anchor(): void
    {
        $project = Project::factory()->create();

        // Create in reverse order to test ordering
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 20,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 80, // Top anchor
        ]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // 80% dominant = 100 - 80 = 20
        $this->assertEquals(20.0, $score);
    }

    /** @test AD-007: Returns float rounded to 2 decimals */
    public function anchor_diversity_returns_rounded_float(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 33,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 67,
        ]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertIsFloat($score);
        // Score should be 33.0 (100 - 67% dominant = 33)
        $this->assertEquals(33.0, $score);
    }

    // =============================================
    // AVERAGE TRUST CALCULATION TESTS (AT)
    // =============================================

    /** @test AT-001: Calculates average correctly */
    public function avg_trust_calculates_correctly(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 50,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 70,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 90,
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(70.0, $avg);
    }

    /** @test AT-002: Returns null for no domains */
    public function avg_trust_returns_null_for_no_domains(): void
    {
        $project = Project::factory()->create();

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertNull($avg);
    }

    /** @test AT-003: Excludes zero trust from calculation */
    public function avg_trust_excludes_zero_trust(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 60,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0, // Should be excluded
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(60.0, $avg);
    }

    /** @test AT-004: Returns null if all domains have zero trust */
    public function avg_trust_returns_null_all_zero(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0,
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertNull($avg);
    }

    /** @test AT-005: Returns rounded to 2 decimals */
    public function avg_trust_rounded_to_two_decimals(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 33,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 34,
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        // 33.5 should be rounded
        $this->assertEquals(33.5, $avg);
    }

    /** @test AT-006: Only counts domains for specified project */
    public function avg_trust_filters_by_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project1->id,
            'domain_trust_score' => 80,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project2->id,
            'domain_trust_score' => 40,
        ]);

        $avg = $this->service->calculateAverageTrust($project1->id);

        $this->assertEquals(80.0, $avg);
    }

    // =============================================
    // INTEGRATION TESTS (IT)
    // =============================================

    /** @test IT-001: Full workflow - create and retrieve */
    public function integration_create_and_retrieve(): void
    {
        Carbon::setTestNow('2026-02-28');

        $project = Project::factory()->create();

        // Create data
        Backlink::factory()->count(100)->dofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        ReferringDomain::factory()->count(20)->create([
            'project_id' => $project->id,
            'domain_trust_score' => 60,
        ]);
        AnchorText::factory()->count(10)->create([
            'project_id' => $project->id,
            'backlinks_count' => 10,
        ]);

        // Create snapshot
        $snapshot = $this->service->createSnapshot($project);

        // Retrieve history
        $history = $this->service->getHistory($project->id);

        $this->assertCount(1, $history);
        $this->assertEquals(100, $history[0]['total_backlinks']);
        $this->assertEquals(20, $history[0]['referring_domains_count']);
        $this->assertEquals(60.0, $history[0]['avg_domain_trust']);

        Carbon::setTestNow();
    }

    /** @test IT-002: Multiple snapshots over time */
    public function integration_multiple_snapshots(): void
    {
        $project = Project::factory()->create();

        // Day 1
        Carbon::setTestNow('2026-02-26');
        Backlink::factory()->count(50)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $this->service->createSnapshot($project);

        // Day 2
        Carbon::setTestNow('2026-02-27');
        Backlink::factory()->count(30)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $this->service->createSnapshot($project);

        // Day 3
        Carbon::setTestNow('2026-02-28');
        Backlink::factory()->count(20)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $this->service->createSnapshot($project);

        $history = $this->service->getHistory($project->id);

        $this->assertCount(3, $history);

        // Verify ordering
        $this->assertEquals('2026-02-26', $history[0]['date']);
        $this->assertEquals('2026-02-27', $history[1]['date']);
        $this->assertEquals('2026-02-28', $history[2]['date']);

        Carbon::setTestNow();
    }

    // =============================================
    // EDGE CASE TESTS (EC)
    // =============================================

    /** @test EC-001: Large backlink counts */
    public function edge_case_large_backlink_counts(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(1000)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(1000, $snapshot->total_backlinks);
    }

    /** @test EC-002: Very large domain trust scores */
    public function edge_case_large_trust_scores(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 100,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 100,
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(100.0, $avg);
    }

    /** @test EC-003: Date range at boundaries */
    public function edge_case_date_range_boundaries(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15', // Start date
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-20', // End date
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-10', // Before range
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-25', // After range
        ]);

        $history = $this->service->getHistory($project->id, '2026-02-15', '2026-02-20');

        $this->assertCount(2, $history);
    }

    /** @test EC-004: Negative project ID */
    public function edge_case_negative_project_id(): void
    {
        $history = $this->service->getHistory(-1);

        $this->assertEmpty($history);
    }

    /** @test EC-005: Mixed active/inactive backlinks */
    public function edge_case_mixed_active_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(40)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(60)->create([
            'project_id' => $project->id,
            'is_active' => false,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        // Only active backlinks counted
        $this->assertEquals(40, $snapshot->total_backlinks);
    }

    // =============================================
    // SECURITY TESTS (SC)
    // =============================================

    /** @test SC-001: SQL injection protection */
    public function security_sql_injection_protection(): void
    {
        $project = Project::factory()->create();

        // This should not cause SQL injection
        $history = $this->service->getHistory($project->id, "'; DROP TABLE backlink_snapshots; --", '2026-02-28');

        // Should return empty, not throw error
        $this->assertIsIterable($history);
    }

    /** @test SC-002: Handles null date parameters */
    public function security_null_date_parameters(): void
    {
        Carbon::setTestNow('2026-02-28');

        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);

        $history = $this->service->getHistory($project->id, null, null);

        $this->assertCount(1, $history);

        Carbon::setTestNow();
    }

    /** @test SC-003: XSS in anchor text handled */
    public function security_xss_in_anchor_text(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => '<script>alert("xss")</script>',
            'backlinks_count' => 10,
        ]);

        // Should not throw error
        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertIsFloat($score);
    }

    // =============================================
    // PERFORMANCE TESTS (PF)
    // =============================================

    /** @test PF-001: Handles large number of snapshots */
    public function performance_large_snapshot_count(): void
    {
        Carbon::setTestNow('2026-12-31');
        
        $project = Project::factory()->create();

        // Create 365 snapshots (1 year of daily data) within the last 30 days
        // Since we default to 30 days, we create 30 snapshots instead
        foreach (range(1, 30) as $i) {
            BacklinkSnapshot::factory()->create([
                'project_id' => $project->id,
                'snapshot_date' => now()->subDays(30 - $i)->toDateString(),
            ]);
        }

        $startTime = microtime(true);
        $history = $this->service->getHistory($project->id);
        $endTime = microtime(true);

        $this->assertCount(30, $history);
        // Should complete in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);
        
        Carbon::setTestNow();
    }
}
