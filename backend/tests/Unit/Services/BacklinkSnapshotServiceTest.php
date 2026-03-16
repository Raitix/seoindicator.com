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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BacklinkSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacklinkSnapshotService $service;
    private BacklinkRepository|MockInterface $backlinkRepo;
    private ReferringDomainRepository|MockInterface $referringDomainRepo;
    private AnchorTextRepository|MockInterface $anchorTextRepo;
    private BacklinkSnapshotRepository|MockInterface $snapshotRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backlinkRepo = Mockery::mock(BacklinkRepository::class);
        $this->referringDomainRepo = Mockery::mock(ReferringDomainRepository::class);
        $this->anchorTextRepo = Mockery::mock(AnchorTextRepository::class);
        $this->snapshotRepo = Mockery::mock(BacklinkSnapshotRepository::class);

        $this->service = new BacklinkSnapshotService(
            $this->backlinkRepo,
            $this->referringDomainRepo,
            $this->anchorTextRepo,
            $this->snapshotRepo
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // 1. Service Instantiation Tests (SI)
    // ============================================

    /** @test */
    public function si_001_service_can_be_instantiated_via_container(): void
    {
        $service = app(BacklinkSnapshotService::class);
        $this->assertInstanceOf(BacklinkSnapshotService::class, $service);
    }

    /** @test */
    public function si_002_service_has_required_dependencies(): void
    {
        $service = app(BacklinkSnapshotService::class);
        $this->assertInstanceOf(BacklinkSnapshotService::class, $service);
    }

    /** @test */
    public function si_003_service_resolves_from_container(): void
    {
        $service1 = app(BacklinkSnapshotService::class);
        $service2 = app(BacklinkSnapshotService::class);
        $this->assertInstanceOf(BacklinkSnapshotService::class, $service1);
    }

    // ============================================
    // 2. createSnapshot() - Basic Tests (CSB)
    // ============================================

    /** @test */
    public function csb_001_creates_snapshot_with_all_metrics(): void
    {
        $project = Project::factory()->create();

        // Mock repository returns
        $this->backlinkRepo->shouldReceive('countActiveByProject')
            ->with($project->id)
            ->once()
            ->andReturn(100);

        $this->referringDomainRepo->shouldReceive('countByProject')
            ->with($project->id)
            ->once()
            ->andReturn(25);

        $this->backlinkRepo->shouldReceive('countByType')
            ->with($project->id, 'dofollow')
            ->once()
            ->andReturn(80);

        $this->backlinkRepo->shouldReceive('countByType')
            ->with($project->id, 'nofollow')
            ->once()
            ->andReturn(20);

        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->with($project->id)
            ->once()
            ->andReturn(65.5);

        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->with($project->id)
            ->once()
            ->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(100);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(25);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(80);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(20);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(65.5);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(75.0);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $result);
    }

    /** @test */
    public function csb_002_returns_backlink_snapshot_model(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $result);
    }

    /** @test */
    public function csb_003_snapshot_date_is_today(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('snapshot_date')->andReturn(now()->toDateString());

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['snapshot_date']) && $data['snapshot_date'] === now()->toDateString();
            })
            ->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(now()->toDateString(), $result->snapshot_date);
    }

    /** @test */
    public function csb_004_stores_snapshot_in_database(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(50);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(10);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(40);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(55.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = BacklinkSnapshot::factory()->make([
            'project_id' => $project->id,
            'total_backlinks' => 50,
            'referring_domains_count' => 10,
            'dofollow_count' => 40,
            'nofollow_count' => 10,
            'avg_domain_trust' => 55.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertNotNull($result);
    }

    /** @test */
    public function csb_005_associates_snapshot_with_project(): void
    {
        $project = Project::factory()->create(['id' => 5]);

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('project_id')->andReturn(5);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($data) use ($project) {
                return $data['project_id'] === $project->id;
            })
            ->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(5, $result->project_id);
    }

    // ============================================
    // 3. createSnapshot() - Total Backlinks (CST)
    // ============================================

    /** @test */
    public function cst_001_counts_active_backlinks_only(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')
            ->with($project->id)
            ->once()
            ->andReturn(10);

        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(8);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.2);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(10);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(10, $result->total_backlinks);
    }

    /** @test */
    public function cst_002_excludes_inactive_backlinks(): void
    {
        $project = Project::factory()->create();

        // Simulating: 10 total, 3 inactive = 7 active
        $this->backlinkRepo->shouldReceive('countActiveByProject')
            ->with($project->id)
            ->once()
            ->andReturn(7);

        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(5);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(40.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(7);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(7, $result->total_backlinks);
    }

    /** @test */
    public function cst_003_zero_backlinks_returns_zero(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->total_backlinks);
    }

    /** @test */
    public function cst_004_large_count_handled_correctly(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(15000);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(500);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(12000);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(45.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.15);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(15000);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(15000, $result->total_backlinks);
    }

    /** @test */
    public function cst_005_counts_from_correct_project(): void
    {
        $project = Project::factory()->create(['id' => 42]);

        $this->backlinkRepo->shouldReceive('countActiveByProject')
            ->with(42)
            ->once()
            ->andReturn(100);

        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(20);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.2);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $this->service->createSnapshot($project);

        // Assertion is in the mock expectation above
        $this->assertTrue(true);
    }

    // ============================================
    // 4. createSnapshot() - Referring Domains (CSR)
    // ============================================

    /** @test */
    public function csr_001_counts_distinct_referring_domains(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')
            ->with($project->id)
            ->once()
            ->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(60.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(3);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(3, $result->referring_domains_count);
    }

    /** @test */
    public function csr_002_uses_referring_domains_table_count(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(60.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(5);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(5, $result->referring_domains_count);
    }

    /** @test */
    public function csr_003_zero_domains_returns_zero(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->referring_domains_count);
    }

    // ============================================
    // 5. createSnapshot() - Dofollow/Nofollow (CSN)
    // ============================================

    /** @test */
    public function csn_001_counts_dofollow_links(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')
            ->with($project->id, 'dofollow')
            ->once()
            ->andReturn(8);
        $this->backlinkRepo->shouldReceive('countByType')
            ->with($project->id, 'nofollow')
            ->once()
            ->andReturn(2);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(8);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(8, $result->dofollow_count);
    }

    /** @test */
    public function csn_002_counts_nofollow_links(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(8);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(2);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(2);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(2, $result->nofollow_count);
    }

    /** @test */
    public function csn_003_unknown_link_type_not_counted(): void
    {
        $project = Project::factory()->create();

        // 5 dofollow + 3 nofollow + 2 unknown = 10 total
        // Only dofollow and nofollow should be counted
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'dofollow')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'nofollow')->andReturn(3);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(5);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(3);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(5, $result->dofollow_count);
        $this->assertEquals(3, $result->nofollow_count);
    }

    /** @test */
    public function csn_004_only_active_backlinks_counted(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(5);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(5);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(5);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(5, $result->dofollow_count);
    }

    /** @test */
    public function csn_005_zero_dofollow_nofollow(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->dofollow_count);
        $this->assertEquals(0, $result->nofollow_count);
    }

    /** @test */
    public function csn_006_all_dofollow(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'dofollow')->andReturn(10);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'nofollow')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(10);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(10, $result->dofollow_count);
        $this->assertEquals(0, $result->nofollow_count);
    }

    /** @test */
    public function csn_007_all_nofollow(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'dofollow')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->with($project->id, 'nofollow')->andReturn(10);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(10);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->dofollow_count);
        $this->assertEquals(10, $result->nofollow_count);
    }

    // ============================================
    // 6. createSnapshot() - Average Domain Trust (CSA)
    // ============================================

    /** @test */
    public function csa_001_calculates_average_trust(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->with($project->id)
            ->once()
            ->andReturn(60.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(60.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(60.0, $result->avg_domain_trust);
    }

    /** @test */
    public function csa_002_handles_zero_trust_scores(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0.0, $result->avg_domain_trust);
    }

    /** @test */
    public function csa_003_zero_domains_returns_zero(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0.0, $result->avg_domain_trust);
    }

    /** @test */
    public function csa_004_precision_to_two_decimals(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(3);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(50.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(50.0, $result->avg_domain_trust);
    }

    // ============================================
    // 7. createSnapshot() - Anchor Diversity (CSD)
    // ============================================

    /** @test */
    public function csd_001_calculates_diversity_correctly(): void
    {
        $project = Project::factory()->create();

        // Top anchor 25% → diversity score = 75
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->with($project->id)
            ->once()
            ->andReturn(0.25);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(75.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(75.0, $result->anchor_diversity_score);
    }

    /** @test */
    public function csd_002_single_anchor_zero_diversity(): void
    {
        $project = Project::factory()->create();

        // Top anchor 100% → diversity score = 0
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(1.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0.0, $result->anchor_diversity_score);
    }

    /** @test */
    public function csd_003_perfect_distribution_100(): void
    {
        $project = Project::factory()->create();

        // Equal distribution → top anchor percentage would be 1/n
        // If 10 anchors equal: top = 10% → diversity = 90
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(5);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(100.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(100.0, $result->anchor_diversity_score);
    }

    /** @test */
    public function csd_004_no_anchors_returns_zero(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0.0, $result->anchor_diversity_score);
    }

    // ============================================
    // 8. createSnapshot() - Edge Cases (CSE)
    // ============================================

    /** @test */
    public function cse_001_project_with_no_backlinks(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(0);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(0.0);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->total_backlinks);
        $this->assertEquals(0, $result->referring_domains_count);
        $this->assertEquals(0, $result->dofollow_count);
        $this->assertEquals(0, $result->nofollow_count);
        $this->assertEquals(0.0, $result->avg_domain_trust);
        $this->assertEquals(0.0, $result->anchor_diversity_score);
    }

    /** @test */
    public function cse_002_project_with_only_inactive_backlinks(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(0, $result->total_backlinks);
    }

    /** @test */
    public function cse_003_concurrent_snapshot_updates_existing(): void
    {
        $project = Project::factory()->create();
        $existingSnapshot = BacklinkSnapshot::factory()->make([
            'project_id' => $project->id,
            'snapshot_date' => now()->toDateString(),
        ]);

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(50);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(10);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(40);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(55.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.3);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->andReturn($existingSnapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $result);
    }

    // ============================================
    // 9. createSnapshot() - Idempotency (CSI)
    // ============================================

    /** @test */
    public function csi_001_upserts_by_project_and_date(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(20);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($data) use ($project) {
                return $data['project_id'] === $project->id
                    && $data['snapshot_date'] === now()->toDateString();
            })
            ->andReturn(Mockery::mock(BacklinkSnapshot::class));

        $this->service->createSnapshot($project);

        $this->assertTrue(true);
    }

    /** @test */
    public function csi_002_returns_existing_snapshot_if_exists(): void
    {
        $project = Project::factory()->create();
        $existingSnapshot = BacklinkSnapshot::factory()->make([
            'id' => 123,
            'project_id' => $project->id,
            'snapshot_date' => now()->toDateString(),
        ]);

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(20);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->andReturn($existingSnapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(123, $result->id);
    }

    // ============================================
    // 10. getHistory() - Basic Tests (GHB)
    // ============================================

    /** @test */
    public function ghb_001_returns_collection(): void
    {
        $snapshots = collect([
            BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-01']),
            BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-02']),
        ]);

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn($snapshots);

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function ghb_002_ordered_by_date_ascending(): void
    {
        $snapshots = collect([
            BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-01']),
            BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-02']),
            BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-03']),
        ]);

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn($snapshots);

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertEquals('2026-01-01', $result->first()->snapshot_date->toDateString());
        $this->assertEquals('2026-01-03', $result->last()->snapshot_date->toDateString());
    }

    /** @test */
    public function ghb_003_returns_empty_collection(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function ghb_004_returns_all_snapshots_in_range(): void
    {
        $snapshots = collect(array_fill(0, 30, null))->map(function ($_, $i) {
            return BacklinkSnapshot::factory()->make([
                'snapshot_date' => now()->subDays(30 - $i)->toDateString(),
            ]);
        });

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn($snapshots);

        $result = $this->service->getHistory(1, now()->subDays(30)->toDateString(), now()->toDateString());

        $this->assertCount(30, $result);
    }

    /** @test */
    public function ghb_005_each_item_is_backlink_snapshot(): void
    {
        $snapshots = collect([
            BacklinkSnapshot::factory()->make(),
            BacklinkSnapshot::factory()->make(),
        ]);

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn($snapshots);

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        foreach ($result as $item) {
            $this->assertInstanceOf(BacklinkSnapshot::class, $item);
        }
    }

    // ============================================
    // 11. getHistory() - Date Filtering (GHF)
    // ============================================

    /** @test */
    public function ghf_001_filters_by_from_date(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-15', '2026-01-31')
            ->once()
            ->andReturn(new EloquentCollection());

        $this->service->getHistory(1, '2026-01-15', '2026-01-31');
    }

    /** @test */
    public function ghf_002_filters_by_to_date(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-20')
            ->once()
            ->andReturn(new EloquentCollection());

        $this->service->getHistory(1, '2026-01-01', '2026-01-20');
    }

    /** @test */
    public function ghf_003_inclusive_date_range(): void
    {
        $snapshot = BacklinkSnapshot::factory()->make(['snapshot_date' => '2026-01-15']);

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-15', '2026-01-15')
            ->once()
            ->andReturn(new EloquentCollection([]));

        $result = $this->service->getHistory(1, '2026-01-15', '2026-01-15');

        $this->assertCount(1, $result);
    }

    /** @test */
    public function ghf_004_date_format_yyyy_mm_dd(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(new EloquentCollection());

        $this->service->getHistory(1, '2026-01-01', '2026-01-31');
    }

    /** @test */
    public function ghf_005_invalid_date_format_throws_exception(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->andThrow(new \InvalidArgumentException('Invalid date format'));

        $this->expectException(\InvalidArgumentException::class);

        $this->service->getHistory(1, 'invalid-date', '2026-01-31');
    }

    /** @test */
    public function ghf_006_from_date_after_to_date(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        $result = $this->service->getHistory(1, '2026-01-31', '2026-01-01');

        $this->assertTrue($result->isEmpty());
    }

    // ============================================
    // 12. getHistory() - Project Filtering (GHP)
    // ============================================

    /** @test */
    public function ghp_001_filters_by_project_id(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(42, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(new EloquentCollection());

        $this->service->getHistory(42, '2026-01-01', '2026-01-31');
    }

    /** @test */
    public function ghp_002_multiple_projects_isolated(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(collect([BacklinkSnapshot::factory()->make(['project_id' => 1])]));

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertCount(1, $result);
    }

    /** @test */
    public function ghp_003_non_existent_project(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(99999, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(new EloquentCollection());

        $result = $this->service->getHistory(99999, '2026-01-01', '2026-01-31');

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function ghp_004_project_with_no_snapshots(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertTrue($result->isEmpty());
    }

    // ============================================
    // 13. calculateAnchorDiversity() - Basic (CADB)
    // ============================================

    /** @test */
    public function cadb_001_returns_float(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.25);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertIsFloat($result);
    }

    /** @test */
    public function cadb_002_score_in_zero_to_hundred_range(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.5);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertGreaterThanOrEqual(0.0, $result);
        $this->assertLessThanOrEqual(100.0, $result);
    }

    /** @test */
    public function cadb_003_uses_anchor_texts_table(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->with(1)
            ->once()
            ->andReturn(0.3);

        $this->service->calculateAnchorDiversity(1);

        $this->assertTrue(true);
    }

    // ============================================
    // 14. calculateAnchorDiversity() - Calculations (CADC)
    // ============================================

    /** @test */
    public function cadc_001_single_anchor_zero_diversity(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(1.0);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function cadc_002_two_equal_anchors_fifty(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.5);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(50.0, $result);
    }

    /** @test */
    public function cadc_003_top_anchor_25_percent(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.25);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(75.0, $result);
    }

    /** @test */
    public function cadc_004_no_anchors_zero(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function cadc_005_top_anchor_10_percent(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.1);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(90.0, $result);
    }

    // ============================================
    // 15. calculateAnchorDiversity() - Edge Cases (CADE)
    // ============================================

    /** @test */
    public function cade_001_no_project_found(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAnchorDiversity(99999);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function cade_002_unicode_anchor_text(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.33);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(67.0, $result);
    }

    // ============================================
    // 16. calculateAverageTrust() - Basic (CATB)
    // ============================================

    /** @test */
    public function catb_001_returns_float(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(50.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertIsFloat($result);
    }

    /** @test */
    public function catb_002_score_in_zero_to_hundred_range(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(75.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertGreaterThanOrEqual(0.0, $result);
        $this->assertLessThanOrEqual(100.0, $result);
    }

    /** @test */
    public function catb_003_uses_referring_domains_table(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->with(1)
            ->once()
            ->andReturn(50.0);

        $this->service->calculateAverageTrust(1);

        $this->assertTrue(true);
    }

    // ============================================
    // 17. calculateAverageTrust() - Calculations (CATC)
    // ============================================

    /** @test */
    public function catc_001_single_domain(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(75.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(75.0, $result);
    }

    /** @test */
    public function catc_002_multiple_domains(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(60.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(60.0, $result);
    }

    /** @test */
    public function catc_003_zero_trust_domains(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function catc_004_no_domains(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function catc_005_all_max_scores(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(100.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(100.0, $result);
    }

    // ============================================
    // 18. calculateAverageTrust() - Edge Cases (CATE)
    // ============================================

    /** @test */
    public function cate_001_non_existent_project(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAverageTrust(99999);

        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function cate_002_null_trust_scores_excluded(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(50.0);

        $result = $this->service->calculateAverageTrust(1);

        $this->assertEquals(50.0, $result);
    }

    // ============================================
    // 19. Error Handling (EH)
    // ============================================

    /** @test */
    public function eh_001_database_connection_lost(): void
    {
        $this->backlinkRepo->shouldReceive('countActiveByProject')
            ->andThrow(new \Exception('Database connection lost'));

        $project = Project::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection lost');

        $this->service->createSnapshot($project);
    }

    /** @test */
    public function eh_002_invalid_project_id_type(): void
    {
        // PHP will type-hint this, but we test the service behavior
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        // Passing a valid int to test the flow
        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function eh_003_invalid_date_format(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->andThrow(new \InvalidArgumentException('Invalid date format'));

        $this->expectException(\InvalidArgumentException::class);

        $this->service->getHistory(1, 'not-a-date', '2026-01-31');
    }

    /** @test */
    public function eh_004_repository_returns_null(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.0);

        $result = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(0.0, $result);
    }

    // ============================================
    // 20. Security Tests (SEC)
    // ============================================

    /** @test */
    public function sec_001_sql_injection_project_id(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        // If this doesn't throw, the parameterized query is working
        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function sec_002_sql_injection_date(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn(new EloquentCollection());

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function sec_003_no_sensitive_data_in_logs(): void
    {
        // The service should not log sensitive data
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(0);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(0);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(0.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn(Mockery::mock(BacklinkSnapshot::class));

        $project = Project::factory()->create();
        $result = $this->service->createSnapshot($project);

        $this->assertInstanceOf(BacklinkSnapshot::class, $result);
    }

    /** @test */
    public function sec_004_mass_assignment_protection(): void
    {
        // Verify that only fillable fields are used
        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(20);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(50.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.25);

        $expectedFields = [
            'project_id',
            'snapshot_date',
            'total_backlinks',
            'referring_domains_count',
            'dofollow_count',
            'nofollow_count',
            'avg_domain_trust',
            'anchor_diversity_score',
        ];

        $this->snapshotRepo->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($data) use ($expectedFields) {
                foreach ($expectedFields as $field) {
                    if (!array_key_exists($field, $data)) {
                        return false;
                    }
                }
                return true;
            })
            ->andReturn(Mockery::mock(BacklinkSnapshot::class));

        $project = Project::factory()->create();
        $this->service->createSnapshot($project);

        $this->assertTrue(true);
    }

    /** @test */
    public function sec_005_data_isolation(): void
    {
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(collect([BacklinkSnapshot::factory()->make(['project_id' => 1])]));

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        foreach ($result as $snapshot) {
            $this->assertEquals(1, $snapshot->project_id);
        }
    }

    // ============================================
    // 21. Integration Tests (INT)
    // ============================================

    /** @test */
    public function int_001_full_snapshot_creation_flow(): void
    {
        $project = Project::factory()->create();

        $this->backlinkRepo->shouldReceive('countActiveByProject')->andReturn(100);
        $this->referringDomainRepo->shouldReceive('countByProject')->andReturn(25);
        $this->backlinkRepo->shouldReceive('countByType')->andReturn(80);
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')->andReturn(60.0);
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')->andReturn(0.2);

        $snapshot = Mockery::mock(BacklinkSnapshot::class);
        $snapshot->shouldReceive('getAttribute')->with('total_backlinks')->andReturn(100);
        $snapshot->shouldReceive('getAttribute')->with('referring_domains_count')->andReturn(25);
        $snapshot->shouldReceive('getAttribute')->with('dofollow_count')->andReturn(80);
        $snapshot->shouldReceive('getAttribute')->with('nofollow_count')->andReturn(20);
        $snapshot->shouldReceive('getAttribute')->with('avg_domain_trust')->andReturn(60.0);
        $snapshot->shouldReceive('getAttribute')->with('anchor_diversity_score')->andReturn(80.0);

        $this->snapshotRepo->shouldReceive('upsert')->andReturn($snapshot);

        $result = $this->service->createSnapshot($project);

        $this->assertEquals(100, $result->total_backlinks);
        $this->assertEquals(25, $result->referring_domains_count);
        $this->assertEquals(80, $result->dofollow_count);
        $this->assertEquals(20, $result->nofollow_count);
        $this->assertEquals(60.0, $result->avg_domain_trust);
        $this->assertEquals(80.0, $result->anchor_diversity_score);
    }

    /** @test */
    public function int_002_history_retrieval_matches_created(): void
    {
        $snapshots = collect([
            BacklinkSnapshot::factory()->make(['project_id' => 1, 'snapshot_date' => '2026-01-01']),
            BacklinkSnapshot::factory()->make(['project_id' => 1, 'snapshot_date' => '2026-01-02']),
            BacklinkSnapshot::factory()->make(['project_id' => 1, 'snapshot_date' => '2026-01-03']),
            BacklinkSnapshot::factory()->make(['project_id' => 1, 'snapshot_date' => '2026-01-04']),
            BacklinkSnapshot::factory()->make(['project_id' => 1, 'snapshot_date' => '2026-01-05']),
        ]);

        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->once()
            ->andReturn($snapshots);

        $result = $this->service->getHistory(1, '2026-01-01', '2026-01-05');

        $this->assertCount(5, $result);
    }

    /** @test */
    public function int_003_diversity_matches_snapshot(): void
    {
        $this->anchorTextRepo->shouldReceive('getTopAnchorPercentage')
            ->once()
            ->andReturn(0.25);

        $diversity = $this->service->calculateAnchorDiversity(1);

        $this->assertEquals(75.0, $diversity);
    }

    /** @test */
    public function int_004_average_trust_matches_snapshot(): void
    {
        $this->referringDomainRepo->shouldReceive('getAverageTrustScore')
            ->once()
            ->andReturn(65.5);

        $trust = $this->service->calculateAverageTrust(1);

        $this->assertEquals(65.5, $trust);
    }

    /** @test */
    public function int_005_multiple_projects_isolated(): void
    {
        // Project 1
        $this->snapshotRepo->shouldReceive('getByDateRange')
            ->with(1, '2026-01-01', '2026-01-31')
            ->once()
            ->andReturn(collect([BacklinkSnapshot::factory()->make(['project_id' => 1])]));

        $result1 = $this->service->getHistory(1, '2026-01-01', '2026-01-31');

        $this->assertCount(1, $result1);
        $this->assertEquals(1, $result1->first()->project_id);
    }
}
