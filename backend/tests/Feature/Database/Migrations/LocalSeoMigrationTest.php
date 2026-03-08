<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for Local SEO database migrations.
 * 
 * Tests verify schema structure, indexes, foreign keys, unique constraints,
 * and rollback functionality for all 7 tables required by SPEC-035.
 * 
 * @see TEST-35-1 for test documentation
 * @see TASK-35-1 for task details
 */
class LocalSeoMigrationTest extends TestCase
{
    /**
     * Tables created by the Local SEO migrations.
     */
    private const TABLES = [
        'business_locations',
        'gbp_insights',
        'gbp_reviews',
        'local_rankings',
        'local_competitors',
        'gbp_posts',
        'local_seo_alerts',
    ];

    // =========================================================================
    // business_locations Table Tests (BL)
    // =========================================================================

    /** @test */
    public function bl_01_business_locations_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('business_locations'));
    }

    /** @test */
    public function bl_02_business_locations_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'id'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getUnsigned());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function bl_03_business_locations_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'user_id'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'user_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getUnsigned());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function bl_04_business_locations_has_site_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'site_id'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'site_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getUnsigned());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function bl_05_business_locations_has_gbp_place_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'gbp_place_id'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_place_id');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_06_business_locations_has_name_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'name'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'name');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function bl_07_business_locations_has_address_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'address'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'address');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function bl_08_business_locations_has_phone_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'phone'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'phone');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_09_business_locations_has_website_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'website'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'website');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_10_business_locations_has_category_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'category'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'category');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_11_business_locations_has_latitude_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'latitude'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'latitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_12_business_locations_has_longitude_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'longitude'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'longitude');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_13_business_locations_has_gbp_connected_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'gbp_connected'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'gbp_connected');
        $this->assertEquals('boolean', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
        $this->assertFalse($column->getDefault());
    }

    /** @test */
    public function bl_14_business_locations_has_settings_column(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'settings'));
        $column = DB::connection()->getDoctrineColumn('business_locations', 'settings');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function bl_15_business_locations_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'created_at'));
        $this->assertTrue(Schema::hasColumn('business_locations', 'updated_at'));
    }

    /** @test */
    public function bl_16_business_locations_has_soft_deletes(): void
    {
        $this->assertTrue(Schema::hasColumn('business_locations', 'deleted_at'));
    }

    /** @test */
    public function bl_17_business_locations_has_user_id_index(): void
    {
        $indexes = $this->getTableIndexes('business_locations');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'user_id'));
    }

    /** @test */
    public function bl_18_business_locations_has_site_id_index(): void
    {
        $indexes = $this->getTableIndexes('business_locations');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'site_id'));
    }

    /** @test */
    public function bl_19_business_locations_has_gbp_place_id_index(): void
    {
        $indexes = $this->getTableIndexes('business_locations');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'gbp_place_id'));
    }

    /** @test */
    public function bl_20_business_locations_has_user_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('business_locations');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'user_id', 'users', 'id'));
    }

    /** @test */
    public function bl_21_business_locations_has_site_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('business_locations');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'site_id', 'sites', 'id'));
    }

    // =========================================================================
    // gbp_insights Table Tests (GI)
    // =========================================================================

    /** @test */
    public function gi_01_gbp_insights_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('gbp_insights'));
    }

    /** @test */
    public function gi_02_gbp_insights_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'id'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function gi_03_gbp_insights_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getUnsigned());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gi_04_gbp_insights_has_views_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'views'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'views');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_05_gbp_insights_has_searches_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'searches'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'searches');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_06_gbp_insights_has_calls_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'calls'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'calls');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_07_gbp_insights_has_directions_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'directions'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'directions');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_08_gbp_insights_has_website_clicks_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'website_clicks'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'website_clicks');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_09_gbp_insights_has_photos_views_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'photos_views'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'photos_views');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function gi_10_gbp_insights_has_insights_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'insights_date'));
        $column = DB::connection()->getDoctrineColumn('gbp_insights', 'insights_date');
        $this->assertEquals('date', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gi_11_gbp_insights_has_created_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_insights', 'created_at'));
    }

    /** @test */
    public function gi_12_gbp_insights_has_unique_location_date_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_insights');
        $this->assertTrue($this->hasCompositeUniqueIndex($indexes, ['location_id', 'insights_date']));
    }

    /** @test */
    public function gi_13_gbp_insights_has_insights_date_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_insights');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'insights_date'));
    }

    /** @test */
    public function gi_14_gbp_insights_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('gbp_insights');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    // =========================================================================
    // gbp_reviews Table Tests (GR)
    // =========================================================================

    /** @test */
    public function gr_01_gbp_reviews_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('gbp_reviews'));
    }

    /** @test */
    public function gr_02_gbp_reviews_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'id'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function gr_03_gbp_reviews_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gr_04_gbp_reviews_has_google_review_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'google_review_id'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'google_review_id');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gr_05_gbp_reviews_has_author_name_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'author_name'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'author_name');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_06_gbp_reviews_has_author_photo_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'author_photo'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'author_photo');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_07_gbp_reviews_has_rating_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'rating'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'rating');
        $this->assertEquals('smallint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gr_08_gbp_reviews_has_review_text_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'review_text'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'review_text');
        $this->assertEquals('text', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_09_gbp_reviews_has_review_text_sentiment_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'review_text_sentiment'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'review_text_sentiment');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_10_gbp_reviews_has_owner_response_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'owner_response'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'owner_response');
        $this->assertEquals('text', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_11_gbp_reviews_has_owner_response_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'owner_response_date'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'owner_response_date');
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_12_gbp_reviews_has_review_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'review_date'));
        $column = DB::connection()->getDoctrineColumn('gbp_reviews', 'review_date');
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gr_13_gbp_reviews_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'created_at'));
        $this->assertTrue(Schema::hasColumn('gbp_reviews', 'updated_at'));
    }

    /** @test */
    public function gr_14_gbp_reviews_has_unique_google_review_id_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_reviews');
        $this->assertTrue($this->hasUniqueIndexOnColumn($indexes, 'google_review_id'));
    }

    /** @test */
    public function gr_15_gbp_reviews_has_location_id_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_reviews');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'location_id'));
    }

    /** @test */
    public function gr_16_gbp_reviews_has_rating_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_reviews');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'rating'));
    }

    /** @test */
    public function gr_17_gbp_reviews_has_review_date_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_reviews');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'review_date'));
    }

    /** @test */
    public function gr_18_gbp_reviews_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('gbp_reviews');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    // =========================================================================
    // local_rankings Table Tests (LR)
    // =========================================================================

    /** @test */
    public function lr_01_local_rankings_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('local_rankings'));
    }

    /** @test */
    public function lr_02_local_rankings_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'id'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function lr_03_local_rankings_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function lr_04_local_rankings_has_keyword_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'keyword_id'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'keyword_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function lr_05_local_rankings_has_search_location_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'search_location'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'search_location');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function lr_06_local_rankings_has_search_lat_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'search_lat'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'search_lat');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lr_07_local_rankings_has_search_lng_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'search_lng'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'search_lng');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lr_08_local_rankings_has_local_pack_position_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'local_pack_position'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'local_pack_position');
        $this->assertEquals('smallint', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lr_09_local_rankings_has_local_organic_position_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'local_organic_position'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'local_organic_position');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lr_10_local_rankings_has_competitors_in_pack_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'competitors_in_pack'));
        $column = DB::connection()->getDoctrineColumn('local_rankings', 'competitors_in_pack');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lr_11_local_rankings_has_checked_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_rankings', 'checked_at'));
    }

    /** @test */
    public function lr_12_local_rankings_has_location_keyword_composite_index(): void
    {
        $indexes = $this->getTableIndexes('local_rankings');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['location_id', 'keyword_id']));
    }

    /** @test */
    public function lr_13_local_rankings_has_checked_at_index(): void
    {
        $indexes = $this->getTableIndexes('local_rankings');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'checked_at'));
    }

    /** @test */
    public function lr_14_local_rankings_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('local_rankings');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    /** @test */
    public function lr_15_local_rankings_has_keyword_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('local_rankings');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'keyword_id', 'keywords', 'id'));
    }

    // =========================================================================
    // local_competitors Table Tests (LC)
    // =========================================================================

    /** @test */
    public function lc_01_local_competitors_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('local_competitors'));
    }

    /** @test */
    public function lc_02_local_competitors_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'id'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function lc_03_local_competitors_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function lc_04_local_competitors_has_competitor_name_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'competitor_name'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_name');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function lc_05_local_competitors_has_competitor_gbp_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'competitor_gbp_id'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_gbp_id');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lc_06_local_competitors_has_competitor_address_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'competitor_address'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_address');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lc_07_local_competitors_has_competitor_rating_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'competitor_rating'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_rating');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function lc_08_local_competitors_has_competitor_review_count_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'competitor_review_count'));
        $column = DB::connection()->getDoctrineColumn('local_competitors', 'competitor_review_count');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function lc_09_local_competitors_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('local_competitors', 'created_at'));
        $this->assertTrue(Schema::hasColumn('local_competitors', 'updated_at'));
    }

    /** @test */
    public function lc_10_local_competitors_has_location_id_index(): void
    {
        $indexes = $this->getTableIndexes('local_competitors');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'location_id'));
    }

    /** @test */
    public function lc_11_local_competitors_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('local_competitors');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    // =========================================================================
    // gbp_posts Table Tests (GP)
    // =========================================================================

    /** @test */
    public function gp_01_gbp_posts_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('gbp_posts'));
    }

    /** @test */
    public function gp_02_gbp_posts_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'id'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function gp_03_gbp_posts_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gp_04_gbp_posts_has_google_post_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'google_post_id'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'google_post_id');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gp_05_gbp_posts_has_post_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'post_type'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'post_type');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function gp_06_gbp_posts_has_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'title'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'title');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_07_gbp_posts_has_content_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'content'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'content');
        $this->assertEquals('text', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_08_gbp_posts_has_media_url_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'media_url'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'media_url');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_09_gbp_posts_has_call_to_action_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'call_to_action'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'call_to_action');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_10_gbp_posts_has_call_to_action_url_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'call_to_action_url'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'call_to_action_url');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_11_gbp_posts_has_published_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'published_at'));
        $column = DB::connection()->getDoctrineColumn('gbp_posts', 'published_at');
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function gp_12_gbp_posts_has_created_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gbp_posts', 'created_at'));
    }

    /** @test */
    public function gp_13_gbp_posts_has_unique_google_post_id_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_posts');
        $this->assertTrue($this->hasUniqueIndexOnColumn($indexes, 'google_post_id'));
    }

    /** @test */
    public function gp_14_gbp_posts_has_location_id_index(): void
    {
        $indexes = $this->getTableIndexes('gbp_posts');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'location_id'));
    }

    /** @test */
    public function gp_15_gbp_posts_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('gbp_posts');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    // =========================================================================
    // local_seo_alerts Table Tests (LA)
    // =========================================================================

    /** @test */
    public function la_01_local_seo_alerts_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('local_seo_alerts'));
    }

    /** @test */
    public function la_02_local_seo_alerts_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'id'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function la_03_local_seo_alerts_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'user_id'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'user_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_04_local_seo_alerts_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'location_id'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'location_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_05_local_seo_alerts_has_alert_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'alert_type'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'alert_type');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_06_local_seo_alerts_has_severity_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'severity'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'severity');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_07_local_seo_alerts_has_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'title'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'title');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_08_local_seo_alerts_has_message_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'message'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'message');
        $this->assertEquals('text', $column->getType()->getName());
        $this->assertFalse($column->getNullable());
    }

    /** @test */
    public function la_09_local_seo_alerts_has_data_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'data'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'data');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertTrue($column->getNullable());
    }

    /** @test */
    public function la_10_local_seo_alerts_has_is_read_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'is_read'));
        $column = DB::connection()->getDoctrineColumn('local_seo_alerts', 'is_read');
        $this->assertEquals('boolean', $column->getType()->getName());
        $this->assertFalse($column->getDefault());
    }

    /** @test */
    public function la_11_local_seo_alerts_has_created_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('local_seo_alerts', 'created_at'));
    }

    /** @test */
    public function la_12_local_seo_alerts_has_user_id_index(): void
    {
        $indexes = $this->getTableIndexes('local_seo_alerts');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'user_id'));
    }

    /** @test */
    public function la_13_local_seo_alerts_has_location_id_index(): void
    {
        $indexes = $this->getTableIndexes('local_seo_alerts');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'location_id'));
    }

    /** @test */
    public function la_14_local_seo_alerts_has_alert_type_index(): void
    {
        $indexes = $this->getTableIndexes('local_seo_alerts');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'alert_type'));
    }

    /** @test */
    public function la_15_local_seo_alerts_has_is_read_index(): void
    {
        $indexes = $this->getTableIndexes('local_seo_alerts');
        $this->assertTrue($this->hasIndexOnColumn($indexes, 'is_read'));
    }

    /** @test */
    public function la_16_local_seo_alerts_has_user_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('local_seo_alerts');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'user_id', 'users', 'id'));
    }

    /** @test */
    public function la_17_local_seo_alerts_has_location_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('local_seo_alerts');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'location_id', 'business_locations', 'id'));
    }

    // =========================================================================
    // Rollback Tests (RB)
    // =========================================================================

    /** @test */
    public function rb_01_all_tables_created_after_migration(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist after migration"
            );
        }
    }

    /** @test */
    public function rb_03_fresh_migration_after_rollback(): void
    {
        // This test assumes rollback tests ran before
        // and verifies we can re-migrate
        foreach (self::TABLES as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist after fresh migration"
            );
        }
    }

    // =========================================================================
    // Integration Tests (INT)
    // =========================================================================

    /** @test */
    public function int_08_latitude_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement BusinessLocation model first.');
    }

    /** @test */
    public function int_09_longitude_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement BusinessLocation model first.');
    }

    /** @test */
    public function int_10_json_settings_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement BusinessLocation model first.');
    }

    /** @test */
    public function int_11_json_competitors_in_pack_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement LocalRanking model first.');
    }

    /** @test */
    public function int_12_json_data_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement LocalSeoAlert model first.');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get all indexes for a table.
     */
    private function getTableIndexes(string $table): array
    {
        return DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
    }

    /**
     * Get all foreign keys for a table.
     */
    private function getTableForeignKeys(string $table): array
    {
        return DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($table);
    }

    /**
     * Check if table has an index on a specific column.
     */
    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (in_array($column, $index->getColumns())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a unique index on a specific column.
     */
    private function hasUniqueIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if ($index->isUnique() && in_array($column, $index->getColumns())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a composite index on specific columns.
     */
    private function hasCompositeIndex(array $indexes, array $columns): bool
    {
        foreach ($indexes as $index) {
            $indexColumns = $index->getColumns();
            if (count($indexColumns) === count($columns) && 
                empty(array_diff($columns, $indexColumns))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a composite unique index on specific columns.
     */
    private function hasCompositeUniqueIndex(array $indexes, array $columns): bool
    {
        foreach ($indexes as $index) {
            if ($index->isUnique()) {
                $indexColumns = $index->getColumns();
                if (count($indexColumns) === count($columns) && 
                    empty(array_diff($columns, $indexColumns))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if table has a foreign key constraint.
     */
    private function hasForeignKey(array $foreignKeys, string $column, string $foreignTable, string $foreignColumn): bool
    {
        foreach ($foreignKeys as $fk) {
            $localColumns = $fk->getLocalColumns();
            if (in_array($column, $localColumns)) {
                $foreignColumns = $fk->getForeignColumns();
                $tableName = $fk->getForeignTableName();
                if ($tableName === $foreignTable && in_array($foreignColumn, $foreignColumns)) {
                    return true;
                }
            }
        }
        return false;
    }
}
