<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\GSCData;
use App\Services\QueryMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QueryMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_similarity_normalizes_strings(): void
    {
        $matcher = new QueryMatcher();

        $score = $matcher->calculateSimilarity(' SEO Tools!! ', 'seo tools');

        $this->assertEquals(1.0, $score);
    }

    public function test_auto_match_updates_keyword_when_threshold_met(): void
    {
        $site = Site::factory()->create();

        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'best seo tools',
            'gsc_query_match' => null,
        ]);

        GSCData::factory()->create([
            'site_id' => $site->id,
            'query' => 'best seo tools',
        ]);

        $matcher = new QueryMatcher(0.9);

        $updated = $matcher->autoMatchForSite($site->id);

        $this->assertEquals(1, $updated);
        $this->assertEquals('best seo tools', $keyword->fresh()->gsc_query_match);
    }

    public function test_manual_match_overrides_value(): void
    {
        $site = Site::factory()->create();
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'seo tools',
            'gsc_query_match' => null,
        ]);

        $matcher = new QueryMatcher();
        $matcher->setManualMatch($keyword, 'SEO TOOLS');

        $this->assertEquals('seo tools', $keyword->fresh()->gsc_query_match);
    }
}
