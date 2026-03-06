<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BulkUpdateIssuesRequest;
use App\Http\Requests\Api\V1\UpdateIssueStatusRequest;
use App\Models\Audit;
use App\Models\AuditIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuditIssueController - Issue Management API
 *
 * TASK-14-14: Provides API endpoints for listing audit issues and updating their status.
 *
 * @see TASK-14-14
 */
class AuditIssueController extends Controller
{
    /**
     * List issues for an audit with filtering, sorting, and pagination.
     *
     * GET /api/v1/audits/{auditId}/issues
     */
    public function index(Request $request, int $auditId): JsonResponse
    {
        $audit = Audit::find($auditId);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'message' => 'Audit not found',
            ], 404);
        }

        // Authorization: user must own the site associated with the audit
        if ($audit->site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $query = AuditIssue::where('audit_id', $auditId)
            ->with('issueType');

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('issueType', function ($q) use ($request) {
                $q->where('category', $request->input('category'));
            });
        }

        // Search in URL or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Handle severity sorting (convert to integer for proper ordering)
        if ($sortBy === 'severity') {
            $severityOrder = [
                'critical' => 1,
                'warning' => 2,
                'notice' => 3,
            ];
            $query->orderByRaw(
                "CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END " . ($sortOrder === 'asc' ? 'ASC' : 'DESC')
            );
        } else {
            $allowedSortFields = ['created_at', 'url', 'severity'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }
        }

        // Pagination with capped per_page
        $perPage = min((int) $request->input('per_page', 25), 100);

        $issues = $query->paginate($perPage);

        // Get severity and status counts for meta
        $severityCounts = AuditIssue::where('audit_id', $auditId)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $statusCounts = AuditIssue::where('audit_id', $auditId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $issues->map(fn ($issue) => $this->formatIssueListItem($issue)),
            'meta' => [
                'current_page' => $issues->currentPage(),
                'per_page' => $issues->perPage(),
                'total' => $issues->total(),
                'severity_counts' => [
                    'critical' => $severityCounts['critical'] ?? 0,
                    'warning' => $severityCounts['warning'] ?? 0,
                    'notice' => $severityCounts['notice'] ?? 0,
                ],
                'status_counts' => [
                    'open' => $statusCounts['open'] ?? 0,
                    'fixed' => $statusCounts['fixed'] ?? 0,
                    'ignored' => $statusCounts['ignored'] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Get a single issue with detailed information.
     *
     * GET /api/v1/issues/{issueId}
     */
    public function show(Request $request, int $issueId): JsonResponse
    {
        $issue = AuditIssue::with(['issueType', 'audit'])->find($issueId);

        if (!$issue) {
            return response()->json([
                'success' => false,
                'message' => 'Issue not found',
            ], 404);
        }

        // Authorization: user must own the site associated with the issue's audit
        if ($issue->audit->site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatIssueDetail($issue),
        ]);
    }

    /**
     * Update an issue's status.
     *
     * PATCH /api/v1/issues/{issueId}
     */
    public function update(UpdateIssueStatusRequest $request, int $issueId): JsonResponse
    {
        $issue = AuditIssue::with('audit.site')->find($issueId);

        if (!$issue) {
            return response()->json([
                'success' => false,
                'message' => 'Issue not found',
            ], 404);
        }

        // Authorization: user must own the site
        if ($issue->audit->site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        // Business rule: issues can only be updated for completed audits
        if ($issue->audit->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Issues can only be updated for completed audits',
            ], 403);
        }

        $issue->status = $request->input('status');
        $issue->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $issue->id,
                'status' => $issue->status,
                'updated_at' => $issue->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Bulk update issues' status.
     *
     * PATCH /api/v1/audits/{auditId}/issues/bulk
     */
    public function bulkUpdate(BulkUpdateIssuesRequest $request, int $auditId): JsonResponse
    {
        $audit = Audit::with('site')->find($auditId);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'message' => 'Audit not found',
            ], 404);
        }

        // Authorization: user must own the site
        if ($audit->site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        // Business rule: issues can only be updated for completed audits
        if ($audit->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Issues can only be updated for completed audits',
            ], 403);
        }

        $issueIds = $request->input('issue_ids');
        $status = $request->input('status');

        // Only update issues belonging to this audit (security: prevents cross-audit updates)
        $updatedCount = AuditIssue::where('audit_id', $auditId)
            ->whereIn('id', $issueIds)
            ->update(['status' => $status]);

        $message = $updatedCount === 1
            ? "1 issue marked as {$status}"
            : "{$updatedCount} issues marked as {$status}";

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updatedCount,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Format issue for list response.
     */
    private function formatIssueListItem(AuditIssue $issue): array
    {
        return [
            'id' => $issue->id,
            'url' => $issue->url,
            'issue_type' => [
                'id' => $issue->issueType->id,
                'name' => $issue->issueType->name,
                'category' => $issue->issueType->category,
            ],
            'severity' => $issue->severity,
            'description' => $issue->description,
            'recommendation' => $issue->recommendation,
            'status' => $issue->status,
            'created_at' => $issue->created_at->toIso8601String(),
        ];
    }

    /**
     * Format issue for detailed response.
     */
    private function formatIssueDetail(AuditIssue $issue): array
    {
        $categories = \App\Models\IssueType::categories();

        return [
            'id' => $issue->id,
            'url' => $issue->url,
            'issue_type' => [
                'id' => $issue->issueType->id,
                'name' => $issue->issueType->name,
                'category' => $issue->issueType->category,
                'category_label' => $categories[$issue->issueType->category] ?? $issue->issueType->category,
            ],
            'severity' => $issue->severity,
            'description' => $issue->description,
            'recommendation' => $issue->recommendation,
            'status' => $issue->status,
            'created_at' => $issue->created_at->toIso8601String(),
            'audit' => [
                'id' => $issue->audit->id,
                'status' => $issue->audit->status,
                'pages_crawled' => $issue->audit->pages_crawled,
            ],
        ];
    }
}
