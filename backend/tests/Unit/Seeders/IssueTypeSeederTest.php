<?php

namespace Tests\Unit\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the IssueTypeSeeder.
 *
 * Validates that the seeder creates the expected issue types
 * with correct data and relationships.
 *
 * @see TASK-14-3
 */
class IssueTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that seeder creates issue types.
     * ST-001
     */
    public function test_seeder_creates_issue_types(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder'])
            ->assertSuccessful();

        $this->assertGreaterThan(0, DB::table('issue_types')->count());
    }

    /**
     * Test that seeder creates issue types in all categories.
     * ST-002
     */
    public function test_seeder_creates_issue_types_in_all_categories(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $categories = DB::table('issue_types')
            ->distinct()
            ->pluck('category')
            ->toArray();

        $this->assertContains('meta', $categories);
        $this->assertContains('content', $categories);
        $this->assertContains('backlink', $categories);
        $this->assertContains('performance', $categories);
    }

    /**
     * Test that seeder creates issue types with all severity levels.
     * ST-003
     */
    public function test_seeder_creates_issue_types_with_all_severities(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $severities = DB::table('issue_types')
            ->distinct()
            ->pluck('severity_default')
            ->toArray();

        $this->assertContains('critical', $severities);
        $this->assertContains('warning', $severities);
        $this->assertContains('notice', $severities);
    }

    /**
     * Test that seeder creates issue types with required fields.
     * ST-004
     */
    public function test_seeder_creates_issue_types_with_required_fields(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $issueType = DB::table('issue_types')->first();

        $this->assertNotNull($issueType->category);
        $this->assertNotNull($issueType->name);
        $this->assertNotNull($issueType->description);
        $this->assertNotNull($issueType->severity_default);
        $this->assertNotNull($issueType->recommendation_template);
    }

    /**
     * Test that seeder can be run multiple times without duplicates.
     * ST-005
     */
    public function test_seeder_can_be_run_multiple_times(): void
    {
        // Run seeder twice
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder'])
            ->assertSuccessful();

        $countAfterFirst = DB::table('issue_types')->count();

        // Truncate to simulate fresh run
        DB::table('issue_types')->truncate();

        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder'])
            ->assertSuccessful();

        $countAfterSecond = DB::table('issue_types')->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    /**
     * Test that seeder creates specific expected issue types.
     * ST-006
     */
    public function test_seeder_creates_expected_issue_types(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        // Check for specific important issue types
        $this->assertDatabaseHas('issue_types', [
            'category' => 'meta',
            'name' => 'Missing Title Tag',
        ]);

        $this->assertDatabaseHas('issue_types', [
            'category' => 'content',
            'name' => 'Missing H1 Tag',
        ]);

        $this->assertDatabaseHas('issue_types', [
            'category' => 'performance',
            'name' => 'Slow Page Load Time',
        ]);
    }

    /**
     * Test that seeder creates issue types with valid categories.
     * ST-007
     */
    public function test_seeder_creates_issue_types_with_valid_categories(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $validCategories = ['meta', 'content', 'backlink', 'performance'];

        $invalidCategories = DB::table('issue_types')
            ->whereNotIn('category', $validCategories)
            ->count();

        $this->assertEquals(0, $invalidCategories, 'Found issue types with invalid categories');
    }

    /**
     * Test that seeder creates issue types with valid severities.
     * ST-008
     */
    public function test_seeder_creates_issue_types_with_valid_severities(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $validSeverities = ['critical', 'warning', 'notice'];

        $invalidSeverities = DB::table('issue_types')
            ->whereNotIn('severity_default', $validSeverities)
            ->count();

        $this->assertEquals(0, $invalidSeverities, 'Found issue types with invalid severities');
    }

    /**
     * Test that seeder creates timestamps.
     * ST-009
     */
    public function test_seeder_creates_timestamps(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $issueType = DB::table('issue_types')->first();

        $this->assertNotNull($issueType->created_at);
        $this->assertNotNull($issueType->updated_at);
    }

    /**
     * Test minimum count of issue types.
     * ST-010
     */
    public function test_minimum_count_of_issue_types(): void
    {
        $this->artisan('db:seed', ['--class' => 'IssueTypeSeeder']);

        $count = DB::table('issue_types')->count();

        // Should have at least 15 issue types for a useful audit system
        $this->assertGreaterThanOrEqual(15, $count);
    }
}
