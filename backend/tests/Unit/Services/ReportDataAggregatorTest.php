<?php

namespace Tests\Unit\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Services\ReportDataAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
        $keywords = Keyword::factory()->count(3)->for($site)->create();

        Ranking::factory()->for($keywords[0])->count(2)->state(new Sequence(
            ['checked_at' => Carbon::now()->subDays(2), 'position' => 4],
            ['checked_at' => Carbon::now()->subDays(1), 'position' => 2],
        ))->create();

        $result = $this->aggregator->aggregate($site->id, ['start' => '2026-02-20', 'end' => '2026-02-24']);

        $this->assertNotEmpty($result['keywords']);
        // More post-setup assertions below
    }

    /**
     * Test 6.2: Compare range calculations.
     */
    public function test_compare_range_calculations(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->for($site)->create(['keyword' => 'seo']);

        // Current range rankings
        Ranking::factory()->for($keyword)->create(['checked_at' => now(), 'position' => 2]);
        Ranking::factory()->for($keyword)->create(['checked_at' => now()->subDays(3), 'position' => 5]);

        // Compare range rankings
        Ranking::factory()->for($keyword)->state(['checked_at' => now()->subDays(3), 'position' => 5])->create();

        logger()->debug('Compare Setup', [
            'keyword' => $keyword,
            'rangeRankings' => [],
            'compareRankings' => []
        ]);

        // Verify test data setup
        $rankings = Ranking::where('keyword_id', $keyword->id)->orderBy('checked_at', 'asc')->get();

        // Verify fetched ranking positions
        $this->assertCount(3, $rankings, 'Ranking entry count mismatch');
        $this->assertEquals(2, $rankings[0]->position, 'Unexpected position for current');
        $this->assertEquals(5, $rankings[1]->position, 'Unexpected position for previous');

        $rankings = Ranking::where('keyword_id', $keyword->id)->orderBy('checked_at', 'asc')->get();
        $this->assertCount(3, $rankings);
        $this->assertEquals(2, $rankings[0]->position);
        $this->assertEquals(5, $rankings[1]->position);

        $result = $this->aggregator->aggregate($site->id, ['start' => now()->subDays(2)->toDateString(), 'end' => now()->toDateString()],
            ['start' => now()->subDays(4)->toDateString(), 'end' => now()->subDays(3)->toDateString()]
        );

        $this->assertEquals(-3, $result['keywords'][0]['change']);
    }

    /**
     * Test 6.3: Top performers selection.
     */
    public function test_top_performers_selection(): void
    {
        $site = Site::factory()->create();
        $keywords = Keyword::factory()->count(3)->for($site)->create();

        Ranking::factory()->for($keywords[0])->state(['position' => 3, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[0])->state(['position' => 5, 'checked_at' => now()->subDays(12)])->create();

        Ranking::factory()->for($keywords[1])->state(['position' => 7, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[1])->state(['position' => 10, 'checked_at' => now()->subDays(12)])->create();

        Ranking::factory()->for($keywords[2])->state(['position' => 1, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[2])->state(['position' => 3, 'checked_at' => now()->subDays(12)])->create();

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => now()->subDays(3)->toDateString(), 'end' => now()->toDateString()],
            ['start' => now()->subDays(10)->toDateString(), 'end' => now()->subDays(5)->toDateString()]
        );

        $this->assertNotEmpty($result['top_performers']);
        $this->assertEquals($keywords[2]->keyword, $result['top_performers'][0]['keyword']);
    }

    /**
     * Test 6.4: Needs attention selection.
     */
    public function test_needs_attention_selection(): void
    {
        $site = Site::factory()->create();
        $keywords = Keyword::factory()->count(3)->for($site)->create();

        Ranking::factory()->for($keywords[0])->state(['position' => 3, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[0])->state(['position' => 1, 'checked_at' => now()->subDays(12)])->create();

        Ranking::factory()->for($keywords[1])->state(['position' => 10, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[1])->state(['position' => 5, 'checked_at' => now()->subDays(12)])->create();

        Ranking::factory()->for($keywords[2])->state(['position' => 15, 'checked_at' => now()->subDays(2)])->create();
        Ranking::factory()->for($keywords[2])->state(['position' => 10, 'checked_at' => now()->subDays(12)])->create();

        $result = $this->aggregator->aggregate(
            $site->id,
            ['start' => now()->subDays(3)->toDateString(), 'end' => now()->toDateString()],
            ['start' => now()->subDays(10)->toDateString(), 'end' => now()->subDays(5)->toDateString()]
        );

        $this->assertNotEmpty($result['needs_attention']);
        $this->assertEquals($keywords[2]->keyword, $result['needs_attention'][0]['keyword']);
    }
}