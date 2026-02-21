<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\Keyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'gap' => 0,
            'onlyIRank' => 0,
            'onlyTheyRank' => 0,
        ];

        $keywords = Keyword::where('user_id', $userId)
            ->with(['competitorRankings'])
            ->get();

        foreach ($keywords as $keyword) {
            $userPosition = $keyword->rankings->first()->position ?? null;

            foreach ($keyword->competitorRankings as $ranking) {
                $competitorPosition = $ranking->position ?? null;
                $status = self::determineStatus($userPosition, $competitorPosition);

                $summary[$status]++;
            }

            $summary['totalKeywords']++;
        }

        return $summary;
    }
}