<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Lead Capture Controller.
 * 
 * Handles email capture from SEO tools.
 * 
 * TASK-16-5: Email Capture Modal & API
 */
class LeadCaptureController extends Controller
{
    protected LeadService $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * Capture an email lead.
     *
     * POST /api/v1/tools/capture-email
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limiting: 5 per IP per hour
        $ip = $request->ip();
        $key = 'email-capture:' . $ip;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many requests',
            ], 429)->header('Retry-After', $seconds);
        }
        
        RateLimiter::hit($key, 3600); // 1 hour decay

        $request->validate([
            'email' => 'required|email|max:255',
            'tool' => 'required|string|in:meta-tag-analyzer,keyword-density,mobile-friendly,social-preview',
            'url' => 'nullable|url|max:2083',
        ]);

        $result = $this->leadService->capture([
            'email' => $request->input('email'),
            'tool' => $request->input('tool'),
            'url' => $request->input('url'),
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to capture lead',
            ], 400);
        }

        // Return 201 for new leads, 200 for duplicates
        $statusCode = $result['is_duplicate'] ? 200 : 201;

        return response()->json([
            'success' => true,
            'data' => [
                'leadId' => $result['lead']->id,
            ],
        ], $statusCode);
    }
}
