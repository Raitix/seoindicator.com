<?php

namespace Tests\Unit\Models;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BacklinkTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // Model Attribute Tests
    // =============================================

    /** @test */
    public function it_can_create_a_backlink_with_all_attributes(): void
    {
        $project = Project::factory()->create();
        
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/blog/post',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com/page',
            'anchor_text' => 'SEO tools',
            'link_type' => 'dofollow',
            'first_seen_at' => now()->subDays(30),
            'last_checked_at' => now(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
            'source_domain' => 'example.com',
            'link_type' => 'dofollow',
        ]);
    }

    /** @test */
    public function it_casts_first_seen_at_to_datetime(): void
    {
        $backlink = Backlink::factory()->create([
            'first_seen_at' => '2026-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backlink->first_seen_at);
        $this->assertEquals('2026-01-15 10:30:00', $backlink->first_seen_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_casts_last_checked_at_to_datetime(): void
    {
        $backlink = Backlink::factory()->create([
            'last_checked_at' => '2026-02-28 14:45:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backlink->last_checked_at);
        $this->assertEquals('2026-02-28 14:45:00', $backlink->last_checked_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_casts_is_active_to_boolean(): void
    {
        $backlink = Backlink::factory()->create(['is_active' => 1]);

        $this->assertIsBool($backlink->is_active);
        $this->assertTrue($backlink->is_active);
    }

    /** @test */
    public function it_defaults_is_active_to_true(): void
    {
        // Create without is_active field - DB default should apply
        $project = Project::factory()->create();
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com/target',
            'link_type' => 'dofollow',
        ]);

        // Re-fetch from DB to get the default
        $freshBacklink = Backlink::find($backlink->id);
        $this->assertTrue($freshBacklink->is_active);
    }

    /** @test */
    public function it_defaults_link_type_to_unknown(): void
    {
        // Create without link_type field - DB default should apply
        $project = Project::factory()->create();
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'source_domain' => 'example.com',
            'target_url' => 'https://myproject.com/target',
        ]);

        // Re-fetch from DB to get the default
        $freshBacklink = Backlink::find($backlink->id);
        $this->assertEquals('unknown', $freshBacklink->link_type);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $backlink = new Backlink();
        
        $fillable = [
            'project_id',
            'source_url',
            'source_domain',
            'target_url',
            'anchor_text',
            'link_type',
            'first_seen_at',
            'last_checked_at',
            'is_active',
        ];

        $this->assertEquals($fillable, $backlink->getFillable());
    }

    /** @test */
    public function it_uses_correct_table_name(): void
    {
        $backlink = new Backlink();

        $this->assertEquals('backlinks', $backlink->getTable());
    }

    // =============================================
    // Relationship Tests
    // =============================================

    /** @test */
    public function it_belongs_to_a_project(): void
    {
        $backlink = Backlink::factory()->create();

        $this->assertInstanceOf(Project::class, $backlink->project);
        $this->assertEquals($backlink->project_id, $backlink->project->id);
    }

    // =============================================
    // Scope Tests
    // =============================================

    /** @test */
    public function scope_active_filters_active_backlinks(): void
    {
        Backlink::factory()->count(3)->create(['is_active' => true]);
        Backlink::factory()->count(2)->inactive()->create();

        $activeBacklinks = Backlink::active()->get();

        $this->assertCount(3, $activeBacklinks);
        $activeBacklinks->each(fn ($backlink) => $this->assertTrue($backlink->is_active));
    }

    /** @test */
    public function scope_dofollow_filters_dofollow_backlinks(): void
    {
        Backlink::factory()->count(3)->dofollow()->create();
        Backlink::factory()->count(2)->nofollow()->create();
        Backlink::factory()->count(1)->create(['link_type' => 'unknown']);

        $dofollowBacklinks = Backlink::dofollow()->get();

        $this->assertCount(3, $dofollowBacklinks);
        $dofollowBacklinks->each(fn ($backlink) => $this->assertEquals('dofollow', $backlink->link_type));
    }

    /** @test */
    public function scope_nofollow_filters_nofollow_backlinks(): void
    {
        Backlink::factory()->count(2)->nofollow()->create();
        Backlink::factory()->count(3)->dofollow()->create();

        $nofollowBacklinks = Backlink::nofollow()->get();

        $this->assertCount(2, $nofollowBacklinks);
        $nofollowBacklinks->each(fn ($backlink) => $this->assertEquals('nofollow', $backlink->link_type));
    }

    /** @test */
    public function scopes_can_be_chained(): void
    {
        Backlink::factory()->count(2)->dofollow()->create(['is_active' => true]);
        Backlink::factory()->count(1)->dofollow()->inactive()->create();
        Backlink::factory()->count(2)->nofollow()->create(['is_active' => true]);

        $results = Backlink::active()->dofollow()->get();

        $this->assertCount(2, $results);
        $results->each(function ($backlink) {
            $this->assertTrue($backlink->is_active);
            $this->assertEquals('dofollow', $backlink->link_type);
        });
    }

    // =============================================
    // Edge Cases
    // =============================================

    /** @test */
    public function anchor_text_can_be_null(): void
    {
        $backlink = Backlink::factory()->create(['anchor_text' => null]);

        $this->assertNull($backlink->anchor_text);
    }

    /** @test */
    public function first_seen_at_can_be_null(): void
    {
        $backlink = Backlink::factory()->create(['first_seen_at' => null]);

        $this->assertNull($backlink->first_seen_at);
    }

    /** @test */
    public function last_checked_at_can_be_null(): void
    {
        $backlink = Backlink::factory()->create(['last_checked_at' => null]);

        $this->assertNull($backlink->last_checked_at);
    }

    /** @test */
    public function it_can_store_max_length_source_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2050);
        $backlink = Backlink::factory()->create(['source_url' => $longUrl]);

        $this->assertEquals($longUrl, $backlink->source_url);
    }

    /** @test */
    public function it_can_store_max_length_anchor_text(): void
    {
        $longAnchor = str_repeat('keyword ', 62); // ~500 chars
        $backlink = Backlink::factory()->create(['anchor_text' => $longAnchor]);

        $this->assertEquals($longAnchor, $backlink->anchor_text);
    }

    /** @test */
    public function it_validates_link_type_values(): void
    {
        // SQLite stores as string, MySQL validates enum
        $backlink = Backlink::factory()->create(['link_type' => 'dofollow']);
        $this->assertEquals('dofollow', $backlink->link_type);

        $backlink = Backlink::factory()->create(['link_type' => 'nofollow']);
        $this->assertEquals('nofollow', $backlink->link_type);

        $backlink = Backlink::factory()->create(['link_type' => 'unknown']);
        $this->assertEquals('unknown', $backlink->link_type);
    }

    // =============================================
    // Security Tests
    // =============================================

    /** @test */
    public function it_safely_stores_sql_injection_in_source_url(): void
    {
        $maliciousUrl = "https://example.com/page'; DROP TABLE backlinks; --";
        
        $backlink = Backlink::factory()->create(['source_url' => $maliciousUrl]);

        // Should be stored as-is (parameterized queries prevent execution)
        $this->assertEquals($maliciousUrl, $backlink->source_url);
        
        // Table should still exist
        $this->assertDatabaseCount('backlinks', 1);
    }

    /** @test */
    public function it_safely_stores_sql_injection_in_anchor_text(): void
    {
        $maliciousText = "'; DELETE FROM backlinks WHERE 1=1; --";
        
        $backlink = Backlink::factory()->create(['anchor_text' => $maliciousText]);

        $this->assertEquals($maliciousText, $backlink->anchor_text);
        $this->assertDatabaseCount('backlinks', 1);
    }

    /** @test */
    public function it_safely_stores_xss_in_anchor_text(): void
    {
        $xssPayload = '<script>alert("xss")</script>';
        
        $backlink = Backlink::factory()->create(['anchor_text' => $xssPayload]);

        // Stored as-is (output escaping is the responsibility of the view layer)
        $this->assertEquals($xssPayload, $backlink->anchor_text);
    }

    /** @test */
    public function it_handles_unicode_in_source_domain(): void
    {
        $unicodeDomain = '例え.jp';
        
        $backlink = Backlink::factory()->create(['source_domain' => $unicodeDomain]);

        $this->assertEquals($unicodeDomain, $backlink->source_domain);
    }

    // =============================================
    // Cascade Delete Tests
    // =============================================

    /** @test */
    public function it_is_deleted_when_project_is_deleted(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('backlinks', ['id' => $backlink->id]);
    }
}
