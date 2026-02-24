<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportDataAggregator
{
    /**
     * Aggregate report data for a site.
     */
    public function aggregate(int $siteId, array $dateRange, ?array $compareRange = null): array
    {
        $site = Site::find($siteId);
        $keywords = Keyword::where('site_id', $siteId)->get();
        $keywordIds = $keywords->pluck('id')->all();

        $start = $this->parseDate($dateRange['start'] ?? $dateRange['from'] ?? ($dateRange[0] ?? null))?->startOfDay();
        $end = $this->parseDate($dateRange['end'] ?? $dateRange['to'] ?? ($dateRange[1] ?? null))?->endOfDay();

        $compareStart = $this->parseDate($compareRange['start'] ?? $compareRange['from'] ?? ($compareRange[0] ?? null))?->startOfDay();
        $compareEnd = $this->parseDate($compareRange['end'] ?? $compareRange['to'] ?? ($compareRange[1] ?? null))?->endOfDay();

        $rankingsInRange = $this->fetchRankings($keywordIds, $start, $end);
        $rankingsInCompare = $compareStart && $compareEnd
            ? $this->fetchRankings($keywordIds, $compareStart, $compareEnd)
            : collect();

        $keywordsData = [];
        $trends = [];

        foreach ($keywords as $keyword) {
            $rangeRankings = $rankingsInRange->get($keyword->id, collect());
            $compareRankings = $rankingsInCompare->get($keyword->id, collect());

            $current = $rangeRankings->sortByDesc('checked_at')->first();
            $previous = $compareRankings->sortByDesc('checked_at')->first();

            $currentPosition = $current?->position;
            $previousPosition = $previous?->position;
            $change = $this->calculateChange($currentPosition, $previousPosition, $compareStart && $compareEnd);

            $keywordsData[] = [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'current_position' => $currentPosition,
                'previous_position' => $previousPosition,
                'change' => $change,
                'search_engine' => $keyword->search_engine,
                'location' => $keyword->location,
                'last_checked_at' => $current?->checked_at?->toISOString(),
            ];

            $trends[] = [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'points' => $rangeRankings
                    ->sortBy('checked_at')
                    ->map(fn (Ranking $ranking) => [
                        'checked_at' => $ranking->checked_at?->toISOString(),
                        'position' => $ranking->position,
                    ])
                    ->values()
                    ->toArray(),
            ];
        }

        // Top performers: most negative change (biggest improvement) -> sort ascending
        $topPerformers = $this->selectTopChanges($keywordsData, 'asc');
        // Needs attention: most positive change (biggest drop) -> sort descending
        $needsAttention = $this->selectTopChanges($keywordsData, 'desc');

        $competitorSummary = null;
        if ($site?->user_id && class_exists(ComparisonService::class)) {
            $competitorSummary = (new ComparisonService())->getSummary($site->user_id);
        }

        return [
            'site_id' => $siteId,
            'date_range' => [
                'start' => $start?->toDateString(),
                'end' => $end?->toDateString(),
            ],
            'compare_range' => $compareStart && $compareEnd ? [
                'start' => $compareStart->toDateString(),
                'end' => $compareEnd->toDateString(),
            ] : null,
            'keywords' => $keywordsData,
            'top_performers' => $topPerformers,
            'needs_attention' => $needsAttention,
            'trends' => $trends,
            'competitors' => $competitorSummary,
        ];
    }

    protected function fetchRankings(array $keywordIds, ?Carbon $start, ?Carbon $end): Collection
    {
        if (empty($keywordIds) || !$start || !$end) {
            return collect();
        }

        $query = Ranking::whereIn('keyword_id', $keywordIds)
            ->whereBetween('checked_at', [$start, $end]);
        $results = $query->orderBy('checked_at', 'asc')->get()->groupBy('keyword_id');

        return $results;
    }

    protected function calculateChange(?int $current, ?int $previous, bool $compareEnabled): ?int
    {
        if (!$compareEnabled || $current === null || $previous === null) {
            return null;
        }

        if ($current === null || $previous === null) {
    return null;
} else {
    return $current - $previous;
}
    }

    protected function selectTopChanges(array $keywordsData, string $direction): array
    {
        // Debugging keywordsData before filtering
file_put_contents('C:\\Users\\Raitis\\.openclaw\\workspace\\PROJECT-SEOINDICATOR\\debug_keywordsData.txt', print_r($keywordsData, true));
$filtered = array_filter($keywordsData, fn($k) => $k['change'] !== null);

        // Debug logging for keywords data
        file_put_contents('C:\\Users\\Raitis\\.openclaw\\workspace\\PROJECT-SEOINDICATOR\\debug_log.txt', print_r($filtered, true)); // Debug raw state before sorting

        usort($filtered, function (array $a, array $b) use ($direction) {
            // Determine sorting ambiguity for debugging
            // Using print/logs
            // Only debug if malformed after trace log exported results fixed
            $diff = $a['change'] <=> $b['change'];
            // For 'desc' (top performers): most negative change first (biggest improvement)
            // For 'asc' (needs attention): most positive change first (biggest drop)
            return $direction === 'asc' ? $a['change'] <=> $b['change'] : $b['change'] <=> $a['change'];
        });

        return array_slice($filtered, 0, 5);
    }

    protected function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value);
    }
}
