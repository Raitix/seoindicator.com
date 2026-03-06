<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAuditRequest;
use App\Models\Audit;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuditController extends Controller
{
    /**
     * List audits for a site.
     *
     * GET /api/v1/sites/{site}/audits
     */
    public function index(Request $request, Site $site): JsonResponse
    {
        // Authorization: user must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $query = Audit::where('site_id', $site->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        // Pagination with capped per_page
        $perPage = min($request->input('per_page', 15), 100);

        $audits = $query->paginate($perPage);

        return response()->json([
            'data' => $audits->map(function ($audit) {
                return $this->formatAuditListItem($audit);
            }),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'last_page' => $audits->lastPage(),
            ],
        ]);
    }

    /**
     * Trigger a new audit.
     *
     * POST /api/v1/sites/{site}/audits
     */
    public function store(StoreAuditRequest $request, Site $site): JsonResponse
    {
        // Authorization: user must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Check for existing running/pending audit
        $existingAudit = Audit::where('site_id', $site->id)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if ($existingAudit) {
            return response()->json([
                'error' => 'An audit is already running for this site',
            ], 409);
        }

        // Create the audit
        $options = $request->getAuditOptions();
        $audit = Audit::create([
            'site_id' => $site->id,
            'max_pages' => $options['max_pages'],
            'max_depth' => $options['max_depth'],
            'user_agent' => $options['user_agent'],
            'status' => 'pending',
        ]);

        // Dispatch the ProcessCrawlJob (mocked in tests)
        // In production, this would be:
        // ProcessCrawlJob::dispatch($audit->id, $site->url);
        // For now, we'll use a job dispatch that can be faked in tests
        if (class_exists(\App\Jobs\ProcessCrawlJob::class)) {
            \App\Jobs\ProcessCrawlJob::dispatch($audit->id, $site->url);
        }

        return response()->json($this->formatAudit($audit), 201);
    }

    /**
     * Get audit status.
     *
     * GET /api/v1/audits/{audit}
     */
    public function show(Request $request, Audit $audit): JsonResponse
    {
        // Authorization: user must own the site via the audit
        if ($audit->site->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $response = $this->formatAudit($audit);

        // Add progress data from cache for running audits
        if ($audit->status === 'running') {
            $progress = Cache::get("audit:{$audit->id}:progress");
            if ($progress) {
                $response['progress'] = [
                    'current_url' => $progress['current_url'] ?? null,
                    'elapsed_seconds' => $progress['elapsed_seconds'] ?? 0,
                ];
                $response['pages_crawled'] = $progress['pages_crawled'] ?? $audit->pages_crawled;
                $response['issues_found'] = $progress['issues_found'] ?? $audit->issues_found;
            }

            // Calculate estimated completion
            if ($audit->started_at) {
                // Use elapsed_seconds from cache if available, otherwise calculate from started_at
                $elapsed = $progress['elapsed_seconds'] ?? abs(now()->diffInSeconds($audit->started_at));
                $estimatedPages = $audit->max_pages;
                $pagesCrawled = $response['pages_crawled'] ?? $audit->pages_crawled;
                
                if ($pagesCrawled > 0 && $elapsed > 0) {
                    $rate = $pagesCrawled / $elapsed; // pages per second
                    $remainingPages = $estimatedPages - $pagesCrawled;
                    $estimatedSeconds = $remainingPages > 0 ? (int) ($remainingPages / $rate) : 0;
                    $response['estimated_completion'] = now()->addSeconds($estimatedSeconds)->toIso8601String();
                }
            }
        }

        return response()->json($response);
    }

    /**
     * Cancel a running audit.
     *
     * DELETE /api/v1/audits/{audit}
     */
    public function destroy(Request $request, Audit $audit): JsonResponse
    {
        // Authorization: user must own the site via the audit
        if ($audit->site->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Check if audit can be cancelled
        if (!$audit->canBeCancelled()) {
            $statusMessages = [
                'completed' => 'Cannot cancel a completed audit',
                'failed' => 'Cannot cancel a failed audit',
                'cancelled' => 'Audit is already cancelled',
            ];
            
            return response()->json([
                'error' => $statusMessages[$audit->status] ?? 'Cannot cancel this audit',
            ], 400);
        }

        $audit->markAsCancelled();

        return response()->json([
            'id' => $audit->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Format audit for list response.
     */
    private function formatAuditListItem(Audit $audit): array
    {
        return [
            'id' => $audit->id,
            'status' => $audit->status,
            'pages_crawled' => $audit->pages_crawled,
            'issues_found' => $audit->issues_found,
            'completed_at' => $audit->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Format audit for detailed response.
     */
    private function formatAudit(Audit $audit): array
    {
        return [
            'id' => $audit->id,
            'site_id' => $audit->site_id,
            'status' => $audit->status,
            'max_pages' => $audit->max_pages,
            'max_depth' => $audit->max_depth,
            'user_agent' => $audit->user_agent,
            'pages_crawled' => $audit->pages_crawled,
            'issues_found' => $audit->issues_found,
            'started_at' => $audit->started_at?->toIso8601String(),
            'completed_at' => $audit->completed_at?->toIso8601String(),
            'created_at' => $audit->created_at->toIso8601String(),
        ];
    }
}
