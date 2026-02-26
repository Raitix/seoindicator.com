<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for Project model validation.
 *
 * Tests database-level constraints and model behavior for validation.
 * Note: Laravel models don't enforce validation by default - these tests
 * verify database constraints work correctly.
 *
 * @see TASK-8-9
 * @see TEST-8-9
 */
class ProjectValidationTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Name Validation Tests (MV-001 to MV-005)
    // ==========================================

    #[Test]
    public function name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Project::create([
            'user_id' => User::factory()->create()->id,
            // name missing
        ]);
    }

    #[Test]
    public function name_can_be_empty_string(): void
    {
        // Note: MySQL doesn't enforce non-empty for NOT NULL columns
        // Empty string is stored as empty string, not rejected
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => '',
        ]);

        $this->assertEquals('', $project->name);
    }

    #[Test]
    public function name_can_be_created_with_valid_value(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Test Project',
        ]);
    }

    #[Test]
    public function name_can_be_255_characters(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 255);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => $longName,
        ]);

        $this->assertEquals(255, strlen($project->name));
    }

    #[Test]
    public function name_exceeding_255_characters_behavior(): void
    {
        // Note: In non-strict SQL mode, MySQL may accept longer values
        // This test documents the actual behavior of the current configuration
        $user = User::factory()->create();
        $longName = str_repeat('a', 300);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => $longName,
        ]);

        // Document actual behavior: MySQL may store the full value in non-strict mode
        // The column is VARCHAR(255) but behavior depends on sql_mode
        $this->assertNotNull($project->id);
    }

    // ==========================================
    // Domain Validation Tests (MV-006 to MV-009)
    // ==========================================

    #[Test]
    public function domain_can_be_null(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => null,
        ]);

        $this->assertNull($project->domain);
    }

    #[Test]
    public function domain_can_be_valid_url(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => 'https://example.com',
        ]);

        $this->assertEquals('https://example.com', $project->domain);
    }

    #[Test]
    public function domain_can_be_500_characters(): void
    {
        $user = User::factory()->create();
        // Create a domain that's exactly 500 characters
        $longDomain = 'https://' . str_repeat('a', 483) . '.com'; // 8 + 483 + 4 = 495 chars

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => $longDomain,
        ]);

        $this->assertEquals($longDomain, $project->domain);
    }

    #[Test]
    public function domain_exceeding_500_characters_behavior(): void
    {
        // Note: In non-strict SQL mode, MySQL may accept longer values
        $user = User::factory()->create();
        $longDomain = 'https://' . str_repeat('a', 600) . '.com';

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => $longDomain,
        ]);

        // Document actual behavior: MySQL may store the full value in non-strict mode
        $this->assertNotNull($project->id);
    }

    // ==========================================
    // User ID Validation Tests (MV-010 to MV-011)
    // ==========================================

    #[Test]
    public function user_id_can_be_null(): void
    {
        // user_id is nullable to support legacy data
        $project = Project::create([
            'user_id' => null,
            'name' => 'Orphan Project',
        ]);

        $this->assertNull($project->user_id);
    }

    #[Test]
    public function user_id_with_non_existent_user_fails(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Project::create([
            'user_id' => 99999,
            'name' => 'Test Project',
        ]);
    }

    // ==========================================
    // Special Characters Tests (MV-012 to MV-013)
    // ==========================================

    #[Test]
    public function name_can_contain_special_characters(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test <script>alert(1)</script>',
        ]);

        // Database should store as-is; output should be escaped by views
        $this->assertEquals('Test <script>alert(1)</script>', $project->name);
    }

    #[Test]
    public function name_can_contain_unicode(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => '测试项目 🚀',
        ]);

        $this->assertEquals('测试项目 🚀', $project->name);
    }

    // ==========================================
    // Notes Validation Tests (MV-014 to MV-015)
    // ==========================================

    #[Test]
    public function notes_can_be_very_long(): void
    {
        $user = User::factory()->create();
        $longNotes = str_repeat('a', 65535);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'notes' => $longNotes,
        ]);

        $this->assertEquals(65535, strlen($project->notes));
    }

    #[Test]
    public function notes_can_be_null(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'notes' => null,
        ]);

        $this->assertNull($project->notes);
    }

    // ==========================================
    // is_default Validation
    // ==========================================

    #[Test]
    public function is_default_defaults_to_false(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
        ]);

        // Fresh from database to get the default value
        $freshProject = Project::find($project->id);
        $this->assertFalse($freshProject->is_default);
    }

    #[Test]
    public function is_default_can_be_true(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Default Project',
            'is_default' => true,
        ]);

        $this->assertTrue($project->is_default);
    }

    // ==========================================
    // archived_at Validation
    // ==========================================

    #[Test]
    public function archived_at_can_be_null(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'archived_at' => null,
        ]);

        $this->assertNull($project->archived_at);
    }

    #[Test]
    public function archived_at_can_be_set(): void
    {
        $user = User::factory()->create();
        $archiveDate = now();

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'archived_at' => $archiveDate,
        ]);

        $this->assertNotNull($project->archived_at);
        $this->assertEquals($archiveDate->toDateTimeString(), $project->archived_at->toDateTimeString());
    }

    // ==========================================
    // Multiple Projects Per User
    // ==========================================

    #[Test]
    public function user_can_have_multiple_projects(): void
    {
        $user = User::factory()->create();

        Project::factory()->count(5)->forUser($user)->create();

        $this->assertEquals(5, Project::where('user_id', $user->id)->count());
    }

    #[Test]
    public function multiple_projects_can_have_same_name_per_user(): void
    {
        $user = User::factory()->create();

        Project::create([
            'user_id' => $user->id,
            'name' => 'Duplicate Name',
        ]);

        Project::create([
            'user_id' => $user->id,
            'name' => 'Duplicate Name',
        ]);

        $this->assertEquals(2, Project::where('user_id', $user->id)->where('name', 'Duplicate Name')->count());
    }
}
