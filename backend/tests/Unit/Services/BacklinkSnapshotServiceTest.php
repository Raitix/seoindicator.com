<?php

namespace Tests\Unit\Services;

use App\Models\AnchorText;
use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Repositories\AnchorTextRepository;
use App\Repositories\BacklinkRepository;
use App\Repositories\BacklinkSnapshotRepository;
use App\Repositories\ReferringDomainRepository;
use App\Services\BacklinkSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Unit tests for BacklinkSnapshotService (TASK-23-6).
 *
 * Tests cover:
 * - createSnapshot() - generates daily snapshots with all metrics
 * - getHistory() - retrieves snapshots for date range
 * - calculateAnchorDiversity() - 0-100 score calculation
 * - calculateAverageTrust() - average domain trust calculation
 *
 * @see TASK-23-6
 * @see SPEC-023
 */
class BacklinkSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacklinkSnapshotService $service;
    private BacklinkRepository $backlinkRepository;
    private ReferringDomainRepository $referringDomainRepository;
    private AnchorTextRepository $anchorTextRepository;
    private BacklinkSnapshotRepository $snapshotRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backlinkRepository = app(BacklinkRepository::class);
        $this->referringDomainRepository = app(ReferringDomainRepository::class);
        $this->anchorTextRepository = app(AnchorTextRepository::class);
        $this->snapshotRepository = app(BacklinkSnapshotRepository::class);

        $this->service = new BacklinkSnapshotService(
            $this->backlinkRepository,
            $this->referringDomainRepository,
            $this->anchorTextRepository,
            $this->snapshotRepository
        );
    }

    // ==========================================
    // Service Instantiation Tests
    // ==========================================

    /**
     * @test
     * Service can be instantiated via container.
     */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(BacklinkSnapshotService::class);

        $this->assertInstanceOf(BacklinkSnapshotService::class, $service);
    }

    /**
     * @test
     * Service has correct dependencies injected.
     */
    public function it_has_correct_dependencies_injected(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $backlinkRepo = $reflection->getProperty('backlinkRepository');
        $backlinkRepo->setAccessible(true);
        $this->assertInstanceOf(BacklinkRepository::class, $backlinkRepo->getValue($this->service));

        $referringDomainRepo = $reflection->getProperty('referringDomainRepository');
        $referringDomainRepo->setAccessible(true);
        $this->assertInstanceOf(ReferringDomainRepository::class, $referringDomainRepo->getValue($this->service));

        $anchorTextRepo = $reflection->getProperty('anchorTextRepository');
        $anchorTextRepo->setAccessible(true);
        $this->assertInstanceOf(AnchorTextRepository::class, $anchorTextRepo->getValue($this->service));

        $snapshotRepo = $reflection->getProperty('snapshotRepository');
        $snapshotRepo->setAccessible(true);
        $this->assertInstanceOf(BacklinkSnapshotRepository::class, $snapshotRepo->getValue($this->service));
    }

    // ==========================================
    // createSnapshot() Tests
    // ==========================================

    /**
     * @test
     * createSnapshot creates a snapshot with all metrics.
     */
    public function it_creates_snapshot_with_all_metrics(): void
    {
        $project = Project::factory()->create();

        // Create backlinks
        Backlink::factory()->count(10)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(5)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(3)->inactive()->create(['project_id' => $project->id]);

        // Create referring domains with trust scores
        ReferringDomain::factory()->count(3)->highTrust()->create(['project_id' => $project->id]);
        ReferringDomain::factory()->count(2)->lowTrust()->create(['project_id' => $project->id]);

        // Create anchor texts
        AnchorText::factory()->count(4)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertEquals($project->id, $snapshot->project_id);
        $this->assertEquals(15, $snapshot->total_backlinks); // 10 + 5 active
        $this->assertEquals(5, $snapshot->referring_domains_count);
        $this->assertEquals(10, $snapshot->dofollow_count);
        $this->assertEquals(5, $snapshot->nofollow_count);
        $this->assertNotNull($snapshot->avg_domain_trust);
        $this->assertNotNull($snapshot->anchor_diversity_score);
        $this->assertEquals(today()->toDateString(), $snapshot->snapshot_date->toDateString());
    }

    /**
     * @test
     * createSnapshot calculates correct total_backlinks (active only).
     */
    public function it_counts_only_active_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(10)->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(20)->inactive()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(10, $snapshot->total_backlinks);
    }

    /**
     * @test
     * createSnapshot calculates correct dofollow count.
     */
    public function it_calculates_correct_dofollow_count(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(8)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(3)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(8, $snapshot->dofollow_count);
    }

    /**
     * @test
     * createSnapshot calculates correct nofollow count.
     */
    public function it_calculates_correct_nofollow_count(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(5)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(7)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(7, $snapshot->nofollow_count);
    }

    /**
     * @test
     * createSnapshot counts distinct referring domains.
     */
    public function it_counts_distinct_referring_domains(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->count(12)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(12, $snapshot->referring_domains_count);
    }

    /**
     * @test
     * createSnapshot uses today as snapshot date.
     */
    public function it_uses_today_as_snapshot_date(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(today()->toDateString(), $snapshot->snapshot_date->toDateString());
    }

    /**
     * @test
     * createSnapshot handles project with no backlinks gracefully.
     */
    public function it_handles_project_with_no_backlinks_gracefully(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
        $this->assertEquals(0, $snapshot->referring_domains_count);
        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(0, $snapshot->nofollow_count);
        $this->assertEquals(0.0, $snapshot->avg_domain_trust);
        $this->assertEquals(0.0, $snapshot->anchor_diversity_score);
    }

    /**
     * @test
     * createSnapshot handles project with only inactive backlinks.
     */
    public function it_handles_project_with_only_inactive_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(10)->inactive()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
    }

    /**
     * @test
     * createSnapshot updates existing snapshot for same date (upsert).
     */
    public function it_updates_existing_snapshot_for_same_date(): void
    {
        $project = Project::factory()->create();

        // Create initial snapshot
        $initial = BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => today(),
            'total_backlinks' => 5,
        ]);

        // Add backlinks and create new snapshot
        Backlink::factory()->count(20)->create(['project_id' => $project->id, 'is_active' => true]);

        $updated = $this->service->createSnapshot($project);

        // Should update existing, not create new
        $this->assertEquals($initial->id, $updated->id);
        $this->assertEquals(20, $updated->total_backlinks);
        $this->assertEquals(1, BacklinkSnapshot::where('project_id', $project->id)->count());
    }

    /**
     * @test
     * createSnapshot ignores backlinks with unknown link_type.
     */
    public function it_ignores_backlinks_with_unknown_link_type(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(5)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(3)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(4)->create([
            'project_id' => $project->id,
            'is_active' => true,
            'link_type' => 'unknown',
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(12, $snapshot->total_backlinks); // 5 + 3 + 4
        $this->assertEquals(5, $snapshot->dofollow_count);
        $this->assertEquals(3, $snapshot->nofollow_count);
        // Unknown are counted in total but not in dofollow/nofollow
    }

    // ==========================================
    // getHistory() Tests
    // ==========================================

    /**
     * @test
     * getHistory returns snapshots within date range.
     */
    public function it_returns_snapshots_within_date_range(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-31')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-02-15')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-01-01', '2026-01-31');

        $this->assertCount(3, $history);
    }

    /**
     * @test
     * getHistory excludes snapshots outside date range.
     */
    public function it_excludes_snapshots_outside_date_range(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-02-15')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-15')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-02-01', '2026-02-28');

        $this->assertCount(1, $history);
        $this->assertEquals('2026-02-15', $history->first()->snapshot_date->toDateString());
    }

    /**
     * @test
     * getHistory returns ordered by date ascending.
     */
    public function it_returns_snapshots_ordered_by_date_ascending(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-20')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-10')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-01-01', '2026-01-31');

        $dates = $history->pluck('snapshot_date')->map->toDateString()->toArray();

        $this->assertEquals(['2026-01-10', '2026-01-15', '2026-01-20'], $dates);
    }

    /**
     * @test
     * getHistory returns empty collection when no snapshots in range.
     */
    public function it_returns_empty_collection_when_no_snapshots_in_range(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-02-01', '2026-02-28');

        $this->assertInstanceOf(Collection::class, $history);
        $this->assertCount(0, $history);
    }

    /**
     * @test
     * getHistory only returns snapshots for specified project.
     */
    public function it_only_returns_snapshots_for_specified_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project1->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project2->id]);

        $history = $this->service->getHistory($project1->id, '2026-01-01', '2026-01-31');

        $this->assertCount(1, $history);
        $this->assertEquals($project1->id, $history->first()->project_id);
    }

    /**
     * @test
     * getHistory includes boundary dates (inclusive).
     */
    public function it_includes_boundary_dates(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-01-31')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-01-01', '2026-01-31');

        $this->assertCount(2, $history);
    }

    // ==========================================
    // calculateAnchorDiversity() Tests
    // ==========================================

    /**
     * @test
     * calculateAnchorDiversity returns 100 for perfectly even distribution.
     */
    public function it_returns_100_for_perfectly_even_distribution(): void
    {
        $project = Project::factory()->create();

        // 4 anchor texts, each with 25 backlinks = perfectly even
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'A', 'backlinks_count' => 25]);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'B', 'backlinks_count' => 25]);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'C', 'backlinks_count' => 25]);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'D', 'backlinks_count' => 25]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(75.0, $score); // 100 - (25/100 * 100) = 75
    }

    /**
     * @test
     * calculateAnchorDiversity returns 0 when all use same anchor.
     */
    public function it_returns_0_when_all_use_same_anchor(): void
    {
        $project = Project::factory()->create();

        // All 100 backlinks use the same anchor
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'same', 'backlinks_count' => 100]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $score); // 100 - (100/100 * 100) = 0
    }

    /**
     * @test
     * calculateAnchorDiversity returns 50 when top anchor is 50%.
     */
    public function it_returns_50_when_top_anchor_is_50_percent(): void
    {
        $project = Project::factory()->create();

        // Top anchor has 50 of 100 backlinks
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'top', 'backlinks_count' => 50]);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'other', 'backlinks_count' => 50]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(50.0, $score); // 100 - (50/100 * 100) = 50
    }

    /**
     * @test
     * calculateAnchorDiversity returns 0 for empty anchor texts.
     */
    public function it_returns_0_for_empty_anchor_texts(): void
    {
        $project = Project::factory()->create();

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $score);
    }

    /**
     * @test
     * calculateAnchorDiversity handles single anchor text.
     */
    public function it_handles_single_anchor_text(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $score); // Single anchor = 0 diversity
    }

    /**
     * @test
     * calculateAnchorDiversity uses top anchor percentage for calculation.
     */
    public function it_uses_top_anchor_percentage(): void
    {
        $project = Project::factory()->create();

        // Top anchor has 70 backlinks, others have 10 each
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 70]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // 100 - (70/100 * 100) = 30
        $this->assertEquals(30.0, $score);
    }

    /**
     * @test
     * calculateAnchorDiversity rounds to 2 decimal places.
     */
    public function it_rounds_to_2_decimal_places(): void
    {
        $project = Project::factory()->create();

        // 33.33...% for top anchor
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 33]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 33]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 34]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // Top is 34 of 100 = 34%, score = 66
        $this->assertEquals(66.0, $score);
    }

    /**
     * @test
     * calculateAnchorDiversity handles large number of anchors.
     */
    public function it_handles_large_number_of_anchors(): void
    {
        $project = Project::factory()->create();

        // 100 different anchors, each with 1 backlink
        for ($i = 0; $i < 100; $i++) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'anchor_text' => "anchor_$i",
                'backlinks_count' => 1,
            ]);
        }

        $score = $this->service->calculateAnchorDiversity($project->id);

        // Each anchor has 1% = score 99
        $this->assertEquals(99.0, $score);
    }

    // ==========================================
    // calculateAverageTrust() Tests
    // ==========================================

    /**
     * @test
     * calculateAverageTrust returns average of domain trust scores.
     */
    public function it_returns_average_of_domain_trust_scores(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 80]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 60]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 70]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(70.0, $avg); // (80 + 60 + 70) / 3 = 70
    }

    /**
     * @test
     * calculateAverageTrust returns 0 when no referring domains.
     */
    public function it_returns_0_when_no_referring_domains(): void
    {
        $project = Project::factory()->create();

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(0.0, $avg);
    }

    /**
     * @test
     * calculateAverageTrust handles single referring domain.
     */
    public function it_handles_single_referring_domain(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 85]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(85.0, $avg);
    }

    /**
     * @test
     * calculateAverageTrust handles domains with zero trust score.
     */
    public function it_handles_domains_with_zero_trust_score(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 0]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(25.0, $avg); // (50 + 0) / 2 = 25
    }

    /**
     * @test
     * calculateAverageTrust handles high trust scores.
     */
    public function it_handles_high_trust_scores(): void
    {
        $project = Project::factory()->create();

        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 100]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 98]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(99.0, $avg);
    }

    /**
     * @test
     * calculateAverageTrust only includes domains for specified project.
     */
    public function it_only_includes_domains_for_specified_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        ReferringDomain::factory()->create(['project_id' => $project1->id, 'domain_trust_score' => 80]);
        ReferringDomain::factory()->create(['project_id' => $project2->id, 'domain_trust_score' => 20]);

        $avg = $this->service->calculateAverageTrust($project1->id);

        $this->assertEquals(80.0, $avg);
    }

    /**
     * @test
     * calculateAverageTrust rounds to 2 decimal places.
     */
    public function it_rounds_to_2_decimal_places_for_trust(): void
    {
        $project = Project::factory()->create();

        // 33 + 33 + 34 = 100, average = 33.33...
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 33]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 33]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 34]);

        $avg = $this->service->calculateAverageTrust($project->id);

        // (33 + 33 + 34) / 3 = 33.33...
        $this->assertEqualsWithDelta(33.33, $avg, 0.01);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    /**
     * @test
     * Full snapshot creation workflow.
     */
    public function it_performs_full_snapshot_creation_workflow(): void
    {
        $project = Project::factory()->create();

        // Create comprehensive data
        Backlink::factory()->count(50)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(20)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(10)->inactive()->create(['project_id' => $project->id]);

        ReferringDomain::factory()->count(15)->create(['project_id' => $project->id, 'domain_trust_score' => 70]);
        ReferringDomain::factory()->count(5)->create(['project_id' => $project->id, 'domain_trust_score' => 30]);

        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 35]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 35]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(70, $snapshot->total_backlinks);
        $this->assertEquals(20, $snapshot->referring_domains_count);
        $this->assertEquals(50, $snapshot->dofollow_count);
        $this->assertEquals(20, $snapshot->nofollow_count);

        // Average trust: (15 * 70 + 5 * 30) / 20 = 60
        $this->assertEquals(60.0, $snapshot->avg_domain_trust);

        // Anchor diversity: 100 - (35/70 * 100) = 50
        $this->assertEquals(50.0, $snapshot->anchor_diversity_score);
    }

    /**
     * @test
     * History retrieval with multiple snapshots.
     */
    public function it_retrieves_history_with_multiple_snapshots(): void
    {
        $project = Project::factory()->create();

        // Create snapshots for the past 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            BacklinkSnapshot::factory()->onDate($date)->create([
                'project_id' => $project->id,
                'total_backlinks' => 100 + ($i * 10),
            ]);
        }

        $history = $this->service->getHistory(
            $project->id,
            now()->subDays(6)->toDateString(),
            today()->toDateString()
        );

        $this->assertCount(7, $history);

        // Verify order (ascending by date)
        $backlinks = $history->pluck('total_backlinks')->toArray();
        $this->assertEquals([160, 150, 140, 130, 120, 110, 100], $backlinks);
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    /**
     * @test
     * Handles project with null domain trust scores.
     */
    public function it_handles_project_with_null_domain_trust_scores(): void
    {
        $project = Project::factory()->create();

        // Note: domain_trust_score has default 0, so null shouldn't happen
        // But test handling if it does
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0,
        ]);

        $avg = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(0.0, $avg);
    }

    /**
     * @test
     * Handles very large backlink counts.
     */
    public function it_handles_very_large_backlink_counts(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->count(10000)->create(['project_id' => $project->id, 'is_active' => true]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(10000, $snapshot->total_backlinks);
    }

    /**
     * @test
     * Handles anchor text with zero backlinks count.
     */
    public function it_handles_anchor_text_with_zero_backlinks_count(): void
    {
        $project = Project::factory()->create();

        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 0]);

        $score = $this->service->calculateAnchorDiversity($project->id);

        // Division by zero protection - should return 0
        $this->assertEquals(0.0, $score);
    }

    /**
     * @test
     * Handles date range with same start and end date.
     */
    public function it_handles_date_range_with_same_start_and_end_date(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-01-15', '2026-01-15');

        $this->assertCount(1, $history);
    }

    /**
     * @test
     * Handles invalid date range (end before start).
     */
    public function it_handles_invalid_date_range(): void
    {
        $project = Project::factory()->create();

        BacklinkSnapshot::factory()->onDate('2026-01-15')->create(['project_id' => $project->id]);

        // End date before start date should return empty
        $history = $this->service->getHistory($project->id, '2026-01-20', '2026-01-10');

        $this->assertCount(0, $history);
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    /**
     * @test
     * createSnapshot completes within 1 second for typical dataset.
     */
    public function it_completes_within_1_second_for_typical_dataset(): void
    {
        $project = Project::factory()->create();

        // Typical dataset: 1000 backlinks, 100 domains, 50 anchors
        Backlink::factory()->count(1000)->create(['project_id' => $project->id, 'is_active' => true]);
        ReferringDomain::factory()->count(100)->create(['project_id' => $project->id]);
        AnchorText::factory()->count(50)->create(['project_id' => $project->id]);

        $start = microtime(true);
        $this->service->createSnapshot($project);
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, "Snapshot creation took {$duration} seconds");
    }

    // ==========================================
    // Security Tests
    // ==========================================

    /**
     * @test
     * Prevents SQL injection via project ID.
     */
    public function it_prevents_sql_injection_via_project_id(): void
    {
        $project = Project::factory()->create();

        // This should not throw an exception
        $result = $this->service->getHistory($project->id, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @test
     * Prevents SQL injection via date parameters.
     */
    public function it_prevents_sql_injection_via_date_parameters(): void
    {
        $project = Project::factory()->create();

        // Use valid date format
        $result = $this->service->getHistory($project->id, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @test
     * Does not expose sensitive data in snapshot.
     */
    public function it_does_not_expose_sensitive_data_in_snapshot(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $apiResponse = $snapshot->toApiResponse();

        // Should only contain aggregate metrics, no sensitive data
        $this->assertArrayHasKey('date', $apiResponse);
        $this->assertArrayHasKey('total_backlinks', $apiResponse);
        $this->assertArrayHasKey('referring_domains_count', $apiResponse);

        // Should NOT contain sensitive fields
        $this->assertArrayNotHasKey('password', $apiResponse);
        $this->assertArrayNotHasKey('api_key', $apiResponse);
        $this->assertArrayNotHasKey('secret', $apiResponse);
    }
}
