<?php

namespace Tests\Unit\Repositories;

use App\Models\AnchorText;
use App\Models\Project;
use App\Repositories\AnchorTextRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Tests for AnchorTextRepository.
 * 
 * @see TASK-23-2
 */
class AnchorTextRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected AnchorTextRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(AnchorTextRepository::class);
    }

    // =============================================
    // CRUD Tests
    // =============================================

    /** @test */
    public function it_can_create_an_anchor_text(): void
    {
        $project = Project::factory()->create();
        
        $anchor = $this->repository->create([
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

        $found = $this->repository->findById($anchor->id);

        $this->assertInstanceOf(AnchorText::class, $found);
        $this->assertEquals($anchor->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_anchor_text_not_found(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_update_an_anchor_text(): void
    {
        $anchor = AnchorText::factory()->create(['backlinks_count' => 10]);

        $result = $this->repository->update($anchor->id, ['backlinks_count' => 25]);

        $this->assertTrue($result);
        $this->assertEquals(25, $anchor->fresh()->backlinks_count);
    }

    /** @test */
    public function it_can_delete_an_anchor_text(): void
    {
        $anchor = AnchorText::factory()->create();

        $result = $this->repository->delete($anchor->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('anchor_texts', ['id' => $anchor->id]);
    }

    /** @test */
    public function it_can_get_all_anchor_texts_for_a_project(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(5)->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(); // Other project

        $anchors = $this->repository->getByProject($project->id);

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

        $result = $this->repository->updateClassification($anchor->id, true, true);

        $this->assertTrue($result);
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

        $count = $this->repository->bulkUpdateClassification($anchors->pluck('id')->toArray(), true, false);

        $this->assertEquals(3, $count);
        $anchors->each(function ($anchor) {
            $this->assertTrue($anchor->fresh()->is_branded);
        });
    }

    /** @test */
    public function it_can_classify_as_branded(): void
    {
        $anchor = AnchorText::factory()->create(['anchor_text' => 'Acme Corp', 'is_branded' => false]);

        $result = $this->repository->classifyAsBranded($anchor->id);

        $this->assertTrue($result);
        $this->assertTrue($anchor->fresh()->is_branded);
    }

    /** @test */
    public function it_can_classify_as_exact_match(): void
    {
        $anchor = AnchorText::factory()->create(['anchor_text' => 'best SEO tools', 'is_exact_match' => false]);

        $result = $this->repository->classifyAsExactMatch($anchor->id);

        $this->assertTrue($result);
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

        $brandedAnchors = $this->repository->getBranded($project->id);

        $this->assertCount(3, $brandedAnchors);
    }

    /** @test */
    public function it_can_get_exact_match_anchors(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(2)->exactMatch()->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(['project_id' => $project->id, 'is_exact_match' => false]);

        $exactMatchAnchors = $this->repository->getExactMatch($project->id);

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

        $topAnchors = $this->repository->getTopByBacklinks($project->id, 3);

        $this->assertCount(3, $topAnchors);
        $this->assertEquals([50, 30, 10], $topAnchors->pluck('backlinks_count')->toArray());
    }

    /** @test */
    public function it_can_get_total_count_for_project(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(7)->create(['project_id' => $project->id]);
        AnchorText::factory()->count(3)->create(); // Other project

        $count = $this->repository->getTotalCount($project->id);

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

        $totalBacklinks = $this->repository->getTotalBacklinks($project->id);

        $this->assertEquals(350, $totalBacklinks);
    }

    /** @test */
    public function it_can_count_unique_anchor_texts(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 1']);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 2']);
        AnchorText::factory()->create(['project_id' => $project->id, 'anchor_text' => 'keyword 3']);

        $count = $this->repository->countUnique($project->id);

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

        $diversity = $this->repository->calculateDiversity($project->id);

        $this->assertEquals(0.02, $diversity);
    }

    /** @test */
    public function it_can_get_branded_vs_non_branded_ratio(): void
    {
        $project = Project::factory()->create();
        
        AnchorText::factory()->count(3)->branded()->create(['project_id' => $project->id, 'backlinks_count' => 100]);
        AnchorText::factory()->count(7)->create(['project_id' => $project->id, 'is_branded' => false, 'backlinks_count' => 50]);

        $ratio = $this->repository->getBrandedRatio($project->id);

        $this->assertEquals(300, $ratio['branded_count']);
        $this->assertEquals(350, $ratio['non_branded_count']);
        $this->assertEqualsWithDelta(0.46, $ratio['branded'], 0.01);
    }

    // =============================================
    // Upsert Tests
    // =============================================

    /** @test */
    public function it_can_upsert_anchor_text_by_project_and_text(): void
    {
        $project = Project::factory()->create();

        // First upsert - creates
        $anchor1 = $this->repository->upsert([
            'project_id' => $project->id,
            'anchor_text' => 'keyword',
            'backlinks_count' => 10,
        ]);

        $this->assertInstanceOf(AnchorText::class, $anchor1);

        // Second upsert - updates
        $anchor2 = $this->repository->upsert([
            'project_id' => $project->id,
            'anchor_text' => 'keyword',
            'backlinks_count' => 20,
        ]);

        $this->assertEquals($anchor1->id, $anchor2->id);
        $this->assertEquals(20, $anchor2->backlinks_count);
    }
}
