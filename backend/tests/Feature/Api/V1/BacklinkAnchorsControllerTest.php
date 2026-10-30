<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Backlink;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for Backlink Anchors API endpoint.
 * 
 * Task: TASK-23-10
 * Test Documentation: TESTS/TEST-23-10.md
 * Endpoint: GET /api/v1/projects/{projectId}/backlinks/anchors
 * 
 * @group integration
 * @group backlinks
 * @group api
 */
class BacklinkAnchorsControllerTest extends TestCase
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
     * AUTH-A-01: Unauthenticated access returns 401.
     */
    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(401);
    }

    /**
     * AUTH-A-02: Project owner can access endpoint.
     */
    public function test_project_owner_can_access_anchors(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
    }

    /**
     * AUTH-A-03: Non-owner cannot access endpoint (403 Forbidden).
     */
    public function test_non_owner_cannot_access_anchors(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(403);
    }

    /**
     * AUTH-A-04: Non-existent project returns 404.
     */
    public function test_non_existent_project_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/99999/backlinks/anchors');
        
        $response->assertStatus(404);
    }

    /**
     * AUTH-A-05: Archived project still returns data (read allowed).
     */
    public function test_archived_project_returns_anchors(): void
    {
        $this->project->update(['archived_at' => now()]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
    }

    // ==================== RESPONSE STRUCTURE TESTS ====================

    /**
     * RES-A-01: Anchor text field present and correct type.
     */
    public function test_anchor_text_field_present(): void
    {
        $this->createBacklinkWithAnchor('best seo tools');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['anchor_text']]]);
        
        $this->assertIsString($response->json('data.0.anchor_text'));
    }

    /**
     * RES-A-02: Backlinks count field present and correct type.
     */
    public function test_backlinks_count_field_present(): void
    {
        $this->createBacklinkWithAnchor('test anchor');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['backlinks_count']]]);
        
        $this->assertIsInt($response->json('data.0.backlinks_count'));
    }

    /**
     * RES-A-03: Percentage field present and in valid range.
     */
    public function test_percentage_field_present(): void
    {
        $this->createBacklinkWithAnchor('test anchor');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['percentage']]]);
        
        $percentage = $response->json('data.0.percentage');
        $this->assertIsFloat($percentage);
        $this->assertGreaterThanOrEqual(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    /**
     * RES-A-04: Is branded flag present and boolean.
     */
    public function test_is_branded_flag_present(): void
    {
        $this->createBacklinkWithAnchor('Brand Name', true, false);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['is_branded']]]);
        
        $this->assertIsBool($response->json('data.0.is_branded'));
    }

    /**
     * RES-A-05: Is exact match flag present and boolean.
     */
    public function test_is_exact_match_flag_present(): void
    {
        $this->createBacklinkWithAnchor('exact match keyword', false, true);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['is_exact_match']]]);
        
        $this->assertIsBool($response->json('data.0.is_exact_match'));
    }

    /**
     * RES-A-06: Data is array of anchor objects.
     */
    public function test_data_is_array_of_anchors(): void
    {
        $this->createBacklinkWithAnchor('anchor 1');
        $this->createBacklinkWithAnchor('anchor 2');
        $this->createBacklinkWithAnchor('anchor 3');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        
        foreach ($data as $anchor) {
            $this->assertArrayHasKey('anchor_text', $anchor);
            $this->assertArrayHasKey('backlinks_count', $anchor);
            $this->assertArrayHasKey('percentage', $anchor);
            $this->assertArrayHasKey('is_branded', $anchor);
            $this->assertArrayHasKey('is_exact_match', $anchor);
        }
    }

    // ==================== DATA INTEGRITY TESTS ====================

    /**
     * DI-A-01: Only returns anchors for the requested project.
     */
    public function test_only_returns_project_anchors(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->createBacklinkWithAnchor('my anchor');
        $this->createBacklinkWithAnchor('other anchor', false, false, $otherProject);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.anchor_text', 'my anchor');
    }

    /**
     * DI-A-02: Cross-project isolation enforced.
     */
    public function test_cross_project_isolation(): void
    {
        $otherUserProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $this->createBacklinkWithAnchor('my anchor');
        $this->createBacklinkWithAnchor('other user anchor', false, false, $otherUserProject);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * DI-A-03: Percentages sum to approximately 100%.
     */
    public function test_percentages_sum_to_100(): void
    {
        // Create backlinks with different anchors
        $this->createMultipleBacklinksWithAnchor('seo tools', 40);
        $this->createMultipleBacklinksWithAnchor('best seo', 30);
        $this->createMultipleBacklinksWithAnchor('click here', 30);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        $anchors = $response->json('data');
        $totalPercentage = array_sum(array_column($anchors, 'percentage'));
        
        // Allow for small floating point differences
        $this->assertEqualsWithDelta(100, $totalPercentage, 0.5);
    }

    /**
     * DI-A-04: Percentage calculated correctly.
     */
    public function test_percentage_calculated_correctly(): void
    {
        // Create 100 total backlinks
        $this->createMultipleBacklinksWithAnchor('popular anchor', 60);
        $this->createMultipleBacklinksWithAnchor('less popular', 40);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        $anchors = $response->json('data');
        
        // Find the popular anchor
        $popularAnchor = collect($anchors)->firstWhere('anchor_text', 'popular anchor');
        $this->assertEquals(60.0, $popularAnchor['percentage']);
        
        // Find the less popular anchor
        $lessPopularAnchor = collect($anchors)->firstWhere('anchor_text', 'less popular');
        $this->assertEquals(40.0, $lessPopularAnchor['percentage']);
    }

    /**
     * DI-A-05: Branded flag accurate for branded anchors.
     */
    public function test_branded_flag_accurate(): void
    {
        $this->createBacklinkWithAnchor('BrandName Official', true, false);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_branded', true)
            ->assertJsonPath('data.0.is_exact_match', false);
    }

    /**
     * DI-A-06: Exact match flag accurate for exact match anchors.
     */
    public function test_exact_match_flag_accurate(): void
    {
        $this->createBacklinkWithAnchor('target keyword', false, true);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_exact_match', true)
            ->assertJsonPath('data.0.is_branded', false);
    }

    /**
     * DI-A-07: Backlinks count matches actual backlinks.
     */
    public function test_backlinks_count_matches_actual(): void
    {
        $this->createMultipleBacklinksWithAnchor('repeated anchor', 15);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.backlinks_count', 15);
    }

    // ==================== SORTING TESTS ====================

    /**
     * SORT-A-01: Default sort by backlinks_count DESC.
     */
    public function test_default_sort_by_backlinks_count(): void
    {
        $this->createMultipleBacklinksWithAnchor('low count', 5);
        $this->createMultipleBacklinksWithAnchor('high count', 50);
        $this->createMultipleBacklinksWithAnchor('medium count', 20);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        $anchors = $response->json('data');
        $this->assertEquals('high count', $anchors[0]['anchor_text']);
        $this->assertEquals('medium count', $anchors[1]['anchor_text']);
        $this->assertEquals('low count', $anchors[2]['anchor_text']);
    }

    /**
     * SORT-A-02: Explicit sort by backlinks_count.
     */
    public function test_explicit_sort_by_backlinks_count(): void
    {
        $this->createMultipleBacklinksWithAnchor('a-low', 5);
        $this->createMultipleBacklinksWithAnchor('b-high', 50);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors?sort=backlinks_count");
        
        $response->assertStatus(200);
        
        $anchors = $response->json('data');
        $this->assertEquals('b-high', $anchors[0]['anchor_text']);
    }

    /**
     * SORT-A-03: Sort by percentage.
     */
    public function test_sort_by_percentage(): void
    {
        $this->createMultipleBacklinksWithAnchor('low percent', 10);
        $this->createMultipleBacklinksWithAnchor('high percent', 90);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors?sort=percentage");
        
        $response->assertStatus(200);
        
        $anchors = $response->json('data');
        $this->assertEquals('high percent', $anchors[0]['anchor_text']);
    }

    /**
     * SORT-A-04: Invalid sort field returns validation error.
     */
    public function test_invalid_sort_field(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors?sort=invalid_field");
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    // ==================== EDGE CASES ====================

    /**
     * EDGE-A-01: No anchors returns empty data array.
     */
    public function test_no_anchors_returns_empty_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * EDGE-A-02: Single anchor returns correct data.
     */
    public function test_single_anchor_returns_correct_data(): void
    {
        $this->createBacklinkWithAnchor('only anchor');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.anchor_text', 'only anchor')
            ->assertJsonPath('data.0.percentage', 100.0);
    }

    /**
     * EDGE-A-03: Long anchor text (500 chars) handled correctly.
     */
    public function test_long_anchor_text_handled(): void
    {
        $longAnchor = str_repeat('a', 500);
        $this->createBacklinkWithAnchor($longAnchor);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.anchor_text', $longAnchor);
    }

    /**
     * EDGE-A-04: Unicode anchor text handled correctly.
     */
    public function test_unicode_anchor_text_handled(): void
    {
        $unicodeAnchor = '点击这里';
        $this->createBacklinkWithAnchor($unicodeAnchor);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.anchor_text', $unicodeAnchor);
    }

    /**
     * EDGE-A-05: Empty anchor text handled correctly.
     */
    public function test_empty_anchor_text_handled(): void
    {
        $this->createBacklinkWithAnchor('');
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        // Empty anchor should be included or handled per business logic
    }

    /**
     * EDGE-A-06: Both flags false works correctly.
     */
    public function test_both_flags_false(): void
    {
        $this->createBacklinkWithAnchor('generic anchor', false, false);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_branded', false)
            ->assertJsonPath('data.0.is_exact_match', false);
    }

    /**
     * EDGE-A-07: Both flags true works correctly (rare but valid).
     */
    public function test_both_flags_true(): void
    {
        $this->createBacklinkWithAnchor('BrandKeyword', true, true);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_branded', true)
            ->assertJsonPath('data.0.is_exact_match', true);
    }

    // ==================== SECURITY TESTS ====================

    /**
     * SEC-A-01: SQL injection in anchor text is safe.
     */
    public function test_sql_injection_in_anchor_safe(): void
    {
        $maliciousAnchor = "'; DROP TABLE backlinks;--";
        $this->createBacklinkWithAnchor($maliciousAnchor);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        // Table should still exist
        $this->assertDatabaseHas('backlinks', ['anchor_text' => $maliciousAnchor]);
    }

    /**
     * SEC-A-02: XSS in anchor text is escaped in response.
     */
    public function test_xss_in_anchor_escaped(): void
    {
        $xssAnchor = '<script>alert("xss")</script>';
        $this->createBacklinkWithAnchor($xssAnchor);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/anchors");
        
        $response->assertStatus(200);
        
        // The anchor should be stored as-is (escaped at output time)
        // JSON response should not execute the script
        $this->assertStringNotContainsString('<script>alert', $response->getContent());
    }

    /**
     * SEC-A-03: IDOR attack prevented.
     */
    public function test_idor_attack_prevented(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}/backlinks/anchors");
        
        $response->assertStatus(403);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Create a backlink with a specific anchor text.
     */
    protected function createBacklinkWithAnchor(
        string $anchorText,
        bool $isBranded = false,
        bool $isExactMatch = false,
        ?Project $project = null
    ): void {
        $targetProject = $project ?? $this->project;
        
        // Create the backlink
        Backlink::create([
            'project_id' => $targetProject->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://' . $targetProject->domain . '/target',
            'anchor_text' => $anchorText,
            'link_type' => 'dofollow',
            'first_seen_at' => now()->subDays(rand(1, 30)),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        // Create or update anchor_texts table entry
        \App\Models\AnchorText::updateOrCreate(
            ['project_id' => $targetProject->id, 'anchor_text' => $anchorText],
            [
                'backlinks_count' => \App\Models\AnchorText::where('project_id', $targetProject->id)
                    ->where('anchor_text', $anchorText)->value('backlinks_count') + 1 ?? 1,
                'is_branded' => $isBranded,
                'is_exact_match' => $isExactMatch,
            ]
        );
    }

    /**
     * Create multiple backlinks with the same anchor text.
     */
    protected function createMultipleBacklinksWithAnchor(string $anchorText, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Backlink::create([
                'project_id' => $this->project->id,
                'source_url' => "https://domain-{$i}.com/page",
                'source_domain' => "domain-{$i}.com",
                'target_url' => 'https://' . $this->project->domain . '/target',
                'anchor_text' => $anchorText,
                'link_type' => 'dofollow',
                'first_seen_at' => now()->subDays(rand(1, 30)),
                'last_checked_at' => now(),
                'is_active' => true,
            ]);
        }
        
        // Create or update anchor_texts table entry
        \App\Models\AnchorText::updateOrCreate(
            ['project_id' => $this->project->id, 'anchor_text' => $anchorText],
            [
                'backlinks_count' => $count,
                'is_branded' => false,
                'is_exact_match' => false,
            ]
        );
    }
}
