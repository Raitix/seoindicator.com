<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateKeywordAlertSettingsTableTest extends TestCase
{
    protected string $tableName = 'keyword_alert_settings';

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
    public function has_keyword_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'keyword_id'));
        $column = DB::getDoctrineColumn($this->tableName, 'keyword_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function has_enabled_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'enabled'));
        $column = DB::getDoctrineColumn($this->tableName, 'enabled');
        $this->assertEquals('boolean', $column->getType()->getName());
        $this->assertEquals('1', $column->getDefault());
    }

    /** @test */
    public function has_custom_threshold_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'custom_threshold'));
        $column = DB::getDoctrineColumn($this->tableName, 'custom_threshold');
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_alert_events_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'alert_events'));
        $column = DB::getDoctrineColumn($this->tableName, 'alert_events');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_notification_channels_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'notification_channels'));
        $column = DB::getDoctrineColumn($this->tableName, 'notification_channels');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_created_at(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'created_at'));
    }

    /** @test */
    public function has_foreign_key_to_keywords(): void
    {
        $foreignKeys = $this->getForeignKeys($this->tableName);
        $keywordFk = collect($foreignKeys)->firstWhere('columns', ['keyword_id']);
        
        $this->assertNotNull($keywordFk);
        $this->assertEquals('keywords', $keywordFk['foreign_table']);
        $this->assertEquals('id', $keywordFk['foreign_columns'][0]);
        $this->assertEquals('CASCADE', $keywordFk['on_delete']);
    }

    /** @test */
    public function has_unique_index_on_keyword_id(): void
    {
        $indexes = $this->getIndexes($this->tableName);
        $keywordIndex = collect($indexes)->firstWhere('columns', ['keyword_id']);
        
        $this->assertNotNull($keywordIndex);
        $this->assertTrue($keywordIndex['unique']);
    }

    /** @test */
    public function rollback_drops_table(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/*_create_keyword_alert_settings_table.php',
            '--step' => 1
        ]);
        
        $this->assertFalse(Schema::hasTable($this->tableName));
        
        // Re-run to restore
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_keyword_alert_settings_table.php'
        ]);
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
