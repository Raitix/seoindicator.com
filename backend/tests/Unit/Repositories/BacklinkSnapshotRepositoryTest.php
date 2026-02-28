<?php

namespace Tests\Unit\Repositories;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests for BacklinkSnapshotRepository.
 * 
 * These tests define the expected repository interface.
 * The repository implementation should be created to pass these tests.
 * 
 * @see TASK-23-2
 */
class BacklinkSnapshotRepositoryTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_a_snapshot(): void
    {
        $project = Project::factory()->create();
        
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 45.67,
            'anchor_diversity_score' => 78.90,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(BacklinkSnapshot::class, $snapshot);
        $this->assertEquals(1000, $snapshot->total_backlinks);
    }

    /** @test */
    public function it_can_find_a_snapshot_by_id(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create();

        $found = BacklinkSnapshot::find($snapshot->id);

        $this->assertInstanceOf(BacklinkSnapshot::class, $found);
        $this->assertEquals($snapshot->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_snapshot_not_found(): void
    {
        $found = BacklinkSnapshot::find(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_get_all_snapshots_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->count(5)->create(['project_id' => $project->id]);
        BacklinkSnapshot::factory()->count(3)->create(); // Other project

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)->get();

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

        $snapshot = BacklinkSnapshot::where('project_id', $project->id)
            ->whereDate('snapshot_date', '2026-02-28')
            ->first();

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

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)
            ->whereBetween('snapshot_date', ['2026-02-12', '2026-02-22'])
            ->orderByDateAsc()
            ->get();

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

        $latest = BacklinkSnapshot::where('project_id', $project->id)
            ->orderBy('snapshot_date', 'desc')
            ->first();

        $this->assertEquals('2026-02-28', $latest->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_can_get_previous_snapshot(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);

        $currentDate = '2026-02-28';
        
        $previous = BacklinkSnapshot::where('project_id', $project->id)
            ->where('snapshot_date', '<', $currentDate)
            ->orderBy('snapshot_date', 'desc')
            ->first();

        $this->assertEquals('2026-02-20', $previous->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_returns_null_when_no_previous_snapshot_exists(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);

        $previous = BacklinkSnapshot::where('project_id', $project->id)
            ->where('snapshot_date', '<', '2026-02-28')
            ->orderBy('snapshot_date', 'desc')
            ->first();

        $this->assertNull($previous);
    }

    // =============================================
    // Upsert Tests
    // =============================================

    /** @test */
    public function it_can_upsert_new_snapshot(): void
    {
        $project = Project::factory()->create();

        $snapshot = BacklinkSnapshot::updateOrCreate(
            [
                'project_id' => $project->id,
                'snapshot_date' => '2026-02-28',
            ],
            [
                'total_backlinks' => 1000,
                'referring_domains_count' => 50,
                'created_at' => now(),
            ]
        );

        $this->assertTrue($snapshot->wasRecentlyCreated);
        $this->assertEquals(1000, $snapshot->total_backlinks);
    }

    /** @test */
    public function it_can_upsert_existing_snapshot(): void
    {
        $project = Project::factory()->create();
        
        // Create initial snapshot using updateOrCreate (first call creates)
        $original = BacklinkSnapshot::updateOrCreate(
            [
                'project_id' => $project->id,
                'snapshot_date' => Carbon::parse('2026-02-28'),
            ],
            [
                'total_backlinks' => 1000,
                'created_at' => now(),
            ]
        );

        // Upsert same date - should update (second call updates)
        $updated = BacklinkSnapshot::updateOrCreate(
            [
                'project_id' => $project->id,
                'snapshot_date' => Carbon::parse('2026-02-28'),
            ],
            [
                'total_backlinks' => 1500,
                'referring_domains_count' => 75,
            ]
        );

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

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)
            ->orderByDateAsc()
            ->get();

        $trend = $snapshots->last()->total_backlinks - $snapshots->first()->total_backlinks;

        $this->assertEquals(200, $trend);
    }

    /** @test */
    public function it_can_calculate_referring_domains_trend(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20', 'referring_domains_count' => 40]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'referring_domains_count' => 50]);

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)
            ->orderByDateAsc()
            ->get();

        $trend = $snapshots->last()->referring_domains_count - $snapshots->first()->referring_domains_count;

        $this->assertEquals(10, $trend);
    }

    /** @test */
    public function it_can_get_trend_percentage(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-20', 'total_backlinks' => 800]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'total_backlinks' => 1000]);

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)
            ->orderByDateAsc()
            ->get();

        $firstValue = $snapshots->first()->total_backlinks;
        $lastValue = $snapshots->last()->total_backlinks;
        $percentageChange = (($lastValue - $firstValue) / $firstValue) * 100;

        $this->assertEquals(25.0, $percentageChange);
    }

    // =============================================
    // API Response Tests
    // =============================================

    /** @test */
    public function it_can_format_snapshots_for_chart_data(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-26', 'total_backlinks' => 900]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-27', 'total_backlinks' => 950]);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28', 'total_backlinks' => 1000]);

        $snapshots = BacklinkSnapshot::where('project_id', $project->id)
            ->orderByDateAsc()
            ->get();

        $chartData = $snapshots->map(fn ($s) => $s->toApiResponse())->toArray();

        $this->assertCount(3, $chartData);
        $this->assertEquals('2026-02-26', $chartData[0]['date']);
        $this->assertEquals('2026-02-27', $chartData[1]['date']);
        $this->assertEquals('2026-02-28', $chartData[2]['date']);
    }

    // =============================================
    // Cleanup Tests
    // =============================================

    /** @test */
    public function it_can_delete_old_snapshots(): void
    {
        $project = Project::factory()->create();
        
        // Old snapshots
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2025-01-01']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2025-06-01']);
        
        // Recent snapshots
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-01']);
        BacklinkSnapshot::factory()->create(['project_id' => $project->id, 'snapshot_date' => '2026-02-28']);

        // Delete snapshots older than 90 days
        $cutoffDate = now()->subDays(90)->format('Y-m-d');
        $deleted = BacklinkSnapshot::where('project_id', $project->id)
            ->where('snapshot_date', '<', $cutoffDate)
            ->delete();

        $this->assertEquals(2, $deleted);
        $this->assertCount(2, BacklinkSnapshot::where('project_id', $project->id)->get());
    }
}
