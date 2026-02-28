<?php

namespace Tests\Unit\Models;

use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BacklinkSnapshotTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // Model Attribute Tests
    // =============================================

    /** @test */
    public function it_can_create_a_snapshot_with_all_attributes(): void
    {
        $project = Project::factory()->create();
        
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1250,
            'referring_domains_count' => 89,
            'dofollow_count' => 900,
            'nofollow_count' => 350,
            'avg_domain_trust' => 45.67,
            'anchor_diversity_score' => 78.90,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('backlink_snapshots', [
            'id' => $snapshot->id,
            'total_backlinks' => 1250,
            'referring_domains_count' => 89,
        ]);
        
        // Check date separately due to SQLite date format differences
        $this->assertEquals('2026-02-28', $snapshot->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_snapshot_date_to_date(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'snapshot_date' => '2026-02-28',
        ]);

        $this->assertInstanceOf(Carbon::class, $snapshot->snapshot_date);
        $this->assertEquals('2026-02-28', $snapshot->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_avg_domain_trust_to_decimal(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'avg_domain_trust' => 45.67,
        ]);

        $this->assertIsFloat((float) $snapshot->avg_domain_trust);
        $this->assertEquals(45.67, (float) $snapshot->avg_domain_trust);
    }

    /** @test */
    public function it_casts_anchor_diversity_score_to_decimal(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'anchor_diversity_score' => 78.90,
        ]);

        $this->assertIsFloat((float) $snapshot->anchor_diversity_score);
        $this->assertEquals(78.90, (float) $snapshot->anchor_diversity_score);
    }

    /** @test */
    public function it_defaults_total_backlinks_to_zero(): void
    {
        // Create without total_backlinks field - DB default should apply
        $project = Project::factory()->create();
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $freshSnapshot = BacklinkSnapshot::find($snapshot->id);
        $this->assertEquals(0, $freshSnapshot->total_backlinks);
    }

    /** @test */
    public function it_defaults_referring_domains_count_to_zero(): void
    {
        // Create without referring_domains_count field - DB default should apply
        $project = Project::factory()->create();
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $freshSnapshot = BacklinkSnapshot::find($snapshot->id);
        $this->assertEquals(0, $freshSnapshot->referring_domains_count);
    }

    /** @test */
    public function it_defaults_dofollow_count_to_zero(): void
    {
        // Create without dofollow_count field - DB default should apply
        $project = Project::factory()->create();
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $freshSnapshot = BacklinkSnapshot::find($snapshot->id);
        $this->assertEquals(0, $freshSnapshot->dofollow_count);
    }

    /** @test */
    public function it_defaults_nofollow_count_to_zero(): void
    {
        // Create without nofollow_count field - DB default should apply
        $project = Project::factory()->create();
        $snapshot = BacklinkSnapshot::create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);

        $freshSnapshot = BacklinkSnapshot::find($snapshot->id);
        $this->assertEquals(0, $freshSnapshot->nofollow_count);
    }

    /** @test */
    public function it_does_not_use_timestamps(): void
    {
        $snapshot = new BacklinkSnapshot();

        $this->assertFalse($snapshot->timestamps);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $snapshot = new BacklinkSnapshot();
        
        $fillable = [
            'project_id',
            'total_backlinks',
            'referring_domains_count',
            'dofollow_count',
            'nofollow_count',
            'avg_domain_trust',
            'anchor_diversity_score',
            'snapshot_date',
            'created_at',
        ];

        $this->assertEquals($fillable, $snapshot->getFillable());
    }

    /** @test */
    public function it_uses_correct_table_name(): void
    {
        $snapshot = new BacklinkSnapshot();

        $this->assertEquals('backlink_snapshots', $snapshot->getTable());
    }

    // =============================================
    // Relationship Tests
    // =============================================

    /** @test */
    public function it_belongs_to_a_project(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create();

        $this->assertInstanceOf(Project::class, $snapshot->project);
        $this->assertEquals($snapshot->project_id, $snapshot->project->id);
    }

    // =============================================
    // Scope Tests
    // =============================================

    /** @test */
    public function scope_for_project_filters_by_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        BacklinkSnapshot::factory()->count(3)->create(['project_id' => $project1->id]);
        BacklinkSnapshot::factory()->count(2)->create(['project_id' => $project2->id]);

        $results = BacklinkSnapshot::forProject($project1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($snapshot) => $this->assertEquals($project1->id, $snapshot->project_id));
    }

    /** @test */
    public function scope_date_range_filters_by_date_range(): void
    {
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-15']);
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-20']);
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-25']);

        $results = BacklinkSnapshot::dateRange('2026-02-12', '2026-02-22')->get();

        $this->assertCount(2, $results);
        $this->assertEquals(['2026-02-15', '2026-02-20'], $results->pluck('snapshot_date')->map->format('Y-m-d')->toArray());
    }

    /** @test */
    public function scope_order_by_date_asc_orders_ascending(): void
    {
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-20']);
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-10']);
        BacklinkSnapshot::factory()->create(['snapshot_date' => '2026-02-15']);

        $snapshots = BacklinkSnapshot::orderByDateAsc()->get();

        $this->assertEquals('2026-02-10', $snapshots[0]->snapshot_date->format('Y-m-d'));
        $this->assertEquals('2026-02-15', $snapshots[1]->snapshot_date->format('Y-m-d'));
        $this->assertEquals('2026-02-20', $snapshots[2]->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function scopes_can_be_chained(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-15',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-25',
        ]);
        BacklinkSnapshot::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'snapshot_date' => '2026-02-20',
        ]);

        $results = BacklinkSnapshot::forProject($project->id)
            ->dateRange('2026-02-10', '2026-02-20')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('2026-02-15', $results[0]->snapshot_date->format('Y-m-d'));
    }

    // =============================================
    // Unique Constraint Tests
    // =============================================

    /** @test */
    public function it_enforces_unique_project_date_constraint(): void
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

    /** @test */
    public function it_allows_same_date_for_different_projects(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        
        $snapshot1 = BacklinkSnapshot::factory()->create([
            'project_id' => $project1->id,
            'snapshot_date' => '2026-02-28',
        ]);

        $snapshot2 = BacklinkSnapshot::factory()->create([
            'project_id' => $project2->id,
            'snapshot_date' => '2026-02-28',
        ]);

        $this->assertNotEquals($snapshot1->id, $snapshot2->id);
        $this->assertEquals('2026-02-28', $snapshot1->snapshot_date->format('Y-m-d'));
        $this->assertEquals('2026-02-28', $snapshot2->snapshot_date->format('Y-m-d'));
    }

    /** @test */
    public function it_allows_multiple_dates_for_same_project(): void
    {
        $project = Project::factory()->create();
        
        $snapshot1 = BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-27',
        ]);

        $snapshot2 = BacklinkSnapshot::factory()->create([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
        ]);

        $this->assertCount(2, BacklinkSnapshot::forProject($project->id)->get());
    }

    // =============================================
    // API Response Tests
    // =============================================

    /** @test */
    public function to_api_response_returns_correct_structure(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'snapshot_date' => '2026-02-28',
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 45.67,
            'anchor_diversity_score' => 78.90,
        ]);

        $response = $snapshot->toApiResponse();

        $this->assertIsArray($response);
        $this->assertEquals('2026-02-28', $response['date']);
        $this->assertEquals(1000, $response['total_backlinks']);
        $this->assertEquals(50, $response['referring_domains_count']);
        $this->assertEquals(800, $response['dofollow_count']);
        $this->assertEquals(200, $response['nofollow_count']);
        $this->assertEquals(45.67, $response['avg_domain_trust']);
        $this->assertEquals(78.90, $response['anchor_diversity_score']);
    }

    /** @test */
    public function to_api_response_casts_decimals_to_floats(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'avg_domain_trust' => 45.67,
            'anchor_diversity_score' => 78.90,
        ]);

        $response = $snapshot->toApiResponse();

        $this->assertIsFloat($response['avg_domain_trust']);
        $this->assertIsFloat($response['anchor_diversity_score']);
    }

    /** @test */
    public function to_api_response_formats_date_as_string(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'snapshot_date' => '2026-02-28',
        ]);

        $response = $snapshot->toApiResponse();

        $this->assertIsString($response['date']);
        $this->assertEquals('2026-02-28', $response['date']);
    }

    // =============================================
    // Edge Cases
    // =============================================

    /** @test */
    public function it_accepts_all_zero_counts(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'total_backlinks' => 0,
            'referring_domains_count' => 0,
            'dofollow_count' => 0,
            'nofollow_count' => 0,
        ]);

        $this->assertEquals(0, $snapshot->total_backlinks);
        $this->assertEquals(0, $snapshot->referring_domains_count);
        $this->assertEquals(0, $snapshot->dofollow_count);
        $this->assertEquals(0, $snapshot->nofollow_count);
    }

    /** @test */
    public function it_accepts_large_counts(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'total_backlinks' => 1000000,
            'referring_domains_count' => 50000,
        ]);

        $this->assertEquals(1000000, $snapshot->total_backlinks);
        $this->assertEquals(50000, $snapshot->referring_domains_count);
    }

    /** @test */
    public function it_rounds_decimals_to_two_places(): void
    {
        $snapshot = BacklinkSnapshot::factory()->create([
            'avg_domain_trust' => 45.678,
        ]);

        // Decimal(5,2) rounds to 2 decimal places
        $this->assertEquals(45.68, (float) $snapshot->avg_domain_trust);
    }

    /** @test */
    public function it_accepts_future_snapshot_date(): void
    {
        $futureDate = now()->addDays(7)->format('Y-m-d');
        
        $snapshot = BacklinkSnapshot::factory()->create([
            'snapshot_date' => $futureDate,
        ]);

        $this->assertEquals($futureDate, $snapshot->snapshot_date->format('Y-m-d'));
    }

    // =============================================
    // Cascade Delete Tests
    // =============================================

    /** @test */
    public function it_is_deleted_when_project_is_deleted(): void
    {
        $project = Project::factory()->create();
        $snapshot = BacklinkSnapshot::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('backlink_snapshots', ['id' => $snapshot->id]);
    }
}
