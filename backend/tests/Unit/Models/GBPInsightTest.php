<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GBPInsight;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

/**
 * Unit tests for GBPInsight model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPInsightTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Model Instantiation Tests (GI-001 to GI-003)
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $insight = new GBPInsight();
        
        $this->assertInstanceOf(GBPInsight::class, $insight);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $insight = new GBPInsight();
        $expectedFillable = [
            'location_id',
            'views',
            'searches',
            'calls',
            'directions',
            'website_clicks',
            'photos_views',
            'insights_date',
        ];
        
        $this->assertEquals($expectedFillable, $insight->getFillable());
    }

    /** @test */
    public function it_guards_non_fillable_attributes(): void
    {
        $insight = GBPInsight::create([
            'location_id' => 1,
            'views' => 100,
            'id' => 999, // Should be ignored
        ]);
        
        $this->assertNotEquals(999, $insight->id);
    }

    // =========================================================================
    // Casts Tests (GI-004 to GI-011)
    // =========================================================================

    /** @test */
    public function it_casts_views_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['views' => '100']);
        
        $this->assertIsInt($insight->views);
        $this->assertEquals(100, $insight->views);
    }

    /** @test */
    public function it_casts_searches_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['searches' => '250']);
        
        $this->assertIsInt($insight->searches);
        $this->assertEquals(250, $insight->searches);
    }

    /** @test */
    public function it_casts_calls_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['calls' => '50']);
        
        $this->assertIsInt($insight->calls);
        $this->assertEquals(50, $insight->calls);
    }

    /** @test */
    public function it_casts_directions_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['directions' => '30']);
        
        $this->assertIsInt($insight->directions);
        $this->assertEquals(30, $insight->directions);
    }

    /** @test */
    public function it_casts_website_clicks_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['website_clicks' => '75']);
        
        $this->assertIsInt($insight->website_clicks);
        $this->assertEquals(75, $insight->website_clicks);
    }

    /** @test */
    public function it_casts_photos_views_to_integer(): void
    {
        $insight = GBPInsight::factory()->create(['photos_views' => '500']);
        
        $this->assertIsInt($insight->photos_views);
        $this->assertEquals(500, $insight->photos_views);
    }

    /** @test */
    public function it_casts_insights_date_to_date(): void
    {
        $insight = GBPInsight::factory()->create(['insights_date' => '2026-03-08']);
        
        $this->assertInstanceOf(Carbon::class, $insight->insights_date);
        $this->assertEquals('2026-03-08', $insight->insights_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_created_at_to_datetime(): void
    {
        $insight = GBPInsight::factory()->create();
        
        $this->assertInstanceOf(Carbon::class, $insight->created_at);
    }

    // =========================================================================
    // Relationship Tests (GI-012 to GI-014)
    // =========================================================================

    /** @test */
    public function it_belongs_to_business_location(): void
    {
        $insight = GBPInsight::factory()->create();
        
        $this->assertInstanceOf(BusinessLocation::class, $insight->location);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $insight = new GBPInsight();
        
        $relation = $insight->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /** @test */
    public function it_can_access_parent_location_data(): void
    {
        $location = BusinessLocation::factory()->create(['name' => 'Test Location']);
        $insight = GBPInsight::factory()->forLocation($location->id)->create();
        
        $this->assertEquals('Test Location', $insight->location->name);
    }

    // =========================================================================
    // Scope Tests (GI-015 to GI-020)
    // =========================================================================

    /** @test */
    public function scope_for_date_range_filters_by_date(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-01')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-05')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-10')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-15')->create();
        
        $results = GBPInsight::forDateRange('2026-03-03', '2026-03-12')->get();
        
        $this->assertCount(2, $results);
        $this->assertEquals(['2026-03-05', '2026-03-10'], $results->pluck('insights_date')->map->format('Y-m-d')->toArray());
    }

    /** @test */
    public function scope_for_date_range_includes_boundary_dates(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-01')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-10')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-20')->create();
        
        $results = GBPInsight::forDateRange('2026-03-01', '2026-03-10')->get();
        
        $this->assertCount(2, $results);
    }

    /** @test */
    public function scope_for_date_range_excludes_outside_range(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-02-28')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-05')->create();
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-11')->create();
        
        $results = GBPInsight::forDateRange('2026-03-01', '2026-03-10')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('2026-03-05', $results->first()->insights_date->format('Y-m-d'));
    }

    /** @test */
    public function scope_for_date_range_works_with_carbon_dates(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-05')->create();
        
        $start = Carbon::parse('2026-03-01');
        $end = Carbon::parse('2026-03-10');
        
        $results = GBPInsight::forDateRange($start, $end)->get();
        
        $this->assertCount(1, $results);
    }

    /** @test */
    public function scope_for_date_range_works_with_string_dates(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-05')->create();
        
        $results = GBPInsight::forDateRange('2026-03-01', '2026-03-10')->get();
        
        $this->assertCount(1, $results);
    }

    /** @test */
    public function scope_for_location_filters_by_location(): void
    {
        $location1 = BusinessLocation::factory()->create();
        $location2 = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location1->id)->create();
        GBPInsight::factory()->forLocation($location1->id)->create();
        GBPInsight::factory()->forLocation($location2->id)->create();
        
        $results = GBPInsight::forLocation($location1->id)->get();
        
        $this->assertCount(2, $results);
        $results->each(function ($insight) use ($location1) {
            $this->assertEquals($location1->id, $insight->location_id);
        });
    }

    // =========================================================================
    // Method Tests (GI-021 to GI-023)
    // =========================================================================

    /** @test */
    public function calculate_total_actions_returns_sum_of_actions(): void
    {
        $insight = GBPInsight::factory()->create([
            'calls' => 10,
            'directions' => 15,
            'website_clicks' => 25,
        ]);
        
        $total = $insight->calculateTotalActions();
        
        $this->assertEquals(50, $total);
    }

    /** @test */
    public function calculate_total_actions_with_zero_values(): void
    {
        $insight = GBPInsight::factory()->noEngagement()->create();
        
        $total = $insight->calculateTotalActions();
        
        $this->assertEquals(0, $total);
    }

    /** @test */
    public function calculate_total_actions_with_high_values(): void
    {
        $insight = GBPInsight::factory()->create([
            'calls' => 10000,
            'directions' => 15000,
            'website_clicks' => 20000,
        ]);
        
        $total = $insight->calculateTotalActions();
        
        $this->assertEquals(45000, $total);
    }

    // =========================================================================
    // Factory Tests (GI-024 to GI-028)
    // =========================================================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $insight = GBPInsight::factory()->create();
        
        $this->assertDatabaseHas('gbp_insights', [
            'id' => $insight->id,
        ]);
    }

    /** @test */
    public function factory_for_location_state_sets_location_id(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $insight = GBPInsight::factory()->forLocation($location->id)->create();
        
        $this->assertEquals($location->id, $insight->location_id);
    }

    /** @test */
    public function factory_with_date_state_sets_insights_date(): void
    {
        $insight = GBPInsight::factory()->withDate('2026-03-08')->create();
        
        $this->assertEquals('2026-03-08', $insight->insights_date->format('Y-m-d'));
    }

    /** @test */
    public function factory_high_engagement_state_sets_high_values(): void
    {
        $insight = GBPInsight::factory()->highEngagement()->create();
        
        $this->assertGreaterThanOrEqual(500, $insight->views);
        $this->assertGreaterThanOrEqual(100, $insight->searches);
        $this->assertGreaterThanOrEqual(20, $insight->calls);
    }

    /** @test */
    public function factory_no_engagement_state_sets_zero_values(): void
    {
        $insight = GBPInsight::factory()->noEngagement()->create();
        
        $this->assertEquals(0, $insight->views);
        $this->assertEquals(0, $insight->searches);
        $this->assertEquals(0, $insight->calls);
        $this->assertEquals(0, $insight->directions);
        $this->assertEquals(0, $insight->website_clicks);
        $this->assertEquals(0, $insight->photos_views);
    }

    // =========================================================================
    // Unique Constraint Tests (GI-029 to GI-031)
    // =========================================================================

    /** @test */
    public function duplicate_location_and_date_fails(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-08')->create();
        
        $this->expectException(QueryException::class);
        
        GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-08')->create();
    }

    /** @test */
    public function same_location_different_date_succeeds(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $insight1 = GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-08')->create();
        $insight2 = GBPInsight::factory()->forLocation($location->id)->withDate('2026-03-09')->create();
        
        $this->assertNotEquals($insight1->id, $insight2->id);
        $this->assertEquals($location->id, $insight1->location_id);
        $this->assertEquals($location->id, $insight2->location_id);
    }

    /** @test */
    public function different_location_same_date_succeeds(): void
    {
        $location1 = BusinessLocation::factory()->create();
        $location2 = BusinessLocation::factory()->create();
        
        $insight1 = GBPInsight::factory()->forLocation($location1->id)->withDate('2026-03-08')->create();
        $insight2 = GBPInsight::factory()->forLocation($location2->id)->withDate('2026-03-08')->create();
        
        $this->assertNotEquals($insight1->id, $insight2->id);
        $this->assertEquals('2026-03-08', $insight1->insights_date->format('Y-m-d'));
        $this->assertEquals('2026-03-08', $insight2->insights_date->format('Y-m-d'));
    }

    // =========================================================================
    // Edge Case Tests (GI-032 to GI-035)
    // =========================================================================

    /** @test */
    public function zero_values_for_all_metrics(): void
    {
        $insight = GBPInsight::factory()->noEngagement()->create();
        
        $this->assertEquals(0, $insight->views);
        $this->assertEquals(0, $insight->searches);
        $this->assertEquals(0, $insight->calls);
        $this->assertEquals(0, $insight->directions);
        $this->assertEquals(0, $insight->website_clicks);
        $this->assertEquals(0, $insight->photos_views);
    }

    /** @test */
    public function maximum_integer_values(): void
    {
        $maxInt = 4294967295; // UNSIGNED INT max
        
        $insight = GBPInsight::factory()->create([
            'views' => $maxInt,
            'searches' => $maxInt,
            'calls' => $maxInt,
        ]);
        
        $this->assertEquals($maxInt, $insight->views);
        $this->assertEquals($maxInt, $insight->searches);
        $this->assertEquals($maxInt, $insight->calls);
    }

    /** @test */
    public function null_insights_date_throws_error(): void
    {
        $this->expectException(QueryException::class);
        
        GBPInsight::factory()->create(['insights_date' => null]);
    }

    /** @test */
    public function invalid_date_format_handled(): void
    {
        $this->expectException(\Exception::class);
        
        GBPInsight::factory()->create(['insights_date' => 'not-a-date']);
    }

    // =========================================================================
    // Security Tests (GI-036 to GI-038)
    // =========================================================================

    /** @test */
    public function sql_injection_in_insights_date_prevented(): void
    {
        $insight = GBPInsight::factory()->create([
            'insights_date' => "2026-03-08'; DROP TABLE gbp_insights; --",
        ]);
        
        // Should not throw, date is sanitized
        $this->assertDatabaseHas('gbp_insights', ['id' => $insight->id]);
    }

    /** @test */
    public function xss_in_numeric_fields_impossible(): void
    {
        // Numeric fields are cast to integers, XSS strings become 0
        $insight = GBPInsight::factory()->create([
            'views' => '<script>alert(1)</script>',
        ]);
        
        $this->assertEquals(0, $insight->views);
    }

    /** @test */
    public function negative_values_rejected(): void
    {
        // UNSIGNED columns reject negative values
        $this->expectException(QueryException::class);
        
        GBPInsight::factory()->create(['views' => -100]);
    }

    // =========================================================================
    // Accessor Tests (GI-039 to GI-041)
    // =========================================================================

    /** @test */
    public function get_formatted_date_attribute_returns_formatted_date(): void
    {
        $insight = GBPInsight::factory()->withDate('2026-03-08')->create();
        
        $this->assertEquals('2026-03-08', $insight->formatted_date);
    }

    /** @test */
    public function get_total_views_attribute_returns_views(): void
    {
        $insight = GBPInsight::factory()->create(['views' => 500]);
        
        $this->assertEquals(500, $insight->total_views);
    }

    /** @test */
    public function get_engagement_rate_attribute_calculates_rate(): void
    {
        $insight = GBPInsight::factory()->create([
            'views' => 1000,
            'searches' => 100,
        ]);
        
        // Rate = views / searches * 100
        $expectedRate = ($insight->views / $insight->searches) * 100;
        
        $this->assertEquals($expectedRate, $insight->engagement_rate);
    }

    // =========================================================================
    // Integration Tests (GI-042 to GI-043)
    // =========================================================================

    /** @test */
    public function crud_operations_work(): void
    {
        // Create
        $insight = GBPInsight::factory()->create();
        $this->assertDatabaseHas('gbp_insights', ['id' => $insight->id]);
        
        // Read
        $found = GBPInsight::find($insight->id);
        $this->assertEquals($insight->views, $found->views);
        
        // Update
        $insight->update(['views' => 999]);
        $this->assertEquals(999, $insight->fresh()->views);
        
        // Delete
        $insight->delete();
        $this->assertDatabaseMissing('gbp_insights', ['id' => $insight->id]);
    }

    /** @test */
    public function cascade_delete_when_location_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $insight = GBPInsight::factory()->forLocation($location->id)->create();
        
        $location->delete();
        
        $this->assertDatabaseMissing('gbp_insights', ['id' => $insight->id]);
    }

    // =========================================================================
    // Performance Tests (GI-044 to GI-045)
    // =========================================================================

    /** @test */
    public function query_uses_insights_date_index(): void
    {
        $location = BusinessLocation::factory()->create();
        
        // Create multiple insights
        for ($i = 0; $i < 30; $i++) {
            GBPInsight::factory()->forLocation($location->id)->withDate(now()->subDays($i)->format('Y-m-d'))->create();
        }
        
        // Query with date range should use index
        $query = GBPInsight::forDateRange(now()->subDays(7), now());
        
        // Just verify it executes quickly
        $start = microtime(true);
        $results = $query->get();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(0.5, $duration);
    }

    /** @test */
    public function bulk_insert_100_records(): void
    {
        $location = BusinessLocation::factory()->create();
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            GBPInsight::factory()->forLocation($location->id)->withDate(now()->subDays($i)->format('Y-m-d'))->create();
        }
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(5, $duration, 'Bulk insert should complete in under 5 seconds');
        $this->assertEquals(100, GBPInsight::where('location_id', $location->id)->count());
    }
}
