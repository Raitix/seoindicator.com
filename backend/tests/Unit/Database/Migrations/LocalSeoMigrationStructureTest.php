<?php

namespace Tests\Unit\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    /**
     * Check if we're running on SQLite.
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Get column info for SQLite.
     */
    private function getSqliteColumnInfo(string $table): array
    {
        $columns = [];
        $result = DB::select("PRAGMA table_info({$table})");
        foreach ($result as $row) {
            $columns[$row->name] = $row;
        }
        return $columns;
    }

    // =========================================================================
    // Column Existence Tests (works on both MySQL and SQLite)
    // =========================================================================

    /** @test */
    public function business_locations_has_all_required_columns(): void
    {
        $columns = ['id', 'user_id', 'site_id', 'gbp_place_id', 'name', 'address', 'phone', 'website', 'category', 'latitude', 'longitude', 'gbp_connected', 'settings', 'created_at', 'updated_at', 'deleted_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('business_locations', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function gbp_insights_has_all_required_columns(): void
    {
        $columns = ['id', 'location_id', 'views', 'searches', 'calls', 'directions', 'website_clicks', 'photos_views', 'insights_date', 'created_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('gbp_insights', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function gbp_reviews_has_all_required_columns(): void
    {
        $columns = ['id', 'location_id', 'google_review_id', 'author_name', 'author_photo', 'rating', 'review_text', 'review_text_sentiment', 'owner_response', 'owner_response_date', 'review_date', 'created_at', 'updated_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('gbp_reviews', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function local_rankings_has_all_required_columns(): void
    {
        $columns = ['id', 'location_id', 'keyword_id', 'search_location', 'search_lat', 'search_lng', 'local_pack_position', 'local_organic_position', 'competitors_in_pack', 'checked_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('local_rankings', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function local_competitors_has_all_required_columns(): void
    {
        $columns = ['id', 'location_id', 'competitor_name', 'competitor_gbp_id', 'competitor_address', 'competitor_rating', 'competitor_review_count', 'created_at', 'updated_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('local_competitors', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function gbp_posts_has_all_required_columns(): void
    {
        $columns = ['id', 'location_id', 'google_post_id', 'post_type', 'title', 'content', 'media_url', 'call_to_action', 'call_to_action_url', 'published_at', 'created_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('gbp_posts', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function local_seo_alerts_has_all_required_columns(): void
    {
        $columns = ['id', 'user_id', 'location_id', 'alert_type', 'severity', 'title', 'message', 'data', 'is_read', 'created_at'];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('local_seo_alerts', $column), "Column {$column} should exist");
        }
    }

    // =========================================================================
    // Column Type Tests (SQLite-compatible)
    // =========================================================================

    /** @test */
    public function business_locations_latitude_is_decimal_type(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('business_locations');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['latitude']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('business_locations', 'latitude');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function business_locations_longitude_is_decimal_type(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('business_locations');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['longitude']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('business_locations', 'longitude');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function business_locations_gbp_connected_is_boolean(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('business_locations');
            $this->assertStringContainsString('TINYINT(1)', strtoupper($columns['gbp_connected']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_connected');
            $this->assertEquals('boolean', $column->getType()->getName());
        }
    }

    /** @test */
    public function business_locations_settings_is_json_type(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('business_locations');
            $this->assertStringContainsString('TEXT', strtoupper($columns['settings']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('business_locations', 'settings');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function gbp_insights_views_is_integer(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('gbp_insights');
            $this->assertStringContainsString('INTEGER', strtoupper($columns['views']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('gbp_insights', 'views');
            $this->assertEquals('integer', $column->getType()->getName());
        }
    }

    /** @test */
    public function gbp_insights_insights_date_is_date(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('gbp_insights');
            // SQLite stores dates as TEXT
            $this->assertTrue(
                str_contains(strtoupper($columns['insights_date']->type), 'TEXT') ||
                str_contains(strtoupper($columns['insights_date']->type), 'DATE'),
                'insights_date should be TEXT or DATE type'
            );
        } else {
            $column = DB::connection()->getDoctrineColumn('gbp_insights', 'insights_date');
            $this->assertEquals('date', $column->getType()->getName());
        }
    }

    /** @test */
    public function gbp_reviews_rating_is_tiny_integer(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('gbp_reviews');
            $this->assertStringContainsString('INTEGER', strtoupper($columns['rating']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'rating');
            $this->assertEquals('smallint', $column->getType()->getName());
        }
    }

    /** @test */
    public function gbp_reviews_review_text_is_text(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('gbp_reviews');
            $this->assertStringContainsString('TEXT', strtoupper($columns['review_text']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'review_text');
            $this->assertEquals('text', $column->getType()->getName());
        }
    }

    /** @test */
    public function local_rankings_competitors_in_pack_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('local_rankings');
            $this->assertStringContainsString('TEXT', strtoupper($columns['competitors_in_pack']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('local_rankings', 'competitors_in_pack');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function local_competitors_competitor_rating_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('local_competitors');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['competitor_rating']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_rating');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function local_seo_alerts_is_read_is_boolean(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('local_seo_alerts');
            $this->assertStringContainsString('TINYINT(1)', strtoupper($columns['is_read']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'is_read');
            $this->assertEquals('boolean', $column->getType()->getName());
        }
    }

    /** @test */
    public function local_seo_alerts_data_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('local_seo_alerts');
            $this->assertStringContainsString('TEXT', strtoupper($columns['data']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'data');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    // =========================================================================
    // Nullable State Tests (SQLite-compatible)
    // =========================================================================

    /** @test */
    public function business_locations_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['user_id', 'site_id', 'name', 'address'];
        $columns = $this->getSqliteColumnInfo('business_locations');
        
        foreach ($requiredColumns as $col) {
            // In SQLite: notnull=1 means NOT NULL, notnull=0 means nullable
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function business_locations_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['gbp_place_id', 'phone', 'website', 'category', 'latitude', 'longitude', 'settings', 'deleted_at'];
        $columns = $this->getSqliteColumnInfo('business_locations');
        
        foreach ($nullableColumns as $col) {
            // In SQLite: notnull=0 means nullable, notnull=1 means NOT NULL
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function gbp_reviews_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['location_id', 'google_review_id', 'rating'];
        $columns = $this->getSqliteColumnInfo('gbp_reviews');
        
        foreach ($requiredColumns as $col) {
            // In SQLite: notnull=1 means NOT NULL, notnull=0 means nullable
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function gbp_reviews_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['author_name', 'author_photo', 'review_text', 'review_text_sentiment', 'owner_response', 'owner_response_date', 'review_date'];
        $columns = $this->getSqliteColumnInfo('gbp_reviews');
        
        foreach ($nullableColumns as $col) {
            // In SQLite: notnull=0 means nullable, notnull=1 means NOT NULL
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function local_rankings_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['location_id', 'keyword_id', 'search_location'];
        $columns = $this->getSqliteColumnInfo('local_rankings');
        
        foreach ($requiredColumns as $col) {
            // In SQLite: notnull=1 means NOT NULL, notnull=0 means nullable
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function local_rankings_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['search_lat', 'search_lng', 'local_pack_position', 'local_organic_position', 'competitors_in_pack'];
        $columns = $this->getSqliteColumnInfo('local_rankings');
        
        foreach ($nullableColumns as $col) {
            // In SQLite: notnull=0 means nullable, notnull=1 means NOT NULL
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function local_seo_alerts_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['user_id', 'location_id', 'alert_type', 'severity', 'title', 'message'];
        $columns = $this->getSqliteColumnInfo('local_seo_alerts');
        
        foreach ($requiredColumns as $col) {
            // In SQLite: notnull=1 means NOT NULL, notnull=0 means nullable
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function local_seo_alerts_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['data'];
        $columns = $this->getSqliteColumnInfo('local_seo_alerts');
        
        foreach ($nullableColumns as $col) {
            // In SQLite: notnull=0 means nullable, notnull=1 means NOT NULL
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    // =========================================================================
    // Table Column Count Tests
    // =========================================================================

    /** @test */
    public function business_locations_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('business_locations');
        $expectedCount = 17; // id, user_id, site_id, gbp_place_id, name, address, phone, website, category, latitude, longitude, gbp_connected, is_active, settings, timestamps(2), deleted_at
        $this->assertCount($expectedCount, $columns, 'business_locations should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_insights_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_insights');
        $expectedCount = 10; // id, location_id, views, searches, calls, directions, website_clicks, photos_views, insights_date, created_at
        $this->assertCount($expectedCount, $columns, 'gbp_insights should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_reviews_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_reviews');
        $expectedCount = 13; // id, location_id, google_review_id, author_name, author_photo, rating, review_text, review_text_sentiment, owner_response, owner_response_date, review_date, created_at, updated_at
        $this->assertCount($expectedCount, $columns, 'gbp_reviews should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_rankings_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_rankings');
        $expectedCount = 10; // id, location_id, keyword_id, search_location, search_lat, search_lng, local_pack_position, local_organic_position, competitors_in_pack, checked_at
        $this->assertCount($expectedCount, $columns, 'local_rankings should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_competitors_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_competitors');
        $expectedCount = 9; // id, location_id, competitor_name, competitor_gbp_id, competitor_address, competitor_rating, competitor_review_count, created_at, updated_at
        $this->assertCount($expectedCount, $columns, 'local_competitors should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function gbp_posts_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('gbp_posts');
        $expectedCount = 11; // id, location_id, google_post_id, post_type, title, content, media_url, call_to_action, call_to_action_url, published_at, created_at
        $this->assertCount($expectedCount, $columns, 'gbp_posts should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function local_seo_alerts_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('local_seo_alerts');
        $expectedCount = 11; // id, user_id, location_id, alert_type, severity, title, message, data, is_read, read_at, created_at
        $this->assertCount($expectedCount, $columns, 'local_seo_alerts should have ' . $expectedCount . ' columns');
    }

    // =========================================================================
    // MySQL-Specific Tests (skipped on SQLite)
    // =========================================================================

    /** @test */
    public function business_locations_gbp_place_id_max_length_is_255(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_place_id');
        $this->assertEquals(255, $column->getLength());
    }

    /** @test */
    public function business_locations_address_max_length_is_500(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('business_locations', 'address');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function business_locations_latitude_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('business_locations', 'latitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(10, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function business_locations_longitude_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('business_locations', 'longitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(11, $column->getPrecision());
        $this->assertEquals(8, $column->getScale());
    }

    /** @test */
    public function local_competitors_competitor_rating_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_rating');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(2, $column->getPrecision());
        $this->assertEquals(1, $column->getScale());
    }

    /** @test */
    public function business_locations_gbp_connected_defaults_to_false(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_connected');
        $this->assertFalse($column->getDefault());
    }

    /** @test */
    public function gbp_insights_views_defaults_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'views');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function local_seo_alerts_is_read_defaults_to_false(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'is_read');
        $this->assertFalse($column->getDefault());
    }
}
