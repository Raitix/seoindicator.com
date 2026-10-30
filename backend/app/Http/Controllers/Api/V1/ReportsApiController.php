<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\Site;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reports API Controller for TASK-6-11.
 *
 * External REST API endpoint for triggering asynchronous report generation.
 *
 * Endpoint: POST /api/v1/reports
 * Authentication: API Key (api-key.auth middleware)
 * Permission: reports:write
 * Rate Limiting: Per-key rate limiting (api-key.ratelimit middleware)
 */
class ReportsApiController extends Controller
{
    protected ApiKeyService $apiKeyService;

    /**
     * Valid report types.
     */
    public const VALID_TYPES = ['daily', 'weekly', 'monthly', 'custom', 'one-time'];

    /**
     * Valid report formats.
     */
    public const VALID_FORMATS = ['pdf', 'csv'];

    /**
     * Valid report sections.
     */
    public const VALID_SECTIONS = [
        'cover',
        'summary',
        'keywords',
        'changes',
        'top-performers',
        'needs-attention',
        'trends',
        'competitors',
        'serp-features',
        'audit',
    ];

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Trigger asynchronous report generation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:sites,id',
            'type' => 'required|string|in:' . implode(',', self::VALID_TYPES),
            'format' => 'required|string|in:' . implode(',', self::VALID_FORMATS),
            'name' => 'nullable|string|max:255',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after_or_equal:date_range.start',
            'sections' => 'nullable|array',
            'sections.*' => 'string|in:' . implode(',', self::VALID_SECTIONS),
        ]);

        // Get the site
        $site = Site::find($validated['site_id']);

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'The selected site_id is invalid.',
                ],
            ], 422);
        }

        // Authorization check: user must own the site
        // The api-key.auth middleware sets the user on the request
        $user = $request->user();
        if (!$user || $site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Generate unique job ID
        $jobId = 'rpt-' . Str::random(16);

        // Sanitize name (prevent XSS)
        $name = isset($validated['name'])
            ? htmlspecialchars($validated['name'], ENT_QUOTES, 'UTF-8')
            : $this->generateName($validated['type']);

        // Determine date range
        $dateRangeStart = $validated['date_range']['start'] ?? $this->getDefaultStartDate($validated['type']);
        $dateRangeEnd = $validated['date_range']['end'] ?? $this->getDefaultEndDate($validated['type']);

        // Default sections if not provided
        $sections = $validated['sections'] ?? ['cover', 'keywords'];

        // Create report record
        $report = Report::create([
            'site_id' => $validated['site_id'],
            'name' => $name,
            'type' => $validated['type'],
            'format' => $validated['format'],
            'status' => 'pending',
            'date_range_start' => $dateRangeStart,
            'date_range_end' => $dateRangeEnd,
            'sections' => $sections,
        ]);

        // Dispatch job for async processing
        GenerateReportJob::dispatch($report->id, $jobId);

        // Update API key last_used_at
        $this->updateApiKeyLastUsed($request);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'status' => 'queued',
            ],
        ], 202);
    }

    /**
     * Generate default report name based on type.
     */
    protected function generateName(string $type): string
    {
        return ucfirst($type) . ' Report - ' . now()->format('Y-m-d');
    }

    /**
     * Get default start date based on report type.
     */
    protected function getDefaultStartDate(string $type): string
    {
        return match ($type) {
            'daily' => now()->subDay()->toDateString(),
            'weekly' => now()->subWeek()->toDateString(),
            'monthly' => now()->subMonth()->toDateString(),
            default => now()->subWeek()->toDateString(),
        };
    }

    /**
     * Get default end date (today).
     */
    protected function getDefaultEndDate(string $type): string
    {
        return now()->toDateString();
    }

    /**
     * Update the API key's last_used_at timestamp.
     */
    protected function updateApiKeyLastUsed(Request $request): void
    {
        // The api-key.auth middleware should store the API key on the request
        $apiKey = $request->attributes->get('api_key');

        if ($apiKey) {
            $apiKey->update(['last_used_at' => now()]);
        }
    }
}
