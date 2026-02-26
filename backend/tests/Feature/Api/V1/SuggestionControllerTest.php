<?php

namespace Tests\Feature\Api\V1;

use App\Models\KeywordSuggestion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Suggestion API endpoints.
 *
 * Tests request validation, response formatting, and authorization.
 *
 * @see TASK-5-7, TASK-5-8, TASK-5-9
 * @see TEST-5-13
 * @see SPEC-005
 */
class SuggestionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // LIST SUGGESTIONS TESTS
    // =========================================================================

    /**
     * Test list suggestions returns paginated list.
     * API-001
     */
    public function test_list_suggestions(): void
    {
        KeywordSuggestion::factory()->count(25)->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'source',
                        'opportunity_score',
                        'is_tracking',
                        'is_dismissed',
                        'discovered_at',
                    ],
                ],
                'meta' => ['total', 'page', 'perPage'],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * Test filter by source.
     * API-002
     */
    public function test_filter_by_source(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'suggest keyword',
            'source' => 'suggest',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'competitor keyword',
            'source' => 'competitor',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?source=suggest");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.source', 'suggest');
    }

    /**
     * Test pagination.
     * API-003
     */
    public function test_pagination(): void
    {
        KeywordSuggestion::factory()->count(25)->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?page=2&perPage=10");

        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25);

        // Page 2 should have 10 items
        $this->assertCount(10, $response->json('data'));
    }

    /**
     * Test exclude dismissed by default.
     * API-004
     */
    public function test_exclude_dismissed_by_default(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'active',
            'is_dismissed' => false,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'dismissed',
            'is_dismissed' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test include tracking.
     * API-005
     */
    public function test_include_tracking(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'tracking',
            'is_tracking' => true,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'not tracking',
            'is_tracking' => false,
        ]);

        // Default: exclude tracking
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertJsonPath('meta.total', 1);

        // Include tracking
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?includeTracking=true");

        $response->assertJsonPath('meta.total', 2);
    }

    // =========================================================================
    // GENERATE SUGGESTIONS TESTS
    // =========================================================================

    /**
     * Test generate suggestions returns job ID.
     * API-006
     */
    public function test_generate_suggestions(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
                'seedKeyword' => 'seo tools',
                'sources' => ['question'],
                'maxResults' => 50,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => ['jobId', 'status'],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'queued');
    }

    /**
     * Test generate validation: missing seed keyword.
     * API-007
     */
    public function test_generate_validation_missing_seed(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
                'sources' => ['question'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['seedKeyword']);
    }

    /**
     * Test generate validation: invalid source.
     * API-008
     */
    public function test_generate_validation_invalid_source(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
                'seedKeyword' => 'seo',
                'sources' => ['invalid'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sources.0']);
    }

    /**
     * Test generate validation: empty sources.
     */
    public function test_generate_validation_empty_sources(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
                'seedKeyword' => 'seo',
                'sources' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sources']);
    }

    // =========================================================================
    // TRACK SUGGESTION TESTS
    // =========================================================================

    /**
     * Test track suggestion creates keyword.
     * API-009
     */
    public function test_track_suggestion(): void
    {
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'best seo tools',
            'is_tracking' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword' => ['id', 'keyword', 'status'],
                    'suggestion' => ['id', 'is_tracking'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.keyword.keyword', 'best seo tools')
            ->assertJsonPath('data.suggestion.is_tracking', true);
    }

    /**
     * Test track already tracking suggestion.
     * API-010
     */
    public function test_track_already_tracking(): void
    {
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'already tracking',
            'is_tracking' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // =========================================================================
    // DISMISS SUGGESTION TESTS
    // =========================================================================

    /**
     * Test dismiss suggestion.
     * API-011
     */
    public function test_dismiss_suggestion(): void
    {
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'is_dismissed' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/dismiss");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_dismissed', true);

        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
            'is_dismissed' => true,
        ]);
    }

    /**
     * Test dismiss not found.
     * API-012
     */
    public function test_dismiss_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/99999/dismiss");

        $response->assertStatus(404);
    }

    // =========================================================================
    // BULK TRACK TESTS
    // =========================================================================

    /**
     * Test bulk track suggestions.
     * API-013
     */
    public function test_bulk_track(): void
    {
        $suggestions = KeywordSuggestion::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'is_tracking' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/bulk-track", [
                'suggestionIds' => $suggestions->pluck('id')->toArray(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['added', 'skipped'],
            ])
            ->assertJsonPath('success', true);
    }

    /**
     * Test bulk track validation.
     * API-014
     */
    public function test_bulk_track_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/bulk-track", [
                'suggestionIds' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['suggestionIds']);
    }

    /**
     * Test bulk track max items.
     */
    public function test_bulk_track_max_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/suggestions/bulk-track", [
                'suggestionIds' => range(1, 101), // 101 items, max is 100
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['suggestionIds']);
    }

    // =========================================================================
    // EXPORT TESTS
    // =========================================================================

    /**
     * Test export CSV.
     * API-015
     */
    public function test_export_csv(): void
    {
        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions/export?format=csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test export with filters.
     */
    public function test_export_with_filters(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'source' => 'suggest',
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'source' => 'competitor',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions/export?format=csv&source=suggest");

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHORIZATION TESTS
    // =========================================================================

    /**
     * Test authorization: wrong user.
     * API-016
     */
    public function test_authorization_wrong_user(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $otherSite->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$otherSite->id}/suggestions");

        $response->assertStatus(403);
    }

    /**
     * Test authorization: unauthenticated.
     * API-017
     */
    public function test_authorization_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(401);
    }

    /**
     * Test cannot track another user's suggestion.
     */
    public function test_cannot_track_other_user_suggestion(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $otherSite->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$otherSite->id}/suggestions/{$suggestion->id}/track");

        $response->assertStatus(403);
    }

    // =========================================================================
    // SORTING TESTS
    // =========================================================================

    /**
     * Test sort by opportunity_score descending (default).
     */
    public function test_sort_by_opportunity_score_desc(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'low score',
            'opportunity_score' => 50,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'high score',
            'opportunity_score' => 90,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions");

        $response->assertStatus(200);
        $this->assertEquals('high score', $response->json('data.0.keyword'));
    }

    /**
     * Test sort by discovered_at.
     */
    public function test_sort_by_discovered_at(): void
    {
        $old = now()->subDay();
        $new = now();

        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'old',
            'discovered_at' => $old,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'new',
            'discovered_at' => $new,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?sort=discovered_at&order=desc");

        $response->assertStatus(200);
        $this->assertEquals('new', $response->json('data.0.keyword'));
    }

    // =========================================================================
    // MIN/Max SCORE FILTER TESTS
    // =========================================================================

    /**
     * Test filter by min opportunity score.
     */
    public function test_filter_min_opportunity_score(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 50,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 80,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?minScore=70");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test filter by max opportunity score.
     */
    public function test_filter_max_opportunity_score(): void
    {
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 50,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $this->site->id,
            'opportunity_score' => 80,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/suggestions?maxScore=60");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }
}
