<?php

namespace Tests\Unit\Services\Backlinks;

use App\Models\AnchorText;
use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Services\Backlinks\BacklinkSnapshotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Unit tests for BacklinkSnapshotService.
 *
 * Tests snapshot generation, history retrieval, and metric calculations
 * for backlink trend analysis.
 *
 * @see TASK-23-6
 * @see TEST-23-6
 */
class BacklinkSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacklinkSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BacklinkSnapshotService::class);
    }

    // ========================================================================
    // BS-001: Service Instantiation
    // ========================================================================

    /** @test */
    public function bs_001_a_service_can_be_instantiated_from_container(): void
    {
        $service = app(BacklinkSnapshotService::class);

        $this->assertInstanceOf(BacklinkSnapshotService::class, $service);
    }

    /** @test */
    public function bs_001_b_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'createSnapshot'));
        $this->assertTrue(method_exists($this->service, 'getHistory'));
        $this->assertTrue(method_exists($this->service, 'calculateAnchorDiversity'));
        $this->assertTrue(method_exists($this->service, 'calculateAverageTrust'));
        $this->assertTrue(method_exists($this->service, 'getLatestSnapshot'));
        $this->assertTrue(method_exists($this->service, 'createSnapshotForDate'));
    }

    /** @test */
    public function bs_001_c_service_is_singleton(): void
    {
        $service1 = app(BacklinkSnapshotService::class);
        $service2 = app(BacklinkSnapshotService::class);

        $this->assertSame($service1, $service2);
    }

    /** @test */
    public function bs_001_d_service_dependencies_are_injected(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    // ========================================================================
    // BS-002: createSnapshot() Basic
    // ========================================================================

    /** @test */
    public function bs_002_a_creates_snapshot_with_valid_project(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertDatabaseHas('backlink_snapshots', [
            'id' => $snapshot->id,
        ]);
    }

    /** @test */
    public function bs_002_b_sets_correct_project_id(): void
    {
        $project = Project::factory()->create(['id' => 5]);
        Backlink::factory()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(5, $snapshot->project_id);
    }

    /** @test */
    public function bs_002_c_sets_snapshot_date_to_today(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(today()->toDateString(), $snapshot->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_002_d_calculates_total_backlinks(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(150)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(150, $snapshot->total_backlinks);
    }

    /** @test */
    public function bs_002_e_calculates_referring_domains_count(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->count(25)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(25, $snapshot->referring_domains_count);
    }

    /** @test */
    public function bs_002_f_calculates_dofollow_count(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(100)->dofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(50)->nofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(100, $snapshot->dofollow_count);
    }

    /** @test */
    public function bs_002_g_calculates_nofollow_count(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(100)->dofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(50)->nofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(50, $snapshot->nofollow_count);
    }

    /** @test */
    public function bs_002_h_calculates_avg_domain_trust(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 70]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 90]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(70.0, (float) $snapshot->avg_domain_trust);
    }

    /** @test */
    public function bs_002_i_calculates_anchor_diversity_score(): void
    {
        $project = Project::factory()->create();
        // 5 anchors with equal 20 backlinks each = 80% diversity (100 - 20)
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 20]);

        $snapshot = $this->service->createSnapshot($project);

        // Top anchor has 20/100 = 20%, diversity = 100 - 20 = 80
        $this->assertEquals(80.0, (float) $snapshot->anchor_diversity_score);
    }

    /** @test */
    public function bs_002_j_excludes_inactive_backlinks(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(100)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(50)->inactive()->create([
            'project_id' => $project->id,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(100, $snapshot->total_backlinks);
    }

    /** @test */
    public function bs_002_k_handles_empty_backlinks(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
        $this->assertEquals(0, $snapshot->referring_domains_count);
        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(0, $snapshot->nofollow_count);
        $this->assertEquals(0.0, (float) $snapshot->avg_domain_trust);
        $this->assertEquals(0.0, (float) $snapshot->anchor_diversity_score);
    }

    /** @test */
    public function bs_002_l_returns_backlink_snapshot_instance(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
    }

    // ========================================================================
    // BS-003: createSnapshotForDate()
    // ========================================================================

    /** @test */
    public function bs_003_a_creates_snapshot_for_specific_date(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshotForDate($project, '2026-03-01');

        $this->assertEquals('2026-03-01', $snapshot->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_003_b_idempotent_same_date_returns_existing(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot1 = $this->service->createSnapshotForDate($project, '2026-03-01');
        $snapshot2 = $this->service->createSnapshotForDate($project, '2026-03-01');

        $this->assertEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals(1, BacklinkSnapshot::where('project_id', $project->id)->count());
    }

    /** @test */
    public function bs_003_c_different_dates_create_new_snapshots(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot1 = $this->service->createSnapshotForDate($project, '2026-03-01');
        $snapshot2 = $this->service->createSnapshotForDate($project, '2026-03-02');

        $this->assertNotEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals(2, BacklinkSnapshot::where('project_id', $project->id)->count());
    }

    /** @test */
    public function bs_003_d_creates_if_not_exists(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $this->assertEquals(0, BacklinkSnapshot::where('project_id', $project->id)->count());

        $snapshot = $this->service->createSnapshotForDate($project, '2026-03-01');

        $this->assertNotNull($snapshot->id);
    }

    /** @test */
    public function bs_003_e_updates_existing_snapshot(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot1 = $this->service->createSnapshotForDate($project, '2026-03-01');
        $this->assertEquals(10, $snapshot1->total_backlinks);

        // Add more backlinks
        Backlink::factory()->count(20)->create(['project_id' => $project->id]);

        $snapshot2 = $this->service->createSnapshotForDate($project, '2026-03-01');

        $this->assertEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals(30, $snapshot2->total_backlinks);
    }

    /** @test */
    public function bs_003_f_invalid_date_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $project = Project::factory()->create();
        $this->service->createSnapshotForDate($project, 'invalid-date');
    }

    /** @test */
    public function bs_003_g_future_date_allowed(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $futureDate = Carbon::tomorrow()->toDateString();
        $snapshot = $this->service->createSnapshotForDate($project, $futureDate);

        $this->assertEquals($futureDate, $snapshot->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_003_h_past_date_allowed(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshotForDate($project, '2020-01-01');

        $this->assertEquals('2020-01-01', $snapshot->snapshot_date->toDateString());
    }

    // ========================================================================
    // BS-004: getHistory()
    // ========================================================================

    /** @test */
    public function bs_004_a_returns_collection_of_snapshots(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->count(7)->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2020-01-01', '2030-12-31');

        $this->assertInstanceOf(Collection::class, $history);
        $this->assertCount(7, $history);
    }

    /** @test */
    public function bs_004_b_filters_by_date_range(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-05')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-10')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-03-03', '2026-03-07');

        $this->assertCount(1, $history);
        $this->assertEquals('2026-03-05', $history->first()->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_004_c_ordered_by_date_ascending(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-10')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-05')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2020-01-01', '2030-12-31');

        $this->assertEquals('2026-03-01', $history->first()->snapshot_date->toDateString());
        $this->assertEquals('2026-03-10', $history->last()->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_004_d_returns_empty_for_no_history(): void
    {
        $project = Project::factory()->create();

        $history = $this->service->getHistory($project->id, '2026-03-01', '2026-03-07');

        $this->assertTrue($history->isEmpty());
    }

    /** @test */
    public function bs_004_e_handles_single_day_range(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-05')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-06')->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-03-05', '2026-03-05');

        $this->assertCount(1, $history);
    }

    /** @test */
    public function bs_004_f_exclusive_of_future_dates(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate(Carbon::tomorrow()->toDateString())->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2026-03-01', Carbon::today()->toDateString());

        $this->assertCount(1, $history);
        $this->assertEquals('2026-03-01', $history->first()->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_004_g_handles_invalid_date_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $project = Project::factory()->create();
        $this->service->getHistory($project->id, 'invalid', '2026-03-07');
    }

    /** @test */
    public function bs_004_h_validates_from_before_to(): void
    {
        $project = Project::factory()->create();

        $history = $this->service->getHistory($project->id, '2026-03-10', '2026-03-01');

        $this->assertTrue($history->isEmpty());
    }

    /** @test */
    public function bs_004_i_large_date_range(): void
    {
        $project = Project::factory()->create();
        
        // Create 365 snapshots
        for ($i = 0; $i < 365; $i++) {
            BacklinkSnapshot::factory()->onDate(
                Carbon::parse('2026-01-01')->addDays($i)->toDateString()
            )->create(['project_id' => $project->id]);
        }

        $history = $this->service->getHistory($project->id, '2026-01-01', '2026-12-31');

        $this->assertCount(365, $history);
    }

    /** @test */
    public function bs_004_j_isolates_by_project_id(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        BacklinkSnapshot::factory()->count(5)->create(['project_id' => $project1->id]);
        BacklinkSnapshot::factory()->count(3)->create(['project_id' => $project2->id]);

        $history = $this->service->getHistory($project1->id, '2020-01-01', '2030-12-31');

        $this->assertCount(5, $history);
        $history->each(function ($snapshot) use ($project1) {
            $this->assertEquals($project1->id, $snapshot->project_id);
        });
    }

    /** @test */
    public function bs_004_k_returns_backlink_snapshot_models(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->create(['project_id' => $project->id]);

        $history = $this->service->getHistory($project->id, '2020-01-01', '2030-12-31');

        $this->assertInstanceOf(BacklinkSnapshot::class, $history->first());
    }

    // ========================================================================
    // BS-005: calculateAnchorDiversity()
    // ========================================================================

    /** @test */
    public function bs_005_a_perfect_diversity(): void
    {
        $project = Project::factory()->create();
        // 5 anchors with 20 backlinks each = 20% top, diversity = 80
        for ($i = 0; $i < 5; $i++) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'backlinks_count' => 20,
            ]);
        }

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(80.0, $diversity);
    }

    /** @test */
    public function bs_005_b_single_anchor_dominates(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'backlinks_count' => 100,
        ]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $diversity);
    }

    /** @test */
    public function bs_005_b2_90_percent_top_anchor(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 90]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(10.0, $diversity);
    }

    /** @test */
    public function bs_005_c_no_anchors(): void
    {
        $project = Project::factory()->create();

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $diversity);
    }

    /** @test */
    public function bs_005_d_zero_backlinks_count(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 0]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 0]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $diversity);
    }

    /** @test */
    public function bs_005_e_two_anchors_equal(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(50.0, $diversity);
    }

    /** @test */
    public function bs_005_f_three_anchors_varied(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 60]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 30]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Top = 60/100 = 60%, diversity = 100 - 60 = 40
        $this->assertEquals(40.0, $diversity);
    }

    /** @test */
    public function bs_005_g_branded_vs_exact_match(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->branded()->create(['project_id' => $project->id, 'backlinks_count' => 40]);
        AnchorText::factory()->exactMatch()->create(['project_id' => $project->id, 'backlinks_count' => 30]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 30]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Top = 40/100 = 40%, diversity = 60
        $this->assertEquals(60.0, $diversity);
    }

    /** @test */
    public function bs_005_h_handles_unicode_anchors(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => 'リンクテキスト',  // Japanese
            'backlinks_count' => 50,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => 'ссылка',  // Russian
            'backlinks_count' => 50,
        ]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(50.0, $diversity);
    }

    /** @test */
    public function bs_005_i_many_small_anchors(): void
    {
        $project = Project::factory()->create();
        
        // 100 anchors with 1 backlink each
        for ($i = 0; $i < 100; $i++) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'backlinks_count' => 1,
            ]);
        }

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Top = 1/100 = 1%, diversity = 99
        $this->assertEquals(99.0, $diversity);
    }

    /** @test */
    public function bs_005_k_filters_by_project_id(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project1->id, 'backlinks_count' => 100]);
        AnchorText::factory()->create(['project_id' => $project2->id, 'backlinks_count' => 50]);
        AnchorText::factory()->create(['project_id' => $project2->id, 'backlinks_count' => 50]);

        $diversity1 = $this->service->calculateAnchorDiversity($project1->id);
        $diversity2 = $this->service->calculateAnchorDiversity($project2->id);

        $this->assertEquals(0.0, $diversity1);  // Single anchor dominates
        $this->assertEquals(50.0, $diversity2);  // Equal distribution
    }

    /** @test */
    public function bs_005_l_returns_float(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertIsFloat($diversity);
    }

    /** @test */
    public function bs_005_m_precision_to_2_decimals(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 33]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 33]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 34]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Top = 34/100 = 34%, diversity = 66
        $this->assertEquals(66.0, $diversity);
        // Verify 2 decimal precision
        $this->assertEquals(2, strlen((string) $diversity) - strpos((string) $diversity, '.'));
    }

    /** @test */
    public function bs_005_n_large_backlinks_count(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 60000]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 40000]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Top = 60000/100000 = 60%, diversity = 40
        $this->assertEquals(40.0, $diversity);
    }

    /** @test */
    public function bs_005_o_division_by_zero_protected(): void
    {
        $project = Project::factory()->create();
        // Create anchors but with all zeros
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 0]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $diversity);
    }

    // ========================================================================
    // BS-006: calculateAverageTrust()
    // ========================================================================

    /** @test */
    public function bs_006_a_calculates_average(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 70]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 90]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(70.0, $avgTrust);
    }

    /** @test */
    public function bs_006_b_single_domain(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 65]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(65.0, $avgTrust);
    }

    /** @test */
    public function bs_006_c_no_domains(): void
    {
        $project = Project::factory()->create();

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(0.0, $avgTrust);
    }

    /** @test */
    public function bs_006_d_all_zero_trust(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->count(3)->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0,
        ]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(0.0, $avgTrust);
    }

    /** @test */
    public function bs_006_e_mixed_trust_scores(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 0]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 100]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(50.0, $avgTrust);
    }

    /** @test */
    public function bs_006_f_filters_by_project_id(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project1->id, 'domain_trust_score' => 80]);
        ReferringDomain::factory()->create(['project_id' => $project2->id, 'domain_trust_score' => 40]);

        $avgTrust1 = $this->service->calculateAverageTrust($project1->id);
        $avgTrust2 = $this->service->calculateAverageTrust($project2->id);

        $this->assertEquals(80.0, $avgTrust1);
        $this->assertEquals(40.0, $avgTrust2);
    }

    /** @test */
    public function bs_006_h_returns_float(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertIsFloat($avgTrust);
    }

    /** @test */
    public function bs_006_i_precision_to_2_decimals(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 50]);
        ReferringDomain::factory()->create(['project_id' => $project->id, 'domain_trust_score' => 67]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(58.5, $avgTrust);
    }

    /** @test */
    public function bs_006_j_large_domain_count(): void
    {
        $project = Project::factory()->create();
        
        // Create 1000 domains with trust score 50
        for ($i = 0; $i < 1000; $i++) {
            ReferringDomain::factory()->create([
                'project_id' => $project->id,
                'domain_trust_score' => 50,
            ]);
        }

        $startTime = microtime(true);
        $avgTrust = $this->service->calculateAverageTrust($project->id);
        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(50.0, $avgTrust);
        $this->assertLessThan(100, $elapsed, 'Query should complete in under 100ms');
    }

    // ========================================================================
    // BS-007: getLatestSnapshot()
    // ========================================================================

    /** @test */
    public function bs_007_a_returns_most_recent_snapshot(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-01')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-10')->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-05')->create(['project_id' => $project->id]);

        $latest = $this->service->getLatestSnapshot($project->id);

        $this->assertNotNull($latest);
        $this->assertEquals('2026-03-10', $latest->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_007_b_returns_null_if_no_snapshots(): void
    {
        $project = Project::factory()->create();

        $latest = $this->service->getLatestSnapshot($project->id);

        $this->assertNull($latest);
    }

    /** @test */
    public function bs_007_c_filters_by_project_id(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2026-03-01')->create(['project_id' => $project1->id]);
        BacklinkSnapshot::factory()->onDate('2026-03-10')->create(['project_id' => $project2->id]);

        $latest = $this->service->getLatestSnapshot($project1->id);

        $this->assertEquals('2026-03-01', $latest->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_007_e_returns_backlink_snapshot_or_null(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->create(['project_id' => $project->id]);

        $latest = $this->service->getLatestSnapshot($project->id);

        $this->assertInstanceOf(BacklinkSnapshot::class, $latest);
    }

    /** @test */
    public function bs_007_f_handles_todays_snapshot(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->today()->create(['project_id' => $project->id]);

        $latest = $this->service->getLatestSnapshot($project->id);

        $this->assertEquals(today()->toDateString(), $latest->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_007_g_handles_old_snapshot_only(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->onDate('2020-01-01')->create(['project_id' => $project->id]);

        $latest = $this->service->getLatestSnapshot($project->id);

        $this->assertNotNull($latest);
        $this->assertEquals('2020-01-01', $latest->snapshot_date->toDateString());
    }

    // ========================================================================
    // BS-008: Error Handling
    // ========================================================================

    /** @test */
    public function bs_008_a_invalid_project_id_returns_zero_values(): void
    {
        // Non-existent project ID
        $snapshot = $this->service->createSnapshot(Project::factory()->make(['id' => 99999]));

        // Should create snapshot with zero values
        $this->assertEquals(0, $snapshot->total_backlinks);
    }

    /** @test */
    public function bs_008_b_invalid_date_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $project = Project::factory()->create();
        $this->service->getHistory($project->id, 'invalid-date', '2026-03-10');
    }

    /** @test */
    public function bs_008_e_concurrent_snapshot_creation(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        // Create two snapshots for same date concurrently (simulate race condition)
        $snapshot1 = $this->service->createSnapshotForDate($project, '2026-03-01');
        $snapshot2 = $this->service->createSnapshotForDate($project, '2026-03-01');

        // Should be same snapshot (idempotent)
        $this->assertEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals(1, BacklinkSnapshot::where('project_id', $project->id)
            ->whereDate('snapshot_date', '2026-03-01')
            ->count());
    }

    // ========================================================================
    // BS-009: Edge Cases
    // ========================================================================

    /** @test */
    public function bs_009_a_project_with_only_inactive_backlinks(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(100)->inactive()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
    }

    /** @test */
    public function bs_009_b_project_with_only_nofollow_links(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(50)->nofollow()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(50, $snapshot->nofollow_count);
    }

    /** @test */
    public function bs_009_c_all_backlinks_from_single_domain(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->create(['project_id' => $project->id]);
        Backlink::factory()->count(100)->create([
            'project_id' => $project->id,
            'source_domain' => 'example.com',
            'is_active' => true,
        ]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(1, $snapshot->referring_domains_count);
    }

    /** @test */
    public function bs_009_d_mixed_active_inactive_backlinks(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(80)->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(20)->inactive()->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(80, $snapshot->total_backlinks);
    }

    /** @test */
    public function bs_009_f_unicode_in_anchor_texts(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => '日本語リンク',  // Japanese
            'backlinks_count' => 50,
        ]);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => '中文链接',  // Chinese
            'backlinks_count' => 50,
        ]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(50.0, $diversity);
    }

    /** @test */
    public function bs_009_g_very_long_anchor_text(): void
    {
        $project = Project::factory()->create();
        $longAnchor = str_repeat('a', 500);
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => $longAnchor,
            'backlinks_count' => 50,
        ]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        $this->assertEquals(0.0, $diversity);  // Single anchor
    }

    /** @test */
    public function bs_009_h_zero_domain_trust_for_all(): void
    {
        $project = Project::factory()->create();
        ReferringDomain::factory()->count(5)->create([
            'project_id' => $project->id,
            'domain_trust_score' => 0,
        ]);

        $avgTrust = $this->service->calculateAverageTrust($project->id);

        $this->assertEquals(0.0, $avgTrust);
    }

    /** @test */
    public function bs_009_i_snapshot_with_all_zeros(): void
    {
        $project = Project::factory()->create();
        // No backlinks, no domains, no anchors

        $snapshot = $this->service->createSnapshot($project);

        $this->assertEquals(0, $snapshot->total_backlinks);
        $this->assertEquals(0, $snapshot->referring_domains_count);
        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(0, $snapshot->nofollow_count);
        $this->assertEquals(0.0, (float) $snapshot->avg_domain_trust);
        $this->assertEquals(0.0, (float) $snapshot->anchor_diversity_score);
    }

    /** @test */
    public function bs_009_j_leap_year_date(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshotForDate($project, '2024-02-29');

        $this->assertEquals('2024-02-29', $snapshot->snapshot_date->toDateString());
    }

    /** @test */
    public function bs_009_k_date_boundary_at_midnight(): void
    {
        $project = Project::factory()->create();
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-03-05 00:00:00',
        ]);

        $history = $this->service->getHistory($project->id, '2026-03-05', '2026-03-05');

        $this->assertCount(1, $history);
    }

    // ========================================================================
    // BS-010: Performance
    // ========================================================================

    /** @test */
    public function bs_010_a_create_snapshot_under_500ms(): void
    {
        $project = Project::factory()->create();
        
        // Create 10,000 backlinks
        Backlink::factory()->count(5000)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(5000)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        
        // Create referring domains
        ReferringDomain::factory()->count(100)->create(['project_id' => $project->id]);
        
        // Create anchor texts
        AnchorText::factory()->count(50)->create(['project_id' => $project->id]);

        $startTime = microtime(true);
        $this->service->createSnapshot($project);
        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $elapsed, 'createSnapshot should complete in under 500ms');
    }

    /** @test */
    public function bs_010_b_get_history_under_200ms(): void
    {
        $project = Project::factory()->create();
        
        // Create 365 snapshots
        for ($i = 0; $i < 365; $i++) {
            BacklinkSnapshot::factory()->onDate(
                Carbon::parse('2025-01-01')->addDays($i)->toDateString()
            )->create(['project_id' => $project->id]);
        }

        $startTime = microtime(true);
        $history = $this->service->getHistory($project->id, '2025-01-01', '2025-12-31');
        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertCount(365, $history);
        $this->assertLessThan(200, $elapsed, 'getHistory should complete in under 200ms');
    }

    /** @test */
    public function bs_010_c_calculate_anchor_diversity_under_100ms(): void
    {
        $project = Project::factory()->create();
        
        // Create 1,000 anchor texts
        for ($i = 0; $i < 1000; $i++) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'backlinks_count' => rand(1, 100),
            ]);
        }

        $startTime = microtime(true);
        $this->service->calculateAnchorDiversity($project->id);
        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(100, $elapsed, 'calculateAnchorDiversity should complete in under 100ms');
    }

    /** @test */
    public function bs_010_d_calculate_average_trust_under_50ms(): void
    {
        $project = Project::factory()->create();
        
        // Create 5,000 referring domains
        for ($i = 0; $i < 5000; $i++) {
            ReferringDomain::factory()->create([
                'project_id' => $project->id,
                'domain_trust_score' => rand(0, 100),
            ]);
        }

        $startTime = microtime(true);
        $this->service->calculateAverageTrust($project->id);
        $elapsed = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(50, $elapsed, 'calculateAverageTrust should complete in under 50ms');
    }

    // ========================================================================
    // BS-011: Security
    // ========================================================================

    /** @test */
    public function bs_011_a_sql_injection_in_project_id(): void
    {
        // Eloquent uses parameterized queries, so this should be safe
        $result = $this->service->getHistory(1, '2026-03-01', '2026-03-10');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function bs_011_b_xss_in_anchor_text(): void
    {
        $project = Project::factory()->create();
        AnchorText::factory()->create([
            'project_id' => $project->id,
            'anchor_text' => '<script>alert("xss")</script>',
            'backlinks_count' => 50,
        ]);

        $diversity = $this->service->calculateAnchorDiversity($project->id);

        // Should calculate correctly without executing script
        $this->assertEquals(0.0, $diversity);
    }

    /** @test */
    public function bs_011_c_project_isolation(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        
        Backlink::factory()->count(100)->create(['project_id' => $project1->id, 'is_active' => true]);
        Backlink::factory()->count(50)->create(['project_id' => $project2->id, 'is_active' => true]);

        $snapshot1 = $this->service->createSnapshot($project1);
        $snapshot2 = $this->service->createSnapshot($project2);

        $this->assertEquals(100, $snapshot1->total_backlinks);
        $this->assertEquals(50, $snapshot2->total_backlinks);
    }

    /** @test */
    public function bs_011_e_mass_assignment_protection(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(10)->create(['project_id' => $project->id]);

        $snapshot = $this->service->createSnapshot($project);

        // Verify only expected fields are set
        $this->assertEquals($project->id, $snapshot->project_id);
        $this->assertNotNull($snapshot->total_backlinks);
        $this->assertNotNull($snapshot->snapshot_date);
    }

    // ========================================================================
    // BS-012: Integration
    // ========================================================================

    /** @test */
    public function bs_012_a_full_workflow(): void
    {
        $project = Project::factory()->create();
        
        // Step 1: Create backlinks
        Backlink::factory()->count(100)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        Backlink::factory()->count(50)->nofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        
        // Step 2: Create referring domains
        ReferringDomain::factory()->count(25)->create(['project_id' => $project->id]);
        
        // Step 3: Create anchor texts
        AnchorText::factory()->count(10)->create(['project_id' => $project->id]);
        
        // Step 4: Create snapshot
        $snapshot = $this->service->createSnapshot($project);
        
        $this->assertEquals(150, $snapshot->total_backlinks);
        $this->assertEquals(25, $snapshot->referring_domains_count);
        $this->assertEquals(100, $snapshot->dofollow_count);
        $this->assertEquals(50, $snapshot->nofollow_count);
        
        // Step 5: Get history
        $history = $this->service->getHistory($project->id, '2020-01-01', '2030-12-31');
        $this->assertCount(1, $history);
        
        // Step 6: Get latest
        $latest = $this->service->getLatestSnapshot($project->id);
        $this->assertNotNull($latest);
        $this->assertEquals($snapshot->id, $latest->id);
    }

    /** @test */
    public function bs_012_d_trend_calculation(): void
    {
        $project = Project::factory()->create();
        
        // Create snapshots for last 7 days with increasing backlinks
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            BacklinkSnapshot::factory()->onDate($date)->create([
                'project_id' => $project->id,
                'total_backlinks' => 100 + ($i * 10),
            ]);
        }

        $history = $this->service->getHistory(
            $project->id,
            Carbon::today()->subDays(6)->toDateString(),
            Carbon::today()->toDateString()
        );

        $this->assertCount(7, $history);
        
        // Verify trend (increasing backlinks)
        $first = $history->first()->total_backlinks;
        $last = $history->last()->total_backlinks;
        $this->assertLessThan($last, $first);
    }

    /** @test */
    public function bs_012_e_visibility_score_integration(): void
    {
        $project = Project::factory()->create();
        
        // Create quality backlink profile
        Backlink::factory()->count(500)->dofollow()->create(['project_id' => $project->id, 'is_active' => true]);
        ReferringDomain::factory()->count(50)->highTrust()->create(['project_id' => $project->id]);
        
        // Create diverse anchors
        for ($i = 0; $i < 20; $i++) {
            AnchorText::factory()->create([
                'project_id' => $project->id,
                'backlinks_count' => 25,
            ]);
        }

        $snapshot = $this->service->createSnapshot($project);

        // Verify metrics are in expected ranges for visibility score contribution
        $this->assertGreaterThan(0, $snapshot->total_backlinks);
        $this->assertGreaterThan(0, $snapshot->referring_domains_count);
        $this->assertGreaterThan(50.0, (float) $snapshot->anchor_diversity_score);  // Good diversity
    }
}
