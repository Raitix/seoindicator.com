<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateAlertSettingsTableTest extends TestCase
{
    protected string $tableName = 'alert_settings';

    /** @test */
    public function table_exists(): void
    {
        $this->assertTrue(Schema::hasTable($this->tableName));
    }

    /** @test */
    public function has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'id'));
        $column = DB::getDoctrineColumn($this->tableName, 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
    }

    /** @test */
    public function has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'user_id'));
        $column = DB::getDoctrineColumn($this->tableName, 'user_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function has_global_enabled_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'global_enabled'));
        $column = DB::getDoctrineColumn($this->tableName, 'global_enabled');
        $this->assertEquals('boolean', $column->getType()->getName());
        $this->assertEquals('1', $column->getDefault());
    }

    /** @test */
    public function has_default_threshold_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'default_threshold'));
        $column = DB::getDoctrineColumn($this->tableName, 'default_threshold');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals('3', $column->getDefault());
    }

    /** @test */
    public function has_quiet_hours_start_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'quiet_hours_start'));
        $column = DB::getDoctrineColumn($this->tableName, 'quiet_hours_start');
        $this->assertEquals('time', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_quiet_hours_end_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'quiet_hours_end'));
        $column = DB::getDoctrineColumn($this->tableName, 'quiet_hours_end');
        $this->assertEquals('time', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_digest_mode_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'digest_mode'));
        $column = DB::getDoctrineColumn($this->tableName, 'digest_mode');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals('instant', $column->getDefault());
    }

    /** @test */
    public function has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'created_at'));
        $this->assertTrue(Schema::hasColumn($this->tableName, 'updated_at'));
    }

    /** @test */
    public function has_foreign_key_to_users(): void
    {
        $foreignKeys = $this->getForeignKeys($this->tableName);
        $userFk = collect($foreignKeys)->firstWhere('columns', ['user_id']);
        
        $this->assertNotNull($userFk);
        $this->assertEquals('users', $userFk['foreign_table']);
        $this->assertEquals('id', $userFk['foreign_columns'][0]);
        $this->assertEquals('CASCADE', $userFk['on_delete']);
    }

    /** @test */
    public function has_unique_index_on_user_id(): void
    {
        $indexes = $this->getIndexes($this->tableName);
        $userIndex = collect($indexes)->firstWhere('columns', ['user_id']);
        
        $this->assertNotNull($userIndex);
        $this->assertTrue($userIndex['unique']);
    }

    /** @test */
    public function rollback_drops_table(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/*_create_alert_settings_table.php',
            '--step' => 1
        ]);
        
        $this->assertFalse(Schema::hasTable($this->tableName));
        
        // Re-run to restore
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_alert_settings_table.php'
        ]);
    }

    /** @test */
    public function rerun_migration_is_idempotent(): void
    {
        // Run migration again
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_alert_settings_table.php'
        ]);
        
        $this->assertTrue(Schema::hasTable($this->tableName));
    }

    protected function getForeignKeys(string $table): array
    {
        $conn = DB::getDoctrineConnection();
        $sm = $conn->createSchemaManager();
        
        return collect($sm->listTableForeignKeys($table))->map(function ($fk) {
            return [
                'columns' => $fk->getLocalColumns(),
                'foreign_table' => $fk->getForeignTableName(),
                'foreign_columns' => $fk->getForeignColumns(),
                'on_delete' => $fk->getOption('onDelete'),
            ];
        })->toArray();
    }

    protected function getIndexes(string $table): array
    {
        $conn = DB::getDoctrineConnection();
        $sm = $conn->createSchemaManager();
        
        return collect($sm->listTableIndexes($table))->map(function ($idx) {
            return [
                'name' => $idx->getName(),
                'columns' => $idx->getColumns(),
                'unique' => $idx->isUnique(),
            ];
        })->toArray();
    }
}
