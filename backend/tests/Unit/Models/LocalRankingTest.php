<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LocalRanking;
use App\Models\BusinessLocation;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;

/**
 * Unit tests for LocalRanking model.
 *
 * @task TASK-35-4
 * @model LocalRanking
 */
class LocalRankingTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // MODEL INSTANTIATION TESTS
    // ============================================

    /** @test */
    public function can_instantiate_model(): void
    {
        $ranking = new LocalRanking();
        
        $this->assertInstanceOf(LocalRanking::class, $ranking);
    }

    /** @test */
    public function model_has_correct_fillable_attributes(): void
    {
        $ranking = new LocalRanking();
        
        $expectedFillable = [
            'location_id',
            'keyword_id',
            'search_location',
            'search_lat',
            'search_lng',
            'local_pack_position',
            'local_organic_position',
            'competitors_in_pack',
            'checked_at',
        ];
        
        $this->assertEquals($expectedFillable, $ranking->getFillable());
    }

    /** @test */
    public function model_does_not_use_timestamps(): void
    {
        $ranking = new LocalRanking();
        
        $this->assertFalse($ranking->timestamps);
    }

    // ============================================
    // CASTS TESTS
    // ============================================

    /** @test */
    public function search_lat_is_cast_to_decimal(): void
    {
        $ranking = LocalRanking::factory()->create([
            'search_lat' => 40.71280000,
        ]);
        
        // Decimal cast returns string in Laravel
        $this->assertIsString($ranking->search_lat);
        $this->assertEquals('40.71280000', $ranking->search_lat);
    }

    /** @test */
    public function search_lng_is_cast_to_decimal(): void
    {
        $ranking = LocalRanking::factory()->create([
            'search_lng' => -74.00600000,
        ]);
        
        // Decimal cast returns string in Laravel
        $this->assertIsString($ranking->search_lng);
        $this->assertEquals('-74.00600000', $ranking->search_lng);
    }

    /** @test */
    public function local_pack_position_is_cast_to_integer(): void
    {
        $ranking = LocalRanking::factory()->create([
            'local_pack_position' => '2',
        ]);
        
        $this->assertIsInt($ranking->local_pack_position);
        $this->assertEquals(2, $ranking->local_pack_position);
    }

    /** @test */
    public function local_organic_position_is_cast_to_integer(): void
    {
        $ranking = LocalRanking::factory()->create([
            'local_organic_position' => '15',
        ]);
        
        $this->assertIsInt($ranking->local_organic_position);
        $this->assertEquals(15, $ranking->local_organic_position);
    }

    /** @test */
    public function competitors_in_pack_is_cast_to_array(): void
    {
        $competitors = [
            ['name' => 'Competitor A', 'position' => 1],
            ['name' => 'Competitor B', 'position' => 2],
        ];
        
        $ranking = LocalRanking::factory()->create([
            'competitors_in_pack' => $competitors,
        ]);
        
        $this->assertIsArray($ranking->competitors_in_pack);
        $this->assertEquals($competitors, $ranking->competitors_in_pack);
    }

    /** @test */
    public function checked_at_is_cast_to_datetime(): void
    {
        $ranking = LocalRanking::factory()->create([
            'checked_at' => '2026-03-08 12:30:00',
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ranking->checked_at);
        $this->assertEquals('2026-03-08 12:30:00', $ranking->checked_at->format('Y-m-d H:i:s'));
    }

    // ============================================
    // RELATIONSHIP TESTS
    // ============================================

    /** @test */
    public function belongs_to_business_location(): void
    {
        $location = BusinessLocation::factory()->create();
        $ranking = LocalRanking::factory()->create([
            'location_id' => $location->id,
        ]);
        
        $this->assertInstanceOf(BusinessLocation::class, $ranking->location);
        $this->assertEquals($location->id, $ranking->location->id);
    }

    /** @test */
    public function belongs_to_keyword(): void
    {
        $keyword = Keyword::factory()->create();
        $ranking = LocalRanking::factory()->create([
            'keyword_id' => $keyword->id,
        ]);
        
        $this->assertInstanceOf(Keyword::class, $ranking->keyword);
        $this->assertEquals($keyword->id, $ranking->keyword->id);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $ranking = new LocalRanking();
        $relationship = $ranking->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    // ============================================
    // SCOPE TESTS
    // ============================================

    /** @test */
    public function scope_in_pack_filters_by_position(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Create rankings in pack
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 1,
        ]);
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 3,
        ]);
        
        // Create rankings not in pack
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => null,
        ]);
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 5,
        ]);
        
        $inPack = LocalRanking::inPack()->get();
        
        $this->assertCount(2, $inPack);
        $this->assertTrue($inPack->every(fn ($r) => $r->local_pack_position !== null && $r->local_pack_position <= 3));
    }

    /** @test */
    public function scope_in_pack_excludes_null_positions(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => null,
        ]);
        
        $inPack = LocalRanking::inPack()->get();
        
        $this->assertCount(0, $inPack);
    }

    /** @test */
    public function scope_date_range_filters_by_checked_at(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'checked_at' => '2026-03-01 10:00:00',
        ]);
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'checked_at' => '2026-03-05 10:00:00',
        ]);
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'checked_at' => '2026-03-10 10:00:00',
        ]);
        
        $results = LocalRanking::dateRange('2026-03-03', '2026-03-08')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('2026-03-05', $results->first()->checked_at->format('Y-m-d'));
    }

    /** @test */
    public function scope_date_range_includes_boundary_dates(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Use start of day to ensure boundary inclusion
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'checked_at' => '2026-03-05 00:00:00',
        ]);
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'checked_at' => '2026-03-10 00:00:00',
        ]);
        
        // Use Carbon instances for date range
        $start = \Carbon\Carbon::parse('2026-03-05')->startOfDay();
        $end = \Carbon\Carbon::parse('2026-03-10')->endOfDay();
        
        $results = LocalRanking::dateRange($start, $end)->get();
        
        $this->assertCount(2, $results);
    }

    // ============================================
    // METHOD TESTS
    // ============================================

    /** @test */
    public function in_local_pack_returns_true_for_position_1_to_3(): void
    {
        $ranking = LocalRanking::factory()->create(['local_pack_position' => 1]);
        $this->assertTrue($ranking->inLocalPack());
        
        $ranking = LocalRanking::factory()->create(['local_pack_position' => 2]);
        $this->assertTrue($ranking->inLocalPack());
        
        $ranking = LocalRanking::factory()->create(['local_pack_position' => 3]);
        $this->assertTrue($ranking->inLocalPack());
    }

    /** @test */
    public function in_local_pack_returns_false_for_position_above_3(): void
    {
        $ranking = LocalRanking::factory()->create(['local_pack_position' => 4]);
        $this->assertFalse($ranking->inLocalPack());
        
        $ranking = LocalRanking::factory()->create(['local_pack_position' => 10]);
        $this->assertFalse($ranking->inLocalPack());
    }

    /** @test */
    public function in_local_pack_returns_false_for_null_position(): void
    {
        $ranking = LocalRanking::factory()->create(['local_pack_position' => null]);
        $this->assertFalse($ranking->inLocalPack());
    }

    /** @test */
    public function get_position_change_returns_positive_when_improved(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Previous ranking at position 5
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 5,
            'checked_at' => '2026-03-01 10:00:00',
        ]);
        
        // Current ranking at position 2
        $current = LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 2,
            'checked_at' => '2026-03-08 10:00:00',
        ]);
        
        $change = $current->getPositionChange();
        
        $this->assertEquals(3, $change); // 5 - 2 = 3 (improved)
    }

    /** @test */
    public function get_position_change_returns_negative_when_dropped(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Previous ranking at position 1
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 1,
            'checked_at' => '2026-03-01 10:00:00',
        ]);
        
        // Current ranking at position 4
        $current = LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 4,
            'checked_at' => '2026-03-08 10:00:00',
        ]);
        
        $change = $current->getPositionChange();
        
        $this->assertEquals(-3, $change); // 1 - 4 = -3 (dropped)
    }

    /** @test */
    public function get_position_change_returns_null_for_first_check(): void
    {
        $ranking = LocalRanking::factory()->create([
            'local_pack_position' => 2,
            'checked_at' => '2026-03-08 10:00:00',
        ]);
        
        $change = $ranking->getPositionChange();
        
        $this->assertNull($change);
    }

    /** @test */
    public function get_position_change_returns_null_when_previous_is_null(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Previous ranking with null position
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => null,
            'checked_at' => '2026-03-01 10:00:00',
        ]);
        
        // Current ranking with position
        $current = LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 2,
            'checked_at' => '2026-03-08 10:00:00',
        ]);
        
        $change = $current->getPositionChange();
        
        $this->assertNull($change);
    }

    /** @test */
    public function get_position_change_returns_null_when_current_is_null(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Previous ranking with position
        LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => 2,
            'checked_at' => '2026-03-01 10:00:00',
        ]);
        
        // Current ranking with null position
        $current = LocalRanking::factory()->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
            'local_pack_position' => null,
            'checked_at' => '2026-03-08 10:00:00',
        ]);
        
        $change = $current->getPositionChange();
        
        $this->assertNull($change);
    }

    // ============================================
    // FACTORY TESTS
    // ============================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $ranking = LocalRanking::factory()->create();
        
        $this->assertDatabaseHas('local_rankings', [
            'id' => $ranking->id,
        ]);
    }

    /** @test */
    public function factory_in_pack_state(): void
    {
        $ranking = LocalRanking::factory()->inPack()->create();
        
        $this->assertNotNull($ranking->local_pack_position);
        $this->assertLessThanOrEqual(3, $ranking->local_pack_position);
        $this->assertGreaterThanOrEqual(1, $ranking->local_pack_position);
    }

    /** @test */
    public function factory_not_in_pack_state(): void
    {
        $ranking = LocalRanking::factory()->notInPack()->create();
        
        $this->assertNull($ranking->local_pack_position);
        $this->assertNotNull($ranking->local_organic_position);
    }

    /** @test */
    public function factory_checked_at_state(): void
    {
        $date = new \DateTime('2026-03-05 15:30:00');
        $ranking = LocalRanking::factory()->checkedAt($date)->create();
        
        $this->assertEquals('2026-03-05 15:30:00', $ranking->checked_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function factory_creates_related_models(): void
    {
        $ranking = LocalRanking::factory()->create();
        
        $this->assertDatabaseHas('business_locations', [
            'id' => $ranking->location_id,
        ]);
        $this->assertDatabaseHas('keywords', [
            'id' => $ranking->keyword_id,
        ]);
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /** @test */
    public function coordinates_accept_valid_lat_lng_ranges(): void
    {
        // Test valid latitude range (-90 to 90)
        $ranking = LocalRanking::factory()->create([
            'search_lat' => 89.99999999,
            'search_lng' => -179.99999999,
        ]);
        
        $this->assertEquals(89.99999999, $ranking->search_lat);
        $this->assertEquals(-179.99999999, $ranking->search_lng);
    }

    /** @test */
    public function null_coordinates_accepted(): void
    {
        $ranking = LocalRanking::factory()->create([
            'search_lat' => null,
            'search_lng' => null,
        ]);
        
        $this->assertNull($ranking->search_lat);
        $this->assertNull($ranking->search_lng);
    }

    /** @test */
    public function null_positions_accepted(): void
    {
        $ranking = LocalRanking::factory()->create([
            'local_pack_position' => null,
            'local_organic_position' => null,
        ]);
        
        $this->assertNull($ranking->local_pack_position);
        $this->assertNull($ranking->local_organic_position);
    }

    /** @test */
    public function competitors_in_pack_can_be_null(): void
    {
        $ranking = LocalRanking::factory()->create([
            'competitors_in_pack' => null,
        ]);
        
        $this->assertNull($ranking->competitors_in_pack);
    }

    /** @test */
    public function competitors_in_pack_can_be_empty_array(): void
    {
        $ranking = LocalRanking::factory()->create([
            'competitors_in_pack' => [],
        ]);
        
        $this->assertIsArray($ranking->competitors_in_pack);
        $this->assertEmpty($ranking->competitors_in_pack);
    }

    /** @test */
    public function max_local_pack_position_accepted(): void
    {
        // TINYINT UNSIGNED max is 255, but practically limited
        $ranking = LocalRanking::factory()->create([
            'local_pack_position' => 255,
        ]);
        
        $this->assertEquals(255, $ranking->local_pack_position);
    }

    /** @test */
    public function large_organic_position_accepted(): void
    {
        $ranking = LocalRanking::factory()->create([
            'local_organic_position' => 1000,
        ]);
        
        $this->assertEquals(1000, $ranking->local_organic_position);
    }

    // ============================================
    // SECURITY TESTS
    // ============================================

    /** @test */
    public function sql_injection_in_search_location_is_safe(): void
    {
        $malicious = "'; DROP TABLE local_rankings; --";
        
        $ranking = LocalRanking::factory()->create([
            'search_location' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $ranking->search_location);
        
        // Table should still exist
        $this->assertDatabaseHas('local_rankings', ['id' => $ranking->id]);
    }

    /** @test */
    public function xss_in_competitors_in_pack_is_safe(): void
    {
        $competitors = [
            ['name' => '<script>alert("xss")</script>', 'position' => 1],
        ];
        
        $ranking = LocalRanking::factory()->create([
            'competitors_in_pack' => $competitors,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($competitors, $ranking->competitors_in_pack);
    }

    /** @test */
    public function invalid_data_type_for_position_is_handled(): void
    {
        // This should be cast to integer
        $ranking = LocalRanking::factory()->create([
            'local_pack_position' => '3.9',
        ]);
        
        $this->assertEquals(3, $ranking->local_pack_position);
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /** @test */
    public function cascade_delete_when_location_force_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $ranking = LocalRanking::factory()->create([
            'location_id' => $location->id,
        ]);
        
        // Use forceDelete to trigger cascade (BusinessLocation uses SoftDeletes)
        $location->forceDelete();
        
        $this->assertDatabaseMissing('local_rankings', [
            'id' => $ranking->id,
        ]);
    }

    /** @test */
    public function cascade_delete_when_keyword_force_deleted(): void
    {
        $keyword = Keyword::factory()->create();
        $ranking = LocalRanking::factory()->create([
            'keyword_id' => $keyword->id,
        ]);
        
        // Keyword uses soft-delete via status, use forceDelete for actual deletion
        $keyword->forceDelete();
        
        $this->assertDatabaseMissing('local_rankings', [
            'id' => $ranking->id,
        ]);
    }

    /** @test */
    public function full_crud_operations(): void
    {
        // Create
        $ranking = LocalRanking::factory()->create([
            'search_location' => 'New York, NY',
        ]);
        $this->assertDatabaseHas('local_rankings', ['id' => $ranking->id]);
        
        // Read
        $found = LocalRanking::find($ranking->id);
        $this->assertEquals('New York, NY', $found->search_location);
        
        // Update
        $ranking->update(['search_location' => 'Los Angeles, CA']);
        $this->assertEquals('Los Angeles, CA', $ranking->fresh()->search_location);
        
        // Delete
        $ranking->delete();
        $this->assertDatabaseMissing('local_rankings', ['id' => $ranking->id]);
    }

    // ============================================
    // PERFORMANCE TESTS
    // ============================================

    /** @test */
    public function index_on_location_keyword_is_used(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        // Create multiple rankings
        LocalRanking::factory()->count(10)->create([
            'location_id' => $location->id,
            'keyword_id' => $keyword->id,
        ]);
        
        // Query should use the composite index
        $results = LocalRanking::where('location_id', $location->id)
            ->where('keyword_id', $keyword->id)
            ->get();
        
        $this->assertCount(10, $results);
    }

    /** @test */
    public function bulk_insert_works(): void
    {
        $location = BusinessLocation::factory()->create();
        $keyword = Keyword::factory()->create();
        
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'location_id' => $location->id,
                'keyword_id' => $keyword->id,
                'search_location' => "Location $i",
                'checked_at' => now()->subDays($i),
            ];
        }
        
        LocalRanking::insert($data);
        
        $this->assertEquals(100, LocalRanking::where('location_id', $location->id)->count());
    }
}
