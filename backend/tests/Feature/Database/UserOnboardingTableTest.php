<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserOnboardingTableTest extends TestCase
{
    use RefreshDatabase;

    private string $tableName = 'user_onboarding';

    // ==================== TABLE EXISTENCE ====================

    /**
     * Test that user_onboarding table exists.
     */
    public function test_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable($this->tableName),
            "Table {$this->tableName} should exist"
        );
    }

    // ==================== COLUMN TESTS ====================

    /**
     * Test id column exists and is big integer.
     */
    public function test_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'id'),
            'Column id should exist'
        );
        
        $column = Schema::getColumnType($this->tableName, 'id');
        $this->assertContains($column, ['bigint', 'integer'], 'id should be big integer');
    }

    /**
     * Test user_id column exists with correct type.
     */
    public function test_user_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'user_id'),
            'Column user_id should exist'
        );
    }

    /**
     * Test project_id column exists and is nullable.
     */
    public function test_project_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'project_id'),
            'Column project_id should exist'
        );
    }

    /**
     * Test current_step column exists with default.
     */
    public function test_current_step_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'current_step'),
            'Column current_step should exist'
        );
    }

    /**
     * Test completed_steps column exists as JSON.
     */
    public function test_completed_steps_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'completed_steps'),
            'Column completed_steps should exist'
        );
    }

    /**
     * Test website_url column exists with max length.
     */
    public function test_website_url_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'website_url'),
            'Column website_url should exist'
        );
    }

    /**
     * Test first_keyword column exists with max length.
     */
    public function test_first_keyword_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'first_keyword'),
            'Column first_keyword should exist'
        );
    }

    /**
     * Test keyword_id column exists and is nullable.
     */
    public function test_keyword_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'keyword_id'),
            'Column keyword_id should exist'
        );
    }

    /**
     * Test alert_preferences column exists as JSON.
     */
    public function test_alert_preferences_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'alert_preferences'),
            'Column alert_preferences should exist'
        );
    }

    /**
     * Test completed_at timestamp column exists.
     */
    public function test_completed_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'completed_at'),
            'Column completed_at should exist'
        );
    }

    /**
     * Test skipped_at timestamp column exists.
     */
    public function test_skipped_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'skipped_at'),
            'Column skipped_at should exist'
        );
    }

    /**
     * Test created_at and updated_at timestamps exist.
     */
    public function test_timestamp_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'created_at'),
            'Column created_at should exist'
        );
        
        $this->assertTrue(
            Schema::hasColumn($this->tableName, 'updated_at'),
            'Column updated_at should exist'
        );
    }

    // ==================== INDEX TESTS ====================

    /**
     * Test unique index on user_id.
     */
    public function test_user_id_unique_index(): void
    {
        $pdo = $this->getConnection()->getPdo();
        
        // SQLite and MySQL have different ways to check indexes
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $result = $pdo->query(
                "PRAGMA index_list({$this->tableName})"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $uniqueIndexes = array_filter($result, fn($idx) => $idx['unique'] === '1');
            $indexNames = array_column($uniqueIndexes, 'name');
            
            // Check if there's a unique index on user_id
            $found = false;
            foreach ($indexNames as $indexName) {
                $columns = $pdo->query(
                    "PRAGMA index_info({$indexName})"
                )->fetchAll(\PDO::FETCH_ASSOC);
                
                if (count($columns) === 1 && $columns[0]['name'] === 'user_id') {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found, 'Unique index on user_id should exist');
        } else {
            // MySQL
            $result = $pdo->query(
                "SHOW INDEXES FROM {$this->tableName} WHERE Column_name = 'user_id' AND Non_unique = 0"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertNotEmpty($result, 'Unique index on user_id should exist');
        }
    }

    /**
     * Test index on completed_at.
     */
    public function test_completed_at_index(): void
    {
        $pdo = $this->getConnection()->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $result = $pdo->query(
                "PRAGMA index_list({$this->tableName})"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $indexNames = array_column($result, 'name');
            
            $found = false;
            foreach ($indexNames as $indexName) {
                $columns = $pdo->query(
                    "PRAGMA index_info({$indexName})"
                )->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($columns as $col) {
                    if ($col['name'] === 'completed_at') {
                        $found = true;
                        break 2;
                    }
                }
            }
            
            $this->assertTrue($found, 'Index on completed_at should exist');
        } else {
            // MySQL
            $result = $pdo->query(
                "SHOW INDEXES FROM {$this->tableName} WHERE Column_name = 'completed_at'"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertNotEmpty($result, 'Index on completed_at should exist');
        }
    }

    // ==================== FOREIGN KEY TESTS ====================

    /**
     * Test foreign key to users table exists.
     */
    public function test_user_foreign_key(): void
    {
        $pdo = $this->getConnection()->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $result = $pdo->query(
                "PRAGMA foreign_key_list({$this->tableName})"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $userFk = array_filter($result, fn($fk) => $fk['from'] === 'user_id');
            $this->assertNotEmpty($userFk, 'Foreign key on user_id should exist');
            
            $fk = reset($userFk);
            $this->assertEquals('users', $fk['table'], 'Foreign key should reference users table');
        } else {
            // MySQL
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $result = $pdo->query(
                "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$dbName}' 
                AND TABLE_NAME = '{$this->tableName}' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertNotEmpty($result, 'Foreign key on user_id should exist');
        }
    }

    /**
     * Test foreign key to projects table exists.
     */
    public function test_project_foreign_key(): void
    {
        $pdo = $this->getConnection()->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $result = $pdo->query(
                "PRAGMA foreign_key_list({$this->tableName})"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $projectFk = array_filter($result, fn($fk) => $fk['from'] === 'project_id');
            $this->assertNotEmpty($projectFk, 'Foreign key on project_id should exist');
        } else {
            // MySQL
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $result = $pdo->query(
                "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$dbName}' 
                AND TABLE_NAME = '{$this->tableName}' 
                AND COLUMN_NAME = 'project_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertNotEmpty($result, 'Foreign key on project_id should exist');
        }
    }

    /**
     * Test foreign key to keywords table exists.
     */
    public function test_keyword_foreign_key(): void
    {
        $pdo = $this->getConnection()->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $result = $pdo->query(
                "PRAGMA foreign_key_list({$this->tableName})"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $keywordFk = array_filter($result, fn($fk) => $fk['from'] === 'keyword_id');
            $this->assertNotEmpty($keywordFk, 'Foreign key on keyword_id should exist');
        } else {
            // MySQL
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $result = $pdo->query(
                "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$dbName}' 
                AND TABLE_NAME = '{$this->tableName}' 
                AND COLUMN_NAME = 'keyword_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL"
            )->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->assertNotEmpty($result, 'Foreign key on keyword_id should exist');
        }
    }

    // ==================== CONSTRAINT TESTS ====================

    /**
     * Test unique constraint on user_id prevents duplicate onboarding.
     */
    public function test_user_id_unique_constraint(): void
    {
        $user = \App\Models\User::factory()->create();
        
        // Create first onboarding
        \DB::table($this->tableName)->insert([
            'user_id' => $user->id,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Try to create second onboarding for same user
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table($this->tableName)->insert([
            'user_id' => $user->id,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test cascade delete when user is deleted.
     */
    public function test_cascade_delete_on_user_delete(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $onboardingId = \DB::table($this->tableName)->insertGetId([
            'user_id' => $user->id,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete user
        $user->delete();
        
        // Verify onboarding is also deleted
        $this->assertDatabaseMissing($this->tableName, [
            'id' => $onboardingId,
        ]);
    }

    /**
     * Test project_id set null when project is deleted.
     */
    public function test_project_id_set_null_on_project_delete(): void
    {
        $user = \App\Models\User::factory()->create();
        $project = \App\Models\Project::factory()->create();
        
        $onboardingId = \DB::table($this->tableName)->insertGetId([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete project
        $project->delete();
        
        // Verify project_id is null
        $this->assertDatabaseHas($this->tableName, [
            'id' => $onboardingId,
            'project_id' => null,
        ]);
    }

    /**
     * Test keyword_id set null when keyword is deleted.
     */
    public function test_keyword_id_set_null_on_keyword_delete(): void
    {
        $user = \App\Models\User::factory()->create();
        $site = \App\Models\Site::factory()->create(['user_id' => $user->id]);
        $keyword = \App\Models\Keyword::factory()->create(['site_id' => $site->id]);
        
        $onboardingId = \DB::table($this->tableName)->insertGetId([
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
            'current_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete keyword
        $keyword->delete();
        
        // Verify keyword_id is null
        $this->assertDatabaseHas($this->tableName, [
            'id' => $onboardingId,
            'keyword_id' => null,
        ]);
    }
}
