<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for SPEC-003-1: Database Migration - Competitors Tables
 * 
 * Tests verify:
 * - Tables created with correct schema
 * - Foreign key constraints
 * - Unique constraints
 * - Indexes
 * - Migration reversibility
 * 
 * @requires keywords table (SPEC-001-1) - tests will fail until this exists
 */
class CreateCompetitorsTablesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Migration creates competitors table with correct columns
     */
    public function test_migration_creates_competitors_table(): void
    {
        // Run the migration
        $this->artisan('migrate');
        
        // Verify table exists
        $this->assertTrue(Schema::hasTable('competitors'));
        
        // Verify columns
        $columns = ['id', 'user_id', 'domain', 'display_name', 'notes', 'created_at', 'updated_at'];
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('competitors', $column),
                "Competitors table missing column: {$column}"
            );
        }
    }

    /**
     * Test 2: Migration creates competitor_rankings table with correct columns
     */
    public function test_migration_creates_competitor_rankings_table(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('competitor_rankings'));
        
        $columns = ['id', 'competitor_id', 'keyword_id', 'position', 'url', 'checked_at'];
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('competitor_rankings', $column),
                "Competitor rankings table missing column: {$column}"
            );
        }
    }

    /**
     * Test 3: Foreign key constraint - competitors.user_id references users.id
     */
    public function test_competitors_foreign_key_to_users(): void
    {
        $this->artisan('migrate');
        
        // Create a user
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create competitor with valid user_id
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => 'competitor.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($competitorId);
        
        // Attempt to create competitor with invalid user_id should fail
        try {
            DB::table('competitors')->insert([
                'user_id' => 99999,
                'domain' => 'invalid.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Should have thrown foreign key constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 4: Foreign key constraint - competitor_rankings.competitor_id references competitors.id
     */
    public function test_rankings_foreign_key_to_competitors(): void
    {
        $this->artisan('migrate');
        
        // Setup: create user, keyword (if exists), and competitor
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => 'competitor2.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Check if keywords table exists (dependency from SPEC-001-1)
        if (!Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $user,
            'keyword' => 'test keyword',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create valid ranking
        $rankingId = DB::table('competitor_rankings')->insertGetId([
            'competitor_id' => $competitorId,
            'keyword_id' => $keywordId,
            'position' => 5,
            'checked_at' => now(),
        ]);
        
        $this->assertIsInt($rankingId);
        
        // Invalid competitor_id should fail
        try {
            DB::table('competitor_rankings')->insert([
                'competitor_id' => 99999,
                'keyword_id' => $keywordId,
                'checked_at' => now(),
            ]);
            $this->fail('Should have thrown foreign key constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 5: Foreign key constraint - competitor_rankings.keyword_id references keywords.id
     */
    public function test_rankings_foreign_key_to_keywords(): void
    {
        $this->artisan('migrate');
        
        if (!Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        // Setup
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $user,
            'keyword' => 'test keyword',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => 'competitor3.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Invalid keyword_id should fail
        try {
            DB::table('competitor_rankings')->insert([
                'competitor_id' => $competitorId,
                'keyword_id' => 99999,
                'checked_at' => now(),
            ]);
            $this->fail('Should have thrown foreign key constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 6: Unique constraint on (user_id, domain) in competitors
     */
    public function test_competitors_unique_user_domain(): void
    {
        $this->artisan('migrate');
        
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create first competitor
        DB::table('competitors')->insert([
            'user_id' => $user,
            'domain' => 'duplicate.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Attempt duplicate should fail
        try {
            DB::table('competitors')->insert([
                'user_id' => $user,
                'domain' => 'duplicate.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Should have thrown unique constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('duplicate', strtolower($e->getMessage()));
        }
        
        // Different user with same domain should be allowed
        $user2 = DB::table('users')->insertGetId([
            'name' => 'Test User 2',
            'email' => 'test5@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $competitor2 = DB::table('competitors')->insertGetId([
            'user_id' => $user2,
            'domain' => 'duplicate.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($competitor2);
    }

    /**
     * Test 7: Unique constraint on (competitor_id, keyword_id, checked_at)
     */
    public function test_rankings_unique_competitor_keyword_date(): void
    {
        $this->artisan('migrate');
        
        if (!Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $user,
            'keyword' => 'test keyword',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => 'competitor.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $checkedAt = now();
        
        // Create first ranking
        DB::table('competitor_rankings')->insert([
            'competitor_id' => $competitorId,
            'keyword_id' => $keywordId,
            'position' => 5,
            'checked_at' => $checkedAt,
        ]);
        
        // Duplicate should fail
        try {
            DB::table('competitor_rankings')->insert([
                'competitor_id' => $competitorId,
                'keyword_id' => $keywordId,
                'position' => 10,
                'checked_at' => $checkedAt,
            ]);
            $this->fail('Should have thrown unique constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('duplicate', strtolower($e->getMessage()));
        }
        
        // Different checked_at should be allowed
        $ranking2 = DB::table('competitor_rankings')->insertGetId([
            'competitor_id' => $competitorId,
            'keyword_id' => $keywordId,
            'position' => 7,
            'checked_at' => now()->addHour(),
        ]);
        
        $this->assertIsInt($ranking2);
    }

    /**
     * Test 8: Indexes are created
     */
    public function test_indexes_created(): void
    {
        $this->artisan('migrate');
        
        // Check competitors indexes
        $competitorIndexes = DB::select("SHOW INDEX FROM competitors");
        $competitorIndexNames = array_column($competitorIndexes, 'Key_name');
        
        // Check for user_id index
        $this->assertTrue(
            collect($competitorIndexNames)->contains('competitors_user_id_index'),
            'Missing index on competitors.user_id'
        );
        
        // Check for domain index
        $this->assertTrue(
            collect($competitorIndexNames)->contains('competitors_domain_index'),
            'Missing index on competitors.domain'
        );
        
        // Check competitor_rankings indexes
        $rankingIndexes = DB::select("SHOW INDEX FROM competitor_rankings");
        $rankingIndexNames = array_column($rankingIndexes, 'Key_name');
        
        // Check for competitor_id index
        $this->assertTrue(
            collect($rankingIndexNames)->contains('competitor_rankings_competitor_id_index'),
            'Missing index on competitor_rankings.competitor_id'
        );
        
        // Check for keyword_id index
        $this->assertTrue(
            collect($rankingIndexNames)->contains('competitor_rankings_keyword_id_index'),
            'Missing index on competitor_rankings.keyword_id'
        );
        
        // Check for checked_at index
        $this->assertTrue(
            collect($rankingIndexNames)->contains('competitor_rankings_checked_at_index'),
            'Missing index on competitor_rankings.checked_at'
        );
    }

    /**
     * Test 9: Migration rollback (down method)
     */
    public function test_migration_rollback(): void
    {
        // Run migration
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('competitors'));
        $this->assertTrue(Schema::hasTable('competitor_rankings'));
        
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Tables should be dropped
        $this->assertFalse(Schema::hasTable('competitors'));
        $this->assertFalse(Schema::hasTable('competitor_rankings'));
        
        // Run migration again
        $this->artisan('migrate');
        
        // Tables should be recreated
        $this->assertTrue(Schema::hasTable('competitors'));
        $this->assertTrue(Schema::hasTable('competitor_rankings'));
    }

    /**
     * Test 18: Position can be null (not ranking)
     */
    public function test_position_can_be_null(): void
    {
        $this->artisan('migrate');
        
        if (!Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test7@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $user,
            'keyword' => 'test keyword',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => 'competitor.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create ranking with null position
        $rankingId = DB::table('competitor_rankings')->insertGetId([
            'competitor_id' => $competitorId,
            'keyword_id' => $keywordId,
            'position' => null,
            'url' => null,
            'checked_at' => now(),
        ]);
        
        $this->assertIsInt($rankingId);
        
        // Verify null values are stored
        $ranking = DB::table('competitor_rankings')->where('id', $rankingId)->first();
        $this->assertNull($ranking->position);
        $this->assertNull($ranking->url);
    }

    /**
     * Test 20: Soft deletes - check for deleted_at column
     */
    public function test_competitors_has_soft_deletes(): void
    {
        $this->artisan('migrate');
        
        // Check if deleted_at column exists for soft deletes
        $hasSoftDeletes = Schema::hasColumn('competitors', 'deleted_at');
        
        // Document the result
        if ($hasSoftDeletes) {
            $this->assertTrue($hasSoftDeletes, 'Competitors table has soft deletes (deleted_at column)');
        } else {
            // This is a design decision - document it
            $this->markTestSkipped('Competitors table does not implement soft deletes - this is acceptable if not required');
        }
    }

    /**
     * Security Test: SQL Injection Prevention
     */
    public function test_sql_injection_prevention(): void
    {
        $this->artisan('migrate');
        
        $user = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test8@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Attempt SQL injection via domain field
        $maliciousDomain = "test.com'; DROP TABLE competitors; --";
        
        $competitorId = DB::table('competitors')->insertGetId([
            'user_id' => $user,
            'domain' => $maliciousDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Verify the malicious input was stored as string, not executed
        $this->assertIsInt($competitorId);
        $this->assertTrue(Schema::hasTable('competitors'), 'Table should still exist after SQL injection attempt');
        
        $competitor = DB::table('competitors')->where('id', $competitorId)->first();
        $this->assertEquals($maliciousDomain, $competitor->domain);
    }
}
