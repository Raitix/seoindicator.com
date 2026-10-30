<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Services\Audit\PublicAuditAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for audit analytics API.
 *
 * TASK-33-28: Implement Public Audit Analytics
 * SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
 */
class AuditAnalyticsController extends Controller
{
    /**
     * @var PublicAuditAnalyticsService
     */
    protected PublicAuditAnalyticsService $analyticsService;

    /**
     * Create a new controller instance.
     *
     * @param PublicAuditAnalyticsService $analyticsService
     */
    public function __construct(PublicAuditAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get analytics for a specific audit.
     *
     * @param Request $request
     * @param int $id Audit ID
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // Validate audit exists
        $audit = Audit::find($id);
        if (!$audit) {
            return response()->json([
                'success' => false,
                'error' => 'Audit not found',
                'code' => 404,
            ], 404);
        }

        // Authorize: user must own the audit (via site relationship) or be admin
        $user = $request->user();
        if (!$this->canAccess($user, $audit)) {
            return response()->json([
                'success' => false,
                'error' => 'You do not have permission to access this audit',
                'code' => 403,
            ], 403);
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'start' => ['nullable', 'date', 'before_or_equal:end'],
            'end' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid date parameters',
                'details' => $validator->errors(),
                'code' => 400,
            ], 400);
        }

        // Check for cache bypass
        $nocache = $request->boolean('nocache');

        // Get analytics
        $analytics = $this->analyticsService->getAnalytics(
            $id,
            $request->input('start'),
            $request->input('end'),
            $nocache // bypass cache if requested
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Check if user can access the audit analytics.
     *
     * @param mixed $user
     * @param Audit $audit
     * @return bool
     */
    protected function canAccess($user, Audit $audit): bool
    {
        if (!$user) {
            return false;
        }

        // Admin or super admin can access any audit
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        // Check if user owns the site that owns the audit
        $site = $audit->site;
        if (!$site) {
            return false;
        }

        return $site->user_id === $user->id;
    }
}
