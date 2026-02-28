<?php

namespace Tests\Unit\Models;

use App\Models\IssueType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for IssueType model.
 *
 * Tests model creation, relationships, and static methods.
 *
 * @see TASK-14-3
 */
class IssueTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_issue_type(): void
    {
        $issueType = IssueType::create([
            'category' => 'meta',
            'name' => 'Missing Title Tag',
            'description' => 'The page is missing a title tag which is important for SEO.',
            'severity_default' => 'critical',
            'recommendation_template' => 'Add a descriptive title tag to the page.',
        ]);

        $this->assertDatabaseHas('issue_types', [
            'id' => $issueType->id,
            'category' => 'meta',
            'name' => 'Missing Title Tag',
            'severity_default' => 'critical',
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $issueType = new IssueType();
        $fillable = $issueType->getFillable();

        $this->assertContains('category', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('severity_default', $fillable);
        $this->assertContains('recommendation_template', $fillable);
    }

    /** @test */
    public function it_returns_categories_as_array(): void
    {
        $categories = IssueType::categories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('meta', $categories);
        $this->assertArrayHasKey('content', $categories);
        $this->assertArrayHasKey('backlink', $categories);
        $this->assertArrayHasKey('performance', $categories);
    }

    /** @test */
    public function it_has_category_labels(): void
    {
        $categories = IssueType::categories();

        $this->assertEquals('SEO Meta', $categories['meta']);
        $this->assertEquals('Content Quality', $categories['content']);
        $this->assertEquals('Backlinks', $categories['backlink']);
        $this->assertEquals('Performance', $categories['performance']);
    }

    /** @test */
    public function it_can_be_created_with_all_severity_levels(): void
    {
        foreach (['critical', 'warning', 'notice'] as $severity) {
            $issueType = IssueType::factory()->create(['severity_default' => $severity]);
            $this->assertEquals($severity, $issueType->fresh()->severity_default);
        }
    }

    /** @test */
    public function it_can_be_created_with_all_categories(): void
    {
        foreach (['meta', 'content', 'backlink', 'performance'] as $category) {
            $issueType = IssueType::factory()->create(['category' => $category]);
            $this->assertEquals($category, $issueType->fresh()->category);
        }
    }

    /** @test */
    public function it_has_audit_issues_relationship(): void
    {
        $issueType = IssueType::factory()->create();

        // Just verify the method exists and returns a relationship
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $issueType->auditIssues()
        );
    }

    /** @test */
    public function it_uses_factory(): void
    {
        $issueType = IssueType::factory()->create();

        $this->assertNotNull($issueType->id);
        $this->assertNotNull($issueType->category);
        $this->assertNotNull($issueType->name);
        $this->assertNotNull($issueType->description);
        $this->assertNotNull($issueType->severity_default);
        $this->assertNotNull($issueType->recommendation_template);
    }

    /** @test */
    public function it_updates_timestamps(): void
    {
        $issueType = IssueType::factory()->create();
        $originalUpdatedAt = $issueType->updated_at;

        sleep(1); // Ensure time difference

        $issueType->update(['name' => 'Updated Name']);

        $this->assertNotEquals($originalUpdatedAt, $issueType->fresh()->updated_at);
    }

    /** @test */
    public function it_can_be_found_by_id(): void
    {
        $issueType = IssueType::factory()->create();

        $found = IssueType::find($issueType->id);

        $this->assertNotNull($found);
        $this->assertEquals($issueType->id, $found->id);
    }

    /** @test */
    public function it_can_be_deleted(): void
    {
        $issueType = IssueType::factory()->create();
        $id = $issueType->id;

        $issueType->delete();

        $this->assertDatabaseMissing('issue_types', ['id' => $id]);
    }

    /** @test */
    public function it_can_be_updated(): void
    {
        $issueType = IssueType::factory()->create();

        $issueType->update([
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('Updated Name', $issueType->fresh()->name);
        $this->assertEquals('Updated description', $issueType->fresh()->description);
    }

    /** @test */
    public function it_can_filter_by_category(): void
    {
        IssueType::factory()->count(3)->create(['category' => 'meta']);
        IssueType::factory()->count(2)->create(['category' => 'content']);

        $metaTypes = IssueType::where('category', 'meta')->get();
        $contentTypes = IssueType::where('category', 'content')->get();

        $this->assertCount(3, $metaTypes);
        $this->assertCount(2, $contentTypes);
    }

    /** @test */
    public function it_can_filter_by_severity(): void
    {
        IssueType::factory()->count(2)->create(['severity_default' => 'critical']);
        IssueType::factory()->count(3)->create(['severity_default' => 'warning']);

        $criticalTypes = IssueType::where('severity_default', 'critical')->get();
        $warningTypes = IssueType::where('severity_default', 'warning')->get();

        $this->assertCount(2, $criticalTypes);
        $this->assertCount(3, $warningTypes);
    }
}
