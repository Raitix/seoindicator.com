<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Repositories\RankingRepository;
use App\Services\Queue\JobQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Ranking Controller for SPEC-001-5.
 */
class RankingController extends Controller
{
    protected RankingRepository $rankingRepository;
    protected JobQueue $jobQueue;

    public function __construct(RankingRepository $rankingRepository, JobQueue $jobQueue)
    {
        $this->rankingRepository = $rankingRepository;
        $this->jobQueue = $jobQueue;
    }

    /**
     * Get ranking history for a keyword.
     */
    public function history(int $id): JsonResponse
    {
        $keyword = Keyword::find($id);
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        $from = request()->query('from');
        $to = request()->query('to');
        $limit = (int) request()->query('limit', 100);
        $limit = $limit > 0 ? $limit : 100;

        $start = $from ? Carbon::parse($from) : now()->subDays(30);
        $end = $to ? Carbon::parse($to) : now();

        $history = $this->rankingRepository
            ->findByKeywordAndDateRange($keyword->id, $start, $end)
            ->take($limit)
            ->map(function ($ranking) {
                return [
                    'position' => $ranking->position,
                    'urlFound' => $ranking->url_found,
                    'checkedAt' => $ranking->checked_at?->toISOString(),
                ];
            })
            ->values();

        $latest = $this->rankingRepository->findLatestByKeyword($keyword->id);
        $stats = $this->rankingRepository->getStats($keyword->id);

        return response()->json([
            'success' => true,
            'data' => [
                'keyword' => [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                ],
                'history' => $history,
                'summary' => [
                    'currentPosition' => $latest?->position,
                    'bestPosition' => $stats['bestPosition'],
                    'worstPosition' => $stats['worstPosition'],
                    'averagePosition' => $stats['averagePosition'],
                    'totalChecks' => $stats['totalChecks'],
                ],
            ],
        ]);
    }

    /**
     * Trigger a manual rank check.
     */
    public function check(int $id): JsonResponse
    {
        $keyword = Keyword::find($id);
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        $jobId = $this->jobQueue->addJob([
            'type' => 'single',
            'keywordId' => $keyword->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'jobId' => $jobId,
                'status' => 'queued',
            ],
        ], 202);
    }
}
