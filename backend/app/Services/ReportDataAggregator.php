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
            logger()->debug('Change calc inputs:', compact('currentPosition', 'previousPosition', 'compareStart', 'compareEnd'));
        $change = $this->calculateChange($currentPosition, $previousPosition, $compareStart && $compareEnd);
logger()->debug('Calculated change:', compact('currentPosition', 'previousPosition', 'change'));

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

        // Temporary debug log
        logger()->debug('Keywords Data:', $keywordsData);
        
        $topPerformers = $this->selectTopChanges($keywordsData, 'desc');
        logger()->debug('Post-Change Keywords:', ['keywords' => $keywordsData]);
        
        $needsAttention = $this->selectTopChanges($keywordsData, 'asc');

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

        $query = Ranking::whereIn('keyword_id', $keywordIds);
        $results = $query->orderBy('checked_at', 'asc')->get()->groupBy('keyword_id');

        logger()->info('Ordered Rankings Retrieved:', $results->flatMap(fn($group) => $group)->toArray());

        logger()->info('Fetched Rankings Query:', ['sql' => $query->toSql()]);
        logger()->info('Fetched Rankings Results:', $results->toArray());

        return $results;
    }

    protected function calculateChange(?int $current, ?int $previous, bool $compareEnabled): ?int
    {
        if (!$compareEnabled || $current === null || $previous === null) {
            return null;
        }

        return $current - $previous;
    }

    protected function selectTopChanges(array $keywordsData, string $direction): array
    {
        $filtered = $keywordsData; // Skip all filters temporarily for debugging

        usort($filtered, function (array $a, array $b) use ($direction) {
            $diff = $a['change'] <=> $b['change'];
            return $direction === 'asc' ? $diff : -$diff;
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
