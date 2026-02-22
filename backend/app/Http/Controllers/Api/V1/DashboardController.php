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

            $rankings = $keywordIds
                ? Ranking::whereIn('keyword_id', $keywordIds)
                    ->orderBy('checked_at', 'desc')
                    ->get()
                    ->groupBy('keyword_id')
                : collect();

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
                $keywordRankings = $rankings->get($keyword->id, collect());
                $latest = $keywordRankings->get(0);
                $previous = $keywordRankings->get(1);

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
}
