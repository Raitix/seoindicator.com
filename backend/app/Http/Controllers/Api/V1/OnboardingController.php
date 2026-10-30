<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserOnboarding;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    private OnboardingService $service;

    public function __construct(OnboardingService $service)
    {
        $this->service = $service;
    }

    /**
     * Get the user's onboarding progress.
     */
    public function index(Request $request): JsonResponse
    {
        $progress = $this->service->getProgress($request->user());

        if (!$progress) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOnboarding($progress),
        ]);
    }

    /**
     * Start or update onboarding.
     */
    public function store(Request $request): JsonResponse
    {
        $step = $request->input('step');
        $data = $request->input('data', []);

        // Validate step if provided
        if ($step !== null) {
            $request->validate([
                'step' => ['integer', 'min:1', 'max:5'],
                'data' => ['array'],
            ]);
        }

        try {
            if ($step === null) {
                // Start onboarding
                $onboarding = $this->service->startOnboarding($request->user());
            } else {
                // Update step
                $onboarding = $this->service->updateStep($request->user(), $step, $data);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatOnboarding($onboarding),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Complete onboarding.
     */
    public function complete(Request $request): JsonResponse
    {
        try {
            $onboarding = $this->service->completeOnboarding($request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'completed_at' => $onboarding->completed_at?->toIso8601String(),
                    'keyword_id' => $onboarding->keyword_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete onboarding.',
            ], 500);
        }
    }

    /**
     * Skip onboarding.
     */
    public function skip(Request $request): JsonResponse
    {
        try {
            $onboarding = $this->service->skipOnboarding($request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'skipped_at' => $onboarding->skipped_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to skip onboarding.',
            ], 500);
        }
    }

    /**
     * Format onboarding data for API response.
     */
    private function formatOnboarding(UserOnboarding $onboarding): array
    {
        return [
            'id' => $onboarding->id,
            'current_step' => $onboarding->current_step,
            'completed_steps' => $onboarding->completed_steps,
            'website_url' => $onboarding->website_url,
            'first_keyword' => $onboarding->first_keyword,
            'keyword_id' => $onboarding->keyword_id,
            'alert_preferences' => $onboarding->alert_preferences,
            'completed_at' => $onboarding->completed_at?->toIso8601String(),
            'progress_percentage' => $onboarding->progress_percentage,
        ];
    }
}
