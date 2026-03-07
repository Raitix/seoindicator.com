<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for TASK-6-20: Keywords API Workflow
 *
 * Tests the complete keywords API lifecycle:
 * - Create keyword via API
 * - List keywords
 * - Update keyword
 * - Delete keyword
 * - Verify ranking history
 */
class ApiKeywordsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // WORKFLOW TESTS (WF)
    // =========================================================================

    /**
     * WF-001: Full keyword lifecycle
     * Create -> List -> Update -> Delete
     */
    public function test_full_keyword_lifecycle(): void
    {
        // Step 1: Create keyword
        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'seo tools',
                'target_url' => 'https://example.com/seo-tools',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.keyword', 'seo tools');

        $keywordId = $createResponse->json('data.id');

        // Step 2: List keywords - should contain our new keyword
        $listResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keyword', 'seo tools');

        // Step 3: Update keyword
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$keywordId}", [
                'target_url' => 'https://example.com/updated-seo-tools',
                'status' => 'paused',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.target_url', 'https://example.com/updated-seo-tools')
            ->assertJsonPath('data.status', 'paused');

        // Step 4: Delete keyword
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$keywordId}");

        $deleteResponse->assertStatus(204);

        // Verify keyword is deleted/soft-deleted
        $this->assertDatabaseMissing('keywords', [
            'id' => $keywordId,
            'status' => 'active',
        ]);
    }

    /**
     * WF-002: Create keyword and verify ranking history
     */
    public function test_create_keyword_and_verify_ranking_history(): void
    {
        // Create keyword
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'ranking history test',
                'target_url' => 'https://example.com/page',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        $keywordId = $response->json('data.id');

        // Simulate ranking history (multiple ranking records)
        Ranking::factory()->create([
            'keyword_id' => $keywordId,
            'position' => 50,
            'checked_at' => now()->subDays(7),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $keywordId,
            'position' => 35,
            'checked_at' => now()->subDays(3),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $keywordId,
            'position' => 20,
            'checked_at' => now()->subDay(),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $keywordId,
            'position' => 10,
            'checked_at' => now(),
        ]);

        // Fetch keyword with ranking history
        $historyResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keywordId}?include=rankingHistory");

        $historyResponse->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 10)
            ->assertJsonPath('data.previousPosition', 20);

        // Verify position change calculation
        $positionChange = $historyResponse->json('data.positionChange');
        $this->assertEquals(-10, $positionChange); // Negative means improved (went up)
    }

    /**
     * WF-003: Multiple keywords with different statuses
     */
    public function test_multiple_keywords_with_different_statuses(): void
    {
        // Create keywords with different statuses
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'active keyword',
                'status' => 'active',
            ]);

        $pausedResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'paused keyword',
                'status' => 'paused',
            ]);

        // List all keywords
        $listAll = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $listAll->assertStatus(200)->assertJsonCount(2, 'data');

        // Filter by status
        $listActive = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?status=active");

        $listActive->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keyword', 'active keyword');

        // Update status
        $pausedId = $pausedResponse->json('data.id');
        $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$pausedId}", ['status' => 'active'])
            ->assertStatus(200);

        // Now both should be active
        $listActive = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?status=active");

        $listActive->assertStatus(200)->assertJsonCount(2, 'data');
    }

    /**
     * WF-004: Keyword with SERP features
     */
    public function test_keyword_with_serp_features(): void
    {
        // Create keyword
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'featured snippet test',
                'search_engine' => 'google',
            ]);

        $keywordId = $response->json('data.id');

        // Add SERP features
        SerpFeature::create([
            'keyword_id' => $keywordId,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);

        SerpFeature::create([
            'keyword_id' => $keywordId,
            'feature_type' => 'local_pack',
            'is_present' => false,
            'checked_at' => now(),
        ]);

        // Fetch keyword with SERP features
        $keywordResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keywordId}");

        $keywordResponse->assertStatus(200);

        // Verify SERP features are included
        $serpFeatures = $keywordResponse->json('data.serpFeatures');
        $this->assertTrue($serpFeatures['featured_snippet']);
        $this->assertFalse($serpFeatures['local_pack']);
    }

    // =========================================================================
    // CREATE WORKFLOW TESTS (CW)
    // =========================================================================

    /**
     * CW-001: Create with minimal data
     */
    public function test_create_with_minimal_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'minimal keyword',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', 'minimal keyword');

        // Verify defaults applied
        $this->assertDatabaseHas('keywords', [
            'keyword' => 'minimal keyword',
            'project_id' => $this->project->id,
        ]);
    }

    /**
     * CW-002: Create with all fields
     */
    public function test_create_with_all_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'full keyword',
                'target_url' => 'https://example.com/target',
                'search_engine' => 'google',
                'location' => 'uk',
                'device' => 'mobile',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', 'full keyword')
            ->assertJsonPath('data.targetUrl', 'https://example.com/target')
            ->assertJsonPath('data.searchEngine', 'google')
            ->assertJsonPath('data.location', 'uk')
            ->assertJsonPath('data.device', 'mobile');
    }

    /**
     * CW-003: Create duplicate keyword should fail
     */
    public function test_create_duplicate_keyword_should_fail(): void
    {
        // Create first keyword
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'duplicate test',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        // Try to create duplicate
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'duplicate test',
                'search_engine' => 'google',
                'location' => 'us',
                'device' => 'desktop',
            ]);

        $response->assertStatus(409); // Conflict
    }

    /**
     * CW-004: Create keyword for another user's project should fail
     */
    public function test_create_keyword_for_another_users_project(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/keywords", [
                'keyword' => 'unauthorized keyword',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // LIST WORKFLOW TESTS (LW)
    // =========================================================================

    /**
     * LW-001: List with pagination
     */
    public function test_list_with_pagination(): void
    {
        // Create 25 keywords
        for ($i = 1; $i <= 25; $i++) {
            Keyword::factory()->create([
                'project_id' => $this->project->id,
                'keyword' => "keyword {$i}",
            ]);
        }

        // First page
        $page1 = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?perPage=10&page=1");

        $page1->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.page', 1);

        // Second page
        $page2 = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?perPage=10&page=2");

        $page2->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 2);

        // Third page
        $page3 = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?perPage=10&page=3");

        $page3->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.page', 3);
    }

    /**
     * LW-002: List sorted by position
     */
    public function test_list_sorted_by_position(): void
    {
        $keyword1 = Keyword::factory()->create(['project_id' => $this->project->id]);
        $keyword2 = Keyword::factory()->create(['project_id' => $this->project->id]);
        $keyword3 = Keyword::factory()->create(['project_id' => $this->project->id]);

        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 30]);
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 5]);
        Ranking::factory()->create(['keyword_id' => $keyword3->id, 'position' => 15]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?sortBy=position&sortOrder=asc");

        $response->assertStatus(200);

        $positions = collect($response->json('data'))->pluck('currentPosition')->toArray();
        $this->assertEquals([5, 15, 30], $positions);
    }

    /**
     * LW-003: List filtered by search term
     */
    public function test_list_filtered_by_search_term(): void
    {
        Keyword::factory()->create([
            'project_id' => $this->project->id,
            'keyword' => 'seo tools software',
        ]);
        Keyword::factory()->create([
            'project_id' => $this->project->id,
            'keyword' => 'keyword research',
        ]);
        Keyword::factory()->create([
            'project_id' => $this->project->id,
            'keyword' => 'best seo practices',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords?search=seo");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * LW-004: List only shows user's keywords
     */
    public function test_list_only_shows_users_keywords(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        // Create keywords for both users
        Keyword::factory()->count(5)->create(['project_id' => $this->project->id]);
        Keyword::factory()->count(3)->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    // =========================================================================
    // UPDATE WORKFLOW TESTS (UW)
    // =========================================================================

    /**
     * UW-001: Update target URL
     */
    public function test_update_target_url(): void
    {
        $keyword = Keyword::factory()->create([
            'project_id' => $this->project->id,
            'target_url' => 'https://old-url.com',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'target_url' => 'https://new-url.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.targetUrl', 'https://new-url.com');
    }

    /**
     * UW-002: Update status
     */
    public function test_update_status(): void
    {
        $keyword = Keyword::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'status' => 'paused',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');

        // Verify database updated
        $this->assertDatabaseHas('keywords', [
            'id' => $keyword->id,
            'status' => 'paused',
        ]);
    }

    /**
     * UW-003: Update multiple fields at once
     */
    public function test_update_multiple_fields(): void
    {
        $keyword = Keyword::factory()->create([
            'project_id' => $this->project->id,
            'target_url' => 'https://old.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'target_url' => 'https://new.com',
                'status' => 'paused',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.targetUrl', 'https://new.com')
            ->assertJsonPath('data.status', 'paused');
    }

    /**
     * UW-004: Cannot update another user's keyword
     */
    public function test_cannot_update_another_users_keyword(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);
        $keyword = Keyword::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/keywords/{$keyword->id}", [
                'status' => 'paused',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // DELETE WORKFLOW TESTS (DW)
    // =========================================================================

    /**
     * DW-001: Delete keyword
     */
    public function test_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(204);
    }

    /**
     * DW-002: Delete keyword keeps history
     */
    public function test_delete_keyword_keeps_history(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
        Ranking::factory()->count(5)->create(['keyword_id' => $keyword->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$keyword->id}?keepHistory=true");

        $response->assertStatus(204);

        // Rankings should still exist
        $this->assertEquals(5, Ranking::where('keyword_id', $keyword->id)->count());
    }

    /**
     * DW-003: Delete keyword removes history
     */
    public function test_delete_keyword_removes_history(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
        Ranking::factory()->count(5)->create(['keyword_id' => $keyword->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$keyword->id}?keepHistory=false");

        $response->assertStatus(204);

        // Rankings should be deleted
        $this->assertEquals(0, Ranking::where('keyword_id', $keyword->id)->count());
    }

    /**
     * DW-004: Cannot delete another user's keyword
     */
    public function test_cannot_delete_another_users_keyword(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);
        $keyword = Keyword::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // RANKING HISTORY TESTS (RH)
    // =========================================================================

    /**
     * RH-001: Ranking history tracks position changes
     */
    public function test_ranking_history_tracks_position_changes(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

        // Create ranking history showing improvement
        $positions = [100, 75, 50, 30, 15, 10, 5, 3];
        foreach ($positions as $index => $position) {
            Ranking::factory()->create([
                'keyword_id' => $keyword->id,
                'position' => $position,
                'checked_at' => now()->subDays(count($positions) - $index - 1),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 3);

        // Verify position change
        $change = $response->json('data.positionChange');
        $this->assertEquals(-2, $change); // Improved from 5 to 3
    }

    /**
     * RH-002: Ranking history for new keyword
     */
    public function test_ranking_history_for_new_keyword(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200);
        // New keyword should have no current position
        $this->assertNull($response->json('data.currentPosition'));
    }

    /**
     * RH-003: Ranking history shows negative change (worsening)
     */
    public function test_ranking_history_shows_negative_change(): void
    {
        $keyword = Keyword::factory()->create(['project_id' => $this->project->id]);

        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 15,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 15);

        // Position worsened (positive change = dropped)
        $change = $response->json('data.positionChange');
        $this->assertEquals(10, $change); // Dropped from 5 to 15
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Keyword with special characters
     */
    public function test_keyword_with_special_characters(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => 'seo & marketing "best practices" 2024',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', 'seo & marketing "best practices" 2024');
    }

    /**
     * EC-002: Keyword with unicode
     */
    public function test_keyword_with_unicode(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => '検索エンジン最適化 🔍',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.keyword', '検索エンジン最適化 🔍');
    }

    /**
     * EC-003: Empty keyword list
     */
    public function test_empty_keyword_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/keywords");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * EC-004: Very long keyword
     */
    public function test_very_long_keyword(): void
    {
        $longKeyword = str_repeat('a', 500);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => $longKeyword,
            ]);

        // Should either truncate or reject
        $this->assertContains($response->status(), [201, 422]);
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: SQL injection protection
     */
    public function test_sql_injection_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => "'; DROP TABLE keywords; --",
            ]);

        // Should accept the keyword as-is (not execute SQL)
        $response->assertStatus(201);
        $this->assertDatabaseHas('keywords', [
            'keyword' => "'; DROP TABLE keywords; --",
        ]);
    }

    /**
     * SEC-002: XSS protection
     */
    public function test_xss_protection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/keywords", [
                'keyword' => '<script>alert("xss")</script>',
            ]);

        $response->assertStatus(201);

        // Keyword should be stored, but output should be sanitized
        $keywordId = $response->json('data.id');
        $getResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keywordId}");

        // Response should not contain executable script
        $keyword = $getResponse->json('data.keyword');
        $this->assertStringNotContainsString('<script>', $keyword);
    }
}
