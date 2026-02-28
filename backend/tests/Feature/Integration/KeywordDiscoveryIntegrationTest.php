<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\KeywordSuggestion;
use App\Models\DismissedSuggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

/**
 * Integration Tests for Keyword Discovery Workflow
 * Task: TASK-5-14
 * Specification: SPEC-005
 * 
 * Tests the complete keyword discovery workflow including:
 * - Generate suggestions → store in database
 * - List suggestions → pagination and filtering
 * - Track suggestion → keyword creation and suggestion update
 * - Dismiss suggestion → marking as dismissed
 * - Authorization → user isolation
 */
class KeywordDiscoveryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Site $site;
    private Site $otherUserSite;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherUserSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
    }

    // ============================================
    // Generate Suggestions Workflow
    // ============================================

    /** @test */
    public function generate_suggestions_stores_in_database(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['best seo tools', 'free seo tools', 'seo tools 2026'],
            ]),
        ]);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['success', 'data' => ['jobId', 'status']]);

        // Verify suggestions stored
        $this->assertDatabaseHas('keyword_suggestions', [
            'site_id' => $this->site->id,
            'source' => 'suggest',
        ]);
    }

    /** @test */
    public function generate_with_question_source_only(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['question'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(202);

        // Only question keywords should be stored
        $this->assertDatabaseMissing('keyword_suggestions', [
            'site_id' => $this->site->id,
            'source' => 'suggest',
        ]);
    }

    /** @test */
    public function generate_with_suggest_source_only(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['best seo tools', 'free seo tools'],
            ]),
        ]);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(202);

        $suggestions = KeywordSuggestion::where('site_id', $this->site->id)
            ->where('source', 'suggest')
            ->count();
        
        $this->assertGreaterThan(0, $suggestions);
    }

    /** @test */
    public function generate_with_both_sources(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['best seo tools'],
            ]),
        ]);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest', 'question'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(202);

        // Both types should be stored, deduplicated
        $suggestCount = KeywordSuggestion::where('site_id', $this->site->id)
            ->where('source', 'suggest')
            ->count();
        $questionCount = KeywordSuggestion::where('site_id', $this->site->id)
            ->where('source', 'question')
            ->count();
        
        $this->assertGreaterThan(0, $suggestCount + $questionCount);
    }

    /** @test */
    public function generate_respects_max_results(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['suggestion 1', 'suggestion 2', 'suggestion 3', 'suggestion 4', 'suggestion 5'],
            ]),
        ]);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest'],
            'maxResults' => 3,
        ]);

        $response->assertStatus(202);

        // At most maxResults suggestions should be stored
        $count = KeywordSuggestion::where('site_id', $this->site->id)->count();
        $this->assertLessThanOrEqual(3, $count);
    }

    // ============================================
    // List Suggestions Workflow
    // ============================================

    /** @test */
    public function list_suggestions_with_pagination(): void
    {
        $this->actingAs($this->user);

        // Create 50 suggestions
        KeywordSuggestion::factory()->count(50)->create(['site_id' => $this->site->id]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions?page=2&perPage=10");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta' => ['total', 'page', 'perPage']])
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10);
    }

    /** @test */
    public function list_with_source_filter(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $this->site->id,
            'source' => 'suggest',
        ]);
        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $this->site->id,
            'source' => 'question',
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions?source=question");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $suggestion) {
            $this->assertEquals('question', $suggestion['source']);
        }
    }

    /** @test */
    public function list_excludes_dismissed_by_default(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => false,
            'keyword' => 'active suggestion',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => true,
            'keyword' => 'dismissed suggestion',
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonMissing(['keyword' => 'dismissed suggestion']);
    }

    /** @test */
    public function list_excludes_tracking_by_default(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_tracking' => false,
            'keyword' => 'not tracking',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_tracking' => true,
            'keyword' => 'tracking',
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonMissing(['keyword' => 'tracking']);
    }

    /** @test */
    public function list_sorted_by_opportunity_score_desc(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 50,
            'keyword' => 'medium score',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 90,
            'keyword' => 'high score',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 30,
            'keyword' => 'low score',
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('high score', $data[0]['keyword']);
    }

    // ============================================
    // Track Suggestion Workflow
    // ============================================

    /** @test */
    public function track_single_suggestion(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
            'is_tracking' => false,
        ]);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track"
        );

        $response->assertStatus(201);

        // Verify keyword created
        $this->assertDatabaseHas('keywords', [
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
            'status' => 'active',
        ]);

        // Verify suggestion updated
        $this->assertTrue($suggestion->fresh()->is_tracking);
    }

    /** @test */
    public function track_already_tracking_is_idempotent(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
            'is_tracking' => true,
        ]);

        // Create existing keyword
        Keyword::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
        ]);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track"
        );

        $response->assertStatus(200);

        // No duplicate keyword created
        $keywordCount = Keyword::where('keyword', 'test keyword')->count();
        $this->assertEquals(1, $keywordCount);
    }

    /** @test */
    public function track_creates_correct_keyword_data(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'test keyword',
        ]);

        $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track"
        );

        $keyword = Keyword::where('keyword', 'test keyword')->first();

        $this->assertEquals($this->site->id, $keyword->site_id);
        $this->assertEquals('active', $keyword->status);
    }

    /** @test */
    public function bulk_track_suggestions(): void
    {
        $this->actingAs($this->user);

        $suggestions = KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $this->site->id,
            'is_tracking' => false,
        ]);

        $suggestionIds = $suggestions->take(3)->pluck('id')->toArray();

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/bulk-track",
            ['suggestionIds' => $suggestionIds]
        );

        $response->assertStatus(201);

        // 3 keywords created
        $this->assertEquals(3, Keyword::count());

        // 3 suggestions updated
        $trackingCount = KeywordSuggestion::whereIn('id', $suggestionIds)
            ->where('is_tracking', true)
            ->count();
        $this->assertEquals(3, $trackingCount);
    }

    // ============================================
    // Dismiss Suggestion Workflow
    // ============================================

    /** @test */
    public function dismiss_single_suggestion(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => false,
        ]);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/dismiss"
        );

        $response->assertStatus(200);

        $this->assertTrue($suggestion->fresh()->is_dismissed);

        // DismissedSuggestion record created
        $this->assertDatabaseHas('dismissed_suggestions', [
            'site_id' => $this->site->id,
            'keyword' => $suggestion->keyword,
        ]);
    }

    /** @test */
    public function dismiss_already_dismissed_is_idempotent(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => true,
        ]);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/dismiss"
        );

        $response->assertStatus(200);

        // No error thrown, idempotent
    }

    /** @test */
    public function dismissed_not_in_default_list(): void
    {
        $this->actingAs($this->user);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => true,
            'keyword' => 'dismissed keyword',
        ]);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonMissing(['keyword' => 'dismissed keyword']);
    }

    /** @test */
    public function dismissed_shown_with_include_dismissed(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => true,
            'keyword' => 'dismissed keyword',
        ]);

        $response = $this->getJson(
            "/api/v1/sites/{$this->site->id}/suggestions?includeDismissed=true"
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['keyword' => 'dismissed keyword']);
    }

    // ============================================
    // Authorization Workflow
    // ============================================

    /** @test */
    public function user_cannot_access_other_users_site(): void
    {
        $this->actingAs($this->otherUser);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_track_other_users_suggestion(): void
    {
        $this->actingAs($this->otherUser);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track"
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_access_denied(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(401);
    }

    // ============================================
    // Full User Flow
    // ============================================

    /** @test */
    public function complete_discovery_workflow(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['best seo tools', 'free seo tools'],
            ]),
        ]);

        // 1. Generate suggestions
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest', 'question'],
            'maxResults' => 50,
        ]);
        $response->assertStatus(202);

        // 2. List suggestions
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");
        $response->assertStatus(200);
        $suggestions = $response->json('data');
        $this->assertGreaterThan(0, count($suggestions));

        // 3. Track one
        $firstSuggestion = $suggestions[0];
        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$firstSuggestion['id']}/track"
        );
        $response->assertStatus(201);

        // 4. Dismiss another
        if (isset($suggestions[1])) {
            $response = $this->postJson(
                "/api/v1/sites/{$this->site->id}/suggestions/{$suggestions[1]['id']}/dismiss"
            );
            $response->assertStatus(200);
        }

        // 5. List again - should not include tracked or dismissed
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");
        $response->assertStatus(200)
            ->assertJsonMissing(['id' => $firstSuggestion['id']]);
    }

    /** @test */
    public function rediscovery_updates_dismissed_status(): void
    {
        $this->actingAs($this->user);

        // Create dismissed suggestion
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
            'is_dismissed' => true,
        ]);

        DismissedSuggestion::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
        ]);

        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['seo tools'],
            ]),
        ]);

        // Generate same keyword again
        $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest'],
            'maxResults' => 50,
        ]);

        // Suggestion should be un-dismissed
        $this->assertFalse($suggestion->fresh()->is_dismissed);
    }

    /** @test */
    public function export_suggestions(): void
    {
        $this->actingAs($this->user);

        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->getJson(
            "/api/v1/sites/{$this->site->id}/suggestions/export?format=csv"
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Keyword,Source', $content);
    }

    // ============================================
    // Error Handling
    // ============================================

    /** @test */
    public function generate_with_invalid_site(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/999/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function track_non_existent_suggestion(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/999/track"
        );

        $response->assertStatus(404);
    }

    /** @test */
    public function bulk_track_with_empty_array(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/bulk-track",
            ['suggestionIds' => []]
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function generate_with_invalid_source(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['invalid'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(422);
    }
}
