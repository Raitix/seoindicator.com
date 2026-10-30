<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AlertSettings;
use App\Http\Requests\Api\V1\StoreAlertSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertSettingsController extends Controller
{
    /**
     * Get the authenticated user's alert settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = AlertSettings::firstOrCreate(
            ['user_id' => $user->id],
            AlertSettings::getDefaults()
        );

        return response()->json([
            'data' => [
                'global_enabled' => $settings->global_enabled,
                'default_threshold' => $settings->default_threshold,
                'quiet_hours_start' => $settings->quiet_hours_start,
                'quiet_hours_end' => $settings->quiet_hours_end,
                'digest_mode' => $settings->digest_mode,
                'serp_feature_alerts_enabled' => $settings->serp_feature_alerts_enabled,
                'serp_feature_types' => $settings->serp_feature_types,
            ],
        ]);
    }

    /**
     * Update the authenticated user's alert settings.
     */
    public function update(StoreAlertSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        $settings = AlertSettings::updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'global_enabled' => $settings->global_enabled,
                'default_threshold' => $settings->default_threshold,
                'quiet_hours_start' => $settings->quiet_hours_start,
                'quiet_hours_end' => $settings->quiet_hours_end,
                'digest_mode' => $settings->digest_mode,
                'serp_feature_alerts_enabled' => $settings->serp_feature_alerts_enabled,
                'serp_feature_types' => $settings->serp_feature_types,
            ],
        ]);
    }
}
