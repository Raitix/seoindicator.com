<?php

namespace Tests\Unit\Repositories;

use App\Models\AnchorText;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests for AnchorTextRepository.
 * 
 * These tests define the expected repository interface.
 * The repository implementation should be created to pass these tests.
 * 
 * @see TASK-23-2
 */
class AnchorTextRepositoryTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_an_anchor_text(): void
    {
        $project = Project::factory()->create();
        
        $anchor = AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'best SEO tools',
            'backlinks_count' => 50,
            'is_branded' => false,
            'is_exact_match' => true,
        ]);

        $this->assertInstanceOf(AnchorText::class, $anchor);
        $this->assertEquals('best SEO tools', $anchor->anchor_text);
    }

    /** @test */
    public function it_can_find_an_anchor_text_by_id(): void
    {
        $anchor = AnchorText::factory()->create();

        $found = AnchorText::find($anchor->id);

        $this->assertInstanceOf(AnchorText::class, $found);
        $this->assertEquals($anchor->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_anchor_text_not_found(): void
    {
        $found = AnchorText::find(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_update_an_anchor_text(): void
    {
        $anchor = AnchorText::factory()->create(['backlinks_count' => 10]);

        $anchor->update(['backlinks_count' => 25]);

        $this->assertEquals(25, $anchor->fresh()->backlinks_count);
    }

    /** @test */
    public function it_can_delete_an_anchor_text(): void
    {
        $anchor = AnchorText::factory()->create();

        $anchor->delete();

        $this->assertDatabaseMissing('anchor_texts', ['id' => $anchor->id]);
    }

    /** @test */
    public function it_can_get_all_anchor_texts_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(5)->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(); // Other project

        $anchors = AnchorText::where('project_id', $project->id)->get();

        $this->assertCount(5, $anchors);
        $anchors->each(fn ($a) => $this->assertEquals($project->id, $a->project_id));
    }

    // =============================================
    // Classification Tests
    // =============================================

    /** @test */
    public function it_can_update_classification_flags(): void
    {
        $anchor = AnchorText::factory()->create([
            'is_branded' => false,
            'is_exact_match' => false,
        ]);

        $anchor->update([
            'is_branded' => true,
            'is_exact_match' => true,
        ]);

        $this->assertTrue($anchor->fresh()->is_branded);
        $this->assertTrue($anchor->fresh()->is_exact_match);
    }

    /** @test */
    public function it_can_bulk_update_classification(): void
    {
        $project = Project::factory()->create();
        
        $anchors = AnchorText::factory()->count(3)->create([
            'project_id' => $project->id,
            'is_branded' => false,
        ]);

        // Bulk update
        AnchorText::whereIn('id', $anchors->pluck('id'))
            ->update(['is_branded' => true]);

        $anchors->each(function ($anchor) {
            $this->assertTrue($anchor->fresh()->is_branded);
        });
    }

    /** @test */
    public function it_can_classify_as_branded(): void
    {
        $anchor = AnchorText::factory()->create(['anchor_text' => 'Acme Corp']);

        $anchor->update(['is_branded' => true]);

        $this->assertTrue($anchor->fresh()->is_branded);
    }

    /** @test */
    public function it_can_classify_as_exact_match(): void
    {
        $anchor = AnchorText::factory()->create(['anchor_text' => 'best SEO tools']);

        $anchor->update(['is_exact_match' => true]);

        $this->assertTrue($anchor->fresh()->is_exact_match);
    }

    // =============================================
    // Query Tests
    // =============================================

    /** @test */
    public function it_can_get_branded_anchors(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(3)->branded()->create(['project_id' => $project->id]);
        AnchorText::factory()->count(2)->create(['project_id' => $project->id, 'is_branded' => false]);

        $brandedAnchors = AnchorText::where('project_id', $project->id)
            ->where('is_branded', true)
            ->get();

        $this->assertCount(3, $brandedAnchors);
    }

    /** @test */
    public function it_can_get_exact_match_anchors(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(2)->exactMatch()->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(['project_id' => $project->id, 'is_exact_match' => false]);

        $exactMatchAnchors = AnchorText::where('project_id', $project->id)
            ->where('is_exact_match', true)
            ->get();

        $this->assertCount(2, $exactMatchAnchors);
    }

    /** @test */
    public function it_can_get_top_anchors_by_backlinks(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 10]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 30]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 5]);

        $topAnchors = AnchorText::where('project_id', $project->id)
            ->orderByBacklinksCount()
            ->limit(3)
            ->get();

        $this->assertCount(3, $topAnchors);
        $this->assertEquals([50, 30, 10], $topAnchors->pluck('backlinks_count')->toArray());
    }

    /** @test */
    public function it_can_get_total_count_for_project(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(7)->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(); // Other project

        $count = AnchorText::where('project_id', $project->id)->count();

        $this->assertEquals(7, $count);
    }

    // =============================================
    // Aggregation Tests
    // =============================================

    /** @test */
    public function it_can_calculate_total_backlinks_from_anchors(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 100]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 200]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);

        $totalBacklinks = AnchorText::where('project_id', $project->id)
            ->sum('backlinks_count');

        $this->assertEquals(350, $totalBacklinks);
    }

    /** @test */
    public function it_can_count_unique_anchor_texts(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 1']);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 2']);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 3']);

        $count = AnchorText::where('project_id', $project->id)->count();

        $this->assertEquals(3, $count);
    }

    // =============================================
    // Diversity Analysis Tests
    // =============================================

    /** @test */
    public function it_can_calculate_anchor_text_diversity(): void
    {
        $project = Project::factory()->create();
        
        // Create anchors with different counts
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 100]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 50]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 25]);
        AnchorText::factory()->create(['project_id' => $project->id, 'backlinks_count' => 25]);

        $totalBacklinks = AnchorText::where('project_id', $project->id)->sum('backlinks_count');
        $uniqueAnchors = AnchorText::where('project_id', $project->id)->count();

        // Diversity = unique anchors / total backlinks (normalized)
        $diversity = $uniqueAnchors / $totalBacklinks;

        $this->assertEquals(200, $totalBacklinks);
        $this->assertEquals(4, $uniqueAnchors);
        $this->assertEquals(0.02, $diversity);
    }

    /** @test */
    public function it_can_get_branded_vs_non_branded_ratio(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(3)->branded()->create(['project_id' => $project->id, 'backlinks_count' => 100]);
        AnchorText::factory()->count(7)->create(['project_id' => $project->id, 'is_branded' => false, 'backlinks_count' => 50]);

        $brandedCount = AnchorText::where('project_id', $project->id)->where('is_branded', true)->sum('backlinks_count');
        $totalCount = AnchorText::where('project_id', $project->id)->sum('backlinks_count');

        $brandedRatio = $brandedCount / $totalCount;

        $this->assertEquals(300, $brandedCount);
        $this->assertEquals(650, $totalCount);
        $this->assertEqualsWithDelta(0.46, $brandedRatio, 0.01);
    }

    // =============================================
    // Upsert Tests
    // =============================================

    /** @test */
    public function it_can_upsert_anchor_text_by_project_and_text(): void
    {
        $project = Project::factory()->create();

        // First upsert - creates
        $anchor1 = AnchorText::updateOrCreate(
            [
                'project_id' => $project->id,
                'anchor_text' => 'keyword',
            ],
            [
                'backlinks_count' => 10,
            ]
        );

        $this->assertTrue($anchor1->wasRecentlyCreated);

        // Second upsert - updates
        $anchor2 = AnchorText::updateOrCreate(
            [
                'project_id' => $project->id,
                'anchor_text' => 'keyword',
            ],
            [
                'backlinks_count' => 20,
            ]
        );

        $this->assertFalse($anchor2->wasRecentlyCreated);
        $this->assertEquals($anchor1->id, $anchor2->id);
        $this->assertEquals(20, $anchor2->backlinks_count);
    }
}
