<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Ranking;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a ranking with valid data.
     */
    public function test_create_ranking_with_valid_data(): void
    {
        $keyword = Keyword::factory()->create();
        
        $ranking = Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'url_found' => 'https://example.com/page',
            'serp_features' => json_encode(['featured_snippet' => true]),
            'checked_at' => now(),
            'check_duration_ms' => 150,
        ]);
        
        $this->assertInstanceOf(Ranking::class, $ranking);
        $this->assertEquals(5, $ranking->position);
    }

    /**
     * Test ranking belongs to keyword relationship.
     */
    public function test_ranking_belongs_to_keyword(): void
    {
        $keyword = Keyword::factory()->create();
        $ranking = Ranking::factory()->create(['keyword_id' => $keyword->id]);
        
        $this->assertInstanceOf(Keyword::class, $ranking->keyword);
        $this->assertEquals($keyword->id, $ranking->keyword->id);
    }

    /**
     * Test position can be NULL (not in top 100).
     */
    public function test_position_can_be_null(): void
    {
        $ranking = Ranking::factory()->create(['position' => null]);
        
        $this->assertNull($ranking->position);
    }

    /**
     * Test SERP features stored as JSON.
     */
    public function test_serp_features_stored_as_json(): void
    {
        $features = [
            'featured_snippet' => true,
            'local_pack' => false,
            'sitelinks' => 4,
        ];
        
        $ranking = Ranking::factory()->create([
            'serp_features' => json_encode($features),
        ]);
        
        $decoded = json_decode($ranking->serp_features, true);
        
        $this->assertEquals($features, $decoded);
    }

    /**
     * Test error message storage.
     */
    public function test_error_message_storage(): void
    {
        $ranking = Ranking::factory()->create([
            'error' => 'Connection timeout',
            'position' => null,
        ]);
        
        $this->assertEquals('Connection timeout', $ranking->error);
        $this->assertNull($ranking->position);
    }

    /**
     * Test check duration tracking.
     */
    public function test_check_duration_tracking(): void
    {
        $ranking = Ranking::factory()->create(['check_duration_ms' => 250]);
        
        $this->assertEquals(250, $ranking->check_duration_ms);
    }

    /**
     * Test scope for latest ranking.
     */
    public function test_latest_scope(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 10,
            'checked_at' => now()->subDays(2),
        ]);
        
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);
        
        $latest = Ranking::where('keyword_id', $keyword->id)->latest('checked_at')->first();
        
        $this->assertEquals(5, $latest->position);
    }
}
