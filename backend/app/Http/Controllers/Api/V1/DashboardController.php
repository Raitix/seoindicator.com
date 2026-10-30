<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
// (removed unused Arr import)
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Controller for SPEC-001-6.
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard summary for a site.
     */
    public function index(int $siteId): JsonResponse
    {
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        $cacheKey = "dashboard:{$siteId}";
        $data = Cache::remember($cacheKey, 300, function () use ($siteId) {
            $keywords = Keyword::where('site_id', $siteId)->get();
            $keywordIds = $keywords->pluck('id')->all();

            // Use window function to fetch only latest 2 rankings per keyword
            // This avoids loading entire ranking history into memory
            $rankings = $keywordIds
                ? $this->getLatestRankingsForKeywords($keywordIds)
                : [];

            $totalKeywords = $keywords->count();
            $activeKeywords = $keywords->where('status', 'active')->count();

            $rankedKeywords = 0;
            $notRankedKeywords = 0;
            $positions = [];

            $positionChanges = [
                'improved' => 0,
                'declined' => 0,
                'unchanged' => 0,
                'new' => 0,
            ];

            $alerts = [];
            $topKeywords = [];

            foreach ($keywords as $keyword) {
                $keywordRankings = $rankings[$keyword->id] ?? [];
                $latest = $keywordRankings[0] ?? null;
                $previous = $keywordRankings[1] ?? null;

                if ($latest && $latest->position !== null) {
                    $rankedKeywords++;
                    $positions[] = $latest->position;
                } else {
                    $notRankedKeywords++;
                }

                if ($latest) {
                    if (!$previous) {
                        $positionChanges['new']++;
                    } else {
                        $currentPosition = $latest->position;
                        $previousPosition = $previous->position;

                        if ($currentPosition === null && $previousPosition === null) {
                            $positionChanges['unchanged']++;
                        } elseif ($currentPosition === null && $previousPosition !== null) {
                            $positionChanges['declined']++;
                        } elseif ($currentPosition !== null && $previousPosition === null) {
                            $positionChanges['improved']++;
                        } elseif ($currentPosition < $previousPosition) {
                            $positionChanges['improved']++;
                        } elseif ($currentPosition > $previousPosition) {
                            $positionChanges['declined']++;
                        } else {
                            $positionChanges['unchanged']++;
                        }

                        if ($currentPosition !== null && $previousPosition !== null) {
                            $drop = $currentPosition - $previousPosition;
                            if ($drop >= 5) {
                                $alerts[] = [
                                    'type' => 'dropped',
                                    'keyword' => $keyword->keyword,
                                    'previousPosition' => $previousPosition,
                                    'currentPosition' => $currentPosition,
                                    'change' => $drop,
                                ];
                            }
                        }
                    }

                    if ($latest->position !== null) {
                        $topKeywords[] = [
                            'keyword' => $keyword->keyword,
                            'position' => $latest->position,
                            'change' => $previous ? $latest->position - $previous->position : null,
                        ];
                    }
                }
            }

            $averagePosition = count($positions) > 0
                ? array_sum($positions) / count($positions)
                : null;

            $topKeywords = collect($topKeywords)
                ->sortBy('position')
                ->take(5)
                ->values()
                ->all();

            return [
                'totalKeywords' => $totalKeywords,
                'activeKeywords' => $activeKeywords,
                'rankedKeywords' => $rankedKeywords,
                'notRankedKeywords' => $notRankedKeywords,
                'averagePosition' => $averagePosition !== null ? (float) $averagePosition : null,
                'positionChanges' => $positionChanges,
                'topKeywords' => $topKeywords,
                'alerts' => array_values($alerts),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Batch fetch the latest 2 rankings for multiple keywords.
     * Uses database-agnostic approach for MySQL and SQLite compatibility.
     * This avoids loading the entire ranking history into memory.
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsForKeywords(array $keywordIds): array
    {
        if (empty($keywordIds)) {
            return [];
        }

        $driver = \DB::getDriverName();

        // Use window function for MySQL (efficient in production)
        if ($driver === 'mysql') {
            return $this->getLatestRankingsWithWindowFunction($keywordIds);
        }

        // Use Eloquent for SQLite (compatible with tests)
        return $this->getLatestRankingsWithEloquent($keywordIds);
    }

    /**
     * Get latest rankings using window function (MySQL).
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsWithWindowFunction(array $keywordIds): array
    {
        $rankings = \DB::select("
            SELECT * FROM (
                SELECT 
                    id, keyword_id, position, url, checked_at, created_at, updated_at,
                    ROW_NUMBER() OVER (PARTITION BY keyword_id ORDER BY checked_at DESC) as rn
                FROM rankings
                WHERE keyword_id IN (" . implode(',', array_map('intval', $keywordIds)) . ")
            ) ranked
            WHERE rn <= 2
        ");

        $grouped = [];
        foreach ($keywordIds as $keywordId) {
            $grouped[$keywordId] = [];
        }

        foreach ($rankings as $row) {
            $ranking = new Ranking();
            $ranking->id = $row->id;
            $ranking->keyword_id = $row->keyword_id;
            $ranking->position = $row->position;
            $ranking->url = $row->url;
            $ranking->checked_at = $row->checked_at;
            $ranking->created_at = $row->created_at;
            $ranking->updated_at = $row->updated_at;

            $grouped[$row->keyword_id][] = $ranking;
        }

        return $grouped;
    }

    /**
     * Get latest rankings using Eloquent (SQLite/tests).
     *
     * @param array $keywordIds
     * @return array<int, array{0:Ranking|null,1:Ranking|null}>
     */
    protected function getLatestRankingsWithEloquent(array $keywordIds): array
    {
        // Load rankings ordered by keyword and date
        $rankings = Ranking::whereIn('keyword_id', $keywordIds)
            ->orderBy('keyword_id')
            ->orderBy('checked_at', 'desc')
            ->get();

        // Group and take only first 2 per keyword
        $grouped = [];
        foreach ($keywordIds as $keywordId) {
            $grouped[$keywordId] = [];
        }

        foreach ($rankings as $ranking) {
            if (count($grouped[$ranking->keyword_id]) < 2) {
                $grouped[$ranking->keyword_id][] = $ranking;
            }
        }

        return $grouped;
    }
}
