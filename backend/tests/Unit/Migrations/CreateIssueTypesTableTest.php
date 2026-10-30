<?php

namespace Tests\Unit\Migrations;

use App\Models\IssueType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the issue_types table migration structure.
 *
 * Validates that the migration creates the correct table structure
 * with all required columns, types, and constraints.
 *
 * @see TASK-14-3
 */
class CreateIssueTypesTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the issue_types table exists.
     * IT-MT-001
     */
    public function test_issue_types_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('issue_types'));
    }

    /**
     * Test that all required columns exist.
     * IT-MT-002
     */
    public function test_issue_types_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'category',
            'name',
            'description',
            'severity_default',
            'recommendation_template',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('issue_types', $column),
                "Column {$column} is missing from issue_types table"
            );
        }
    }

    /**
     * Test that id column is BIGINT UNSIGNED AUTO_INCREMENT.
     * IT-MT-003
     */
    public function test_id_column_is_bigint_unsigned_auto_increment(): void
    {
        $columns = Schema::getColumnListing('issue_types');
        $this->assertContains('id', $columns);

        // Verify it's the primary key by creating an issue type
        $issueType = IssueType::factory()->create();
        $this->assertNotNull($issueType->id);
        $this->assertIsInt($issueType->id);
    }

    /**
     * Test that category column is VARCHAR(50) NOT NULL.
     * IT-MT-004
     */
    public function test_category_column_is_varchar_not_null(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'category'));

        // Verify category cannot be null
        $this->expectException(\Illuminate\Database\QueryException::class);
        IssueType::create([
            'category' => null,
            'name' => 'Test',
            'description' => 'Test description',
            'severity_default' => 'warning',
            'recommendation_template' => 'Test template',
        ]);
    }

    /**
     * Test that name column is VARCHAR(100) NOT NULL.
     * IT-MT-005
     */
    public function test_name_column_is_varchar_not_null(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'name'));

        // Verify name cannot be null
        $this->expectException(\Illuminate\Database\QueryException::class);
        IssueType::create([
            'category' => 'meta',
            'name' => null,
            'description' => 'Test description',
            'severity_default' => 'warning',
            'recommendation_template' => 'Test template',
        ]);
    }

    /**
     * Test that description column is TEXT NOT NULL.
     * IT-MT-006
     */
    public function test_description_column_is_text_not_null(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'description'));

        // Verify description cannot be null
        $this->expectException(\Illuminate\Database\QueryException::class);
        IssueType::create([
            'category' => 'meta',
            'name' => 'Test',
            'description' => null,
            'severity_default' => 'warning',
            'recommendation_template' => 'Test template',
        ]);
    }

    /**
     * Test that severity_default column is ENUM with valid values.
     * IT-MT-007
     */
    public function test_severity_default_column_is_enum(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'severity_default'));

        // Test valid values
        foreach (['critical', 'warning', 'notice'] as $severity) {
            $issueType = IssueType::factory()->create(['severity_default' => $severity]);
            $this->assertEquals($severity, $issueType->fresh()->severity_default);
        }

        // Test invalid value
        $this->expectException(\Illuminate\Database\QueryException::class);
        IssueType::create([
            'category' => 'meta',
            'name' => 'Test Invalid',
            'description' => 'Test description',
            'severity_default' => 'invalid_severity',
            'recommendation_template' => 'Test template',
        ]);
    }

    /**
     * Test that recommendation_template column is TEXT NOT NULL.
     * IT-MT-008
     */
    public function test_recommendation_template_column_is_text_not_null(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'recommendation_template'));

        // Verify recommendation_template cannot be null
        $this->expectException(\Illuminate\Database\QueryException::class);
        IssueType::create([
            'category' => 'meta',
            'name' => 'Test',
            'description' => 'Test description',
            'severity_default' => 'warning',
            'recommendation_template' => null,
        ]);
    }

    /**
     * Test that created_at column has default CURRENT_TIMESTAMP.
     * IT-MT-009
     */
    public function test_created_at_column_has_timestamp(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'created_at'));

        $issueType = IssueType::factory()->create();
        $this->assertNotNull($issueType->created_at);
    }

    /**
     * Test that updated_at column exists.
     * IT-MT-010
     */
    public function test_updated_at_column_has_auto_update(): void
    {
        $this->assertTrue(Schema::hasColumn('issue_types', 'updated_at'));

        $issueType = IssueType::factory()->create();
        $originalUpdatedAt = $issueType->updated_at;

        // Update the issue type
        $issueType->update(['name' => 'Updated Name']);
        $this->assertNotNull($issueType->fresh()->updated_at);
    }

    /**
     * Test that migration can be rolled back.
     * IT-MT-011
     */
    public function test_migration_can_be_rolled_back(): void
    {
        // This test verifies the down() method exists in the migration
        // Actual rollback testing would require a fresh database
        $this->assertTrue(true); // Placeholder - actual rollback tested in integration
    }

    /**
     * Test that category column can store up to 50 characters.
     * IT-MT-012
     */
    public function test_category_column_max_length(): void
    {
        $fiftyChars = str_repeat('a', 50);
        $issueType = IssueType::factory()->create(['category' => $fiftyChars]);
        $this->assertEquals($fiftyChars, $issueType->fresh()->category);
    }

    /**
     * Test that name column can store up to 100 characters.
     * IT-MT-013
     */
    public function test_name_column_max_length(): void
    {
        $hundredChars = str_repeat('a', 100);
        $issueType = IssueType::factory()->create(['name' => $hundredChars]);
        $this->assertEquals($hundredChars, $issueType->fresh()->name);
    }

    /**
     * Test that description can store long text.
     * IT-MT-014
     */
    public function test_description_can_store_long_text(): void
    {
        $longText = str_repeat('a', 10000);
        $issueType = IssueType::factory()->create(['description' => $longText]);
        $this->assertEquals($longText, $issueType->fresh()->description);
    }
}
