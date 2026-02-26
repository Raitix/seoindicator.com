<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\KeywordSuggestion;
use App\Models\Site;
use App\Repositories\KeywordSuggestionRepository;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API Controller for keyword suggestions.
 *
 * Handles CRUD operations, generation, tracking, and dismissal
 * of keyword suggestions for SEO discovery.
 *
 * @see TASK-5-7, TASK-5-8, TASK-5-9
 * @see SPEC-005
 */
class SuggestionController extends Controller
{
    /**
     * Create a new SuggestionController instance.
     */
    public function __construct(
        protected KeywordSuggestionRepository $repository,
        protected SuggestionService $suggestionService
    ) {
    }

    /**
     * List suggestions for a site.
     *
     * @param int $siteId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(int $siteId, Request $request): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('view', $site);

        $filters = [
            'page' => $request->input('page', 1),
            'perPage' => $request->input('perPage', 20),
            'source' => $request->input('source'),
            'excludeDismissed' => $request->boolean('includeDismissed') ? false : true,
            'includeTracking' => $request->boolean('includeTracking'),
            'minScore' => $request->input('minScore'),
            'maxScore' => $request->input('maxScore'),
            'sort' => $request->input('sort', 'opportunity_score'),
            'order' => $request->input('order', 'desc'),
        ];

        $paginator = $this->repository->getPaginated($siteId, $filters);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * Generate new suggestions.
     *
     * @param int $siteId
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(int $siteId, Request $request): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('update', $site);

        $request->validate([
            'seedKeyword' => 'required|string|max:255',
            'sources' => 'required|array|min:1',
            'sources.*' => 'string|in:question,suggest,related,competitor',
            'maxResults' => 'nullable|integer|min:1|max:100',
        ]);

        $seedKeyword = $request->input('seedKeyword');
        $sources = $request->input('sources');
        $maxResults = $request->input('maxResults', 50);

        // Generate suggestions synchronously (could be queued for production)
        $count = $this->suggestionService->generateSuggestions(
            $siteId,
            $seedKeyword,
            $sources,
            $maxResults
        );

        $jobId = 'suggest-' . Str::random(8);

        return response()->json([
            'success' => true,
            'data' => [
                'jobId' => $jobId,
                'status' => 'queued',
                'count' => $count,
            ],
        ], 202);
    }

    /**
     * Track a suggestion (convert to keyword).
     *
     * @param int $siteId
     * @param int $suggestionId
     * @return JsonResponse
     */
    public function track(int $siteId, int $suggestionId): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('update', $site);

        $suggestion = KeywordSuggestion::where('site_id', $siteId)
            ->where('id', $suggestionId)
            ->firstOrFail();

        $statusCode = 200;

        DB::transaction(function () use ($suggestion, $site, &$statusCode) {
            // Check if already tracking
            if ($suggestion->is_tracking) {
                // Already tracking, just return success
                return;
            }

            // Create or find keyword
            $keyword = Keyword::firstOrCreate(
                [
                    'site_id' => $site->id,
                    'keyword' => $suggestion->keyword,
                ],
                [
                    'status' => 'active',
                ]
            );

            // Mark suggestion as tracking
            $suggestion->update(['is_tracking' => true]);
            $statusCode = 201;
        });

        // Refresh suggestion
        $suggestion->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'keyword' => [
                    'id' => $suggestion->id,
                    'keyword' => $suggestion->keyword,
                    'status' => 'active',
                ],
                'suggestion' => [
                    'id' => $suggestion->id,
                    'is_tracking' => true,
                ],
            ],
        ], $statusCode);
    }

    /**
     * Bulk track multiple suggestions.
     *
     * @param int $siteId
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkTrack(int $siteId, Request $request): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('update', $site);

        $request->validate([
            'suggestionIds' => 'required|array|min:1|max:100',
            'suggestionIds.*' => 'integer|exists:keyword_suggestions,id',
        ]);

        $suggestionIds = $request->input('suggestionIds');

        $added = [];
        $skipped = [];

        DB::transaction(function () use ($suggestionIds, $siteId, &$added, &$skipped) {
            $suggestions = KeywordSuggestion::where('site_id', $siteId)
                ->whereIn('id', $suggestionIds)
                ->get();

            foreach ($suggestions as $suggestion) {
                // Check if already tracking
                if ($suggestion->is_tracking) {
                    $skipped[] = [
                        'keyword' => $suggestion->keyword,
                        'reason' => 'Already tracking',
                    ];
                    continue;
                }

                // Create keyword
                Keyword::firstOrCreate(
                    [
                        'site_id' => $siteId,
                        'keyword' => $suggestion->keyword,
                    ],
                    [
                        'status' => 'active',
                    ]
                );

                // Mark as tracking
                $suggestion->update(['is_tracking' => true]);

                $added[] = [
                    'keyword' => $suggestion->keyword,
                    'keywordId' => $suggestion->id,
                ];
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'added' => $added,
                'skipped' => $skipped,
            ],
        ], 201);
    }

    /**
     * Dismiss a suggestion.
     *
     * @param int $siteId
     * @param int $suggestionId
     * @return JsonResponse
     */
    public function dismiss(int $siteId, int $suggestionId): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('update', $site);

        $suggestion = KeywordSuggestion::where('site_id', $siteId)
            ->where('id', $suggestionId)
            ->firstOrFail();

        $suggestion->update(['is_dismissed' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $suggestion->id,
                'is_dismissed' => true,
            ],
        ]);
    }

    /**
     * Export suggestions.
     *
     * @param int $siteId
     * @param Request $request
     * @return Response
     */
    public function export(int $siteId, Request $request): Response
    {
        $site = Site::findOrFail($siteId);

        // Authorization
        $this->authorize('view', $site);

        $format = $request->input('format', 'csv');

        $query = KeywordSuggestion::where('site_id', $siteId)
            ->where('is_dismissed', false)
            ->orderBy('opportunity_score', 'desc');

        if ($request->input('source')) {
            $query->where('source', $request->input('source'));
        }

        $suggestions = $query->get();

        // Generate CSV
        $csv = "Keyword,Source,Tracking Status,Opportunity Score\n";
        foreach ($suggestions as $s) {
            $csv .= sprintf(
                "%s,%s,%s,%d\n",
                $s->keyword,
                $s->source,
                $s->tracking_status,
                $s->opportunity_score ?? 0
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suggestions.csv"',
        ]);
    }
}
