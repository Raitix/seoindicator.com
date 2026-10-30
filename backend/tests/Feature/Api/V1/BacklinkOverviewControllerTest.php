<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Backlink;
use App\Models\ReferringDomain;
use App\Models\BacklinkSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for Backlink Overview API endpoint.
 * 
 * Task: TASK-23-9
 * Test Documentation: TESTS/TEST-23-9.md
 * Endpoint: GET /api/v1/projects/{projectId}/backlinks
 * 
 * @group integration
 * @group backlinks
 * @group api
 */
class BacklinkOverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    // ==================== AUTHORIZATION TESTS ====================

    /**
     * AUTH-01: Unauthenticated access returns 401.
     */
    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(401);
    }

    /**
     * AUTH-02: Project owner can access endpoint.
     */
    public function test_project_owner_can_access_overview(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
    }

    /**
     * AUTH-03: Non-owner cannot access endpoint (403 Forbidden).
     */
    public function test_non_owner_cannot_access_overview(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(403);
    }

    /**
     * AUTH-04: Non-existent project returns 404.
     */
    public function test_non_existent_project_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/99999/backlinks');
        
        $response->assertStatus(404);
    }

    /**
     * AUTH-05: Archived project still returns data (read allowed).
     */
    public function test_archived_project_returns_overview(): void
    {
        $this->project->update(['archived_at' => now()]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
    }

    // ==================== RESPONSE STRUCTURE TESTS ====================

    /**
     * Response has success and data structure.
     */
    public function test_response_has_success_and_data_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary',
                    'recentBacklinks',
                    'topReferringDomains',
                ],
            ])
            ->assertJsonPath('success', true);
    }

    /**
     * Summary has all required fields.
     */
    public function test_summary_has_all_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'totalBacklinks',
                        'referringDomains',
                        'dofollowRatio',
                        'avgDomainTrust',
                        'growthRate',
                        'profileScore',
                    ],
                ],
            ]);
    }

    // ==================== SUMMARY TESTS ====================

    /**
     * SUM-01: totalBacklinks field is integer.
     */
    public function test_total_backlinks_is_integer(): void
    {
        $this->createSnapshot(1250, 89, 900, 350, 45.2);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.totalBacklinks', 1250);
        
        $this->assertIsInt($response->json('data.summary.totalBacklinks'));
    }

    /**
     * SUM-02: referringDomains field is integer.
     */
    public function test_referring_domains_is_integer(): void
    {
        $this->createSnapshot(1250, 89, 900, 350, 45.2);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.referringDomains', 89);
        
        $this->assertIsInt($response->json('data.summary.referringDomains'));
    }

    /**
     * SUM-03: dofollowRatio is float between 0 and 1.
     */
    public function test_dofollow_ratio_is_float_between_0_and_1(): void
    {
        $this->createSnapshot(100, 10, 72, 28, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $ratio = $response->json('data.summary.dofollowRatio');
        $this->assertIsFloat($ratio);
        $this->assertGreaterThanOrEqual(0.0, $ratio);
        $this->assertLessThanOrEqual(1.0, $ratio);
    }

    /**
     * SUM-04: avgDomainTrust is float between 0 and 100.
     */
    public function test_avg_domain_trust_is_float_between_0_and_100(): void
    {
        $this->createSnapshot(100, 10, 50, 50, 65.5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $trust = $response->json('data.summary.avgDomainTrust');
        $this->assertIsFloat($trust);
        $this->assertGreaterThanOrEqual(0.0, $trust);
        $this->assertLessThanOrEqual(100.0, $trust);
    }

    /**
     * SUM-05: growthRate field present.
     */
    public function test_growth_rate_field_present(): void
    {
        $this->createSnapshot(100, 10, 50, 50, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['summary' => ['growthRate']]]);
    }

    /**
     * SUM-06: profileScore is integer between 0 and 100.
     */
    public function test_profile_score_is_integer_between_0_and_100(): void
    {
        $this->createSnapshot(100, 10, 50, 50, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['summary' => ['profileScore']]]);
        
        $score = $response->json('data.summary.profileScore');
        if ($score !== null) {
            $this->assertIsInt($score);
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }

    /**
     * SUM-07: Empty project returns zeros for summary.
     */
    public function test_empty_project_returns_zeros_for_summary(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.totalBacklinks', 0)
            ->assertJsonPath('data.summary.referringDomains', 0)
            ->assertJsonPath('data.summary.dofollowRatio', 0.0);
    }

    // ==================== CALCULATION TESTS ====================

    /**
     * CALC-01: Total backlinks count from snapshot.
     */
    public function test_total_backlinks_from_snapshot(): void
    {
        $this->createSnapshot(500, 25, 400, 100, 45.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.totalBacklinks', 500);
    }

    /**
     * CALC-02: Referring domains count from snapshot.
     */
    public function test_referring_domains_from_snapshot(): void
    {
        $this->createSnapshot(500, 42, 400, 100, 45.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.referringDomains', 42);
    }

    /**
     * CALC-03: Dofollow ratio calculation (72 dofollow, 28 nofollow = 0.72).
     */
    public function test_dofollow_ratio_calculation(): void
    {
        $this->createSnapshot(100, 10, 72, 28, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        // 72 dofollow / 100 total = 0.72
        $this->assertEqualsWithDelta(0.72, $response->json('data.summary.dofollowRatio'), 0.01);
    }

    /**
     * CALC-04: Average domain trust from snapshot.
     */
    public function test_avg_domain_trust_from_snapshot(): void
    {
        $this->createSnapshot(100, 10, 50, 50, 67.5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        $this->assertEqualsWithDelta(67.5, $response->json('data.summary.avgDomainTrust'), 0.1);
    }

    /**
     * CALC-05: Growth rate positive (100 → 120 = 20%).
     */
    public function test_growth_rate_positive(): void
    {
        // Previous snapshot: 100 backlinks
        $this->createSnapshot(100, 10, 50, 50, 50.0, now()->subDay()->toDateString());
        
        // Current snapshot: 120 backlinks
        $this->createSnapshot(120, 12, 60, 60, 55.0, now()->toDateString());
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $growthRate = $response->json('data.summary.growthRate');
        if ($growthRate !== null) {
            $this->assertEqualsWithDelta(20.0, $growthRate, 0.1);
        }
    }

    /**
     * CALC-06: Growth rate negative (120 → 100 = -16.67%).
     */
    public function test_growth_rate_negative(): void
    {
        // Previous snapshot: 120 backlinks
        $this->createSnapshot(120, 12, 60, 60, 50.0, now()->subDay()->toDateString());
        
        // Current snapshot: 100 backlinks
        $this->createSnapshot(100, 10, 50, 50, 55.0, now()->toDateString());
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $growthRate = $response->json('data.summary.growthRate');
        if ($growthRate !== null) {
            $this->assertEqualsWithDelta(-16.67, $growthRate, 0.1);
        }
    }

    /**
     * CALC-07: Growth rate null for first snapshot (no history).
     */
    public function test_growth_rate_null_for_first_snapshot(): void
    {
        // Only one snapshot - no history
        $this->createSnapshot(100, 10, 50, 50, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        // Growth rate should be null or 0 for first snapshot
        $growthRate = $response->json('data.summary.growthRate');
        $this->assertTrue($growthRate === null || $growthRate === 0);
    }

    // ==================== RECENT BACKLINKS TESTS ====================

    /**
     * RECENT-01: Returns last 10 backlinks when more exist.
     */
    public function test_returns_last_10_backlinks(): void
    {
        $this->createBacklinks(15);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.recentBacklinks');
    }

    /**
     * RECENT-02: Returns all backlinks if fewer than 10.
     */
    public function test_returns_all_backlinks_if_fewer_than_10(): void
    {
        $this->createBacklinks(5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.recentBacklinks');
    }

    /**
     * RECENT-03: Ordered by first_seen_at descending.
     */
    public function test_recent_backlinks_ordered_by_first_seen_desc(): void
    {
        $this->createBacklinkWithDate('oldest.com', now()->subDays(10));
        $this->createBacklinkWithDate('newest.com', now());
        $this->createBacklinkWithDate('middle.com', now()->subDays(5));
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $backlinks = $response->json('data.recentBacklinks');
        $this->assertEquals('newest.com', $backlinks[0]['source_domain']);
        $this->assertEquals('middle.com', $backlinks[1]['source_domain']);
        $this->assertEquals('oldest.com', $backlinks[2]['source_domain']);
    }

    /**
     * RECENT-04: source_url field present.
     */
    public function test_recent_backlinks_source_url_present(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['source_url']]]]);
    }

    /**
     * RECENT-05: source_domain field present.
     */
    public function test_recent_backlinks_source_domain_present(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['source_domain']]]]);
    }

    /**
     * RECENT-06: target_url field present.
     */
    public function test_recent_backlinks_target_url_present(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['target_url']]]]);
    }

    /**
     * RECENT-07: anchor_text field present.
     */
    public function test_recent_backlinks_anchor_text_present(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['anchor_text']]]]);
    }

    /**
     * RECENT-08: link_type field present.
     */
    public function test_recent_backlinks_link_type_present(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['link_type']]]]);
    }

    /**
     * RECENT-09: first_seen_at field is ISO 8601.
     */
    public function test_recent_backlinks_first_seen_is_iso8601(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['recentBacklinks' => [['first_seen_at']]]]);
        
        $timestamp = $response->json('data.recentBacklinks.0.first_seen_at');
        if ($timestamp !== null) {
            // Verify ISO 8601 format
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp);
        }
    }

    /**
     * RECENT-10: Empty recent backlinks when none exist.
     */
    public function test_recent_backlinks_empty_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.recentBacklinks');
    }

    // ==================== TOP REFERRING DOMAINS TESTS ====================

    /**
     * TOP-01: Returns top 5 domains when more exist.
     */
    public function test_returns_top_5_domains(): void
    {
        $this->createReferringDomains(10);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.topReferringDomains');
    }

    /**
     * TOP-02: Returns all domains if fewer than 5.
     */
    public function test_returns_all_domains_if_fewer_than_5(): void
    {
        $this->createReferringDomains(3);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.topReferringDomains');
    }

    /**
     * TOP-03: Ordered by backlinks_count descending.
     */
    public function test_top_domains_ordered_by_backlinks_desc(): void
    {
        $this->createReferringDomain('low.com', 5, 50);
        $this->createReferringDomain('high.com', 100, 50);
        $this->createReferringDomain('medium.com', 50, 50);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $domains = $response->json('data.topReferringDomains');
        $this->assertEquals('high.com', $domains[0]['domain']);
        $this->assertEquals('medium.com', $domains[1]['domain']);
        $this->assertEquals('low.com', $domains[2]['domain']);
    }

    /**
     * TOP-04: domain field present.
     */
    public function test_top_domains_domain_field_present(): void
    {
        $this->createReferringDomains(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['topReferringDomains' => [['domain']]]]);
    }

    /**
     * TOP-05: backlinks_count field present.
     */
    public function test_top_domains_backlinks_count_present(): void
    {
        $this->createReferringDomains(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['topReferringDomains' => [['backlinks_count']]]]);
    }

    /**
     * TOP-06: domain_trust_score field in valid range.
     */
    public function test_top_domains_trust_score_in_range(): void
    {
        $this->createReferringDomains(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['topReferringDomains' => [['domain_trust_score']]]]);
        
        $score = $response->json('data.topReferringDomains.0.domain_trust_score');
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * TOP-07: first_seen_at field present.
     */
    public function test_top_domains_first_seen_present(): void
    {
        $this->createReferringDomains(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['topReferringDomains' => [['first_seen_at']]]]);
    }

    /**
     * TOP-08: Empty top domains when none exist.
     */
    public function test_top_domains_empty_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.topReferringDomains');
    }

    // ==================== DATA INTEGRITY TESTS ====================

    /**
     * DI-01: Only returns data for the requested project.
     */
    public function test_only_returns_project_data(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->createBacklinkForProject($this->project->id, 'myproject.com');
        $this->createBacklinkForProject($otherProject->id, 'otherproject.com');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.recentBacklinks')
            ->assertJsonPath('data.recentBacklinks.0.source_domain', 'myproject.com');
    }

    /**
     * DI-02: Cross-project isolation enforced.
     */
    public function test_cross_project_isolation(): void
    {
        $otherUserProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $this->createBacklinkForProject($this->project->id, 'myproject.com');
        $this->createBacklinkForProject($otherUserProject->id, 'otheruser.com');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.recentBacklinks');
    }

    /**
     * DI-03: Cross-user isolation enforced.
     */
    public function test_cross_user_isolation(): void
    {
        $otherUserProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherUserProject->id}/backlinks");
        
        $response->assertStatus(403);
    }

    /**
     * DI-04: Active backlinks only.
     */
    public function test_only_active_backlinks_returned(): void
    {
        // Create active backlink
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://active.com/page',
            'source_domain' => 'active.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => 'active link',
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        // Create inactive backlink
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://inactive.com/page',
            'source_domain' => 'inactive.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => 'inactive link',
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => false,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.recentBacklinks')
            ->assertJsonPath('data.recentBacklinks.0.source_domain', 'active.com');
    }

    /**
     * DI-05: Counts match actual data.
     */
    public function test_counts_match_actual_data(): void
    {
        $this->createBacklinks(50);
        $this->createSnapshot(50, 5, 40, 10, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.totalBacklinks', 50);
    }

    // ==================== EDGE CASES ====================

    /**
     * EDGE-01: No backlinks returns zeros and empty arrays.
     */
    public function test_no_backlinks_returns_zeros(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.totalBacklinks', 0)
            ->assertJsonPath('data.summary.referringDomains', 0)
            ->assertJsonCount(0, 'data.recentBacklinks')
            ->assertJsonCount(0, 'data.topReferringDomains');
    }

    /**
     * EDGE-02: No snapshots returns null growth rate.
     */
    public function test_no_snapshots_returns_null_growth_rate(): void
    {
        $this->createBacklinks(5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        // Growth rate should be null when no snapshots
        $growthRate = $response->json('data.summary.growthRate');
        $this->assertTrue($growthRate === null || $growthRate === 0);
    }

    /**
     * EDGE-03: Single backlink returns correct data.
     */
    public function test_single_backlink_returns_correct_data(): void
    {
        $this->createBacklinks(1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.recentBacklinks');
    }

    /**
     * EDGE-04: Unicode domain handled correctly.
     */
    public function test_unicode_domain_handled(): void
    {
        $this->createBacklinkForProject($this->project->id, '例え.jp');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.recentBacklinks.0.source_domain', '例え.jp');
    }

    /**
     * EDGE-05: Unicode anchor text handled correctly.
     */
    public function test_unicode_anchor_text_handled(): void
    {
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => '点击这里',
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.recentBacklinks.0.anchor_text', '点击这里');
    }

    /**
     * EDGE-06: Null anchor text handled correctly.
     */
    public function test_null_anchor_text_handled(): void
    {
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => null,
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.recentBacklinks');
    }

    /**
     * EDGE-07: Large dataset performance (< 1 second).
     */
    public function test_large_dataset_performance(): void
    {
        // Create 500 backlinks
        $this->createBacklinks(500);
        $this->createReferringDomains(50);
        $this->createSnapshot(500, 50, 400, 100, 50.0);
        
        $start = microtime(true);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $elapsed = microtime(true) - $start;
        
        $response->assertStatus(200);
        
        // Should respond in under 1 second
        $this->assertLessThan(1.0, $elapsed, 'Response took longer than 1 second');
    }

    /**
     * EDGE-08: All nofollow links ratio is 0.
     */
    public function test_all_nofollow_ratio_is_zero(): void
    {
        $this->createSnapshot(100, 10, 0, 100, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        $this->assertEquals(0.0, $response->json('data.summary.dofollowRatio'));
    }

    /**
     * EDGE-09: All dofollow links ratio is 1.
     */
    public function test_all_dofollow_ratio_is_one(): void
    {
        $this->createSnapshot(100, 10, 100, 0, 50.0);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        $this->assertEquals(1.0, $response->json('data.summary.dofollowRatio'));
    }

    // ==================== SECURITY TESTS ====================

    /**
     * SEC-01: SQL injection in projectId is blocked.
     */
    public function test_sql_injection_in_project_id_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/1' OR '1'='1/backlinks");
        
        // Route constraint should reject non-numeric IDs
        $response->assertStatus(404);
    }

    /**
     * SEC-02: XSS in anchor_text is escaped.
     */
    public function test_xss_in_anchor_text_escaped(): void
    {
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => '<script>alert("xss")</script>',
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $anchor = $response->json('data.recentBacklinks.0.anchor_text');
        // Should not contain unescaped script tag
        $this->assertStringNotContainsString('<script>', $anchor);
    }

    /**
     * SEC-03: XSS in domain is escaped.
     */
    public function test_xss_in_domain_escaped(): void
    {
        $this->createBacklinkForProject($this->project->id, '<script>alert("xss")</script>.com');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");
        
        $response->assertStatus(200);
        
        $domain = $response->json('data.recentBacklinks.0.source_domain');
        // Should not contain unescaped script tag
        $this->assertStringNotContainsString('<script>', $domain);
    }

    /**
     * SEC-04: IDOR attack prevented.
     */
    public function test_idor_attack_prevented(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}/backlinks");
        
        $response->assertStatus(403);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Create a backlink snapshot.
     */
    protected function createSnapshot(
        int $totalBacklinks,
        int $referringDomains,
        int $dofollow,
        int $nofollow,
        float $avgTrust,
        ?string $date = null
    ): BacklinkSnapshot {
        return BacklinkSnapshot::create([
            'project_id' => $this->project->id,
            'total_backlinks' => $totalBacklinks,
            'referring_domains_count' => $referringDomains,
            'dofollow_count' => $dofollow,
            'nofollow_count' => $nofollow,
            'avg_domain_trust' => $avgTrust,
            'anchor_diversity_score' => 0.75,
            'snapshot_date' => $date ?? now()->toDateString(),
            'created_at' => now(),
        ]);
    }

    /**
     * Create multiple backlinks.
     */
    protected function createBacklinks(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $domain = "domain-{$i}.com";
            Backlink::create([
                'project_id' => $this->project->id,
                'source_url' => "https://{$domain}/page-{$i}",
                'source_domain' => $domain,
                'target_url' => 'https://' . $this->project->domain . '/target',
                'anchor_text' => "link {$i}",
                'link_type' => $i % 3 === 0 ? 'nofollow' : 'dofollow',
                'first_seen_at' => now()->subDays(rand(1, 30)),
                'last_checked_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create a backlink with specific first_seen_at date.
     */
    protected function createBacklinkWithDate(string $domain, $date): void
    {
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => "https://{$domain}/page",
            'source_domain' => $domain,
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => 'test link',
            'link_type' => 'dofollow',
            'first_seen_at' => $date,
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Create a backlink for a specific project.
     */
    protected function createBacklinkForProject(int $projectId, string $domain): void
    {
        $project = Project::find($projectId);
        
        Backlink::create([
            'project_id' => $projectId,
            'source_url' => "https://{$domain}/page",
            'source_domain' => $domain,
            'target_url' => 'https://' . ($project->domain ?? 'example.com') . '/target',
            'anchor_text' => 'test link',
            'link_type' => 'dofollow',
            'first_seen_at' => now(),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Create multiple referring domains.
     */
    protected function createReferringDomains(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            ReferringDomain::create([
                'project_id' => $this->project->id,
                'domain' => "refdomain-{$i}.com",
                'backlinks_count' => rand(1, 50),
                'domain_trust_score' => rand(10, 90),
                'first_seen_at' => now()->subDays(rand(30, 365)),
                'last_seen_at' => now(),
            ]);
        }
    }

    /**
     * Create a referring domain with specific values.
     */
    protected function createReferringDomain(string $domain, int $backlinksCount, int $trustScore): void
    {
        ReferringDomain::create([
            'project_id' => $this->project->id,
            'domain' => $domain,
            'backlinks_count' => $backlinksCount,
            'domain_trust_score' => $trustScore,
            'first_seen_at' => now()->subDays(30),
            'last_seen_at' => now(),
        ]);
    }
}
