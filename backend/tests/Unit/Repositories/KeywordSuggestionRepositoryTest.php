<?php

namespace Tests\Unit\Repositories;

use App\Models\KeywordSuggestion;
use App\Models\Site;
use App\Repositories\KeywordSuggestionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Unit tests for the KeywordSuggestionRepository.
 *
 * Tests CRUD operations and query methods for keyword suggestions.
 *
 * @see TASK-5-2
 * @see TEST-5-13
 * @see SPEC-005
 */
class KeywordSuggestionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected KeywordSuggestionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new KeywordSuggestionRepository();
    }

    // =========================================================================
    // FIND TESTS
    // =========================================================================

    /**
     * Test find returns suggestion by ID.
     * R-001
     */
    public function test_find_returns_suggestion_by_id(): void
    {
        $suggestion = KeywordSuggestion::factory()->create();

        $found = $this->repository->find($suggestion->id);

        $this->assertNotNull($found);
        $this->assertEquals($suggestion->id, $found->id);
    }

    /**
     * Test find returns null for non-existent ID.
     */
    public function test_find_returns_null_for_nonexistent_id(): void
    {
        $found = $this->repository->find(99999);

        $this->assertNull($found);
    }

    // =========================================================================
    // CREATE TESTS
    // =========================================================================

    /**
     * Test create stores and returns new suggestion.
     * R-002
     */
    public function test_create_stores_and_returns_suggestion(): void
    {
        $site = Site::factory()->create();
        $data = [
            'site_id' => $site->id,
            'keyword' => 'best seo tools',
            'source' => 'suggest',
            'opportunity_score' => 75,
        ];

        $suggestion = $this->repository->create($data);

        $this->assertInstanceOf(KeywordSuggestion::class, $suggestion);
        $this->assertEquals('best seo tools', $suggestion->keyword);
        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
            'keyword' => 'best seo tools',
        ]);
    }

    /**
     * Test create sets default values.
     */
    public function test_create_sets_default_values(): void
    {
        $site = Site::factory()->create();
        $data = [
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
        ];

        $suggestion = $this->repository->create($data);

        $this->assertFalse($suggestion->is_tracking);
        $this->assertFalse($suggestion->is_dismissed);
        $this->assertNotNull($suggestion->discovered_at);
    }

    // =========================================================================
    // UPDATE TESTS
    // =========================================================================

    /**
     * Test update modifies and returns suggestion.
     * R-003
     */
    public function test_update_modifies_suggestion(): void
    {
        $suggestion = KeywordSuggestion::factory()->create([
            'opportunity_score' => 50,
        ]);
        $data = [
            'opportunity_score' => 85,
            'is_tracking' => true,
        ];

        $updated = $this->repository->update($suggestion->id, $data);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
            'opportunity_score' => 85,
            'is_tracking' => true,
        ]);
    }

    /**
     * Test update returns false for non-existent ID.
     */
    public function test_update_returns_false_for_nonexistent(): void
    {
        $result = $this->repository->update(99999, ['keyword' => 'test']);

        $this->assertFalse($result);
    }

    // =========================================================================
    // DELETE TESTS
    // =========================================================================

    /**
     * Test delete removes suggestion.
     * R-004
     */
    public function test_delete_removes_suggestion(): void
    {
        $suggestion = KeywordSuggestion::factory()->create();

        $result = $this->repository->delete($suggestion->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('keyword_suggestions', [
            'id' => $suggestion->id,
        ]);
    }

    /**
     * Test delete returns false for non-existent ID.
     */
    public function test_delete_returns_false_for_nonexistent(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    // =========================================================================
    // QUERY TESTS
    // =========================================================================

    /**
     * Test findBySite returns suggestions for a site.
     * R-005
     */
    public function test_find_by_site(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site1->id, 'keyword' => 'test 1']);
        KeywordSuggestion::factory()->create(['site_id' => $site1->id, 'keyword' => 'test 2']);
        KeywordSuggestion::factory()->create(['site_id' => $site2->id, 'keyword' => 'test 3']);

        $results = $this->repository->findBySite($site1->id);

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) use ($site1) {
            $this->assertEquals($site1->id, $suggestion->site_id);
        });
    }

    /**
     * Test findDismissed returns only dismissed suggestions.
     * R-006
     */
    public function test_find_dismissed(): void
    {
        $site = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => true, 'keyword' => 'dismissed 1']);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => true, 'keyword' => 'dismissed 2']);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => false, 'keyword' => 'active']);

        $results = $this->repository->findDismissed($site->id);

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) {
            $this->assertTrue($suggestion->is_dismissed);
        });
    }

    // =========================================================================
    // STATE CHANGE TESTS
    // =========================================================================

    /**
     * Test markAsTracking sets is_tracking to true.
     * R-007
     */
    public function test_mark_as_tracking(): void
    {
        $suggestion = KeywordSuggestion::factory()->create(['is_tracking' => false]);

        $result = $this->repository->markAsTracking($suggestion->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
            'is_tracking' => true,
        ]);
    }

    /**
     * Test dismiss sets is_dismissed to true.
     * R-008
     */
    public function test_dismiss(): void
    {
        $suggestion = KeywordSuggestion::factory()->create(['is_dismissed' => false]);

        $result = $this->repository->dismiss($suggestion->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
            'is_dismissed' => true,
        ]);
    }

    // =========================================================================
    // ADDITIONAL QUERY TESTS
    // =========================================================================

    /**
     * Test findByKeyword returns suggestion by keyword.
     * R-009
     */
    public function test_find_by_keyword(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'unique keyword',
        ]);

        $found = $this->repository->findByKeyword($site->id, 'unique keyword');

        $this->assertNotNull($found);
        $this->assertEquals('unique keyword', $found->keyword);
    }

    /**
     * Test findByKeyword returns null for non-existent keyword.
     */
    public function test_find_by_keyword_returns_null(): void
    {
        $site = Site::factory()->create();

        $found = $this->repository->findByKeyword($site->id, 'nonexistent keyword');

        $this->assertNull($found);
    }

    /**
     * Test bulkCreate creates multiple suggestions.
     * R-010
     */
    public function test_bulk_create(): void
    {
        $site = Site::factory()->create();
        $suggestions = [
            ['site_id' => $site->id, 'keyword' => 'keyword 1', 'source' => 'suggest'],
            ['site_id' => $site->id, 'keyword' => 'keyword 2', 'source' => 'suggest'],
            ['site_id' => $site->id, 'keyword' => 'keyword 3', 'source' => 'question'],
        ];

        $count = $this->repository->bulkCreate($suggestions);

        $this->assertEquals(3, $count);
        $this->assertDatabaseHas('keyword_suggestions', ['keyword' => 'keyword 1']);
        $this->assertDatabaseHas('keyword_suggestions', ['keyword' => 'keyword 2']);
        $this->assertDatabaseHas('keyword_suggestions', ['keyword' => 'keyword 3']);
    }

    /**
     * Test exists returns true for existing suggestion.
     * R-011
     */
    public function test_exists_returns_true(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'existing keyword',
        ]);

        $exists = $this->repository->exists($site->id, 'existing keyword');

        $this->assertTrue($exists);
    }

    /**
     * Test exists returns false for non-existent suggestion.
     */
    public function test_exists_returns_false(): void
    {
        $site = Site::factory()->create();

        $exists = $this->repository->exists($site->id, 'nonexistent keyword');

        $this->assertFalse($exists);
    }

    /**
     * Test getPaginated returns paginator.
     * R-012
     */
    public function test_get_paginated(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->count(25)->create(['site_id' => $site->id]);

        $results = $this->repository->getPaginated($site->id, ['page' => 1, 'perPage' => 10]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertCount(10, $results->items());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    /**
     * Test getPaginated applies filters.
     */
    public function test_get_paginated_with_filters(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $site->id,
            'source' => 'suggest',
            'is_dismissed' => false,
        ]);
        KeywordSuggestion::factory()->count(3)->create([
            'site_id' => $site->id,
            'source' => 'competitor',
            'is_dismissed' => false,
        ]);
        KeywordSuggestion::factory()->count(2)->create([
            'site_id' => $site->id,
            'source' => 'suggest',
            'is_dismissed' => true,
        ]);

        $results = $this->repository->getPaginated($site->id, [
            'source' => 'suggest',
            'excludeDismissed' => true,
        ]);

        $this->assertCount(5, $results->items());
    }

    /**
     * Test getBySource returns filtered results.
     * R-013
     */
    public function test_get_by_source(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->count(3)->create([
            'site_id' => $site->id,
            'source' => 'suggest',
        ]);
        KeywordSuggestion::factory()->count(2)->create([
            'site_id' => $site->id,
            'source' => 'competitor',
        ]);

        $results = $this->repository->getBySource($site->id, 'suggest');

        $this->assertCount(3, $results);
        $results->each(function ($suggestion) {
            $this->assertEquals('suggest', $suggestion->source);
        });
    }

    /**
     * Test countBySite returns correct count.
     * R-014
     */
    public function test_count_by_site(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->count(7)->create(['site_id' => $site->id]);

        $count = $this->repository->countBySite($site->id);

        $this->assertEquals(7, $count);
    }

    /**
     * Test countBySite excludes dismissed by default.
     */
    public function test_count_by_site_excludes_dismissed(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->count(5)->create([
            'site_id' => $site->id,
            'is_dismissed' => false,
        ]);
        KeywordSuggestion::factory()->count(3)->create([
            'site_id' => $site->id,
            'is_dismissed' => true,
        ]);

        $count = $this->repository->countBySite($site->id, excludeDismissed: true);

        $this->assertEquals(5, $count);
    }

    // =========================================================================
    // UPSERT TESTS
    // =========================================================================

    /**
     * Test upsertOrCreate creates new or updates existing.
     */
    public function test_upsert_create_creates_new(): void
    {
        $site = Site::factory()->create();
        $data = [
            'site_id' => $site->id,
            'keyword' => 'new keyword',
            'source' => 'suggest',
            'opportunity_score' => 75,
        ];

        $suggestion = $this->repository->upsertOrCreate($data);

        $this->assertTrue($suggestion->wasRecentlyCreated);
        $this->assertEquals('new keyword', $suggestion->keyword);
        $this->assertEquals(75, $suggestion->opportunity_score);
    }

    /**
     * Test upsertOrCreate updates existing suggestion.
     */
    public function test_upsert_create_updates_existing(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'existing keyword',
            'opportunity_score' => 50,
            'is_dismissed' => true,
        ]);

        $data = [
            'site_id' => $site->id,
            'keyword' => 'existing keyword',
            'source' => 'suggest',
            'opportunity_score' => 85,
        ];

        $suggestion = $this->repository->upsertOrCreate($data);

        $this->assertFalse($suggestion->wasRecentlyCreated);
        $this->assertEquals(85, $suggestion->opportunity_score);
        $this->assertFalse($suggestion->is_dismissed); // Should be reset on rediscovery
    }
}
