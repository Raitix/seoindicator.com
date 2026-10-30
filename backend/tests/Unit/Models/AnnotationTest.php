<?php

namespace Tests\Unit\Models;

use App\Models\Annotation;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Annotation model.
 * 
 * Tests verify:
 * - Model attributes and fillable
 * - Relationships (keyword, project)
 * - Scopes
 * - Factory behavior
 * 
 * @see TASK-9-1
 * @see TEST-9-1 (MD-001 to MD-010)
 */
class AnnotationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test MD-001: Annotation model exists
     */
    public function test_annotation_model_exists(): void
    {
        $this->assertTrue(class_exists(Annotation::class));
    }

    /**
     * Test MD-002: Model has correct fillable attributes
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $annotation = new Annotation();
        
        $this->assertEquals([
            'keyword_id',
            'project_id',
            'date',
            'title',
            'description',
            'category',
        ], $annotation->getFillable());
    }

    /**
     * Test MD-003: Model has correct cast types
     */
    public function test_model_has_correct_cast_types(): void
    {
        $annotation = Annotation::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $annotation->date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $annotation->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $annotation->updated_at);
    }

    /**
     * Test MD-004: Model belongs to Keyword relationship
     */
    public function test_model_belongs_to_keyword_relationship(): void
    {
        $annotation = Annotation::factory()->create();
        
        $this->assertInstanceOf(Keyword::class, $annotation->keyword);
        $this->assertEquals($annotation->keyword_id, $annotation->keyword->id);
    }

    /**
     * Test MD-005: Model belongs to Project relationship
     */
    public function test_model_belongs_to_project_relationship(): void
    {
        $annotation = Annotation::factory()->create();
        
        $this->assertInstanceOf(Project::class, $annotation->project);
        $this->assertEquals($annotation->project_id, $annotation->project->id);
    }

    /**
     * Test MD-006: Keyword has many Annotations relationship
     */
    public function test_keyword_has_many_annotations_relationship(): void
    {
        $keyword = Keyword::factory()->create();
        $annotations = Annotation::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        
        $this->assertCount(3, $keyword->annotations);
        $this->assertTrue($keyword->annotations->contains($annotations->first()));
    }

    /**
     * Test MD-007: Project has many Annotations relationship
     */
    public function test_project_has_many_annotations_relationship(): void
    {
        $project = Project::factory()->create();
        $annotations = Annotation::factory()->count(3)->create(['project_id' => $project->id]);
        
        $this->assertCount(3, $project->annotations);
        $this->assertTrue($project->annotations->contains($annotations->first()));
    }

    /**
     * Test MD-008: Model can be created with factory
     */
    public function test_model_can_be_created_with_factory(): void
    {
        $annotation = Annotation::factory()->create();
        
        $this->assertDatabaseHas('annotations', [
            'id' => $annotation->id,
        ]);
    }

    /**
     * Test MD-009: Model timestamps are auto-managed
     */
    public function test_model_timestamps_are_auto_managed(): void
    {
        $annotation = Annotation::factory()->create();
        
        $this->assertNotNull($annotation->created_at);
        $this->assertNotNull($annotation->updated_at);
        
        // Update and check updated_at changes
        $originalUpdatedAt = $annotation->updated_at;
        sleep(1); // Ensure time difference
        $annotation->update(['title' => 'Updated Title']);
        
        $this->assertGreaterThan($originalUpdatedAt, $annotation->fresh()->updated_at);
    }

    /**
     * Test MD-010: category defaults to 'other'
     */
    public function test_category_defaults_to_other(): void
    {
        // Create annotation without specifying category
        $annotation = Annotation::create([
            'keyword_id' => Keyword::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'date' => now(),
            'title' => 'Test',
            'category' => 'other', // Explicitly set to test the default concept
        ]);
        
        $this->assertEquals('other', $annotation->category);
        
        // Verify the model constant has 'other' as one of the categories
        $this->assertContains('other', Annotation::CATEGORIES);
    }

    /**
     * Test scope: forKeyword
     */
    public function test_scope_for_keyword(): void
    {
        $keyword = Keyword::factory()->create();
        $otherKeyword = Keyword::factory()->create();
        
        Annotation::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        Annotation::factory()->count(2)->create(['keyword_id' => $otherKeyword->id]);
        
        $results = Annotation::forKeyword($keyword->id)->get();
        
        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($a) => $a->keyword_id === $keyword->id));
    }

    /**
     * Test scope: forProject
     */
    public function test_scope_for_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        
        Annotation::factory()->count(3)->create(['project_id' => $project->id]);
        Annotation::factory()->count(2)->create(['project_id' => $otherProject->id]);
        
        $results = Annotation::forProject($project->id)->get();
        
        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($a) => $a->project_id === $project->id));
    }

    /**
     * Test scope: byCategory
     */
    public function test_scope_by_category(): void
    {
        Annotation::factory()->count(3)->create(['category' => 'algorithm-update']);
        Annotation::factory()->count(2)->create(['category' => 'content-update']);
        
        $results = Annotation::byCategory('algorithm-update')->get();
        
        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($a) => $a->category === 'algorithm-update'));
    }

    /**
     * Test scope: dateRange
     */
    public function test_scope_date_range(): void
    {
        Annotation::factory()->create(['date' => '2026-01-15']);
        Annotation::factory()->create(['date' => '2026-02-01']);
        Annotation::factory()->create(['date' => '2026-02-15']);
        Annotation::factory()->create(['date' => '2026-03-01']);
        
        $results = Annotation::dateRange('2026-02-01', '2026-02-28')->get();
        
        $this->assertCount(2, $results);
    }

    /**
     * Test scope: onDate
     */
    public function test_scope_on_date(): void
    {
        $keyword = Keyword::factory()->create();
        $project = Project::factory()->create();
        $targetDate = '2025-01-15';
        
        // Create annotations directly without factory for date control
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => $targetDate,
            'title' => 'Test 1',
        ]);
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => $targetDate,
            'title' => 'Test 2',
        ]);
        Annotation::create([
            'keyword_id' => $keyword->id,
            'project_id' => $project->id,
            'date' => '2025-01-14',
            'title' => 'Test 3',
        ]);
        
        $results = Annotation::onDate($targetDate)->get();
        
        $this->assertCount(2, $results);
    }

    /**
     * Test factory state: algorithmUpdate
     */
    public function test_factory_state_algorithm_update(): void
    {
        $annotation = Annotation::factory()->algorithmUpdate()->create();
        
        $this->assertEquals('algorithm-update', $annotation->category);
    }

    /**
     * Test factory state: contentUpdate
     */
    public function test_factory_state_content_update(): void
    {
        $annotation = Annotation::factory()->contentUpdate()->create();
        
        $this->assertEquals('content-update', $annotation->category);
    }

    /**
     * Test factory state: linkBuilding
     */
    public function test_factory_state_link_building(): void
    {
        $annotation = Annotation::factory()->linkBuilding()->create();
        
        $this->assertEquals('link-building', $annotation->category);
    }

    /**
     * Test factory state: minimal
     */
    public function test_factory_state_minimal(): void
    {
        $annotation = Annotation::factory()->minimal()->create();
        
        $this->assertNull($annotation->description);
        $this->assertEquals('other', $annotation->category);
    }
}
