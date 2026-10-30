<?php

namespace Tests\Unit\Models;

use App\Models\KeywordSuggestion;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the KeywordSuggestion model.
 *
 * Tests model relationships, scopes, accessors, and configuration.
 *
 * @see TASK-5-2
 * @see TEST-5-13
 * @see SPEC-005
 */
class KeywordSuggestionTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /**
     * Test that a suggestion belongs to a site.
     * M-001
     */
    public function test_suggestion_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
        ]);

        $this->assertInstanceOf(Site::class, $suggestion->site);
        $this->assertEquals($site->id, $suggestion->site->id);
    }

    /**
     * Test that site relationship can be eager loaded.
     */
    public function test_site_relationship_can_be_eager_loaded(): void
    {
        $site = Site::factory()->create();
        KeywordSuggestion::factory()->create(['site_id' => $site->id]);

        $suggestion = KeywordSuggestion::with('site')->first();

        $this->assertTrue($suggestion->relationLoaded('site'));
    }

    // =========================================================================
    // SCOPE TESTS
    // =========================================================================

    /**
     * Test scope bySite filters by site_id.
     * M-002
     */
    public function test_scope_by_site(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site1->id, 'keyword' => 'test 1']);
        KeywordSuggestion::factory()->create(['site_id' => $site1->id, 'keyword' => 'test 2']);
        KeywordSuggestion::factory()->create(['site_id' => $site2->id, 'keyword' => 'test 3']);

        $results = KeywordSuggestion::bySite($site1->id)->get();

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) use ($site1) {
            $this->assertEquals($site1->id, $suggestion->site_id);
        });
    }

    /**
     * Test scope bySource filters by source.
     * M-003
     */
    public function test_scope_by_source(): void
    {
        $site = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'source' => 'suggest']);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'source' => 'suggest']);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'source' => 'competitor']);

        $results = KeywordSuggestion::bySource('suggest')->get();

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) {
            $this->assertEquals('suggest', $suggestion->source);
        });
    }

    /**
     * Test scope notDismissed filters out dismissed suggestions.
     * M-004
     */
    public function test_scope_not_dismissed(): void
    {
        $site = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => false]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => false]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_dismissed' => true]);

        $results = KeywordSuggestion::notDismissed()->get();

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) {
            $this->assertFalse($suggestion->is_dismissed);
        });
    }

    /**
     * Test scope notTracking filters out tracking suggestions.
     * M-005
     */
    public function test_scope_not_tracking(): void
    {
        $site = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_tracking' => false]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_tracking' => false]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'is_tracking' => true]);

        $results = KeywordSuggestion::notTracking()->get();

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) {
            $this->assertFalse($suggestion->is_tracking);
        });
    }

    /**
     * Test scope highOpportunity filters by minimum score.
     * M-006
     */
    public function test_scope_high_opportunity(): void
    {
        $site = Site::factory()->create();

        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'opportunity_score' => 85]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'opportunity_score' => 75]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'opportunity_score' => 65]);
        KeywordSuggestion::factory()->create(['site_id' => $site->id, 'opportunity_score' => 55]);

        $results = KeywordSuggestion::highOpportunity(70)->get();

        $this->assertCount(2, $results);
        $results->each(function ($suggestion) {
            $this->assertGreaterThanOrEqual(70, $suggestion->opportunity_score);
        });
    }

    /**
     * Test scopes can be chained.
     */
    public function test_scopes_can_be_chained(): void
    {
        $site = Site::factory()->create();

        // Create suggestions with various states
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'source' => 'suggest',
            'is_dismissed' => false,
            'is_tracking' => false,
            'opportunity_score' => 80,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'source' => 'suggest',
            'is_dismissed' => true, // dismissed - should be excluded
            'is_tracking' => false,
            'opportunity_score' => 85,
        ]);
        KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'source' => 'competitor', // different source - should be excluded
            'is_dismissed' => false,
            'is_tracking' => false,
            'opportunity_score' => 90,
        ]);

        $results = KeywordSuggestion::bySite($site->id)
            ->bySource('suggest')
            ->notDismissed()
            ->notTracking()
            ->highOpportunity(70)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(80, $results->first()->opportunity_score);
    }

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    /**
     * Test tracking_status accessor returns correct values.
     * M-007
     */
    public function test_tracking_status_accessor(): void
    {
        $site = Site::factory()->create();

        // Tracking status
        $tracking = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'is_tracking' => true,
            'is_dismissed' => false,
        ]);
        $this->assertEquals('tracking', $tracking->tracking_status);

        // Dismissed status
        $dismissed = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'is_tracking' => false,
            'is_dismissed' => true,
        ]);
        $this->assertEquals('dismissed', $dismissed->tracking_status);

        // Available status
        $available = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'is_tracking' => false,
            'is_dismissed' => false,
        ]);
        $this->assertEquals('available', $available->tracking_status);
    }

    // =========================================================================
    // CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test fillable attributes are correctly defined.
     * M-008
     */
    public function test_fillable_attributes(): void
    {
        $suggestion = new KeywordSuggestion();
        $fillable = $suggestion->getFillable();

        $expectedFillable = [
            'site_id',
            'keyword',
            'source',
            'search_volume',
            'difficulty_score',
            'opportunity_score',
            'is_tracking',
            'is_dismissed',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    /**
     * Test that id is not fillable.
     */
    public function test_id_is_not_fillable(): void
    {
        $suggestion = new KeywordSuggestion();
        $fillable = $suggestion->getFillable();

        $this->assertNotContains('id', $fillable);
    }

    /**
     * Test that discovered_at is not fillable.
     */
    public function test_discovered_at_is_not_fillable(): void
    {
        $suggestion = new KeywordSuggestion();
        $fillable = $suggestion->getFillable();

        $this->assertNotContains('discovered_at', $fillable);
    }

    /**
     * Test boolean casts for is_tracking and is_dismissed.
     * M-009
     */
    public function test_boolean_casts(): void
    {
        $site = Site::factory()->create();
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
            'is_tracking' => 1,
            'is_dismissed' => 0,
        ]);

        $this->assertIsBool($suggestion->is_tracking);
        $this->assertIsBool($suggestion->is_dismissed);
        $this->assertTrue($suggestion->is_tracking);
        $this->assertFalse($suggestion->is_dismissed);
    }

    /**
     * Test timestamp cast for discovered_at.
     * M-010
     */
    public function test_discovered_at_is_carbon_instance(): void
    {
        $site = Site::factory()->create();
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $suggestion->discovered_at);
    }

    /**
     * Test updated_at is Carbon instance.
     */
    public function test_updated_at_is_carbon_instance(): void
    {
        $site = Site::factory()->create();
        $suggestion = KeywordSuggestion::factory()->create([
            'site_id' => $site->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $suggestion->updated_at);
    }

    // =========================================================================
    // FACTORY TESTS
    // =========================================================================

    /**
     * Test factory creates valid suggestion.
     */
    public function test_factory_creates_valid_suggestion(): void
    {
        $suggestion = KeywordSuggestion::factory()->create();

        $this->assertDatabaseHas('keyword_suggestions', [
            'id' => $suggestion->id,
        ]);
        $this->assertNotNull($suggestion->keyword);
        $this->assertNotNull($suggestion->source);
        $this->assertNotNull($suggestion->site_id);
    }

    /**
     * Test factory tracking state.
     */
    public function test_factory_tracking_state(): void
    {
        $suggestion = KeywordSuggestion::factory()->tracking()->create();

        $this->assertTrue($suggestion->is_tracking);
    }

    /**
     * Test factory dismissed state.
     */
    public function test_factory_dismissed_state(): void
    {
        $suggestion = KeywordSuggestion::factory()->dismissed()->create();

        $this->assertTrue($suggestion->is_dismissed);
    }

    /**
     * Test factory highOpportunity state.
     */
    public function test_factory_high_opportunity_state(): void
    {
        $suggestion = KeywordSuggestion::factory()->highOpportunity()->create();

        $this->assertGreaterThanOrEqual(70, $suggestion->opportunity_score);
    }
}
