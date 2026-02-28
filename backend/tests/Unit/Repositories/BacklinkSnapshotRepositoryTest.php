<?php

namespace Tests\Unit\Repositories;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use App\Repositories\BacklinkSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests for BacklinkSnapshotRepository.
 * 
 * @see TASK-23-2
 */
class BacklinkSnapshotRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected BacklinkSnapshotRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(BacklinkSnapshotRepository::class);
    }

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_a_snapshot(): void
    {
        $project = Project::factory()->create();
        
        $snapshot = $this->repository->create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'snapshot_date' => '2026-02-28',
        ]);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertEquals(1000, $snapshot->total_backlinks);
    }

    /** @test */
    public function it_can_find_a_snapshot_by_id(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create();

        $found = $this->repository->findById($snapshot->id);

        $this->assertInstanceOf(BacklinkSnapshot::class, $found);
        $this->assertEquals($snapshot->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_snapshot_not_found(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_get_all_snapshots_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->count(5)->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->count(3)->create(); // Other project

        $snapshots = $this->repository->getByProject($project->id);

        $this->assertCount(5, $snapshots);
        $snapshots->each(fn ($s) => $this->assertEquals($project->id, $s->project_id));
    }

    // =============================================
    // Date Query Tests
    // =============================================

    /** @test */
    public function it_can_get_snapshot_by_date(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
        ]);

        $snapshot = $this->repository->getByDate($project->id, '2026-02-28');

        $this->assertNotNull($snapshot);
        $this->assertEquals('2026-02-28', $snapshot->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_get_snapshots_by_date_range(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-15']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-25']);

        $snapshots = $this->repository->getByDateRange($project->id, '2026-02-12', '2026-02-22');

        $this->assertCount(2, $snapshots);
        $this->assertEquals('2026-02-15', $snapshots[0]->snapshot_date->format('Y-m-d'));
        $this->assertEquals('2026-02-20', $snapshots[1]->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_get_latest_snapshot(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-15']);

        $latest = $this->repository->getLatest($project->id);

        $this->assertEquals('2026-02-28', $latest->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_get_previous_snapshot(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);

        $previous = $this->repository->getPrevious($project->id, '2026-02-28');

        $this->assertEquals('2026-02-20', $previous->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_returns_null_when_no_previous_snapshot_exists(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);

        $previous = $this->repository->getPrevious($project->id, '2026-02-28');

        $this->assertNull($previous);
    }

    // =============================================
    // Upsert Tests
    // =============================================

    /** @test */
    public function it_can_upsert_new_snapshot(): void
    {
        $project = Project::factory()->create();

        $snapshot = $this->repository->upsert([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
        ]);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertEquals(1000, $snapshot->total_backlinks);
    }

    /** @test */
    public function it_can_upsert_existing_snapshot(): void
    {
        $project = Project::factory()->create();
        
        // Create initial snapshot
        $original = BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'total_backlinks' => 1000,
        ]);

        // Upsert same date - should update
        $updated = $this->repository->upsert([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'total_backlinks' => 1500,
            'referring_domains_count' => 75,
        ]);

        $this->assertEquals($original->id, $updated->id);
        $this->assertEquals(1500, $updated->total_backlinks);
        $this->assertEquals(75, $updated->referring_domains_count);
    }

    /** @test */
    public function it_rejects_duplicate_date_for_same_project(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
        ]);
    }

    // =============================================
    // Trend Analysis Tests
    // =============================================

    /** @test */
    public function it_can_calculate_backlinks_trend(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20', 'total_backlinks' => 800]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'total_backlinks' => 1000]);

        $trend = $this->repository->calculateBacklinksTrend($project->id, '2026-02-28');

        $this->assertNotNull($trend);
        $this->assertEquals(1000, $trend['current']);
        $this->assertEquals(800, $trend['previous']);
        $this->assertEquals(200, $trend['change']);
        $this->assertEquals(25.0, $trend['percentage']);
    }

    /** @test */
    public function it_can_calculate_referring_domains_trend(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20', 'referring_domains_count' => 40]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'referring_domains_count' => 50]);

        $trend = $this->repository->calculateReferringDomainsTrend($project->id, '2026-02-28');

        $this->assertNotNull($trend);
        $this->assertEquals(50, $trend['current']);
        $this->assertEquals(40, $trend['previous']);
        $this->assertEquals(10, $trend['change']);
        $this->assertEquals(25.0, $trend['percentage']);
    }

    /** @test */
    public function it_can_get_trend_percentage(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20', 'total_backlinks' => 800]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'total_backlinks' => 1000]);

        $percentage = $this->repository->getTrendPercentage($project->id, '2026-02-28', 'Backlinks');

        $this->assertEquals(25.0, $percentage);
    }

    // =============================================
    // API Response Tests
    // =============================================

    /** @test */
    public function it_can_format_snapshots_for_chart_data(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-26', 'total_backlinks' => 900, 'referring_domains_count' => 45, 'dofollow_count' => 720, 'nofollow_count' => 180]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-27', 'total_backlinks' => 950, 'referring_domains_count' => 47, 'dofollow_count' => 760, 'nofollow_count' => 190]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'total_backlinks' => 1000, 'referring_domains_count' => 50, 'dofollow_count' => 800, 'nofollow_count' => 200]);

        $chartData = $this->repository->formatForChart($project->id);

        $this->assertCount(3, $chartData['labels']);
        $this->assertCount(3, $chartData['backlinks']);
        $this->assertEquals([900, 950, 1000], $chartData['backlinks']);
        $this->assertEquals([45, 47, 50], $chartData['referring_domains']);
    }

    // =============================================
    // Cleanup Tests
    // =============================================

    /** @test */
    public function it_can_delete_old_snapshots(): void
    {
        $project = Project::factory()->create();
        
        // Old snapshots (more than 365 days ago from now)
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => now()->subDays(400)->toDateString()]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => now()->subDays(500)->toDateString()]);
        
        // Recent snapshots (within 365 days)
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => now()->subDays(30)->toDateString()]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => now()->subDays(100)->toDateString()]);

        $deleted = $this->repository->deleteOldSnapshots($project->id, 365);

        $this->assertEquals(2, $deleted);
        $this->assertCount(2, BacklinkSnapshot::where('project_id', $project->id)->get());
    }
}
