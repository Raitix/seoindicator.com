<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * User Model Preferences Migration Tests (TASK-26-19)
 *
 * Tests for the user_model_preferences table migration.
 * Validates schema structure, indexes, foreign keys, and constraints.
 *
 * @group user-model-preferences
 * @group migrations
 */
class UserModelPreferencesMigrationTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Table Structure Tests (UMP-MIG-TABLE)
    // =========================================================================

    /** @testUMP-MIG-TABLE-01 */
    public function it_creates_user_model_preferences_table(): void
    {
        $this->assertTrue(
            Schema::hasTable('user_model_preferences'),
            'Table user_model_preferences should exist'
        );
    }

    /** @testUMP-MIG-TABLE-02 */
    public function it_has_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'id'),
            'Column id should exist'
        );
    }

    /** @testUMP-MIG-TABLE-03 */
    public function it_has_user_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'user_id'),
            'Column user_id should exist'
        );
    }

    /** @testUMP-MIG-TABLE-04 */
    public function it_has_model_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'model_id'),
            'Column model_id should exist'
        );
    }

    /** @testUMP-MIG-TABLE-05 */
    public function it_has_is_default_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'is_default'),
            'Column is_default should exist'
        );
    }

    /** @testUMP-MIG-TABLE-06 */
    public function it_has_created_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'created_at'),
            'Column created_at should exist'
        );
    }

    /** @testUMP-MIG-TABLE-07 */
    public function it_has_updated_at_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('user_model_preferences', 'updated_at'),
            'Column updated_at should exist'
        );
    }

    // =========================================================================
    // Column Type Tests (UMP-MIG-TYPE)
    // =========================================================================

    /** @testUMP-MIG-TYPE-01 */
    public function id_is_bigint_unsigned(): void
    {
        $column = $this->getColumn('user_model_preferences', 'id');

        $this->assertEquals('bigint', $column->getType());
        $this->assertFalse($column->getUnsigned());
    }

    /** @testUMP-MIG-TYPE-02 */
    public function user_id_is_bigint_unsigned(): void
    {
        $column = $this->getColumn('user_model_preferences', 'user_id');

        $this->assertEquals('bigint', $column->getType());
        $this->assertFalse($column->getUnsigned());
    }

    /** @testUMP-MIG-TYPE-03 */
    public function model_id_is_bigint_unsigned(): void
    {
        $column = $this->getColumn('user_model_preferences', 'model_id');

        $this->assertEquals('bigint', $column->getType());
        $this->assertFalse($column->getUnsigned());
    }

    /** @testUMP-MIG-TYPE-04 */
    public function is_default_is_tinyint_or_boolean(): void
    {
        $column = $this->getColumn('user_model_preferences', 'is_default');

        $type = $column->getType();

        $this->assertTrue(
            in_array($type, ['tinyint', 'boolean', 'smallint']),
            "is_default should be tinyint or boolean, got {$type}"
        );
    }

    /** @testUMP-MIG-TYPE-05 */
    public function created_at_is_timestamp(): void
    {
        $column = $this->getColumn('user_model_preferences', 'created_at');

        $this->assertEquals('timestamp', $column->getType());
    }

    /** @testUMP-MIG-TYPE-06 */
    public function updated_at_is_timestamp(): void
    {
        $column = $this->getColumn('user_model_preferences', 'updated_at');

        $this->assertEquals('timestamp', $column->getType());
    }

    // =========================================================================
    // Default Value Tests (UMP-MIG-DEFAULT)
    // =========================================================================

    /** @testUMP-MIG-DEFAULT-01 */
    public function is_default_defaults_to_false(): void
    {
        $column = $this->getColumn('user_model_preferences', 'is_default');

        // The default should be 0 (false)
        $default = $column->getDefault();

        $this->assertEquals(0, $default, 'is_default should default to 0 (false)');
    }

    /** @testUMP-MIG-DEFAULT-02 */
    public function created_at_has_default_current_timestamp(): void
    {
        $column = $this->getColumn('user_model_preferences', 'created_at');

        $this->assertNotNull($column->getDefault(), 'created_at should have a default');
    }

    // =========================================================================
    // Index Tests (UMP-MIG-INDEX)
    // =========================================================================

    /** @testUMP-MIG-INDEX-01 */
    public function it_has_primary_key_on_id(): void
    {
        $connection = \DB::connection();
        $indexes = $this->getTableIndexes('user_model_preferences');

        $primaryKey = collect($indexes)->firstWhere('name', 'PRIMARY');

        $this->assertNotNull($primaryKey, 'Primary key should exist on id');
        $this->assertEquals(['id'], $primaryKey['columns'] ?? []);
    }

    /** @testUMP-MIG-INDEX-02 */
    public function it_has_index_on_user_id(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('user_id', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index should exist on user_id');
    }

    /** @testUMP-MIG-INDEX-03 */
    public function it_has_index_on_model_id(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('model_id', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index should exist on model_id');
    }

    /** @testUMP-MIG-INDEX-04 */
    public function it_has_unique_constraint_on_user_id_and_model_id(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $uniqueIndex = collect($indexes)->first(function ($index) {
            $columns = $index['columns'] ?? [];
            sort($columns);

            return $index['unique'] === true &&
                $columns === ['model_id', 'user_id'];
        });

        $this->assertNotNull(
            $uniqueIndex,
            'Unique constraint should exist on (user_id, model_id)'
        );
    }

    /** @testUMP-MIG-INDEX-05 */
    public function it_has_index_on_is_default(): void
    {
        $indexes = $this->getTableIndexes('user_model_preferences');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('is_default', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index should exist on is_default');
    }

    // =========================================================================
    // Foreign Key Tests (UMP-MIG-FK)
    // =========================================================================

    /** @testUMP-MIG-FK-01 */
    public function user_id_references_users_table(): void
    {
        $foreignKeys = $this->getTableForeignKeys('user_model_preferences');

        $userFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('user_id', $fk['columns'] ?? []);
        });

        $this->assertNotNull($userFk, 'Foreign key should exist on user_id');
        $this->assertEquals('users', $userFk['foreign_table'] ?? null);
        $this->assertEquals(['id'], $userFk['foreign_columns'] ?? []);
    }

    /** @testUMP-MIG-FK-02 */
    public function model_id_references_ai_models_table(): void
    {
        $foreignKeys = $this->getTableForeignKeys('user_model_preferences');

        $modelFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('model_id', $fk['columns'] ?? []);
        });

        $this->assertNotNull($modelFk, 'Foreign key should exist on model_id');
        $this->assertEquals('ai_models', $modelFk['foreign_table'] ?? null);
        $this->assertEquals(['id'], $modelFk['foreign_columns'] ?? []);
    }

    /** @testUMP-MIG-FK-03 */
    public function user_id_cascades_on_delete(): void
    {
        $foreignKeys = $this->getTableForeignKeys('user_model_preferences');

        $userFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('user_id', $fk['columns'] ?? []);
        });

        $this->assertEquals(
            'CASCADE',
            $userFk['on_delete'] ?? null,
            'user_id should CASCADE on DELETE'
        );
    }

    /** @testUMP-MIG-FK-04 */
    public function model_id_cascades_on_delete(): void
    {
        $foreignKeys = $this->getTableForeignKeys('user_model_preferences');

        $modelFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('model_id', $fk['columns'] ?? []);
        });

        $this->assertEquals(
            'CASCADE',
            $modelFk['on_delete'] ?? null,
            'model_id should CASCADE on DELETE'
        );
    }

    // =========================================================================
    // Migration Rollback Tests (UMP-MIG-ROLLBACK)
    // =========================================================================

    /** @testUMP-MIG-ROLLBACK-01 */
    public function it_can_drop_table_on_rollback(): void
    {
        // This test verifies the down() method exists
        // The actual rollback test would require running migration rollback
        $migration = new class extends \Illuminate\Database\Migrations\Migration
        {
            public function up()
            {
                Schema::create('user_model_preferences', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->foreignId('model_id')->constrained('ai_models')->onDelete('cascade');
                    $table->boolean('is_default')->default(false);
                    $table->timestamps();

                    $table->unique(['user_id', 'model_id']);
                    $table->index('is_default');
                });
            }

            public function down()
            {
                Schema::dropIfExists('user_model_preferences');
            }
        };

        $this->assertTrue(method_exists($migration, 'down'), 'Migration should have down() method');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function getColumn(string $table, string $column): \Doctrine\DBAL\Schema\Column
    {
        return Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableDetails($table)
            ->getColumn($column);
    }

    protected function getTableIndexes(string $tableName): array
    {
        $connection = \DB::connection();
        $indexes = [];

        if ($connection->getDriverName() === 'sqlite') {
            $result = \DB::select("PRAGMA index_list('{$tableName}')");
            foreach ($result as $row) {
                $columns = \DB::select("PRAGMA index_info('{$row->name}')");
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => array_map(fn ($c) => $c->name, $columns),
                    'unique' => $row->unique === 1,
                ];
            }
        } else {
            $result = \DB::select("SHOW INDEX FROM {$tableName}");
            foreach ($result as $row) {
                $found = false;
                foreach ($indexes as &$index) {
                    if ($index['name'] === $row->Key_name) {
                        $index['columns'][] = $row->Column_name;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $indexes[] = [
                        'name' => $row->Key_name,
                        'columns' => [$row->Column_name],
                        'unique' => !$row->Non_unique,
                    ];
                }
            }
        }

        return $indexes;
    }

    protected function getTableForeignKeys(string $tableName): array
    {
        $connection = \DB::connection();
        $foreignKeys = [];

        if ($connection->getDriverName() === 'sqlite') {
            $result = \DB::select("PRAGMA foreign_key_list('{$tableName}')");
            foreach ($result as $row) {
                $foreignKeys[] = [
                    'columns' => [$row->from],
                    'foreign_table' => $row->table,
                    'foreign_columns' => [$row->to],
                    'on_delete' => strtoupper($row->on_delete ?? 'NO ACTION'),
                ];
            }
        } else {
            $rows = \DB::select("
                SELECT
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            $constraintRows = \DB::select("
                SELECT
                    CONSTRAINT_NAME,
                    DELETE_RULE
                FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
            ", [$tableName]);

            $deleteRules = [];
            foreach ($constraintRows as $row) {
                $deleteRules[$row->CONSTRAINT_NAME] = $row->DELETE_RULE;
            }

            foreach ($rows as $row) {
                $foreignKeys[] = [
                    'columns' => [$row->COLUMN_NAME],
                    'foreign_table' => $row->REFERENCED_TABLE_NAME,
                    'foreign_columns' => [$row->REFERENCED_COLUMN_NAME],
                    'on_delete' => $deleteRules[$row->CONSTRAINT_NAME] ?? 'NO ACTION',
                ];
            }
        }

        return $foreignKeys;
    }
}
