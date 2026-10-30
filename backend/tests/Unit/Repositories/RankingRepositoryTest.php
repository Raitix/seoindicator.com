<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\RankingRepository;
use App\Models\Ranking;
use App\Models\Keyword;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RankingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected RankingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RankingRepository();
    }

    /**
     * Test findLatestByKeyword returns most recent ranking.
     */
    public function test_find_latest_by_keyword(): void
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
            'checked_at' => now(),
        ]);
        
        $result = $this->repository->findLatestByKeyword($keyword->id);
        
        $this->assertEquals(5, $result->position);
    }

    /**
     * Test findLatestByKeyword returns null for no history.
     */
    public function test_find_latest_by_keyword_no_history(): void
    {
        $keyword = Keyword::factory()->create();
        
        $result = $this->repository->findLatestByKeyword($keyword->id);
        
        $this->assertNull($result);
    }

    /**
     * Test findByKeywordAndDateRange.
     */
    public function test_find_by_keyword_and_date_range(): void
    {
        $keyword = Keyword::factory()->create();
        
        // Within range
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDays(10),
        ]);
        
        // Outside range (too old)
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 15,
            'checked_at' => now()->subDays(40),
        ]);
        
        $result = $this->repository->findByKeywordAndDateRange(
            $keyword->id,
            now()->subDays(30),
            now()
        );
        
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result->first()->position);
    }

    /**
     * Test findByKeywordAndDateRange with no results.
     */
    public function test_find_by_keyword_and_date_range_no_results(): void
    {
        $keyword = Keyword::factory()->create();
        
        $result = $this->repository->findByKeywordAndDateRange(
            $keyword->id,
            now()->subDays(30),
            now()
        );
        
        $this->assertCount(0, $result);
    }

    /**
     * Test create ranking.
     */
    public function test_create_ranking(): void
    {
        $keyword = Keyword::factory()->create();
        
        $data = [
            'keyword_id' => $keyword->id,
            'position' => 7,
            'url_found' => 'https://example.com/page',
            'serp_features' => json_encode(['featured_snippet' => true]),
            'checked_at' => now(),
            'check_duration_ms' => 150,
        ];
        
        $result = $this->repository->create($data);
        
        $this->assertInstanceOf(Ranking::class, $result);
        $this->assertEquals(7, $result->position);
        $this->assertDatabaseHas('ranking_history', ['position' => 7]);
    }

    /**
     * Test create ranking with NULL position.
     */
    public function test_create_ranking_null_position(): void
    {
        $keyword = Keyword::factory()->create();
        
        $result = $this->repository->create([
            'keyword_id' => $keyword->id,
            'position' => null,
            'checked_at' => now(),
        ]);
        
        $this->assertNull($result->position);
    }

    /**
     * Test getStats - average position.
     */
    public function test_get_stats_average_position(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDays(3)],
            ['keyword_id' => $keyword->id, 'position' => 10, 'checked_at' => now()->subDays(2)],
            ['keyword_id' => $keyword->id, 'position' => 15, 'checked_at' => now()->subDay()],
        ]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertEquals(10, $stats['averagePosition']);
    }

    /**
     * Test getStats excludes NULL positions.
     */
    public function test_get_stats_excludes_null_positions(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDays(2)],
            ['keyword_id' => $keyword->id, 'position' => null, 'checked_at' => now()->subDay()],
        ]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertEquals(5, $stats['averagePosition']);
    }

    /**
     * Test getStats - best position.
     */
    public function test_get_stats_best_position(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDays(2)],
            ['keyword_id' => $keyword->id, 'position' => 3, 'checked_at' => now()->subDay()],
            ['keyword_id' => $keyword->id, 'position' => 10, 'checked_at' => now()],
        ]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertEquals(3, $stats['bestPosition']);
    }

    /**
     * Test getStats - worst position.
     */
    public function test_get_stats_worst_position(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDays(2)],
            ['keyword_id' => $keyword->id, 'position' => 50, 'checked_at' => now()->subDay()],
            ['keyword_id' => $keyword->id, 'position' => 10, 'checked_at' => now()],
        ]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertEquals(50, $stats['worstPosition']);
    }

    /**
     * Test getStats - total checks.
     */
    public function test_get_stats_total_checks(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->count(10)->create(['keyword_id' => $keyword->id]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertEquals(10, $stats['totalChecks']);
    }

    /**
     * Test getStats with all NULL positions.
     */
    public function test_get_stats_all_null_positions(): void
    {
        $keyword = Keyword::factory()->create();
        
        Ranking::factory()->count(3)->create([
            'keyword_id' => $keyword->id,
            'position' => null,
        ]);
        
        $stats = $this->repository->getStats($keyword->id);
        
        $this->assertNull($stats['averagePosition']);
        $this->assertNull($stats['bestPosition']);
        $this->assertNull($stats['worstPosition']);
        $this->assertEquals(3, $stats['totalChecks']);
    }
}
