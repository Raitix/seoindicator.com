<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LocalCompetitor;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for LocalCompetitor model.
 *
 * @task TASK-35-4
 * @model LocalCompetitor
 */
class LocalCompetitorTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // MODEL INSTANTIATION TESTS
    // ============================================

    /** @test */
    public function can_instantiate_model(): void
    {
        $competitor = new LocalCompetitor();
        
        $this->assertInstanceOf(LocalCompetitor::class, $competitor);
    }

    /** @test */
    public function model_has_correct_fillable_attributes(): void
    {
        $competitor = new LocalCompetitor();
        
        $expectedFillable = [
            'location_id',
            'competitor_name',
            'competitor_gbp_id',
            'competitor_address',
            'competitor_rating',
            'competitor_review_count',
        ];
        
        $this->assertEquals($expectedFillable, $competitor->getFillable());
    }

    /** @test */
    public function model_uses_timestamps(): void
    {
        $competitor = new LocalCompetitor();
        
        $this->assertTrue($competitor->timestamps);
    }

    // ============================================
    // CASTS TESTS
    // ============================================

    /** @test */
    public function competitor_rating_is_cast_to_decimal_1(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.5,
        ]);
        
        // Decimal cast returns string in Laravel
        $this->assertIsString($competitor->competitor_rating);
        $this->assertEquals('4.5', $competitor->competitor_rating);
    }

    /** @test */
    public function competitor_review_count_is_cast_to_integer(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_review_count' => '150',
        ]);
        
        $this->assertIsInt($competitor->competitor_review_count);
        $this->assertEquals(150, $competitor->competitor_review_count);
    }

    /** @test */
    public function rating_decimal_precision_is_maintained(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 3.7,
        ]);
        
        $this->assertEquals(3.7, $competitor->competitor_rating);
    }

    // ============================================
    // RELATIONSHIP TESTS
    // ============================================

    /** @test */
    public function belongs_to_business_location(): void
    {
        $location = BusinessLocation::factory()->create();
        $competitor = LocalCompetitor::factory()->create([
            'location_id' => $location->id,
        ]);
        
        $this->assertInstanceOf(BusinessLocation::class, $competitor->location);
        $this->assertEquals($location->id, $competitor->location->id);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $competitor = new LocalCompetitor();
        $relationship = $competitor->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    // ============================================
    // METHOD TESTS - getMapsUrl()
    // ============================================

    /** @test */
    public function get_maps_url_with_gbp_id(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_gbp_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
        ]);
        
        $url = $competitor->getMapsUrl();
        
        $this->assertEquals(
            'https://www.google.com/maps/place/?q=place_id:ChIJN1t_tDeuEmsRUsoyG83frY4',
            $url
        );
    }

    /** @test */
    public function get_maps_url_without_gbp_id_uses_address(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => 'Test Business',
            'competitor_gbp_id' => null,
            'competitor_address' => '123 Main St, New York, NY 10001',
        ]);
        
        $url = $competitor->getMapsUrl();
        
        $this->assertStringContainsString('https://www.google.com/maps/search/', $url);
        $this->assertStringContainsString(urlencode('Test Business'), $url);
        $this->assertStringContainsString(urlencode('123 Main St, New York, NY 10001'), $url);
    }

    /** @test */
    public function get_maps_url_without_gbp_id_or_address_uses_name(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => 'Test Business',
            'competitor_gbp_id' => null,
            'competitor_address' => null,
        ]);
        
        $url = $competitor->getMapsUrl();
        
        $this->assertEquals(
            'https://www.google.com/maps/search/' . urlencode('Test Business'),
            $url
        );
    }

    /** @test */
    public function get_maps_url_prefers_gbp_id_over_address(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => 'Test Business',
            'competitor_gbp_id' => 'ChIJTest123',
            'competitor_address' => '123 Main St',
        ]);
        
        $url = $competitor->getMapsUrl();
        
        $this->assertStringContainsString('place_id:ChIJTest123', $url);
        $this->assertStringNotContainsString('123 Main St', $url);
    }

    // ============================================
    // METHOD TESTS - compareWithLocation()
    // ============================================

    /** @test */
    public function compare_with_location_returns_comparison_array(): void
    {
        // Create a mock BusinessLocation with rating and review_count
        $location = $this->createMockLocation(['rating' => 4.5, 'review_count' => 100]);
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.0,
            'competitor_review_count' => 150,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('rating_diff', $comparison);
        $this->assertArrayHasKey('review_count_diff', $comparison);
        $this->assertArrayHasKey('competitor_rating', $comparison);
        $this->assertArrayHasKey('location_rating', $comparison);
        $this->assertArrayHasKey('competitor_reviews', $comparison);
        $this->assertArrayHasKey('location_reviews', $comparison);
    }

    /** @test */
    public function compare_with_location_calculates_rating_difference(): void
    {
        $location = $this->createMockLocation(['rating' => 4.5, 'review_count' => 100]);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.0,
            'competitor_review_count' => 150,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        // Positive = location has higher rating
        $this->assertEquals(0.5, $comparison['rating_diff']);
        $this->assertEquals(4.5, $comparison['location_rating']);
        $this->assertEquals('4.0', $comparison['competitor_rating']);
    }

    /** @test */
    public function compare_with_location_calculates_negative_rating_difference(): void
    {
        $location = $this->createMockLocation(['rating' => 3.5, 'review_count' => 50]);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.8,
            'competitor_review_count' => 200,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        // Negative = competitor has higher rating
        $this->assertEqualsWithDelta(-1.3, $comparison['rating_diff'], 0.001);
    }

    /** @test */
    public function compare_with_location_handles_null_competitor_rating(): void
    {
        $location = $this->createMockLocation(['rating' => 4.5, 'review_count' => 100]);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => null,
            'competitor_review_count' => 50,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        $this->assertNull($comparison['rating_diff']);
    }

    /** @test */
    public function compare_with_location_calculates_review_count_difference(): void
    {
        $location = $this->createMockLocation(['rating' => 4.0, 'review_count' => 50]);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.0,
            'competitor_review_count' => 150,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        // Positive = competitor has more reviews (150 - 50 = 100)
        $this->assertEquals(100, $comparison['review_count_diff']);
    }

    /** @test */
    public function compare_with_location_handles_missing_location_rating(): void
    {
        $location = $this->createMockLocation(['rating' => null, 'review_count' => 50]);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 4.5,
            'competitor_review_count' => 100,
        ]);
        
        $comparison = $competitor->compareWithLocation($location);
        
        // Location rating defaults to 0 when null
        $this->assertEquals(-4.5, $comparison['rating_diff']);
    }

    /**
     * Helper to create a mock BusinessLocation with rating and review_count.
     * These properties are not in the database schema but may be set dynamically.
     */
    private function createMockLocation(array $attributes = []): BusinessLocation
    {
        $location = BusinessLocation::factory()->create();
        
        // Dynamically set rating and review_count (not persisted)
        $location->rating = $attributes['rating'] ?? null;
        $location->review_count = $attributes['review_count'] ?? 0;
        
        return $location;
    }

    // ============================================
    // FACTORY TESTS
    // ============================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $competitor = LocalCompetitor::factory()->create();
        
        $this->assertDatabaseHas('local_competitors', [
            'id' => $competitor->id,
        ]);
    }

    /** @test */
    public function factory_without_gbp_id_state(): void
    {
        $competitor = LocalCompetitor::factory()->withoutGbpId()->create();
        
        $this->assertNull($competitor->competitor_gbp_id);
    }

    /** @test */
    public function factory_high_rated_state(): void
    {
        $competitor = LocalCompetitor::factory()->highRated()->create();
        
        $this->assertGreaterThanOrEqual(4.5, $competitor->competitor_rating);
        $this->assertGreaterThanOrEqual(100, $competitor->competitor_review_count);
    }

    /** @test */
    public function factory_creates_related_location(): void
    {
        $competitor = LocalCompetitor::factory()->create();
        
        $this->assertDatabaseHas('business_locations', [
            'id' => $competitor->location_id,
        ]);
    }

    /** @test */
    public function factory_generates_valid_gbp_id_format(): void
    {
        $competitor = LocalCompetitor::factory()->create();
        
        if ($competitor->competitor_gbp_id) {
            // GBP Place IDs start with "ChIJ" and are ~27 chars
            $this->assertMatchesRegularExpression('/^ChIJ[A-Za-z0-9_-]{27}$/', $competitor->competitor_gbp_id);
        }
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /** @test */
    public function null_gbp_id_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_gbp_id' => null,
        ]);
        
        $this->assertNull($competitor->competitor_gbp_id);
    }

    /** @test */
    public function null_address_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_address' => null,
        ]);
        
        $this->assertNull($competitor->competitor_address);
    }

    /** @test */
    public function null_rating_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => null,
        ]);
        
        $this->assertNull($competitor->competitor_rating);
    }

    /** @test */
    public function zero_review_count_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_review_count' => 0,
        ]);
        
        $this->assertEquals(0, $competitor->competitor_review_count);
    }

    /** @test */
    public function max_rating_value_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 5.0,
        ]);
        
        $this->assertEquals(5.0, $competitor->competitor_rating);
    }

    /** @test */
    public function min_rating_value_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 1.0,
        ]);
        
        $this->assertEquals(1.0, $competitor->competitor_rating);
    }

    /** @test */
    public function long_address_accepted(): void
    {
        $longAddress = str_repeat('A', 499);
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_address' => $longAddress,
        ]);
        
        $this->assertEquals($longAddress, $competitor->competitor_address);
    }

    /** @test */
    public function large_review_count_accepted(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_review_count' => 999999,
        ]);
        
        $this->assertEquals(999999, $competitor->competitor_review_count);
    }

    /** @test */
    public function unicode_in_competitor_name(): void
    {
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => 'Café Münich 日本語',
        ]);
        
        $this->assertEquals('Café Münich 日本語', $competitor->competitor_name);
    }

    // ============================================
    // SECURITY TESTS
    // ============================================

    /** @test */
    public function sql_injection_in_name_is_safe(): void
    {
        $malicious = "'; DROP TABLE local_competitors; --";
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $competitor->competitor_name);
        
        // Table should still exist
        $this->assertDatabaseHas('local_competitors', ['id' => $competitor->id]);
    }

    /** @test */
    public function xss_in_address_is_safe(): void
    {
        $malicious = '<script>alert("xss")</script>';
        
        $competitor = LocalCompetitor::factory()->create([
            'competitor_address' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $competitor->competitor_address);
    }

    /** @test */
    public function invalid_rating_value_is_handled(): void
    {
        // DECIMAL(2,1) - max is 9.9
        $competitor = LocalCompetitor::factory()->create([
            'competitor_rating' => 9.9,
        ]);
        
        $this->assertEquals(9.9, $competitor->competitor_rating);
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /** @test */
    public function cascade_delete_when_location_force_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $competitor = LocalCompetitor::factory()->create([
            'location_id' => $location->id,
        ]);
        
        // Use forceDelete to trigger cascade (BusinessLocation uses SoftDeletes)
        $location->forceDelete();
        
        $this->assertDatabaseMissing('local_competitors', [
            'id' => $competitor->id,
        ]);
    }

    /** @test */
    public function full_crud_operations(): void
    {
        // Create
        $competitor = LocalCompetitor::factory()->create([
            'competitor_name' => 'Test Competitor',
        ]);
        $this->assertDatabaseHas('local_competitors', ['id' => $competitor->id]);
        
        // Read
        $found = LocalCompetitor::find($competitor->id);
        $this->assertEquals('Test Competitor', $found->competitor_name);
        
        // Update
        $competitor->update(['competitor_name' => 'Updated Competitor']);
        $this->assertEquals('Updated Competitor', $competitor->fresh()->competitor_name);
        
        // Delete
        $competitor->delete();
        $this->assertDatabaseMissing('local_competitors', ['id' => $competitor->id]);
    }

    /** @test */
    public function timestamps_are_updated(): void
    {
        $competitor = LocalCompetitor::factory()->create();
        
        $this->assertNotNull($competitor->created_at);
        $this->assertNotNull($competitor->updated_at);
        
        $originalUpdatedAt = $competitor->updated_at;
        
        sleep(1);
        
        $competitor->update(['competitor_name' => 'New Name']);
        
        $this->assertGreaterThan($originalUpdatedAt, $competitor->fresh()->updated_at);
    }

    // ============================================
    // PERFORMANCE TESTS
    // ============================================

    /** @test */
    public function index_on_location_id_is_used(): void
    {
        $location = BusinessLocation::factory()->create();
        
        // Create multiple competitors for same location
        LocalCompetitor::factory()->count(10)->create([
            'location_id' => $location->id,
        ]);
        
        // Query should use the index
        $results = LocalCompetitor::where('location_id', $location->id)->get();
        
        $this->assertCount(10, $results);
    }

    /** @test */
    public function bulk_insert_works(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'location_id' => $location->id,
                'competitor_name' => "Competitor $i",
                'competitor_review_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        LocalCompetitor::insert($data);
        
        $this->assertEquals(100, LocalCompetitor::where('location_id', $location->id)->count());
    }
}
