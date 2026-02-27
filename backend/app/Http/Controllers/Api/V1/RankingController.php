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
 * 
 * Security: All endpoints require authentication and validate
 * user ownership of keywords (IDOR protection).
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
     * Verify the authenticated user owns the keyword.
     * 
     * @param Keyword $keyword
     * @return bool
     */
    protected function userOwnsKeyword(Keyword $keyword): bool
    {
        $userId = auth()->id();
        
        // Check direct ownership via user_id
        if ($keyword->user_id === $userId) {
            return true;
        }
        
        // Check ownership via site relationship
        if ($keyword->site && $keyword->site->user_id === $userId) {
            return true;
        }
        
        return false;
    }

    /**
     * Get ranking history for a keyword.
     */
    public function history(int $id): JsonResponse
    {
        $keyword = Keyword::with('site')->find($id);
        
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        // IDOR protection: verify user owns the keyword
        if (!$this->userOwnsKeyword($keyword)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ], 403);
        }

        $keyword->last_viewed_at = now();
        $keyword->save();

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
        $keyword = Keyword::with('site')->find($id);
        
        if (!$keyword) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Keyword not found.',
                ],
            ], 404);
        }

        // IDOR protection: verify user owns the keyword
        if (!$this->userOwnsKeyword($keyword)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this keyword.',
                ],
            ], 403);
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
