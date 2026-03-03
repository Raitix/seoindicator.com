<?php

namespace Tests\Integration\Services\SEO;

use Tests\TestCase;
use App\Services\SEO\BacklinkProfileScorer;
use App\Models\Project;
use App\Models\BacklinkSnapshot;
use App\Models\ReferringDomain;
use App\Models\AnchorText;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * Integration tests for BacklinkProfileScorer
 * 
 * @see TASK-23-8
 * @see TEST-23-8
 */
class BacklinkProfileScorerIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    // =========================================================================
    // PROJECT INTEGRATION TESTS
    // =========================================================================

    /**
     * IN-001: Score method accepts Project model.
     * 
     * @test
     */
    public function score_method_accepts_project_model(): void
    {
        $project = Project::factory()->create();
        $scorer = app(BacklinkProfileScorer::class);

        $result = $scorer->score($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
    }

    /**
     * IN-002: Score uses project's latest snapshot.
     * 
     * @test
     */
    public function score_uses_project_latest_snapshot(): void
    {
        $project = Project::factory()->create();
        
        // Create old snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 500,
            'referring_domains_count' => 25,
            'dofollow_count' => 400,
            'nofollow_count' => 100,
            'avg_domain_trust' => 40.0,
            'anchor_diversity_score' => 50.0,
            'snapshot_date' => now()->subDays(60)->toDateString(),
        ]);
        
        // Create latest snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // Should use latest snapshot (1000 backlinks, 50 domains)
        $this->assertEquals(50, $result['component_details']['referring_domains']);
        $this->assertEquals(1000, $result['component_details']['total_backlinks']);
    }

    /**
     * IN-003: Score calculates growth from previous snapshot.
     * 
     * @test
     */
    public function score_calculates_growth_from_previous_snapshot(): void
    {
        $project = Project::factory()->create();
        
        // Previous snapshot (30 days ago)
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 800,
            'referring_domains_count' => 40,
            'dofollow_count' => 600,
            'nofollow_count' => 200,
            'avg_domain_trust' => 50.0,
            'anchor_diversity_score' => 60.0,
            'snapshot_date' => now()->subDays(30)->toDateString(),
        ]);
        
        // Current snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // Growth: (1000 - 800) / 800 * 100 = 25%
        $this->assertEquals(25.0, $result['component_details']['growth_rate']);
    }

    /**
     * IN-004: Score with no snapshots returns 0.
     * 
     * @test
     */
    public function score_with_no_snapshots_returns_zero(): void
    {
        $project = Project::factory()->create();

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        $this->assertEquals(0.0, $result['overall_score']);
    }

    /**
     * IN-005: Score with single snapshot uses neutral growth.
     * 
     * @test
     */
    public function score_with_single_snapshot_uses_neutral_growth(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // No previous snapshot = neutral growth score
        $this->assertEquals(12.5, $result['growth_score']);
    }

    // =========================================================================
    // ANCHOR DATA INTEGRATION TESTS
    // =========================================================================

    /**
     * IN-006: Score calculates exact match ratio from anchors.
     * 
     * @test
     */
    public function score_calculates_exact_match_ratio_from_anchors(): void
    {
        $project = Project::factory()->create();
        
        // Create snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 100,
            'referring_domains_count' => 50,
            'dofollow_count' => 80,
            'nofollow_count' => 20,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);
        
        // Create anchor texts
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'target keyword',
            'backlinks_count' => 20, // 20% exact match
            'is_exact_match' => true,
            'is_branded' => false,
        ]);
        
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'Brand Name',
            'backlinks_count' => 30,
            'is_exact_match' => false,
            'is_branded' => true,
        ]);
        
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'click here',
            'backlinks_count' => 50,
            'is_exact_match' => false,
            'is_branded' => false,
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // Exact match ratio: 20 / 100 = 0.20
        $this->assertEquals(0.20, $result['component_details']['exact_match_ratio']);
    }

    /**
     * IN-007: Score with no anchor data uses neutral value.
     * 
     * @test
     */
    public function score_with_no_anchor_data_uses_neutral_value(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 100,
            'referring_domains_count' => 50,
            'dofollow_count' => 80,
            'nofollow_count' => 20,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // No anchor data = neutral score
        $this->assertEquals(12.5, $result['anchor_health_score']);
    }

    // =========================================================================
    // DOMAIN TRUST INTEGRATION TESTS
    // =========================================================================

    /**
     * IN-008: Score uses average domain trust from referring domains.
     * 
     * @test
     */
    public function score_uses_average_domain_trust_from_referring_domains(): void
    {
        $project = Project::factory()->create();
        
        // Create snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 100,
            'referring_domains_count' => 3,
            'dofollow_count' => 80,
            'nofollow_count' => 20,
            'avg_domain_trust' => null, // Will be calculated
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);
        
        // Create referring domains with trust scores
        ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'high-trust.com',
            'backlinks_count' => 50,
            'domain_trust_score' => 90,
        ]);
        
        ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'medium-trust.com',
            'backlinks_count' => 30,
            'domain_trust_score' => 60,
        ]);
        
        ReferringDomain::create([
            'project_id' => $project->id,
            'domain' => 'low-trust.com',
            'backlinks_count' => 20,
            'domain_trust_score' => 30,
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // Average trust: (90 + 60 + 30) / 3 = 60
        $this->assertEquals(60.0, $result['component_details']['avg_domain_trust']);
    }

    // =========================================================================
    // FULL WORKFLOW TESTS
    // =========================================================================

    /**
     * IN-009: Complete scoring workflow.
     * 
     * @test
     */
    public function complete_scoring_workflow(): void
    {
        $project = Project::factory()->create();
        
        // Create previous snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 800,
            'referring_domains_count' => 40,
            'dofollow_count' => 600,
            'nofollow_count' => 200,
            'avg_domain_trust' => 50.0,
            'anchor_diversity_score' => 60.0,
            'snapshot_date' => now()->subDays(30)->toDateString(),
        ]);
        
        // Create current snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 60,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 70.0,
            'anchor_diversity_score' => 75.0,
            'snapshot_date' => now()->toDateString(),
        ]);
        
        // Create anchor texts
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'target keyword',
            'backlinks_count' => 100, // 10% exact match
            'is_exact_match' => true,
            'is_branded' => false,
        ]);
        
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'Brand Name',
            'backlinks_count' => 300,
            'is_exact_match' => false,
            'is_branded' => true,
        ]);
        
        AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'misc anchor',
            'backlinks_count' => 600,
            'is_exact_match' => false,
            'is_branded' => false,
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        // Verify all components present
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('diversity_score', $result);
        $this->assertArrayHasKey('trust_score', $result);
        $this->assertArrayHasKey('growth_score', $result);
        $this->assertArrayHasKey('anchor_health_score', $result);
        
        // Verify component details
        $this->assertEquals(60, $result['component_details']['referring_domains']);
        $this->assertEquals(1000, $result['component_details']['total_backlinks']);
        $this->assertEquals(70.0, $result['component_details']['avg_domain_trust']);
        $this->assertEquals(25.0, $result['component_details']['growth_rate']); // (1000-800)/800*100
        $this->assertEquals(0.10, $result['component_details']['exact_match_ratio']); // 100/1000
        
        // Verify overall score is in valid range
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /**
     * IN-010: Multiple projects scored independently.
     * 
     * @test
     */
    public function multiple_projects_scored_independently(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        
        // Project 1: Good profile
        BacklinkSnapshot::create([
            'project_id' => $project1->id,
            'total_backlinks' => 100,
            'referring_domains_count' => 80,
            'dofollow_count' => 80,
            'nofollow_count' => 20,
            'avg_domain_trust' => 80.0,
            'anchor_diversity_score' => 80.0,
            'snapshot_date' => now()->toDateString(),
        ]);
        
        // Project 2: Poor profile
        BacklinkSnapshot::create([
            'project_id' => $project2->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 5,
            'dofollow_count' => 100,
            'nofollow_count' => 900,
            'avg_domain_trust' => 20.0,
            'anchor_diversity_score' => 20.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        
        $result1 = $scorer->score($project1);
        $result2 = $scorer->score($project2);

        // Project 1 should have higher score
        $this->assertGreaterThan($result2['overall_score'], $result1['overall_score']);
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    /**
     * IN-011: Performance with large dataset.
     * 
     * @test
     */
    public function performance_with_large_dataset(): void
    {
        $project = Project::factory()->create();
        
        // Create snapshot with large numbers
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 100000,
            'referring_domains_count' => 5000,
            'dofollow_count' => 80000,
            'nofollow_count' => 20000,
            'avg_domain_trust' => 65.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        
        $startTime = microtime(true);
        $result = $scorer->score($project);
        $duration = (microtime(true) - $startTime) * 1000; // ms

        $this->assertIsArray($result);
        $this->assertLessThan(500, $duration, 'Scoring should complete in under 500ms');
    }

    // =========================================================================
    // DATA CONSISTENCY TESTS
    // =========================================================================

    /**
     * IN-012: Score is consistent across multiple calls.
     * 
     * @test
     */
    public function score_is_consistent_across_multiple_calls(): void
    {
        $project = Project::factory()->create();
        
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 50,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 60.0,
            'anchor_diversity_score' => 70.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        
        $result1 = $scorer->score($project);
        $result2 = $scorer->score($project);
        $result3 = $scorer->score($project);

        $this->assertEquals($result1['overall_score'], $result2['overall_score']);
        $this->assertEquals($result2['overall_score'], $result3['overall_score']);
    }

    /**
     * IN-013: Score updates when snapshot changes.
     * 
     * @test
     */
    public function score_updates_when_snapshot_changes(): void
    {
        $project = Project::factory()->create();
        
        // Initial snapshot
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 500,
            'referring_domains_count' => 25,
            'dofollow_count' => 400,
            'nofollow_count' => 100,
            'avg_domain_trust' => 40.0,
            'anchor_diversity_score' => 50.0,
            'snapshot_date' => now()->subDay()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        $initialScore = $scorer->score($project);
        
        // Create new snapshot with better metrics
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => 1000,
            'referring_domains_count' => 80,
            'dofollow_count' => 800,
            'nofollow_count' => 200,
            'avg_domain_trust' => 80.0,
            'anchor_diversity_score' => 80.0,
            'snapshot_date' => now()->toDateString(),
        ]);

        $updatedScore = $scorer->score($project);

        $this->assertGreaterThan($initialScore['overall_score'], $updatedScore['overall_score']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * IN-014: Graceful handling of corrupted snapshot data.
     * 
     * @test
     */
    public function graceful_handling_of_corrupted_snapshot_data(): void
    {
        $project = Project::factory()->create();
        
        // Create snapshot with null values
        BacklinkSnapshot::create([
            'project_id' => $project->id,
            'total_backlinks' => null,
            'referring_domains_count' => null,
            'dofollow_count' => null,
            'nofollow_count' => null,
            'avg_domain_trust' => null,
            'anchor_diversity_score' => null,
            'snapshot_date' => now()->toDateString(),
        ]);

        $scorer = app(BacklinkProfileScorer::class);
        
        // Should not throw exception
        $result = $scorer->score($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
    }

    /**
     * IN-015: Handles project with deleted snapshots.
     * 
     * @test
     */
    public function handles_project_with_deleted_snapshots(): void
    {
        $project = Project::factory()->create();
        
        // No snapshots exist

        $scorer = app(BacklinkProfileScorer::class);
        $result = $scorer->score($project);

        $this->assertEquals(0.0, $result['overall_score']);
        $this->assertEquals(0, $result['component_details']['total_backlinks']);
        $this->assertEquals(0, $result['component_details']['referring_domains']);
    }
}
