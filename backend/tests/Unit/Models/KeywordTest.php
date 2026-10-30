<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\Ranking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KeywordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a keyword with valid data.
     */
    public function test_create_keyword_with_valid_data(): void
    {
        $site = Site::factory()->create();
        
        $keyword = Keyword::create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'target_url' => 'https://example.com/page',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
        ]);
        
        $this->assertInstanceOf(Keyword::class, $keyword);
        $this->assertEquals('test keyword', $keyword->keyword);
        $this->assertEquals('active', $keyword->status);
    }

    /**
     * Test keyword belongs to site relationship.
     */
    public function test_keyword_belongs_to_site(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);
        
        $this->assertInstanceOf(Site::class, $keyword->site);
        $this->assertEquals($site->id, $keyword->site->id);
    }

    /**
     * Test keyword has many ranking history.
     */
    public function test_keyword_has_many_ranking_history(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        
        $this->assertCount(3, $keyword->rankingHistory);
        $this->assertInstanceOf(Ranking::class, $keyword->rankingHistory->first());
    }

    /**
     * Test status enum values.
     */
    public function test_status_enum_values(): void
    {
        $statuses = ['active', 'paused', 'deleted'];
        
        foreach ($statuses as $status) {
            $keyword = Keyword::factory()->create(['status' => $status]);
            $this->assertEquals($status, $keyword->status);
        }
    }

    /**
     * Test search engine enum (MVP: google only).
     */
    public function test_search_engine_enum(): void
    {
        $keyword = Keyword::factory()->create(['search_engine' => 'google']);
        
        $this->assertEquals('google', $keyword->search_engine);
    }

    /**
     * Test device enum (MVP: desktop only).
     */
    public function test_device_enum(): void
    {
        $keyword = Keyword::factory()->create(['device' => 'desktop']);
        
        $this->assertEquals('desktop', $keyword->device);
    }

    /**
     * Test soft delete (status = deleted).
     */
    public function test_soft_delete(): void
    {
        $keyword = Keyword::factory()->create(['status' => 'active']);
        
        $keyword->delete();
        
        $this->assertEquals('deleted', $keyword->fresh()->status);
    }

    /**
     * Test active scope.
     */
    public function test_active_scope(): void
    {
        Keyword::factory()->count(3)->create(['status' => 'active']);
        Keyword::factory()->count(2)->create(['status' => 'paused']);
        Keyword::factory()->count(1)->create(['status' => 'deleted']);
        
        $activeKeywords = Keyword::active()->get();
        
        $this->assertCount(3, $activeKeywords);
        $activeKeywords->each(fn($k) => $this->assertEquals('active', $k->status));
    }

    /**
     * Test bySite scope.
     */
    public function test_by_site_scope(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();
        
        Keyword::factory()->count(3)->create(['site_id' => $site1->id]);
        Keyword::factory()->count(2)->create(['site_id' => $site2->id]);
        
        $site1Keywords = Keyword::bySite($site1->id)->get();
        
        $this->assertCount(3, $site1Keywords);
    }

    /**
     * Test needsCheck scope.
     */
    public function test_needs_check_scope(): void
    {
        // Never checked
        Keyword::factory()->create(['last_checked_at' => null]);
        
        // Checked yesterday (needs check if daily)
        Keyword::factory()->create(['last_checked_at' => now()->subDay()]);
        
        // Checked today (doesn't need check)
        Keyword::factory()->create(['last_checked_at' => now()]);
        
        $needsCheck = Keyword::needsCheck()->get();
        
        $this->assertCount(2, $needsCheck);
    }

    /**
     * Test unique constraint on keyword combination.
     */
    public function test_unique_constraint(): void
    {
        $site = Site::factory()->create();
        
        Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'duplicate keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'duplicate keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);
    }

    /**
     * Test same keyword allowed with different location.
     */
    public function test_same_keyword_different_location(): void
    {
        $site = Site::factory()->create();
        
        $keyword1 = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'location' => 'us',
        ]);
        
        $keyword2 = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'location' => 'uk',
        ]);
        
        $this->assertNotEquals($keyword1->id, $keyword2->id);
    }
}
