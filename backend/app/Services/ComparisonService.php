<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\Keyword;
use App\Models\Ranking;
use Illuminate\Support\Collection;

class ComparisonService
{
    /**
     * Calculate position difference (negative = user ahead).
     */
    public static function calculatePositionDiff(?int $userPosition, ?int $competitorPosition): ?int
    {
        if (is_null($userPosition) || is_null($competitorPosition)) {
            return null;
        }

        return $userPosition - $competitorPosition;
    }

    /**
     * Determine status based on user and competitor positions.
     */
    public static function determineStatus(?int $userPosition, ?int $competitorPosition): string
    {
        if (is_null($userPosition) && !is_null($competitorPosition)) {
            return 'gap';
        }

        if (!is_null($userPosition) && is_null($competitorPosition)) {
            return 'only-i-rank';
        }

        if (is_null($userPosition) && is_null($competitorPosition)) {
            return 'no-rank';
        }

        if ($userPosition === $competitorPosition) {
            return 'both-rank';
        }

        return $userPosition < $competitorPosition ? 'im-winning' : 'theyre-winning';
    }

    /**
     * Get summary metrics for the comparison dashboard.
     */
    public function getSummary(int $userId): array
    {
        $summary = [
            'totalKeywords' => 0,
            'imWinning' => 0,
            'theyreWinning' => 0,
            'bothRank' => 0,
            'onlyIRank' => 0,
            'onlyTheyRank' => 0,
        ];

        $keywords = Keyword::where('user_id', $userId)
            ->with(['competitorRankings', 'rankingHistory'])
            ->get();

        foreach ($keywords as $keyword) {
            $summary['totalKeywords']++;

            $userPosition = $keyword->rankingHistory
                ->sortByDesc('checked_at')
                ->first()?->position;

            $rankingsByCompetitor = $keyword->competitorRankings
                ->sortByDesc('checked_at')
                ->groupBy('competitor_id');

            if ($rankingsByCompetitor->isEmpty()) {
                $status = self::determineStatus($userPosition, null);
                $this->incrementSummary($summary, $status);
                continue;
            }

            foreach ($rankingsByCompetitor as $rankings) {
                $competitorPosition = $rankings->first()?->position;
                $status = self::determineStatus($userPosition, $competitorPosition);
                $this->incrementSummary($summary, $status);
            }
        }

        return $summary;
    }

