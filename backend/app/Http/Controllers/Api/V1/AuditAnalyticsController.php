<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Services\Audit\PublicAuditAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller for audit analytics API.
 *
 * TASK-33-28: Implement Public Audit Analytics
 *
 * Provides analytics aggregation for public audits including
 * views, leads, and badge metrics.
 */
class AuditAnalyticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private PublicAuditAnalyticsService $analyticsService
    ) {}

    /**
     * Get analytics for a specific audit.
     *
     * @param Request $request
     * @param int $id Audit ID
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // Find audit
        $audit = Audit::find($id);

        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
                'code' => 404,
            ], 404);
        }

        // Authorize - user must own the audit (via site) or be admin
        $user = $request->user();
        
        if (!$this->canViewAnalytics($user, $audit)) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have permission to view this audit analytics',
                'code' => 403,
            ], 403);
        }

        // Validate query parameters
        $validated = $request->validate([
            'start' => ['nullable', 'date', 'before_or_equal:end'],
            'end' => ['nullable', 'date', 'before_or_equal:today'],
            'nocache' => ['nullable', 'boolean'],
        ], [
            'start.date' => 'The start date must be a valid date.',
            'start.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end.date' => 'The end date must be a valid date.',
            'end.before_or_equal' => 'The end date cannot be in the future.',
        ]);

        // Get date parameters
        $start = $validated['start'] ?? null;
        $end = $validated['end'] ?? null;

        // Bypass cache if requested
        if (!empty($validated['nocache'])) {
            // Clear cache for this query
            $this->clearCache($id, $start, $end);
        }

        // Get analytics
        $data = $this->analyticsService->getAnalytics($id, $start, $end);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Check if user can view analytics for the audit.
     *
     * @param mixed $user
     * @param Audit $audit
     * @return bool
     */
    private function canViewAnalytics($user, Audit $audit): bool
    {
        // Check if user is admin or super admin
        if (in_array($user->role ?? '', ['admin', 'super_admin'])) {
            return true;
        }

        // Check if user owns the site that owns the audit
        $site = $audit->site;
        
        if (!$site) {
            return false;
        }

        return $site->user_id === $user->id;
    }

    /**
     * Clear cache for specific analytics query.
     *
     * @param int $auditId
     * @param string|null $start
     * @param string|null $end
     * @return void
     */
    private function clearCache(int $auditId, ?string $start, ?string $end): void
    {
        // Calculate cache key
        $now = now();
        $startDate = $start ? \Carbon\Carbon::parse($start)->toDateString() : $now->copy()->subDays(30)->toDateString();
        $endDate = $end ? \Carbon\Carbon::parse($end)->toDateString() : $now->toDateString();
        
        $cacheKey = "audit_analytics:{$auditId}:{$startDate}:{$endDate}";
        
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
}
