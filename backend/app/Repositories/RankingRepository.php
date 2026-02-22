<?php

namespace App\Repositories;

use App\Models\Ranking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Ranking Repository for SPEC-001-3.
 */
class RankingRepository
{
    /**
     * Find latest ranking for a keyword.
     */
    public function findLatestByKeyword(int $keywordId): ?Ranking
    {
        return Ranking::where('keyword_id', $keywordId)
            ->orderBy('checked_at', 'desc')
            ->first();
    }

    /**
     * Find rankings by keyword and date range.
     */
    public function findByKeywordAndDateRange(int $keywordId, Carbon $start, Carbon $end): Collection
    {
        return Ranking::where('keyword_id', $keywordId)
            ->whereBetween('checked_at', [$start, $end])
            ->orderBy('checked_at', 'asc')
            ->get();
    }

    /**
     * Create a ranking entry.
     */
    public function create(array $data): Ranking
    {
        return Ranking::create($data);
    }

    /**
     * Get ranking stats for a keyword.
     */
    public function getStats(int $keywordId): array
    {
        $baseQuery = Ranking::where('keyword_id', $keywordId);
        $totalChecks = (clone $baseQuery)->count();

        $positionsQuery = (clone $baseQuery)->whereNotNull('position');

        $averagePosition = $positionsQuery->avg('position');
        $bestPosition = (clone $positionsQuery)->min('position');
        $worstPosition = (clone $positionsQuery)->max('position');

        return [
            'averagePosition' => $averagePosition !== null ? (float) $averagePosition : null,
            'bestPosition' => $bestPosition !== null ? (int) $bestPosition : null,
            'worstPosition' => $worstPosition !== null ? (int) $worstPosition : null,
            'totalChecks' => $totalChecks,
        ];
    }
}
