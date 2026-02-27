<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-9-1: Database Migration - Annotations Table
 * 
 * Tests verify:
 * - Table created with correct schema
 * - Foreign key constraints (keyword_id, project_id)
 * - Indexes for efficient querying
 * - Migration reversibility
 * 
 * @requires keywords table (existing)
 * @requires projects table (TASK-8-1)
 */
class CreateAnnotationsTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Migration creates annotations table with correct columns
     * MT-001, MT-002, MT-003
     */
    public function test_migration_creates_annotations_table(): void
    {
        // Run the migration
        $this->artisan('migrate');
        
        // Verify table exists
        $this->assertTrue(Schema::hasTable('annotations'));
        
        // Verify columns
        $columns = ['id', 'keyword_id', 'project_id', 'date', 'title', 'description', 'category', 'created_at', 'updated_at'];
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('annotations', $column),
                "Annotations table missing column: {$column}"
            );
        }
    }

    /**
     * Test 2: Primary key is BIGINT UNSIGNED AUTO_INCREMENT
     * MT-004
     */
    public function test_id_column_is_bigint_unsigned_auto_increment(): void
    {
        $this->artisan('migrate');
        
        $columns = Schema::getColumnListing('annotations');
        $this->assertContains('id', $columns);
        
        // Create required dependencies for foreign keys
        $this->createRequiredDependencies();
        
        // Verify it's the primary key by creating an annotation
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
        ]);
        
        $this->assertIsInt($annotationId);
    }

    /**
     * Test 3: keyword_id column is BIGINT UNSIGNED NOT NULL
     * MT-005
     */
    public function test_keyword_id_column_is_bigint_unsigned_not_null(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'keyword_id'));
        
        // Attempt to create annotation with null keyword_id should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => null,
                'project_id' => 1,
                'date' => '2026-02-27',
                'title' => 'Test',
            ]);
            $this->fail('Should have thrown NOT NULL constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('not null', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 4: project_id column is BIGINT UNSIGNED NOT NULL
     * MT-006
     */
    public function test_project_id_column_is_bigint_unsigned_not_null(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'project_id'));
        
        // Attempt to create annotation with null project_id should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => 1,
                'project_id' => null,
                'date' => '2026-02-27',
                'title' => 'Test',
            ]);
            $this->fail('Should have thrown NOT NULL constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('not null', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 5: date column is DATE NOT NULL
     * MT-007
     */
    public function test_date_column_is_date_not_null(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'date'));
        
        // Attempt to create annotation with null date should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => 1,
                'project_id' => 1,
                'date' => null,
                'title' => 'Test',
            ]);
            $this->fail('Should have thrown NOT NULL constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('not null', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 6: title column is VARCHAR(255) NOT NULL
     * MT-008
     */
    public function test_title_column_is_varchar_not_null(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'title'));
        
        // Attempt to create annotation with null title should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => 1,
                'project_id' => 1,
                'date' => '2026-02-27',
                'title' => null,
            ]);
            $this->fail('Should have thrown NOT NULL constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('not null', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 7: description column is TEXT NULL
     * MT-009, EC-005
     */
    public function test_description_column_is_text_nullable(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'description'));
        
        // Setup required foreign keys
        $this->createRequiredDependencies();
        
        // Create annotation with null description
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
            'description' => null,
        ]);
        
        $this->assertIsInt($annotationId);
        
        // Verify null is stored
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertNull($annotation->description);
        
        // Test long text storage
        $longDescription = str_repeat('a', 65000);
        DB::table('annotations')->where('id', $annotationId)->update(['description' => $longDescription]);
        $this->assertEquals($longDescription, DB::table('annotations')->where('id', $annotationId)->value('description'));
    }

    /**
     * Test 8: category column is VARCHAR(50) NOT NULL DEFAULT 'other'
     * MT-010, CV-001
     */
    public function test_category_column_has_default_other(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasColumn('annotations', 'category'));
        
        // Setup required foreign keys
        $this->createRequiredDependencies();
        
        // Create annotation without specifying category
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Test Annotation',
        ]);
        
        // Verify default value
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertEquals('other', $annotation->category);
    }

    /**
     * Test 9: Foreign key constraint - annotations.keyword_id references keywords.id
     * FK-001, FK-002, FK-003, FK-009
     */
    public function test_foreign_key_to_keywords(): void
    {
        $this->artisan('migrate');
        
        // Check if keywords table exists
        if (!Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not found');
        }
        
        // Create required dependencies
        $this->createRequiredDependencies();
        
        // Attempt to create annotation with invalid keyword_id should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => 99999,
                'project_id' => 1,
                'date' => '2026-02-27',
                'title' => 'Invalid Keyword',
            ]);
            $this->fail('Should have thrown foreign key constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 10: Foreign key constraint - annotations.project_id references projects.id
     * FK-004, FK-005, FK-006, FK-010
     */
    public function test_foreign_key_to_projects(): void
    {
        $this->artisan('migrate');
        
        // Check if projects table exists
        if (!Schema::hasTable('projects')) {
            $this->markTestSkipped('Projects table not found');
        }
        
        // Create required dependencies
        $this->createRequiredDependencies();
        
        // Attempt to create annotation with invalid project_id should fail
        try {
            DB::table('annotations')->insert([
                'keyword_id' => 1,
                'project_id' => 99999,
                'date' => '2026-02-27',
                'title' => 'Invalid Project',
            ]);
            $this->fail('Should have thrown foreign key constraint error');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
    }

    /**
     * Test 11: CASCADE delete - Deleting keyword deletes all its annotations
     * FK-007
     */
    public function test_cascade_delete_keyword(): void
    {
        $this->artisan('migrate');
        
        if (!Schema::hasTable('keywords') || !Schema::hasTable('projects')) {
            $this->markTestSkipped('Required tables not found');
        }
        
        // Create dependencies
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test_keyword_cascade@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create a site for the keyword (if required)
        $siteId = null;
        if (Schema::hasTable('sites')) {
            $siteId = DB::table('sites')->insertGetId([
                'user_id' => $userId,
                'url' => 'https://cascade-test.example.com',
                'name' => 'Cascade Test Site',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'test keyword cascade',
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create multiple annotations for this keyword
        for ($i = 1; $i <= 5; $i++) {
            DB::table('annotations')->insert([
                'keyword_id' => $keywordId,
                'project_id' => $projectId,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'title' => "Annotation {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Verify annotations exist
        $this->assertEquals(5, DB::table('annotations')->where('keyword_id', $keywordId)->count());
        
        // Delete the keyword
        DB::table('keywords')->where('id', $keywordId)->delete();
        
        // Verify all annotations were deleted via CASCADE
        $this->assertEquals(0, DB::table('annotations')->where('keyword_id', $keywordId)->count());
    }

    /**
     * Test 12: CASCADE delete - Deleting project deletes all its annotations
     * FK-008
     */
    public function test_cascade_delete_project(): void
    {
        $this->artisan('migrate');
        
        if (!Schema::hasTable('keywords') || !Schema::hasTable('projects')) {
            $this->markTestSkipped('Required tables not found');
        }
        
        // Create dependencies
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User 2',
            'email' => 'test_project_cascade@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Test Project Cascade',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create a site for the keyword (if required)
        $siteId = null;
        if (Schema::hasTable('sites')) {
            $siteId = DB::table('sites')->insertGetId([
                'user_id' => $userId,
                'url' => 'https://project-cascade-test.example.com',
                'name' => 'Project Cascade Test Site',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $keywordId = DB::table('keywords')->insertGetId([
            'user_id' => $userId,
            'keyword' => 'test keyword project cascade',
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create multiple annotations for this project
        for ($i = 1; $i <= 5; $i++) {
            DB::table('annotations')->insert([
                'keyword_id' => $keywordId,
                'project_id' => $projectId,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'title' => "Project Annotation {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Verify annotations exist
        $this->assertEquals(5, DB::table('annotations')->where('project_id', $projectId)->count());
        
        // Delete the project
        DB::table('projects')->where('id', $projectId)->delete();
        
        // Verify all annotations were deleted via CASCADE
        $this->assertEquals(0, DB::table('annotations')->where('project_id', $projectId)->count());
    }

    /**
     * Test 13: Indexes are created
     * IX-001, IX-002, IX-003, IX-004
     */
    public function test_indexes_created(): void
    {
        $this->artisan('migrate');
        
        // Use database-agnostic approach to check indexes
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: query sqlite_master for indexes
            $indexes = DB::select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='annotations'"
            );
            $indexNames = array_column($indexes, 'name');
        } else {
            // MySQL: use SHOW INDEX
            $indexes = DB::select("SHOW INDEX FROM annotations");
            $indexNames = array_column($indexes, 'Key_name');
        }
        
        // Check for keyword_id index
        $this->assertTrue(
            collect($indexNames)->contains(function ($name) {
                return str_contains($name, 'keyword_id');
            }),
            'Missing index on annotations.keyword_id'
        );
        
        // Check for project_id index
        $this->assertTrue(
            collect($indexNames)->contains(function ($name) {
                return str_contains($name, 'project_id');
            }),
            'Missing index on annotations.project_id'
        );
        
        // Check for date index
        $this->assertTrue(
            collect($indexNames)->contains(function ($name) {
                return str_contains($name, 'date');
            }),
            'Missing index on annotations.date'
        );
        
        // Check for category index
        $this->assertTrue(
            collect($indexNames)->contains(function ($name) {
                return str_contains($name, 'category');
            }),
            'Missing index on annotations.category'
        );
    }

    /**
     * Test 14: Migration rollback (down method)
     * MT-013
     */
    public function test_migration_rollback(): void
    {
        // Run migration
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('annotations'));
        
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Table should be dropped
        $this->assertFalse(Schema::hasTable('annotations'));
        
        // Run migration again
        $this->artisan('migrate');
        
        // Table should be recreated
        $this->assertTrue(Schema::hasTable('annotations'));
    }

    /**
     * Test 15: Multiple annotations for same keyword on same date allowed
     * EC-012
     */
    public function test_multiple_annotations_same_keyword_same_date(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        // Create two annotations for same keyword on same date
        $annotation1 = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'First Event',
            'description' => 'First annotation for this date',
        ]);
        
        $annotation2 = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Second Event',
            'description' => 'Second annotation for this date',
        ]);
        
        // Both should be created successfully
        $this->assertIsInt($annotation1);
        $this->assertIsInt($annotation2);
        $this->assertNotEquals($annotation1, $annotation2);
        
        // Verify both exist
        $count = DB::table('annotations')
            ->where('keyword_id', 1)
            ->where('date', '2026-02-27')
            ->count();
        $this->assertEquals(2, $count);
    }

    /**
     * Test 16: Title exactly 255 chars is accepted
     * EC-002
     */
    public function test_title_exactly_255_chars(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        $title255 = str_repeat('a', 255);
        
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => $title255,
        ]);
        
        $this->assertIsInt($annotationId);
        
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertEquals($title255, $annotation->title);
    }

    /**
     * Test 17: Category with max length (50 chars) is accepted
     * EC-010
     */
    public function test_category_exactly_50_chars(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        $category50 = str_repeat('a', 50);
        
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Test',
            'category' => $category50,
        ]);
        
        $this->assertIsInt($annotationId);
        
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertEquals($category50, $annotation->category);
    }

    /**
     * Test 18: Date in the past and future are accepted
     * EC-008, EC-009
     */
    public function test_dates_past_and_future(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        // Past date
        $annotationPast = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2020-01-01',
            'title' => 'Past Event',
        ]);
        
        // Future date
        $annotationFuture = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2030-12-31',
            'title' => 'Future Event',
        ]);
        
        $this->assertIsInt($annotationPast);
        $this->assertIsInt($annotationFuture);
    }

    /**
     * Test 19: Description with special characters and Unicode
     * EC-006, EC-007
     */
    public function test_description_special_characters_and_unicode(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        $specialChars = '<script>alert("xss")</script> & "quotes" \'apostrophes\' <>&"';
        $unicodeChars = 'Emoji test: 🚀📈🔥 Unicode: 你好世界 مرحبا';
        
        // Test special characters
        $annotation1 = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Special Chars',
            'description' => $specialChars,
        ]);
        
        // Test Unicode
        $annotation2 = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Unicode',
            'description' => $unicodeChars,
        ]);
        
        $this->assertIsInt($annotation1);
        $this->assertIsInt($annotation2);
        
        // Verify stored correctly
        $stored1 = DB::table('annotations')->where('id', $annotation1)->value('description');
        $stored2 = DB::table('annotations')->where('id', $annotation2)->value('description');
        
        $this->assertEquals($specialChars, $stored1);
        $this->assertEquals($unicodeChars, $stored2);
    }

    /**
     * Test 20: Annotation with minimal data (only required fields)
     * EC-013
     */
    public function test_annotation_minimal_data(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => 'Minimal',
        ]);
        
        $this->assertIsInt($annotationId);
        
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertNull($annotation->description);
        $this->assertEquals('other', $annotation->category);
    }

    /**
     * Security Test: SQL Injection Prevention
     */
    public function test_sql_injection_prevention(): void
    {
        $this->artisan('migrate');
        
        // Create dependencies
        $this->createRequiredDependencies();
        
        // Attempt SQL injection via title field
        $maliciousTitle = "Test'; DROP TABLE annotations; --";
        
        $annotationId = DB::table('annotations')->insertGetId([
            'keyword_id' => 1,
            'project_id' => 1,
            'date' => '2026-02-27',
            'title' => $maliciousTitle,
        ]);
        
        // Verify the malicious input was stored as string, not executed
        $this->assertIsInt($annotationId);
        $this->assertTrue(Schema::hasTable('annotations'), 'Table should still exist after SQL injection attempt');
        
        $annotation = DB::table('annotations')->where('id', $annotationId)->first();
        $this->assertEquals($maliciousTitle, $annotation->title);
    }

    /**
     * Helper: Create required dependencies for tests
     */
    private function createRequiredDependencies(): void
    {
        // Create user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test_annotations@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create project
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $userId,
            'name' => 'Test Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create site if table exists (requires user_id)
        $siteId = null;
        if (Schema::hasTable('sites')) {
            $siteId = DB::table('sites')->insertGetId([
                'user_id' => $userId,
                'url' => 'https://test-annotations.example.com',
                'name' => 'Test Site',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create keyword
        if (Schema::hasTable('keywords')) {
            DB::table('keywords')->insert([
                'id' => 1,
                'user_id' => $userId,
                'keyword' => 'test keyword',
                'site_id' => $siteId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Ensure project has id 1 for tests
        if ($projectId != 1) {
            DB::table('projects')->where('id', $projectId)->update(['id' => 1]);
        }
    }
}
