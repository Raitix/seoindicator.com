<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateNotificationsTableTest extends TestCase
{
    protected string $tableName = 'notifications';

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
    public function has_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'type'));
        $column = DB::getDoctrineColumn($this->tableName, 'type');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function has_keyword_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'keyword_id'));
        $column = DB::getDoctrineColumn($this->tableName, 'keyword_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_data_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'data'));
        $column = DB::getDoctrineColumn($this->tableName, 'data');
        $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        $this->assertFalse($column->isNullable());
    }

    /** @test */
    public function has_read_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'read_at'));
        $column = DB::getDoctrineColumn($this->tableName, 'read_at');
        $this->assertTrue($column->isNullable());
    }

    /** @test */
    public function has_created_at(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'created_at'));
    }

    /** @test */
    public function has_foreign_key_to_users(): void
    {
        $foreignKeys = $this->getForeignKeys($this->tableName);
        $userFk = collect($foreignKeys)->firstWhere('columns', ['user_id']);
        
        $this->assertNotNull($userFk);
        $this->assertEquals('users', $userFk['foreign_table']);
        $this->assertEquals('CASCADE', $userFk['on_delete']);
    }

    /** @test */
    public function has_foreign_key_to_keywords(): void
    {
        $foreignKeys = $this->getForeignKeys($this->tableName);
        $keywordFk = collect($foreignKeys)->firstWhere('columns', ['keyword_id']);
        
        $this->assertNotNull($keywordFk);
        $this->assertEquals('keywords', $keywordFk['foreign_table']);
        $this->assertEquals('SET NULL', $keywordFk['on_delete']);
    }

    /** @test */
    public function has_index_on_user_id_and_read_at(): void
    {
        $indexes = $this->getIndexes($this->tableName);
        $compositeIndex = collect($indexes)->first(function ($idx) {
            return in_array('user_id', $idx['columns']) && in_array('read_at', $idx['columns']);
        });
        
        $this->assertNotNull($compositeIndex, 'Expected composite index on (user_id, read_at)');
    }

    /** @test */
    public function rollback_drops_table(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/*_create_notifications_table.php',
            '--step' => 1
        ]);
        
        $this->assertFalse(Schema::hasTable($this->tableName));
        
        // Re-run to restore
        $this->artisan('migrate', [
            '--path' => 'database/migrations/*_create_notifications_table.php'
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
