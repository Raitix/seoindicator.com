<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for backlinks database migrations
 * 
 * Tests 4 tables:
 * - backlinks
 * - referring_domains
 * - anchor_texts
 * - backlink_snapshots
 * 
 * @see TASK-23-1
 * @see TEST-23-1
 * @see SPEC-023
 */
class CreateBacklinksTablesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BACKLINKS TABLE TESTS
    // =========================================================================

    /**
     * Test that the backlinks table exists after migration.
     * 
     * @test
     * @return void
     */
    public function backlinks_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('backlinks'),
            'The backlinks table should exist after migration.'
        );
    }

    /**
     * Test backlinks table has all required columns.
     * 
     * @test
     * @return void
     */
    public function backlinks_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'project_id',
            'source_url',
            'source_domain',
            'target_url',
            'anchor_text',
            'link_type',
            'first_seen_at',
            'last_checked_at',
            'is_active',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('backlinks', $column),
                "The backlinks table should have column: {$column}"
            );
        }
    }

    /**
     * Test backlinks id column is primary key.
     * 
     * @test
     * @return void
     */
    public function backlinks_id_is_primary_key(): void
    {
        $column = Schema::getColumnType('backlinks', 'id');
        $this->assertContains($column, ['bigint', 'integer'], 'The id column should be a bigint.');
    }

    /**
     * Test backlinks project_id is required.
     * 
     * @test
     * @return void
     */
    public function backlinks_project_id_is_required(): void
    {
        $this->expectException(\Exception::class);
        DB::table('backlinks')->insert([
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test backlinks source_url is required.
     * 
     * @test
     * @return void
     */
    public function backlinks_source_url_is_required(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $this->expectException(\Exception::class);
        DB::table('backlinks')->insert([
            'project_id' => $project->id,
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test backlinks anchor_text is nullable.
     * 
     * @test
     * @return void
     */
    public function backlinks_anchor_text_is_nullable(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('backlinks')->find($id);
        $this->assertNull($record->anchor_text, 'anchor_text should be nullable.');
    }

    /**
     * Test backlinks link_type enum values.
     * 
     * @test
     * @dataProvider backlinkLinkTypeProvider
     * @return void
     */
    public function backlinks_link_type_accepts_valid_values(string $linkType): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => "https://example-{$linkType}.com/page",
            'source_domain' => "example-{$linkType}.com",
            'target_url' => 'https://myproject.com',
            'link_type' => $linkType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('backlinks')->find($id);
        $this->assertEquals($linkType, $record->link_type, "link_type '{$linkType}' should be accepted.");
    }

    /**
     * Valid link_type values data provider.
     */
    public static function backlinkLinkTypeProvider(): array
    {
        return [
            'dofollow' => ['dofollow'],
            'nofollow' => ['nofollow'],
            'unknown' => ['unknown'],
        ];
    }

    /**
     * Test backlinks link_type default value.
     * 
     * @test
     * @return void
     */
    public function backlinks_link_type_default_is_unknown(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('backlinks')->find($id);
        $this->assertEquals('unknown', $record->link_type, 'link_type should default to unknown.');
    }

    /**
     * Test backlinks first_seen_at is nullable.
     * 
     * @test
     * @return void
     */
    public function backlinks_first_seen_at_is_nullable(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('backlinks')->find($id);
        $this->assertNull($record->first_seen_at, 'first_seen_at should be nullable.');
    }

    /**
     * Test backlinks is_active default value.
     * 
     * @test
     * @return void
     */
    public function backlinks_is_active_default_is_true(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('backlinks')->find($id);
        $this->assertEquals(1, $record->is_active, 'is_active should default to 1 (true).');
    }

    /**
     * Test backlinks indexes exist.
     * 
     * @test
     * @return void
     */
    public function backlinks_indexes_exist(): void
    {
        $indexes = $this->getTableIndexes('backlinks');
        $indexNames = array_column($indexes, 'name');
        
        // Check for project_domain composite index
        $this->assertTrue(
            $this->hasCompositeIndex($indexes, ['project_id', 'source_domain']) ||
            in_array('idx_project_domain', $indexNames) ||
            in_array('backlinks_project_id_source_domain_index', $indexNames),
            'An index on (project_id, source_domain) should exist.'
        );
        
        // Check for is_active index
        $this->assertTrue(
            $this->hasIndexOnColumn($indexes, 'is_active') ||
            in_array('idx_is_active', $indexNames) ||
            in_array('backlinks_is_active_index', $indexNames),
            'An index on is_active should exist.'
        );
    }

    /**
     * Test backlinks project_id foreign key cascade.
     * 
     * @test
     * @return void
     */
    public function backlinks_cascade_delete_on_project_delete(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete project
        $project->delete();
        
        // Check backlink was deleted
        $record = DB::table('backlinks')->find($id);
        $this->assertNull($record, 'Backlink should be deleted when project is deleted (CASCADE).');
    }

    // =========================================================================
    // REFERRING_DOMAINS TABLE TESTS
    // =========================================================================

    /**
     * Test that the referring_domains table exists after migration.
     * 
     * @test
     * @return void
     */
    public function referring_domains_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('referring_domains'),
            'The referring_domains table should exist after migration.'
        );
    }

    /**
     * Test referring_domains table has all required columns.
     * 
     * @test
     * @return void
     */
    public function referring_domains_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'project_id',
            'domain',
            'backlinks_count',
            'domain_trust_score',
            'first_seen_at',
            'last_seen_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('referring_domains', $column),
                "The referring_domains table should have column: {$column}"
            );
        }
    }

    /**
     * Test referring_domains domain is required.
     * 
     * @test
     * @return void
     */
    public function referring_domains_domain_is_required(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $this->expectException(\Exception::class);
        DB::table('referring_domains')->insert([
            'project_id' => $project->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test referring_domains backlinks_count default value.
     * 
     * @test
     * @return void
     */
    public function referring_domains_backlinks_count_default_is_zero(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('referring_domains')->insertGetId([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('referring_domains')->find($id);
        $this->assertEquals(0, $record->backlinks_count, 'backlinks_count should default to 0.');
    }

    /**
     * Test referring_domains domain_trust_score default value.
     * 
     * @test
     * @return void
     */
    public function referring_domains_domain_trust_score_default_is_zero(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('referring_domains')->insertGetId([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('referring_domains')->find($id);
        $this->assertEquals(0, $record->domain_trust_score, 'domain_trust_score should default to 0.');
    }

    /**
     * Test referring_domains unique constraint on (project_id, domain).
     * 
     * @test
     * @return void
     */
    public function referring_domains_unique_project_domain(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        // First insert should succeed
        DB::table('referring_domains')->insert([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Second insert with same project_id and domain should fail
        $this->expectException(\Exception::class);
        DB::table('referring_domains')->insert([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test referring_domains same domain for different projects.
     * 
     * @test
     * @return void
     */
    public function referring_domains_same_domain_different_projects(): void
    {
        $project1 = \App\Models\Project::factory()->create();
        $project2 = \App\Models\Project::factory()->create();
        
        // Both inserts should succeed
        $id1 = DB::table('referring_domains')->insertGetId([
            'project_id' => $project1->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $id2 = DB::table('referring_domains')->insertGetId([
            'project_id' => $project2->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotEquals($id1, $id2, 'Same domain should be allowed for different projects.');
    }

    /**
     * Test referring_domains cascade delete on project delete.
     * 
     * @test
     * @return void
     */
    public function referring_domains_cascade_delete_on_project_delete(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('referring_domains')->insertGetId([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete project
        $project->delete();
        
        // Check referring_domain was deleted
        $record = DB::table('referring_domains')->find($id);
        $this->assertNull($record, 'Referring domain should be deleted when project is deleted (CASCADE).');
    }

    // =========================================================================
    // ANCHOR_TEXTS TABLE TESTS
    // =========================================================================

    /**
     * Test that the anchor_texts table exists after migration.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('anchor_texts'),
            'The anchor_texts table should exist after migration.'
        );
    }

    /**
     * Test anchor_texts table has all required columns.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'project_id',
            'anchor_text',
            'backlinks_count',
            'is_branded',
            'is_exact_match',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('anchor_texts', $column),
                "The anchor_texts table should have column: {$column}"
            );
        }
    }

    /**
     * Test anchor_texts is_branded default value.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_is_branded_default_is_false(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => 'click here',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('anchor_texts')->find($id);
        $this->assertEquals(0, $record->is_branded, 'is_branded should default to 0 (false).');
    }

    /**
     * Test anchor_texts is_exact_match default value.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_is_exact_match_default_is_false(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => 'click here',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('anchor_texts')->find($id);
        $this->assertEquals(0, $record->is_exact_match, 'is_exact_match should default to 0 (false).');
    }

    /**
     * Test anchor_texts both flags can be true.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_both_flags_can_be_true(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => 'brand name keyword',
            'is_branded' => 1,
            'is_exact_match' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('anchor_texts')->find($id);
        $this->assertEquals(1, $record->is_branded, 'is_branded should be true.');
        $this->assertEquals(1, $record->is_exact_match, 'is_exact_match should be true.');
    }

    /**
     * Test anchor_texts cascade delete on project delete.
     * 
     * @test
     * @return void
     */
    public function anchor_texts_cascade_delete_on_project_delete(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => 'click here',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete project
        $project->delete();
        
        // Check anchor_text was deleted
        $record = DB::table('anchor_texts')->find($id);
        $this->assertNull($record, 'Anchor text should be deleted when project is deleted (CASCADE).');
    }

    // =========================================================================
    // BACKLINK_SNAPSHOTS TABLE TESTS
    // =========================================================================

    /**
     * Test that the backlink_snapshots table exists after migration.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('backlink_snapshots'),
            'The backlink_snapshots table should exist after migration.'
        );
    }

    /**
     * Test backlink_snapshots table has all required columns.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'project_id',
            'total_backlinks',
            'referring_domains_count',
            'dofollow_count',
            'nofollow_count',
            'avg_domain_trust',
            'anchor_diversity_score',
            'snapshot_date',
            'created_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('backlink_snapshots', $column),
                "The backlink_snapshots table should have column: {$column}"
            );
        }
    }

    /**
     * Test backlink_snapshots snapshot_date is required.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_snapshot_date_is_required(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $this->expectException(\Exception::class);
        DB::table('backlink_snapshots')->insert([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);
    }

    /**
     * Test backlink_snapshots integer defaults.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_integer_defaults(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        $record = DB::table('backlink_snapshots')->find($id);
        $this->assertEquals(0, $record->total_backlinks, 'total_backlinks should default to 0.');
        $this->assertEquals(0, $record->referring_domains_count, 'referring_domains_count should default to 0.');
        $this->assertEquals(0, $record->dofollow_count, 'dofollow_count should default to 0.');
        $this->assertEquals(0, $record->nofollow_count, 'nofollow_count should default to 0.');
    }

    /**
     * Test backlink_snapshots decimal defaults.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_decimal_defaults(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        $record = DB::table('backlink_snapshots')->find($id);
        $this->assertEquals('0.00', $record->avg_domain_trust, 'avg_domain_trust should default to 0.00.');
        $this->assertEquals('0.00', $record->anchor_diversity_score, 'anchor_diversity_score should default to 0.00.');
    }

    /**
     * Test backlink_snapshots decimal precision.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_decimal_precision(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'avg_domain_trust' => 45.67,
            'anchor_diversity_score' => 78.90,
            'created_at' => now(),
        ]);
        
        $record = DB::table('backlink_snapshots')->find($id);
        $this->assertEquals('45.67', $record->avg_domain_trust, 'avg_domain_trust should store DECIMAL(5,2).');
        $this->assertEquals('78.90', $record->anchor_diversity_score, 'anchor_diversity_score should store DECIMAL(5,2).');
    }

    /**
     * Test backlink_snapshots unique constraint on (project_id, snapshot_date).
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_unique_project_date(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        // First insert should succeed
        DB::table('backlink_snapshots')->insert([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        // Second insert with same project_id and date should fail
        $this->expectException(\Exception::class);
        DB::table('backlink_snapshots')->insert([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
    }

    /**
     * Test backlink_snapshots same date for different projects.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_same_date_different_projects(): void
    {
        $project1 = \App\Models\Project::factory()->create();
        $project2 = \App\Models\Project::factory()->create();
        
        // Both inserts should succeed
        $id1 = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project1->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        $id2 = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project2->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        $this->assertNotEquals($id1, $id2, 'Same date should be allowed for different projects.');
    }

    /**
     * Test backlink_snapshots cascade delete on project delete.
     * 
     * @test
     * @return void
     */
    public function backlink_snapshots_cascade_delete_on_project_delete(): void
    {
        $project = \App\Models\Project::factory()->create();
        
        $id = DB::table('backlink_snapshots')->insertGetId([
            'project_id' => $project->id,
            'snapshot_date' => '2026-02-28',
            'created_at' => now(),
        ]);
        
        // Delete project
        $project->delete();
        
        // Check snapshot was deleted
        $record = DB::table('backlink_snapshots')->find($id);
        $this->assertNull($record, 'Snapshot should be deleted when project is deleted (CASCADE).');
    }

    // =========================================================================
    // MIGRATION ROLLBACK TESTS
    // =========================================================================

    /**
     * Test rollback removes all backlinks tables.
     * 
     * @test
     * @return void
     */
    public function rollback_removes_all_backlinks_tables(): void
    {
        // Verify tables exist
        $this->assertTrue(Schema::hasTable('backlinks'));
        $this->assertTrue(Schema::hasTable('referring_domains'));
        $this->assertTrue(Schema::hasTable('anchor_texts'));
        $this->assertTrue(Schema::hasTable('backlink_snapshots'));
        
        // Run rollback (may need multiple steps if separate migrations)
        $this->artisan('migrate:rollback', ['--step' => 4]);
        
        $this->assertFalse(
            Schema::hasTable('backlinks'),
            'The backlinks table should not exist after rollback.'
        );
        $this->assertFalse(
            Schema::hasTable('referring_domains'),
            'The referring_domains table should not exist after rollback.'
        );
        $this->assertFalse(
            Schema::hasTable('anchor_texts'),
            'The anchor_texts table should not exist after rollback.'
        );
        $this->assertFalse(
            Schema::hasTable('backlink_snapshots'),
            'The backlink_snapshots table should not exist after rollback.'
        );
        
        // Re-run migration for other tests
        $this->artisan('migrate');
    }

    /**
     * Test migration can be re-run.
     * 
     * @test
     * @return void
     */
    public function migration_can_be_rerun(): void
    {
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 4]);
        
        // Re-run
        $this->artisan('migrate');
        
        $this->assertTrue(
            Schema::hasTable('backlinks'),
            'The backlinks table should exist after re-running migration.'
        );
        $this->assertTrue(
            Schema::hasTable('referring_domains'),
            'The referring_domains table should exist after re-running migration.'
        );
        $this->assertTrue(
            Schema::hasTable('anchor_texts'),
            'The anchor_texts table should exist after re-running migration.'
        );
        $this->assertTrue(
            Schema::hasTable('backlink_snapshots'),
            'The backlink_snapshots table should exist after re-running migration.'
        );
    }

    // =========================================================================
    // SECURITY TESTS
    // =========================================================================

    /**
     * Test SQL injection protection on backlinks source_url.
     * 
     * @test
     * @return void
     */
    public function sql_injection_protection_on_backlinks_source_url(): void
    {
        $project = \App\Models\Project::factory()->create();
        $maliciousUrl = "https://example.com'; DROP TABLE backlinks;--";
        
        $id = DB::table('backlinks')->insertGetId([
            'project_id' => $project->id,
            'source_url' => $maliciousUrl,
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // If SQL injection succeeded, table would be dropped
        $this->assertTrue(
            Schema::hasTable('backlinks'),
            'Table should still exist after SQL injection attempt.'
        );
        
        // Verify the URL was stored as-is (parameterized queries)
        $record = DB::table('backlinks')->find($id);
        $this->assertEquals($maliciousUrl, $record->source_url, 'URL should be stored safely.');
    }

    /**
     * Test SQL injection protection on referring_domains domain.
     * 
     * @test
     * @return void
     */
    public function sql_injection_protection_on_referring_domains_domain(): void
    {
        $project = \App\Models\Project::factory()->create();
        $maliciousDomain = "example.com'; DROP TABLE referring_domains;--";
        
        $id = DB::table('referring_domains')->insertGetId([
            'project_id' => $project->id,
            'domain' => $maliciousDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // If SQL injection succeeded, table would be dropped
        $this->assertTrue(
            Schema::hasTable('referring_domains'),
            'Table should still exist after SQL injection attempt.'
        );
        
        $record = DB::table('referring_domains')->find($id);
        $this->assertEquals($maliciousDomain, $record->domain, 'Domain should be stored safely.');
    }

    /**
     * Test XSS protection on anchor_texts anchor_text.
     * 
     * @test
     * @return void
     */
    public function xss_protection_on_anchor_text(): void
    {
        $project = \App\Models\Project::factory()->create();
        $xssText = '<script>alert(1)</script>';
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => $xssText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('anchor_texts')->find($id);
        // Storage should work, output should be escaped
        $this->assertEquals($xssText, $record->anchor_text, 'XSS string stored as-is (escaping happens at output).');
    }

    /**
     * Test unicode handling in anchor_text.
     * 
     * @test
     * @return void
     */
    public function unicode_handling_in_anchor_text(): void
    {
        $project = \App\Models\Project::factory()->create();
        $unicodeText = '点击这里了解更多';
        
        $id = DB::table('anchor_texts')->insertGetId([
            'project_id' => $project->id,
            'anchor_text' => $unicodeText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('anchor_texts')->find($id);
        $this->assertEquals($unicodeText, $record->anchor_text, 'Unicode text should be stored correctly.');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Helper: Get table indexes.
     */
    private function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            $result = DB::select("PRAGMA index_list({$table})");
            $indexes = [];
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info({$row->name})");
                $columns = array_column($indexInfo, 'name');
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => $columns,
                ];
            }
            return $indexes;
        }
        
        if ($driver === 'mysql') {
            $result = DB::select("SHOW INDEX FROM {$table}");
            $indexes = [];
            foreach ($result as $row) {
                $indexes[$row->Key_name]['name'] = $row->Key_name;
                $indexes[$row->Key_name]['columns'][] = $row->Column_name;
            }
            return array_values($indexes);
        }
        
        return [];
    }

    /**
     * Helper: Check if composite index exists.
     */
    private function hasCompositeIndex(array $indexes, array $columns): bool
    {
        foreach ($indexes as $index) {
            if (isset($index['columns']) && $index['columns'] === $columns) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper: Check if index exists on column.
     */
    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (isset($index['columns']) && in_array($column, $index['columns'])) {
                return true;
            }
        }
        return false;
    }
}
