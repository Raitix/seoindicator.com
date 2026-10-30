<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\KeywordAlertSettings;
use App\Http\Requests\Api\V1\StoreKeywordAlertSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeywordAlertSettingsController extends Controller
{
    /**
     * Get alert settings for a specific keyword.
     */
    public function show(Request $request, Keyword $keyword): JsonResponse
    {
        // Authorization check
        if ($request->user()->id !== $keyword->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $settings = KeywordAlertSettings::where('keyword_id', $keyword->id)->first();

        if (!$settings) {
            // Return default structure
            return response()->json([
                'data' => [
                    'keyword_id' => $keyword->id,
                    'enabled' => true,
                    'custom_threshold' => null,
                    'alert_events' => null,
                    'notification_channels' => null,
                    'is_default' => true,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'keyword_id' => $settings->keyword_id,
                'enabled' => $settings->enabled,
                'custom_threshold' => $settings->custom_threshold,
                'alert_events' => $settings->alert_events,
                'notification_channels' => $settings->notification_channels,
                'is_default' => false,
            ],
        ]);
    }

    /**
     * Create or update alert settings for a specific keyword.
     */
    public function update(StoreKeywordAlertSettingsRequest $request, Keyword $keyword): JsonResponse
    {
        // Authorization check
        if ($request->user()->id !== $keyword->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $settings = KeywordAlertSettings::updateOrCreate(
            ['keyword_id' => $keyword->id],
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'keyword_id' => $settings->keyword_id,
                'enabled' => $settings->enabled,
                'custom_threshold' => $settings->custom_threshold,
                'alert_events' => $settings->alert_events,
                'notification_channels' => $settings->notification_channels,
            ],
        ]);
    }

    /**
     * Delete keyword-specific alert settings (reset to defaults).
     */
    public function destroy(Request $request, Keyword $keyword): JsonResponse
    {
        // Authorization check
        if ($request->user()->id !== $keyword->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $deleted = KeywordAlertSettings::where('keyword_id', $keyword->id)->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted > 0,
        ]);
    }
}
