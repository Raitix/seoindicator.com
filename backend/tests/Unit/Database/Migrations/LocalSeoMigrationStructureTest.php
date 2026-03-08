<?php

namespace Tests\Unit\Database\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit tests for Local SEO database migration column structures.
 * 
 * Tests verify precise column types, nullable states, default values,
 * and length constraints for all 7 tables required by SPEC-035.
 * 
 * @see TEST-35-1 for test documentation
 * @see TASK-35-1 for task details
 */
class LocalSeoMigrationStructureTest extends TestCase
{
    // =========================================================================
    // Column Length Tests
    // =========================================================================

    /** @test */
    public function business_locations_gbp_place_id_max_length_is_255(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_place_id');
        $this->assertEquals(255, $column->getLength());
    }

    /** @test */
    public function business_locations_address_max_length_is_500(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'address');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function business_locations_phone_max_length_is_50(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'phone');
        $this->assertEquals(50, $column->getLength());
    }

    /** @test */
    public function business_locations_website_max_length_is_500(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'website');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function gbp_reviews_google_review_id_max_length_is_255(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'google_review_id');
        $this->assertEquals(255, $column->getLength());
    }

    /** @test */
    public function gbp_reviews_author_name_max_length_is_255(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'author_name');
        $this->assertEquals(255, $column->getLength());
    }

    /** @test */
    public function gbp_reviews_author_photo_max_length_is_500(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'author_photo');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function gbp_posts_google_post_id_max_length_is_255(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'google_post_id');
        $this->assertEquals(255, $column->getLength());
    }

    /** @test */
    public function gbp_posts_post_type_max_length_is_50(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'post_type');
        $this->assertEquals(50, $column->getLength());
    }

    /** @test */
    public function gbp_posts_call_to_action_max_length_is_100(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'call_to_action');
        $this->assertEquals(100, $column->getLength());
    }

    /** @test */
    public function gbp_posts_media_url_max_length_is_500(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'media_url');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function gbp_posts_call_to_action_url_max_length_is_500(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'call_to_action_url');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function local_seo_alerts_alert_type_max_length_is_50(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'alert_type');
        $this->assertEquals(50, $column->getLength());
    }

    /** @test */
    public function local_seo_alerts_severity_max_length_is_20(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'severity');
        $this->assertEquals(20, $column->getLength());
    }

    /** @test */
    public function gbp_reviews_review_text_sentiment_max_length_is_20(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'review_text_sentiment');
        $this->assertEquals(20, $column->getLength());
    }

    // =========================================================================
    // Decimal Precision Tests
    // =========================================================================

    /** @test */
    public function business_locations_latitude_has_correct_precision(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'latitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(10, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function business_locations_longitude_has_correct_precision(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'longitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(11, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function local_rankings_search_lat_has_correct_precision(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'search_lat');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(10, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function local_rankings_search_lng_has_correct_precision(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'search_lng');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(11, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function local_competitors_competitor_rating_has_correct_precision(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_rating');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(2, $column->getPrecision());
        $this->assertEquals(1, $column->getScale());
    }

    // =========================================================================
    // Default Value Tests
    // =========================================================================

    /** @test */
    public function business_locations_gbp_connected_defaults_to_false(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_connected');
        $this->assertFalse($column->getDefault());
    }

    /** @test */
    public function gbp_insights_views_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'views');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gbp_insights_searches_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'searches');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gbp_insights_calls_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'calls');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gbp_insights_directions_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'directions');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gbp_insights_website_clicks_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'website_clicks');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gbp_insights_photos_views_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'photos_views');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function local_competitors_competitor_review_count_defaults_to_zero(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_review_count');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function local_seo_alerts_is_read_defaults_to_false(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'is_read');
        $this->assertFalse($column->getDefault());
    }

    // =========================================================================
    // Nullable State Tests
    // =========================================================================

    /** @test */
    public function business_locations_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['user_id', 'site_id', 'name', 'address'];
        
        foreach ($requiredColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('business_locations', $col);
            $this->assertFalse($column->getNullable(), "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function business_locations_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['gbp_place_id', 'phone', 'website', 'category', 'latitude', 'longitude', 'settings', 'deleted_at'];
        
        foreach ($nullableColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('business_locations', $col);
            $this->assertTrue($column->getNullable(), "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function gbp_reviews_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['location_id', 'google_review_id', 'rating'];
        
        foreach ($requiredColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('gbp_reviews', $col);
            $this->assertFalse($column->getNullable(), "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function gbp_reviews_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['author_name', 'author_photo', 'review_text', 'review_text_sentiment', 'owner_response', 'owner_response_date', 'review_date'];
        
        foreach ($nullableColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('gbp_reviews', $col);
            $this->assertTrue($column->getNullable(), "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function local_rankings_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['location_id', 'keyword_id', 'search_location'];
        
        foreach ($requiredColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('local_rankings', $col);
            $this->assertFalse($column->getNullable(), "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function local_rankings_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['search_lat', 'search_lng', 'local_pack_position', 'local_organic_position', 'competitors_in_pack'];
        
        foreach ($nullableColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('local_rankings', $col);
            $this->assertTrue($column->getNullable(), "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function local_seo_alerts_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['user_id', 'location_id', 'alert_type', 'severity', 'title', 'message'];
        
        foreach ($requiredColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('local_seo_alerts', $col);
            $this->assertFalse($column->getNullable(), "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function local_seo_alerts_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['data'];
        
        foreach ($nullableColumns as $col) {
            $column = DB::connection()->getDoctrineColumn('local_seo_alerts', $col);
            $this->assertTrue($column->getNullable(), "Column {$col} should be nullable");
        }
    }

    // =========================================================================
    // Unsigned Column Tests
    // =========================================================================

    /** @test */
    public function business_locations_user_id_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'user_id');
        $this->assertTrue($column->getUnsigned(), 'user_id should be unsigned');
    }

    /** @test */
    public function business_locations_site_id_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'site_id');
        $this->assertTrue($column->getUnsigned(), 'site_id should be unsigned');
    }

    /** @test */
    public function gbp_insights_views_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'views');
        $this->assertTrue($column->getUnsigned(), 'views should be unsigned');
    }

    /** @test */
    public function gbp_insights_searches_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'searches');
        $this->assertTrue($column->getUnsigned(), 'searches should be unsigned');
    }

    /** @test */
    public function gbp_insights_calls_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'calls');
        $this->assertTrue($column->getUnsigned(), 'calls should be unsigned');
    }

    /** @test */
    public function gbp_reviews_rating_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'rating');
        $this->assertTrue($column->getUnsigned(), 'rating should be unsigned');
    }

    /** @test */
    public function local_rankings_local_pack_position_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'local_pack_position');
        $this->assertTrue($column->getUnsigned(), 'local_pack_position should be unsigned');
    }

    /** @test */
    public function local_rankings_local_organic_position_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'local_organic_position');
        $this->assertTrue($column->getUnsigned(), 'local_organic_position should be unsigned');
    }

    /** @test */
    public function local_competitors_competitor_review_count_is_unsigned(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_review_count');
        $this->assertTrue($column->getUnsigned(), 'competitor_review_count should be unsigned');
    }

    // =========================================================================
    // Table Column Count Tests
    // =========================================================================

    /** @test */
    public function business_locations_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('business_locations');
        $expectedCount = 16; // id, user_id, site_id, gbp_place_id, name, address, phone, website, category, latitude, longitude, gbp_connected, settings, timestamps(2), deleted_at
        $this->assertCount($expectedCount, $columns, 'business_locations should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_insights_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_insights');
        $expectedCount = 11; // id, location_id, views, searches, calls, directions, website_clicks, photos_views, insights_date, created_at
        $this->assertCount($expectedCount, $columns, 'gbp_insights should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_reviews_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_reviews');
        $expectedCount = 14; // id, location_id, google_review_id, author_name, author_photo, rating, review_text, review_text_sentiment, owner_response, owner_response_date, review_date, timestamps(2)
        $this->assertCount($expectedCount, $columns, 'gbp_reviews should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_rankings_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_rankings');
        $expectedCount = 11; // id, location_id, keyword_id, search_location, search_lat, search_lng, local_pack_position, local_organic_position, competitors_in_pack, checked_at
        $this->assertCount($expectedCount, $columns, 'local_rankings should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_competitors_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_competitors');
        $expectedCount = 10; // id, location_id, competitor_name, competitor_gbp_id, competitor_address, competitor_rating, competitor_review_count, timestamps(2)
        $this->assertCount($expectedCount, $columns, 'local_competitors should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_posts_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_posts');
        $expectedCount = 12; // id, location_id, google_post_id, post_type, title, content, media_url, call_to_action, call_to_action_url, published_at, created_at
        $this->assertCount($expectedCount, $columns, 'gbp_posts should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_seo_alerts_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_seo_alerts');
        $expectedCount = 11; // id, user_id, location_id, alert_type, severity, title, message, data, is_read, created_at
        $this->assertCount($expectedCount, $columns, 'local_seo_alerts should have ' . $expectedCount . ' columns');
    }

    // =========================================================================
    // JSON Column Tests
    // =========================================================================

    /** @test */
    public function business_locations_settings_is_json_type(): void
    {
        $column = DB::connection()->getDoctrineColumn('business_locations', 'settings');
        $type = $column->getType()->getName();
        $this->assertTrue(
            in_array($type, ['json', 'text']),
            "settings column should be json or text type, got {$type}"
        );
    }

    /** @test */
    public function local_rankings_competitors_in_pack_is_json_type(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'competitors_in_pack');
        $type = $column->getType()->getName();
        $this->assertTrue(
            in_array($type, ['json', 'text']),
            "competitors_in_pack column should be json or text type, got {$type}"
        );
    }

    /** @test */
    public function local_seo_alerts_data_is_json_type(): void
    {
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'data');
        $type = $column->getType()->getName();
        $this->assertTrue(
            in_array($type, ['json', 'text']),
            "data column should be json or text type, got {$type}"
        );
    }
}