    /**
     * Get comparison results with optional filter + pagination.
     */
    public function getComparisonResults(int $userId, string $filter, int $page, int $perPage): array
    {
        $keywords = Keyword::where('user_id', $userId)
            ->with(['competitorRankings.competitor', 'rankingHistory'])
            ->get();

        $results = [];

        foreach ($keywords as $keyword) {
            $userPosition = $keyword->rankingHistory
                ->sortByDesc('checked_at')
                ->first()?->position;

            $rankingsByCompetitor = $keyword->competitorRankings
                ->sortByDesc('checked_at')
                ->groupBy('competitor_id');

            if ($rankingsByCompetitor->isEmpty()) {
                $status = self::determineStatus($userPosition, null);
                if ($filter === 'all' || $filter === $status) {
                    $results[] = $this->formatComparisonRow(
                        $keyword,
                        null,
                        $userPosition,
                        null,
                        $status,
                        null
                    );
                }
                continue;
            }

            foreach ($rankingsByCompetitor as $rankings) {
                $latest = $rankings->first();
                $competitor = $latest?->competitor;
                $competitorPosition = $latest?->position;
                $status = self::determineStatus($userPosition, $competitorPosition);

                if ($filter !== 'all' && $filter !== $status) {
                    continue;
                }

                $results[] = $this->formatComparisonRow(
                    $keyword,
                    $competitor,
                    $userPosition,
                    $competitorPosition,
                    $status,
                    $latest?->checked_at?->toISOString()
                );
            }
        }

        $total = count($results);
        $page = $page > 0 ? $page : 1;
        $perPage = $perPage > 0 ? $perPage : 20;
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($results, $offset, $perPage);

        return [
            'results' => array_values($paged),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ],
        ];
    }

    /**
     * Get rankings for a specific competitor (latest per keyword).
     */
    public function getCompetitorRankings(int $userId, int $competitorId, int $page, int $perPage): array
    {
        $competitor = Competitor::where('id', $competitorId)
            ->where('user_id', $userId)
            ->first();

        if (!$competitor) {
            return ['competitor' => null, 'rankings' => [], 'meta' => ['total' => 0, 'page' => $page, 'perPage' => $perPage]];
        }

        $rankings = CompetitorRanking::where('competitor_id', $competitorId)
            ->with('keyword')
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy('keyword_id')
            ->map(fn (Collection $items) => $items->first())
            ->values();

        $total = $rankings->count();
        $page = $page > 0 ? $page : 1;
        $perPage = $perPage > 0 ? $perPage : 20;
        $offset = ($page - 1) * $perPage;

        $paged = $rankings->slice($offset, $perPage)->values();

        return [
            'competitor' => $competitor,
            'rankings' => $paged->map(function (CompetitorRanking $ranking) {
                return [
                    'keywordId' => $ranking->keyword_id,
                    'keyword' => $ranking->keyword?->keyword,
                    'position' => $ranking->position,
                    'url' => $ranking->url,
                    'checkedAt' => $ranking->checked_at?->toISOString(),
                ];
            })->toArray(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ],
        ];
    }

    /**
     * Get keyword gaps for a competitor (competitor ranks, user doesn't).
     */
    public function getCompetitorGaps(int $userId, int $competitorId, int $page, int $perPage): array
    {
        $competitor = Competitor::where('id', $competitorId)
            ->where('user_id', $userId)
            ->first();

        if (!$competitor) {
            return ['competitor' => null, 'gaps' => [], 'meta' => ['total' => 0, 'page' => $page, 'perPage' => $perPage]];
        }

        $rankings = CompetitorRanking::where('competitor_id', $competitorId)
            ->with('keyword')
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy('keyword_id')
            ->map(fn (Collection $items) => $items->first())
            ->values();

        $keywordIds = $rankings->pluck('keyword_id')->all();

        $userPositions = Ranking::whereIn('keyword_id', $keywordIds)
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy('keyword_id')
            ->map(fn (Collection $items) => $items->first()?->position);

        $gaps = $rankings->filter(function (CompetitorRanking $ranking) use ($userPositions) {
            $userPosition = $userPositions->get($ranking->keyword_id);
            return $userPosition === null;
        })->values();

        $total = $gaps->count();
        $page = $page > 0 ? $page : 1;
        $perPage = $perPage > 0 ? $perPage : 20;
        $offset = ($page - 1) * $perPage;

        $paged = $gaps->slice($offset, $perPage)->values();

        return [
            'competitor' => $competitor,
            'gaps' => $paged->map(function (CompetitorRanking $ranking) {
                return [
                    'keywordId' => $ranking->keyword_id,
                    'keyword' => $ranking->keyword?->keyword,
                    'competitorPosition' => $ranking->position,
                    'url' => $ranking->url,
                    'checkedAt' => $ranking->checked_at?->toISOString(),
                ];
            })->toArray(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ],
        ];
    }

    /**
     * Get historical comparison data for a keyword.
     */
    public function getHistory(int $userId, int $keywordId, int $days, ?int $competitorId = null): array
    {
        $keyword = Keyword::where('id', $keywordId)
            ->where('user_id', $userId)
            ->first();

        if (!$keyword) {
            return ['keyword' => null, 'history' => []];
        }

        $from = now()->subDays($days);

        $userHistory = Ranking::where('keyword_id', $keywordId)
            ->where('checked_at', '>=', $from)
            ->orderBy('checked_at', 'asc')
            ->get();

        $competitorHistory = CompetitorRanking::where('keyword_id', $keywordId)
            ->when($competitorId, fn ($q) => $q->where('competitor_id', $competitorId))
            ->where('checked_at', '>=', $from)
            ->orderBy('checked_at', 'asc')
            ->get();

        return [
            'keyword' => [
                'id' => $keyword->id,
                'keyword' => $keyword->keyword,
            ],
            'history' => [
                'user' => $userHistory->map(fn (Ranking $ranking) => [
                    'position' => $ranking->position,
                    'checkedAt' => $ranking->checked_at?->toISOString(),
                ])->toArray(),
                'competitors' => $competitorHistory->map(fn (CompetitorRanking $ranking) => [
                    'competitorId' => $ranking->competitor_id,
                    'position' => $ranking->position,
                    'checkedAt' => $ranking->checked_at?->toISOString(),
                ])->toArray(),
            ],
        ];
    }

    protected function incrementSummary(array &$summary, string $status): void
    {
        switch ($status) {
            case 'im-winning':
                $summary['imWinning']++;
                break;
            case 'theyre-winning':
                $summary['theyreWinning']++;
                break;
            case 'both-rank':
                $summary['bothRank']++;
                break;
            case 'only-i-rank':
                $summary['onlyIRank']++;
                break;
            case 'gap':
                $summary['onlyTheyRank']++;
                break;
        }
    }

    protected function formatComparisonRow(
        Keyword $keyword,
        ?Competitor $competitor,
        ?int $userPosition,
        ?int $competitorPosition,
        string $status,
        ?string $checkedAt
    ): array {
        return [
            'keywordId' => $keyword->id,
            'keyword' => $keyword->keyword,
            'competitorId' => $competitor?->id,
            'competitorDomain' => $competitor?->domain,
            'userPosition' => $userPosition,
            'competitorPosition' => $competitorPosition,
            'positionDiff' => self::calculatePositionDiff($userPosition, $competitorPosition),
            'status' => $status,
            'checkedAt' => $checkedAt,
        ];
    }
}
