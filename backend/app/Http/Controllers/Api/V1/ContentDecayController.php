<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentDecay\UpdateProjectSettingsRequest;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Content Decay Controller for SPEC-036.
 *
 * Main endpoints for content decay summary, manual detection runs, and settings.
 */
class ContentDecayController extends Controller
{
    /**
     * GET /api/v1/sites/{siteId}/content-decay/summary
     *
     * Get summary statistics for a site's decay queue.
     */
    public function summary(int $siteId, Request $request, ContentDecayServiceInterface $service): JsonResponse
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

        // Authorization: User must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $summary = $service->getSummary($siteId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * POST /api/v1/sites/{siteId}/content-decay/run
     *
     * Trigger a manual content decay detection run.
     */
    public function run(int $siteId, Request $request, ContentDecayDetectionServiceInterface $service): JsonResponse
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

        // Authorization: User must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        try {
            $result = $service->runDetection($siteId);

            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $result['job_id'],
                ],
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DETECTION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 400);
        }
    }

    /**
     * PATCH /api/v1/sites/{siteId}/content-decay/settings
     *
     * Update content decay detection settings for a site.
     */
    public function updateSettings(
        int $siteId,
        UpdateProjectSettingsRequest $request,
        ContentDecayServiceInterface $service
    ): JsonResponse {
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

        // Authorization: User must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        $updatedSettings = $service->updateSettings($siteId, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $updatedSettings,
        ]);
    }
}
