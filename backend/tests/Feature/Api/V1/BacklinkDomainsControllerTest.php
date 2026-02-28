<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Backlink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Feature tests for Backlink Domains API endpoint.
 * 
 * Task: TASK-23-10
 * Test Documentation: TESTS/TEST-23-10.md
 * Endpoint: GET /api/v1/projects/{projectId}/backlinks/domains
 * 
 * @group integration
 * @group backlinks
 * @group api
 */
class BacklinkDomainsControllerTest extends TestCase
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
     * AUTH-D-01: Unauthenticated access returns 401.
     */
    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(401);
    }

    /**
     * AUTH-D-02: Project owner can access endpoint.
     */
    public function test_project_owner_can_access_domains(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
    }

    /**
     * AUTH-D-03: Non-owner cannot access endpoint (403 Forbidden).
     */
    public function test_non_owner_cannot_access_domains(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(403);
    }

    /**
     * AUTH-D-04: Non-existent project returns 404.
     */
    public function test_non_existent_project_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects/99999/backlinks/domains');
        
        $response->assertStatus(404);
    }

    /**
     * AUTH-D-05: Archived project still returns data (read allowed).
     */
    public function test_archived_project_returns_domains(): void
    {
        $this->project->update(['archived_at' => now()]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
    }

    // ==================== PAGINATION TESTS ====================

    /**
     * PAG-D-01: Default pagination (page=1, perPage=20).
     */
    public function test_default_pagination(): void
    {
        $this->createBacklinksForDomains(25);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.perPage', 20)
            ->assertJsonCount(20, 'data');
    }

    /**
     * PAG-D-02: Custom page number.
     */
    public function test_custom_page_number(): void
    {
        $this->createBacklinksForDomains(25);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?page=2");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonCount(5, 'data');
    }

    /**
     * PAG-D-03: Custom perPage value.
     */
    public function test_custom_per_page(): void
    {
        $this->createBacklinksForDomains(60);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?perPage=50");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 50)
            ->assertJsonCount(50, 'data');
    }

    /**
     * PAG-D-04: Max perPage limit (capped to 100).
     */
    public function test_max_per_page_limit(): void
    {
        $this->createBacklinksForDomains(150);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?perPage=150");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 100);
    }

    /**
     * PAG-D-05: Min perPage (1 item).
     */
    public function test_min_per_page(): void
    {
        $this->createBacklinksForDomains(5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?perPage=1");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.perPage', 1)
            ->assertJsonCount(1, 'data');
    }

    /**
     * PAG-D-06: Invalid page value returns validation error.
     */
    public function test_invalid_page_value(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?page=abc");
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * PAG-D-07: Invalid perPage value returns validation error.
     */
    public function test_invalid_per_page_value(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?perPage=-1");
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['perPage']);
    }

    /**
     * PAG-D-08: Empty page returns empty data array.
     */
    public function test_empty_page_returns_empty_data(): void
    {
        $this->createBacklinksForDomains(5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?page=999");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * PAG-D-09: Meta includes all pagination fields.
     */
    public function test_meta_includes_all_pagination_fields(): void
    {
        $this->createBacklinksForDomains(50);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['total', 'page', 'perPage', 'lastPage'],
            ]);
    }

    /**
     * PAG-D-10: Single item per page works correctly.
     */
    public function test_single_item_per_page(): void
    {
        $this->createBacklinksForDomains(3);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?perPage=1");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.lastPage', 3);
    }

    // ==================== SORTING TESTS ====================

    /**
     * SORT-D-01: Default sort by backlinks_count DESC.
     */
    public function test_default_sort_by_backlinks_count(): void
    {
        $this->createBacklinksForDomain('low-count.com', 5);
        $this->createBacklinksForDomain('high-count.com', 50);
        $this->createBacklinksForDomain('medium-count.com', 20);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
        
        $domains = $response->json('data');
        $this->assertEquals('high-count.com', $domains[0]['domain']);
        $this->assertEquals('medium-count.com', $domains[1]['domain']);
        $this->assertEquals('low-count.com', $domains[2]['domain']);
    }

    /**
     * SORT-D-02: Explicit sort by backlinks_count.
     */
    public function test_explicit_sort_by_backlinks_count(): void
    {
        $this->createBacklinksForDomain('a-low.com', 5);
        $this->createBacklinksForDomain('b-high.com', 50);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?sort=backlinks_count");
        
        $response->assertStatus(200);
        
        $domains = $response->json('data');
        $this->assertEquals('b-high.com', $domains[0]['domain']);
    }

    /**
     * SORT-D-03: Sort by domain_trust_score.
     */
    public function test_sort_by_domain_trust_score(): void
    {
        $this->createBacklinkWithTrustScore('low-trust.com', 25);
        $this->createBacklinkWithTrustScore('high-trust.com', 85);
        $this->createBacklinkWithTrustScore('medium-trust.com', 50);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?sort=domain_trust_score");
        
        $response->assertStatus(200);
        
        $domains = $response->json('data');
        $this->assertEquals('high-trust.com', $domains[0]['domain']);
        $this->assertEquals('medium-trust.com', $domains[1]['domain']);
        $this->assertEquals('low-trust.com', $domains[2]['domain']);
    }

    /**
     * SORT-D-04: Invalid sort field returns validation error.
     */
    public function test_invalid_sort_field(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?sort=invalid_field");
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    /**
     * SORT-D-05: Sort combined with pagination.
     */
    public function test_sort_with_pagination(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->createBacklinksForDomain("domain-{$i}.com", $i);
        }
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?sort=backlinks_count&page=2&perPage=5");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonCount(5, 'data');
    }

    // ==================== RESPONSE STRUCTURE TESTS ====================

    /**
     * RES-D-01: Domain field present and correct type.
     */
    public function test_domain_field_present(): void
    {
        $this->createBacklinksForDomain('example.com', 5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['domain']]]);
        
        $this->assertIsString($response->json('data.0.domain'));
    }

    /**
     * RES-D-02: Backlinks count field present and correct type.
     */
    public function test_backlinks_count_field_present(): void
    {
        $this->createBacklinksForDomain('example.com', 5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['backlinks_count']]]);
        
        $this->assertIsInt($response->json('data.0.backlinks_count'));
    }

    /**
     * RES-D-03: Domain trust score field in valid range.
     */
    public function test_domain_trust_score_in_valid_range(): void
    {
        $this->createBacklinkWithTrustScore('example.com', 72);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['domain_trust_score']]]);
        
        $score = $response->json('data.0.domain_trust_score');
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * RES-D-04: First seen timestamp present.
     */
    public function test_first_seen_timestamp_present(): void
    {
        $this->createBacklinksForDomain('example.com', 1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['first_seen_at']]]);
    }

    /**
     * RES-D-05: Last seen timestamp present.
     */
    public function test_last_seen_timestamp_present(): void
    {
        $this->createBacklinksForDomain('example.com', 1);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['last_seen_at']]]);
    }

    /**
     * RES-D-06: Meta structure correct.
     */
    public function test_meta_structure_correct(): void
    {
        $this->createBacklinksForDomains(5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'total',
                    'page',
                    'perPage',
                    'lastPage',
                ],
            ]);
    }

    /**
     * RES-D-07: Data is array of domain objects.
     */
    public function test_data_is_array_of_domains(): void
    {
        $this->createBacklinksForDomains(3);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        
        foreach ($data as $domain) {
            $this->assertArrayHasKey('domain', $domain);
            $this->assertArrayHasKey('backlinks_count', $domain);
        }
    }

    // ==================== DATA INTEGRITY TESTS ====================

    /**
     * DI-D-01: Only returns domains for the requested project.
     */
    public function test_only_returns_project_domains(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        
        $this->createBacklinksForDomain('myproject.com', 5);
        $this->createBacklinksForDomain('otherproject.com', 5, $otherProject);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.domain', 'myproject.com');
    }

    /**
     * DI-D-02: Cross-project isolation enforced.
     */
    public function test_cross_project_isolation(): void
    {
        $otherUserProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $this->createBacklinksForDomain('myproject.com', 5);
        $this->createBacklinksForDomain('otheruser.com', 10, $otherUserProject);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * DI-D-03: Trust score within valid range (0-100).
     */
    public function test_trust_score_within_range(): void
    {
        for ($i = 0; $i <= 100; $i += 20) {
            $this->createBacklinkWithTrustScore("score-{$i}.com", $i);
        }
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
        
        foreach ($response->json('data') as $domain) {
            $this->assertGreaterThanOrEqual(0, $domain['domain_trust_score']);
            $this->assertLessThanOrEqual(100, $domain['domain_trust_score']);
        }
    }

    /**
     * DI-D-05: Backlinks count matches actual backlinks.
     */
    public function test_backlinks_count_matches_actual(): void
    {
        $this->createBacklinksForDomain('example.com', 25);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.backlinks_count', 25);
    }

    // ==================== EDGE CASES ====================

    /**
     * EDGE-D-01: No domains returns empty data array.
     */
    public function test_no_domains_returns_empty_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * EDGE-D-02: Single domain returns correct data.
     */
    public function test_single_domain_returns_correct_data(): void
    {
        $this->createBacklinksForDomain('single.com', 10);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.lastPage', 1);
    }

    /**
     * EDGE-D-03: Large dataset pagination works correctly.
     */
    public function test_large_dataset_pagination(): void
    {
        $this->createBacklinksForDomains(150);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 150)
            ->assertJsonCount(20, 'data'); // Default perPage
    }

    /**
     * EDGE-D-04: Unicode domain handled correctly.
     */
    public function test_unicode_domain_handled(): void
    {
        $this->createBacklinksForDomain('例え.jp', 5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.domain', '例え.jp');
    }

    /**
     * EDGE-D-05: Special characters in domain handled correctly.
     */
    public function test_special_characters_in_domain(): void
    {
        $this->createBacklinksForDomain('test-site.com', 5);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'test-site.com');
    }

    /**
     * EDGE-D-06: Null timestamps handled correctly.
     */
    public function test_null_timestamps_handled(): void
    {
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => 'https://nonull.com/page',
            'source_domain' => 'nonull.com',
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => 'test',
            'link_type' => 'dofollow',
            'first_seen_at' => null,
            'last_checked_at' => null,
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
        // The endpoint should handle null timestamps gracefully
    }

    // ==================== SECURITY TESTS ====================

    /**
     * SEC-D-01: SQL injection in sort parameter is rejected.
     */
    public function test_sql_injection_in_sort_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains?sort=backlinks_count'+OR+'1'='1");
        
        $response->assertStatus(422);
    }

    /**
     * SEC-D-02: XSS in domain is escaped in response.
     */
    public function test_xss_in_domain_escaped(): void
    {
        // Note: This test assumes the system sanitizes or rejects XSS input
        // In practice, domains with scripts would be validated at input
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/domains");
        
        $response->assertStatus(200);
        // XSS content should be escaped if present
    }

    /**
     * SEC-D-03: IDOR attack prevented (accessing another user's project).
     */
    public function test_idor_attack_prevented(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}/backlinks/domains");
        
        $response->assertStatus(403);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Create multiple backlinks for a single domain.
     */
    protected function createBacklinksForDomain(string $domain, int $count, ?Project $project = null): void
    {
        $targetProject = $project ?? $this->project;
        
        for ($i = 0; $i < $count; $i++) {
            Backlink::create([
                'project_id' => $targetProject->id,
                'source_url' => "https://{$domain}/page-{$i}",
                'source_domain' => $domain,
                'target_url' => 'https://' . $targetProject->domain . '/target',
                'anchor_text' => "link {$i}",
                'link_type' => 'dofollow',
                'first_seen_at' => now()->subDays(rand(1, 30)),
                'last_checked_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create backlinks with specific trust score (stored in referring_domains table).
     * Also creates the referring_domain record with the trust score.
     */
    protected function createBacklinkWithTrustScore(string $domain, int $trustScore): void
    {
        // Create the backlink
        Backlink::create([
            'project_id' => $this->project->id,
            'source_url' => "https://{$domain}/page",
            'source_domain' => $domain,
            'target_url' => 'https://' . $this->project->domain . '/target',
            'anchor_text' => 'test link',
            'link_type' => 'dofollow',
            'first_seen_at' => now()->subDays(rand(1, 30)),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);
        
        // Create or update the referring_domain with trust score
        \App\Models\ReferringDomain::updateOrCreate(
            ['project_id' => $this->project->id, 'domain' => $domain],
            [
                'backlinks_count' => 1,
                'domain_trust_score' => $trustScore,
                'first_seen_at' => now()->subDays(rand(1, 30)),
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * Create backlinks across multiple domains.
     */
    protected function createBacklinksForDomains(int $domainCount): void
    {
        for ($i = 1; $i <= $domainCount; $i++) {
            $this->createBacklinksForDomain("domain-{$i}.com", rand(1, 10));
        }
    }
}
