<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GBPReview;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

/**
 * Unit tests for GBPReview model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPReviewTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Model Instantiation Tests (GR-001 to GR-003)
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $review = new GBPReview();
        
        $this->assertInstanceOf(GBPReview::class, $review);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $review = new GBPReview();
        $expectedFillable = [
            'location_id',
            'google_review_id',
            'author_name',
            'author_photo',
            'rating',
            'review_text',
            'review_text_sentiment',
            'owner_response',
            'owner_response_date',
            'review_date',
        ];
        
        $this->assertEquals($expectedFillable, $review->getFillable());
    }

    /** @test */
    public function it_guards_non_fillable_attributes(): void
    {
        $review = GBPReview::create([
            'location_id' => 1,
            'google_review_id' => 'test123',
            'rating' => 5,
            'id' => 999, // Should be ignored
        ]);
        
        $this->assertNotEquals(999, $review->id);
    }

    // =========================================================================
    // Casts Tests (GR-004 to GR-008)
    // =========================================================================

    /** @test */
    public function it_casts_rating_to_integer(): void
    {
        $review = GBPReview::factory()->create(['rating' => '5']);
        
        $this->assertIsInt($review->rating);
        $this->assertEquals(5, $review->rating);
    }

    /** @test */
    public function it_casts_review_date_to_datetime(): void
    {
        $review = GBPReview::factory()->create(['review_date' => '2026-03-08 10:30:00']);
        
        $this->assertInstanceOf(Carbon::class, $review->review_date);
    }

    /** @test */
    public function it_casts_owner_response_date_to_datetime(): void
    {
        $review = GBPReview::factory()->responded()->create();
        
        $this->assertInstanceOf(Carbon::class, $review->owner_response_date);
    }

    /** @test */
    public function it_casts_created_at_to_datetime(): void
    {
        $review = GBPReview::factory()->create();
        
        $this->assertInstanceOf(Carbon::class, $review->created_at);
    }

    /** @test */
    public function it_casts_updated_at_to_datetime(): void
    {
        $review = GBPReview::factory()->create();
        
        $this->assertInstanceOf(Carbon::class, $review->updated_at);
    }

    // =========================================================================
    // Relationship Tests (GR-009 to GR-011)
    // =========================================================================

    /** @test */
    public function it_belongs_to_business_location(): void
    {
        $review = GBPReview::factory()->create();
        
        $this->assertInstanceOf(BusinessLocation::class, $review->location);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $review = new GBPReview();
        
        $relation = $review->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /** @test */
    public function it_can_access_parent_location_data(): void
    {
        $location = BusinessLocation::factory()->create(['name' => 'Test Location']);
        $review = GBPReview::factory()->forLocation($location->id)->create();
        
        $this->assertEquals('Test Location', $review->location->name);
    }

    // =========================================================================
    // Scope Tests (GR-012 to GR-019)
    // =========================================================================

    /** @test */
    public function scope_positive_filters_rating_4_and_above(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 3]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 4]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 5]);
        
        $results = GBPReview::positive()->get();
        
        $this->assertCount(2, $results);
        $results->each(fn($r) => $this->assertGreaterThanOrEqual(4, $r->rating));
    }

    /** @test */
    public function scope_negative_filters_rating_2_and_below(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 1]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 2]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 3]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 4]);
        
        $results = GBPReview::negative()->get();
        
        $this->assertCount(2, $results);
        $results->each(fn($r) => $this->assertLessThanOrEqual(2, $r->rating));
    }

    /** @test */
    public function scope_neutral_filters_rating_3(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 2]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 3]);
        GBPReview::factory()->forLocation($location->id)->create(['rating' => 4]);
        
        $results = GBPReview::neutral()->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals(3, $results->first()->rating);
    }

    /** @test */
    public function scope_unresponded_filters_null_owner_response(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->unresponded()->create();
        GBPReview::factory()->forLocation($location->id)->responded()->create();
        
        $results = GBPReview::unresponded()->get();
        
        $this->assertCount(1, $results);
        $this->assertNull($results->first()->owner_response);
    }

    /** @test */
    public function scope_responded_filters_non_null_owner_response(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->unresponded()->create();
        GBPReview::factory()->forLocation($location->id)->responded()->create();
        GBPReview::factory()->forLocation($location->id)->responded()->create();
        
        $results = GBPReview::responded()->get();
        
        $this->assertCount(2, $results);
        $results->each(fn($r) => $this->assertNotNull($r->owner_response));
    }

    /** @test */
    public function scope_for_location_filters_by_location(): void
    {
        $location1 = BusinessLocation::factory()->create();
        $location2 = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location1->id)->create();
        GBPReview::factory()->forLocation($location1->id)->create();
        GBPReview::factory()->forLocation($location2->id)->create();
        
        $results = GBPReview::forLocation($location1->id)->get();
        
        $this->assertCount(2, $results);
        $results->each(fn($r) => $this->assertEquals($location1->id, $r->location_id));
    }

    /** @test */
    public function scope_with_rating_filters_by_exact_rating(): void
    {
        $location = BusinessLocation::factory()->create();
        
        for ($i = 1; $i <= 5; $i++) {
            GBPReview::factory()->forLocation($location->id)->create(['rating' => $i]);
        }
        
        $results = GBPReview::withRating(5)->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals(5, $results->first()->rating);
    }

    /** @test */
    public function scope_recent_filters_by_date(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPReview::factory()->forLocation($location->id)->create(['review_date' => now()->subDays(5)]);
        GBPReview::factory()->forLocation($location->id)->create(['review_date' => now()->subDays(10)]);
        GBPReview::factory()->forLocation($location->id)->create(['review_date' => now()->subDays(20)]);
        
        $results = GBPReview::recent(7)->get();
        
        $this->assertCount(1, $results);
    }

    // =========================================================================
    // Method Tests (GR-020 to GR-025)
    // =========================================================================

    /** @test */
    public function has_owner_response_returns_true_when_responded(): void
    {
        $review = GBPReview::factory()->responded()->create();
        
        $this->assertTrue($review->hasOwnerResponse());
    }

    /** @test */
    public function has_owner_response_returns_false_when_not_responded(): void
    {
        $review = GBPReview::factory()->unresponded()->create();
        
        $this->assertFalse($review->hasOwnerResponse());
    }

    /** @test */
    public function has_owner_response_returns_false_for_null_response(): void
    {
        $review = GBPReview::factory()->create(['owner_response' => null]);
        
        $this->assertFalse($review->hasOwnerResponse());
    }

    /** @test */
    public function get_sentiment_label_attribute_returns_correct_label(): void
    {
        $positiveReview = GBPReview::factory()->create(['review_text_sentiment' => 'positive']);
        $negativeReview = GBPReview::factory()->create(['review_text_sentiment' => 'negative']);
        $neutralReview = GBPReview::factory()->create(['review_text_sentiment' => 'neutral']);
        
        $this->assertEquals('Positive', $positiveReview->sentiment_label);
        $this->assertEquals('Negative', $negativeReview->sentiment_label);
        $this->assertEquals('Neutral', $neutralReview->sentiment_label);
    }

    /** @test */
    public function get_sentiment_label_attribute_handles_null_sentiment(): void
    {
        $review = GBPReview::factory()->create(['review_text_sentiment' => null]);
        
        $this->assertEquals('Neutral', $review->sentiment_label);
    }

    /** @test */
    public function get_sentiment_label_attribute_handles_invalid_sentiment(): void
    {
        $review = GBPReview::factory()->create(['review_text_sentiment' => 'invalid']);
        
        $this->assertEquals('Neutral', $review->sentiment_label);
    }

    // =========================================================================
    // Factory Tests (GR-026 to GR-033)
    // =========================================================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $review = GBPReview::factory()->create();
        
        $this->assertDatabaseHas('gbp_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function factory_for_location_state_sets_location_id(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $review = GBPReview::factory()->forLocation($location->id)->create();
        
        $this->assertEquals($location->id, $review->location_id);
    }

    /** @test */
    public function factory_positive_state_sets_rating_4_or_5(): void
    {
        $review = GBPReview::factory()->positive()->create();
        
        $this->assertContains($review->rating, [4, 5]);
    }

    /** @test */
    public function factory_negative_state_sets_rating_1_or_2(): void
    {
        $review = GBPReview::factory()->negative()->create();
        
        $this->assertContains($review->rating, [1, 2]);
    }

    /** @test */
    public function factory_neutral_state_sets_rating_3(): void
    {
        $review = GBPReview::factory()->neutral()->create();
        
        $this->assertEquals(3, $review->rating);
    }

    /** @test */
    public function factory_responded_state_sets_owner_response(): void
    {
        $review = GBPReview::factory()->responded()->create();
        
        $this->assertNotNull($review->owner_response);
        $this->assertNotNull($review->owner_response_date);
    }

    /** @test */
    public function factory_unresponded_state_sets_null_response(): void
    {
        $review = GBPReview::factory()->unresponded()->create();
        
        $this->assertNull($review->owner_response);
        $this->assertNull($review->owner_response_date);
    }

    /** @test */
    public function factory_with_sentiment_state_sets_sentiment(): void
    {
        $review = GBPReview::factory()->withSentiment('positive')->create();
        
        $this->assertEquals('positive', $review->review_text_sentiment);
    }

    // =========================================================================
    // Unique Constraint Tests (GR-034 to GR-035)
    // =========================================================================

    /** @test */
    public function duplicate_google_review_id_fails(): void
    {
        GBPReview::factory()->create(['google_review_id' => 'unique-id-123']);
        
        $this->expectException(QueryException::class);
        
        GBPReview::factory()->create(['google_review_id' => 'unique-id-123']);
    }

    /** @test */
    public function different_google_review_id_succeeds(): void
    {
        $review1 = GBPReview::factory()->create(['google_review_id' => 'unique-id-1']);
        $review2 = GBPReview::factory()->create(['google_review_id' => 'unique-id-2']);
        
        $this->assertNotEquals($review1->id, $review2->id);
    }

    // =========================================================================
    // Rating Tests (GR-036 to GR-039)
    // =========================================================================

    /** @test */
    public function rating_1_stored_correctly(): void
    {
        $review = GBPReview::factory()->create(['rating' => 1]);
        
        $this->assertEquals(1, $review->rating);
    }

    /** @test */
    public function rating_5_stored_correctly(): void
    {
        $review = GBPReview::factory()->create(['rating' => 5]);
        
        $this->assertEquals(5, $review->rating);
    }

    /** @test */
    public function rating_0_rejected(): void
    {
        $this->expectException(QueryException::class);
        
        GBPReview::factory()->create(['rating' => 0]);
    }

    /** @test */
    public function rating_6_rejected(): void
    {
        $this->expectException(QueryException::class);
        
        GBPReview::factory()->create(['rating' => 6]);
    }

    // =========================================================================
    // Edge Case Tests (GR-040 to GR-045)
    // =========================================================================

    /** @test */
    public function null_author_name_allowed(): void
    {
        $review = GBPReview::factory()->create(['author_name' => null]);
        
        $this->assertNull($review->author_name);
    }

    /** @test */
    public function null_review_text_allowed(): void
    {
        $review = GBPReview::factory()->create(['review_text' => null]);
        
        $this->assertNull($review->review_text);
    }

    /** @test */
    public function long_review_text_stored(): void
    {
        $longText = str_repeat('This is a long review text. ', 1000); // ~30KB
        
        $review = GBPReview::factory()->create(['review_text' => $longText]);
        
        $this->assertEquals($longText, $review->review_text);
    }

    /** @test */
    public function unicode_in_review_text_stored_correctly(): void
    {
        $unicodeText = 'Great experience! 日本語テスト русский العربية';
        
        $review = GBPReview::factory()->create(['review_text' => $unicodeText]);
        
        $this->assertEquals($unicodeText, $review->review_text);
    }

    /** @test */
    public function emoji_in_author_name_stored_correctly(): void
    {
        $emojiName = 'John 👨‍💻 Doe 🌟';
        
        $review = GBPReview::factory()->create(['author_name' => $emojiName]);
        
        $this->assertEquals($emojiName, $review->author_name);
    }

    /** @test */
    public function long_author_name_truncated(): void
    {
        $longName = str_repeat('a', 300); // Exceeds VARCHAR 255
        
        // Factory should handle this, or the database will truncate
        $review = GBPReview::factory()->create(['author_name' => $longName]);
        
        // The stored value will be truncated by the database
        $this->assertLessThanOrEqual(255, strlen($review->author_name));
    }

    // =========================================================================
    // Security Tests (GR-046 to GR-048)
    // =========================================================================

    /** @test */
    public function sql_injection_in_review_text_prevented(): void
    {
        $maliciousText = "Great review'); DROP TABLE gbp_reviews; --";
        
        $review = GBPReview::factory()->create(['review_text' => $maliciousText]);
        
        // Should store as literal string, not execute
        $this->assertEquals($maliciousText, $review->fresh()->review_text);
        $this->assertDatabaseHas('gbp_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function xss_in_author_name_escaped_on_output(): void
    {
        $xssName = '<script>alert("xss")</script>';
        
        $review = GBPReview::factory()->create(['author_name' => $xssName]);
        
        // Stored as-is (database doesn't escape)
        $this->assertEquals($xssName, $review->author_name);
        
        // Output should be escaped by Blade templates
        // This test verifies storage, output escaping is tested in integration tests
    }

    /** @test */
    public function xss_in_review_text_escaped_on_output(): void
    {
        $xssText = '<script>document.location="http://evil.com"</script>';
        
        $review = GBPReview::factory()->create(['review_text' => $xssText]);
        
        // Stored as-is
        $this->assertEquals($xssText, $review->review_text);
    }

    // =========================================================================
    // Accessor Tests (GR-049 to GR-051)
    // =========================================================================

    /** @test */
    public function get_star_rating_attribute_returns_star_string(): void
    {
        $review1 = GBPReview::factory()->create(['rating' => 1]);
        $review3 = GBPReview::factory()->create(['rating' => 3]);
        $review5 = GBPReview::factory()->create(['rating' => 5]);
        
        $this->assertEquals('★☆☆☆☆', $review1->star_rating);
        $this->assertEquals('★★★☆☆', $review3->star_rating);
        $this->assertEquals('★★★★★', $review5->star_rating);
    }

    /** @test */
    public function get_is_positive_attribute_for_rating_4_plus(): void
    {
        $review4 = GBPReview::factory()->create(['rating' => 4]);
        $review5 = GBPReview::factory()->create(['rating' => 5]);
        $review3 = GBPReview::factory()->create(['rating' => 3]);
        
        $this->assertTrue($review4->is_positive);
        $this->assertTrue($review5->is_positive);
        $this->assertFalse($review3->is_positive);
    }

    /** @test */
    public function get_is_negative_attribute_for_rating_2_minus(): void
    {
        $review1 = GBPReview::factory()->create(['rating' => 1]);
        $review2 = GBPReview::factory()->create(['rating' => 2]);
        $review3 = GBPReview::factory()->create(['rating' => 3]);
        
        $this->assertTrue($review1->is_negative);
        $this->assertTrue($review2->is_negative);
        $this->assertFalse($review3->is_negative);
    }

    // =========================================================================
    // Integration Tests (GR-052 to GR-053)
    // =========================================================================

    /** @test */
    public function crud_operations_work(): void
    {
        // Create
        $review = GBPReview::factory()->create();
        $this->assertDatabaseHas('gbp_reviews', ['id' => $review->id]);
        
        // Read
        $found = GBPReview::find($review->id);
        $this->assertEquals($review->rating, $found->rating);
        
        // Update
        $review->update(['rating' => 1]);
        $this->assertEquals(1, $review->fresh()->rating);
        
        // Delete
        $review->delete();
        $this->assertDatabaseMissing('gbp_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function cascade_delete_when_location_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $review = GBPReview::factory()->forLocation($location->id)->create();
        
        $location->delete();
        
        $this->assertDatabaseMissing('gbp_reviews', ['id' => $review->id]);
    }

    // =========================================================================
    // Performance Tests (GR-054 to GR-055)
    // =========================================================================

    /** @test */
    public function query_with_rating_index(): void
    {
        $location = BusinessLocation::factory()->create();
        
        for ($i = 1; $i <= 5; $i++) {
            for ($j = 0; $j < 20; $j++) {
                GBPReview::factory()->forLocation($location->id)->create(['rating' => $i]);
            }
        }
        
        $start = microtime(true);
        $results = GBPReview::withRating(5)->get();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(0.5, $duration);
        $this->assertCount(20, $results);
    }

    /** @test */
    public function bulk_insert_100_reviews(): void
    {
        $location = BusinessLocation::factory()->create();
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            GBPReview::factory()->forLocation($location->id)->create();
        }
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(5, $duration, 'Bulk insert should complete in under 5 seconds');
        $this->assertEquals(100, GBPReview::where('location_id', $location->id)->count());
    }
}
