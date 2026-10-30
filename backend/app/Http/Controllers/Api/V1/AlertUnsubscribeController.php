<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AlertSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AlertUnsubscribeController extends Controller
{
    /**
     * Handle unsubscribe from alert emails.
     *
     * This uses a signed URL for security - no login required.
     */
    public function unsubscribe(Request $request, User $user): JsonResponse
    {
        // Verify signed URL
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired unsubscribe link.',
            ], 403);
        }

        // Disable global alerts for this user
        $settings = AlertSettings::where('user_id', $user->id)->first();

        if ($settings) {
            $settings->update([
                'global_enabled' => false,
            ]);
        } else {
            // Create disabled settings
            AlertSettings::create([
                'user_id' => $user->id,
                'global_enabled' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'You have been unsubscribed from ranking alerts.',
        ]);
    }

    /**
     * Resubscribe to alerts (requires auth).
     */
    public function resubscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = AlertSettings::updateOrCreate(
            ['user_id' => $user->id],
            ['global_enabled' => true]
        );

        return response()->json([
            'success' => true,
            'message' => 'You have been resubscribed to ranking alerts.',
            'data' => [
                'global_enabled' => $settings->global_enabled,
            ],
        ]);
    }
}
