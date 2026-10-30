<?php

namespace Tests\Unit\Models;

use App\Models\AnchorText;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnchorTextTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // Model Attribute Tests
    // =============================================

    /** @test */
    public function it_can_create_an_anchor_text_with_all_attributes(): void
    {
        $project = Project::factory()->create();
        
        $anchor = AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'best SEO tools',
            'backlinks_count' => 150,
            'is_branded' => false,
            'is_exact_match' => true,
        ]);

        $this->assertDatabaseHas('anchor_texts', [
            'id' => $anchor->id,
            'anchor_text' => 'best SEO tools',
            'backlinks_count' => 150,
            'is_branded' => false,
            'is_exact_match' => true,
        ]);
    }

    /** @test */
    public function it_casts_backlinks_count_to_integer(): void
    {
        $anchor = AnchorText::factory()->create(['backlinks_count' => 200]);

        $this->assertIsInt($anchor->backlinks_count);
        $this->assertEquals(200, $anchor->backlinks_count);
    }

    /** @test */
    public function it_casts_is_branded_to_boolean(): void
    {
        $anchor = AnchorText::factory()->create(['is_branded' => true]);

        $this->assertIsBool($anchor->is_branded);
        $this->assertTrue($anchor->is_branded);
    }

    /** @test */
    public function it_casts_is_exact_match_to_boolean(): void
    {
        $anchor = AnchorText::factory()->create(['is_exact_match' => true]);

        $this->assertIsBool($anchor->is_exact_match);
        $this->assertTrue($anchor->is_exact_match);
    }

    /** @test */
    public function it_defaults_backlinks_count_to_zero(): void
    {
        // Create without backlinks_count field - DB default should apply
        $project = Project::factory()->create();
        $anchor = AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'test keyword',
        ]);

        $freshAnchor = AnchorText::find($anchor->id);
        $this->assertEquals(0, $freshAnchor->backlinks_count);
    }

    /** @test */
    public function it_defaults_is_branded_to_false(): void
    {
        // Create without is_branded field - DB default should apply
        $project = Project::factory()->create();
        $anchor = AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'test keyword',
        ]);

        $freshAnchor = AnchorText::find($anchor->id);
        $this->assertFalse($freshAnchor->is_branded);
    }

    /** @test */
    public function it_defaults_is_exact_match_to_false(): void
    {
        // Create without is_exact_match field - DB default should apply
        $project = Project::factory()->create();
        $anchor = AnchorText::create([
            'project_id' => $project->id,
            'anchor_text' => 'test keyword',
        ]);

        $freshAnchor = AnchorText::find($anchor->id);
        $this->assertFalse($freshAnchor->is_exact_match);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $anchor = new AnchorText();
        
        $fillable = [
            'project_id',
            'anchor_text',
            'backlinks_count',
            'is_branded',
            'is_exact_match',
        ];

        $this->assertEquals($fillable, $anchor->getFillable());
    }

    /** @test */
    public function it_uses_correct_table_name(): void
    {
        $anchor = new AnchorText();

        $this->assertEquals('anchor_texts', $anchor->getTable());
    }

    // =============================================
    // Relationship Tests
    // =============================================

    /** @test */
    public function it_belongs_to_a_project(): void
    {
        $anchor = AnchorText::factory()->create();

        $this->assertInstanceOf(Project::class, $anchor->project);
        $this->assertEquals($anchor->project_id, $anchor->project->id);
    }

    // =============================================
    // Scope Tests
    // =============================================

    /** @test */
    public function scope_order_by_backlinks_count_orders_descending(): void
    {
        AnchorText::factory()->create(['backlinks_count' => 10]);
        AnchorText::factory()->create(['backlinks_count' => 50]);
        AnchorText::factory()->create(['backlinks_count' => 30]);

        $anchors = AnchorText::orderByBacklinksCount()->get();

        $this->assertEquals(50, $anchors[0]->backlinks_count);
        $this->assertEquals(30, $anchors[1]->backlinks_count);
        $this->assertEquals(10, $anchors[2]->backlinks_count);
    }

    /** @test */
    public function scope_branded_filters_branded_anchors(): void
    {
        AnchorText::factory()->count(3)->branded()->create();
        AnchorText::factory()->count(2)->create(['is_branded' => false]);

        $brandedAnchors = AnchorText::branded()->get();

        $this->assertCount(3, $brandedAnchors);
        $brandedAnchors->each(fn ($anchor) => $this->assertTrue($anchor->is_branded));
    }

    /** @test */
    public function scope_exact_match_filters_exact_match_anchors(): void
    {
        AnchorText::factory()->count(2)->exactMatch()->create();
        AnchorText::factory()->count(3)->create(['is_exact_match' => false]);

        $exactMatchAnchors = AnchorText::exactMatch()->get();

        $this->assertCount(2, $exactMatchAnchors);
        $exactMatchAnchors->each(fn ($anchor) => $this->assertTrue($anchor->is_exact_match));
    }

    /** @test */
    public function scopes_can_be_chained(): void
    {
        AnchorText::factory()->create(['is_branded' => true, 'is_exact_match' => true, 'backlinks_count' => 100]);
        AnchorText::factory()->create(['is_branded' => true, 'is_exact_match' => false, 'backlinks_count' => 50]);
        AnchorText::factory()->create(['is_branded' => false, 'is_exact_match' => true, 'backlinks_count' => 75]);

        $results = AnchorText::branded()->exactMatch()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(100, $results[0]->backlinks_count);
    }

    // =============================================
    // Edge Cases
    // =============================================

    /** @test */
    public function it_can_store_max_length_anchor_text(): void
    {
        $longAnchor = str_repeat('keyword ', 62); // ~500 chars
        $anchor = AnchorText::factory()->create(['anchor_text' => $longAnchor]);

        $this->assertEquals($longAnchor, $anchor->anchor_text);
    }

    /** @test */
    public function both_flags_can_be_true(): void
    {
        $anchor = AnchorText::factory()->create([
            'is_branded' => true,
            'is_exact_match' => true,
        ]);

        $this->assertTrue($anchor->is_branded);
        $this->assertTrue($anchor->is_exact_match);
    }

    /** @test */
    public function both_flags_can_be_false(): void
    {
        $anchor = AnchorText::factory()->create([
            'is_branded' => false,
            'is_exact_match' => false,
        ]);

        $this->assertFalse($anchor->is_branded);
        $this->assertFalse($anchor->is_exact_match);
    }

    /** @test */
    public function it_handles_unicode_in_anchor_text(): void
    {
        $unicodeAnchor = '点击这里查看更多';
        
        $anchor = AnchorText::factory()->create(['anchor_text' => $unicodeAnchor]);

        $this->assertEquals($unicodeAnchor, $anchor->anchor_text);
    }

    /** @test */
    public function it_handles_html_in_anchor_text(): void
    {
        $htmlAnchor = '<a href="test">link</a>';
        
        $anchor = AnchorText::factory()->create(['anchor_text' => $htmlAnchor]);

        // Stored as-is (output escaping is responsibility of view layer)
        $this->assertEquals($htmlAnchor, $anchor->anchor_text);
    }

    // =============================================
    // Security Tests
    // =============================================

    /** @test */
    public function it_safely_stores_sql_injection_in_anchor_text(): void
    {
        $maliciousText = "'; DELETE FROM anchor_texts WHERE 1=1; --";
        
        $anchor = AnchorText::factory()->create(['anchor_text' => $maliciousText]);

        $this->assertEquals($maliciousText, $anchor->anchor_text);
        $this->assertDatabaseCount('anchor_texts', 1);
    }

    /** @test */
    public function it_safely_stores_xss_in_anchor_text(): void
    {
        $xssPayload = '<script>alert("xss")</script>';
        
        $anchor = AnchorText::factory()->create(['anchor_text' => $xssPayload]);

        $this->assertEquals($xssPayload, $anchor->anchor_text);
    }

    // =============================================
    // Cascade Delete Tests
    // =============================================

    /** @test */
    public function it_is_deleted_when_project_is_deleted(): void
    {
        $project = Project::factory()->create();
        $anchor = AnchorText::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('anchor_texts', ['id' => $anchor->id]);
    }
}
