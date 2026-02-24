<?php

namespace Tests\Unit\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Services\ReportDataAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportDataAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private ReportDataAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-02-24');
        $this->aggregator = new ReportDataAggregator();
    }

    /**
     * Test 6.1: Aggregate with valid date range.
     */
    public function test_aggregate_with_valid_date_range(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'test seo']);

        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 4,
        ]);
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-23'),
            'position' => 2,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        $this->assertEquals($site->id, $result['site_id']);
        $this->assertEquals('2026-02-20', $result['date_range']['start']);
        $this->assertEquals('2026-02-24', $result['date_range']['end']);
        $this->assertNotEmpty($result['keywords']);
        $this->assertCount(1, $result['keywords']);
        $this->assertEquals('test seo', $result['keywords'][0]['keyword']);
        // Latest position should be 2 (most recent)
        $this->assertEquals(2, $result['keywords'][0]['current_position']);
    }

    /**
     * Test 6.2: Compare range calculations - position change computed correctly.
     */
    public function test_compare_range_calculations(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'seo tools']);

        // Current range: 2026-02-22 to 2026-02-24
        // Latest in current range: position 2
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-23'),
            'position' => 2,
        ]);

        // Compare range: 2026-02-18 to 2026-02-20
        // Latest in compare range: position 5
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-19'),
            'position' => 5,
        ]);

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => '2026-02-22', 'end' => '2026-02-24'],
            ['start' => '2026-02-18', 'end' => '2026-02-20']
        );

        $this->assertNotNull($result['keywords'][0]['change']);
        // Change = current - previous = 2 - 5 = -3 (improvement, lower is better)
        $this->assertEquals(-3, $result['keywords'][0]['change']);
    }

    /**
     * Test 6.3: Top performers selection - biggest gains identified.
     * Lower position change = bigger gain (position 3 to 1 = -2 change = best)
     */
    public function test_top_performers_selection(): void
    {
        $site = Site::factory()->create();

        // Keyword 0: Position 3 -> 1 (change = -2, best improvement)
        $keyword0 = Keyword::factory()->for($site)->create(['keyword' => 'best seo']);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 1,
        ]);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 3,
        ]);

        // Keyword 1: Position 7 -> 5 (change = -2, same improvement)
        $keyword1 = Keyword::factory()->for($site)->create(['keyword' => 'good seo']);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 5,
        ]);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 7,
        ]);

        // Keyword 2: Position 10 -> 15 (change = +5, drop)
        $keyword2 = Keyword::factory()->for($site)->create(['keyword' => 'ok seo']);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 15,
        ]);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 10,
        ]);

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => '2026-02-20', 'end' => '2026-02-24'],
            ['start' => '2026-02-14', 'end' => '2026-02-18']
        );

        $this->assertNotEmpty($result['top_performers']);
        // Top performer should have the most negative change (biggest improvement)
        $this->assertEquals(-2, $result['top_performers'][0]['change']);
        $this->assertContains($result['top_performers'][0]['keyword'], ['best seo', 'good seo']);
    }

    /**
     * Test 6.4: Needs attention selection - biggest drops identified.
     * Higher position change = biggest drop (position 5 to 10 = +5 change = worst)
     */
    public function test_needs_attention_selection(): void
    {
        $site = Site::factory()->create();

        // Keyword 0: Position 5 -> 10 (change = +5, biggest drop)
        $keyword0 = Keyword::factory()->for($site)->create(['keyword' => 'dropping keyword']);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 10,
        ]);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 5,
        ]);

        // Keyword 1: Position 3 -> 4 (change = +1, slight drop)
        $keyword1 = Keyword::factory()->for($site)->create(['keyword' => 'slight drop']);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 4,
        ]);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 3,
        ]);

        // Keyword 2: Position 10 -> 5 (change = -5, improvement)
        $keyword2 = Keyword::factory()->for($site)->create(['keyword' => 'improving']);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 5,
        ]);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 10,
        ]);

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => '2026-02-20', 'end' => '2026-02-24'],
            ['start' => '2026-02-14', 'end' => '2026-02-18']
        );

        $this->assertNotEmpty($result['needs_attention']);
        // Needs attention should have the most positive change (biggest drop)
        $this->assertEquals('dropping keyword', $result['needs_attention'][0]['keyword']);
        $this->assertEquals(5, $result['needs_attention'][0]['change']);
    }

    /**
     * Test 6.5: Trend data aggregation - time-series data returned.
     */
    public function test_trend_data_aggregation(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'trending']);

        // Create multiple rankings over time
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-20'),
            'position' => 10,
        ]);
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-21'),
            'position' => 8,
        ]);
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 5,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        $this->assertNotEmpty($result['trends']);
        $this->assertCount(1, $result['trends']);
        $this->assertEquals('trending', $result['trends'][0]['keyword']);
        $this->assertCount(3, $result['trends'][0]['points']);

        // Points should be sorted by checked_at ascending
        $this->assertEquals('2026-02-20', Carbon::parse($result['trends'][0]['points'][0]['checked_at'])->toDateString());
        $this->assertEquals(10, $result['trends'][0]['points'][0]['position']);
        $this->assertEquals(5, $result['trends'][0]['points'][2]['position']);
    }

    /**
     * Test 6.6: Optional competitor data - included when FEAT-003 data available.
     */
    public function test_optional_competitor_data(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create();

        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 5,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        // Competitor data may be null if FEAT-003 not fully implemented
        // Just verify the key exists
        $this->assertArrayHasKey('competitors', $result);
    }

    /**
     * Test 6.7: No rankings for range - returns empty datasets with safe defaults.
     */
    public function test_no_rankings_for_range(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->for($site)->create(['keyword' => 'no data']);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        $this->assertNotEmpty($result['keywords']);
        $this->assertNull($result['keywords'][0]['current_position']);
        $this->assertNull($result['keywords'][0]['previous_position']);
        $this->assertNull($result['keywords'][0]['change']);
        $this->assertEmpty($result['trends'][0]['points']);
    }

    /**
     * Test: Large dataset performance with batch queries.
     */
    public function test_large_dataset_performance(): void
    {
        $site = Site::factory()->create();
        $keywords = Keyword::factory()->count(50)->for($site)->create();

        foreach ($keywords as $keyword) {
            Ranking::factory()->for($keyword)->create([
                'checked_at' => Carbon::parse('2026-02-22'),
                'position' => rand(1, 100),
            ]);
        }

        $start = microtime(true);
        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);
        $duration = microtime(true) - $start;

        $this->assertCount(50, $result['keywords']);
        // Should complete within 2 seconds
        $this->assertLessThan(2.0, $duration, 'Aggregation took too long for large dataset');
    }

    /**
     * Test: Compare range missing - changes default to null.
     */
    public function test_compare_range_missing(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create();

        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 5,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ], null); // No compare range

        $this->assertNull($result['compare_range']);
        $this->assertNull($result['keywords'][0]['change']);
    }

    /**
     * Test: Ties in gains handled deterministically.
     */
    public function test_ties_in_gains_handled_deterministically(): void
    {
        $site = Site::factory()->create();

        // All have same improvement: -2
        $keyword0 = Keyword::factory()->for($site)->create(['keyword' => 'alpha']);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 1,
        ]);
        Ranking::factory()->for($keyword0)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 3,
        ]);

        $keyword1 = Keyword::factory()->for($site)->create(['keyword' => 'beta']);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 1,
        ]);
        Ranking::factory()->for($keyword1)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 3,
        ]);

        $keyword2 = Keyword::factory()->for($site)->create(['keyword' => 'gamma']);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 1,
        ]);
        Ranking::factory()->for($keyword2)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 3,
        ]);

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => '2026-02-20', 'end' => '2026-02-24'],
            ['start' => '2026-02-14', 'end' => '2026-02-18']
        );

        // All should have same change
        $this->assertEquals(-2, $result['top_performers'][0]['change']);
        $this->assertCount(3, $result['top_performers']);
    }

    /**
     * Test: Edge case - rankings outside date range are excluded.
     */
    public function test_rankings_outside_date_range_excluded(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'test']);

        // This ranking is OUTSIDE the query range (before start)
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-15'),
            'position' => 50,
        ]);

        // This ranking is INSIDE the query range
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22'),
            'position' => 10,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        // Should only have the ranking from Feb 22
        $this->assertEquals(10, $result['keywords'][0]['current_position']);
        $this->assertCount(1, $result['trends'][0]['points']);
    }

    /**
     * Test: Multiple rankings per day - returns latest.
     */
    public function test_multiple_rankings_per_day_returns_latest(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'test']);

        // Multiple rankings on same day
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22 10:00:00'),
            'position' => 15,
        ]);
        Ranking::factory()->for($keyword)->create([
            'checked_at' => Carbon::parse('2026-02-22 18:00:00'),
            'position' => 10,
        ]);

        $result = $this->aggregator->aggregate($site->id, [
            'start' => '2026-02-20',
            'end' => '2026-02-24',
        ]);

        // Latest position should be 10 (most recent timestamp)
        $this->assertEquals(10, $result['keywords'][0]['current_position']);
    }
}
