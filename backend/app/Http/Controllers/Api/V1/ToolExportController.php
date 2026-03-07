<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tools\ToolExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Controller for tool result exports.
 *
 * API endpoints for creating and downloading PDF/XLS exports.
 *
 * TASK-29-3: ToolExportService & Controller
 * TASK-29-5: Rate Limiting Integration
 */
class ToolExportController extends Controller
{
    /**
     * @param ToolExportService $exportService
     */
    public function __construct(
        private ToolExportService $exportService
    ) {}

    /**
     * Create a new export.
     *
     * POST /api/v1/tools/export
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tool' => 'required|string',
                'url' => 'required|url',
                'format' => 'required|string|in:pdf,xls',
                'data' => 'required|array',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid request data.',
                    'details' => $e->errors(),
                ],
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'You must be logged in to create exports.',
                ],
            ], 401);
        }

        // TASK-29-5: Check rate limit
        $rateLimitResult = $this->checkRateLimit($user->id);
        
        if (!$rateLimitResult['allowed']) {
            return $this->rateLimitExceededResponse($rateLimitResult);
        }

        try {
            $downloadUrl = $this->exportService->createExport(
                tool: $validated['tool'],
                url: $validated['url'],
                format: $validated['format'],
                data: $validated['data'],
                userId: $user->id
            );

            // Increment rate limit counter after successful export
            $this->incrementRateLimit($user->id);

            return $this->successResponse($downloadUrl, $rateLimitResult);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('INVALID_FORMAT', $e->getMessage(), 422, $rateLimitResult);
        } catch (\Exception $e) {
            return $this->errorResponse('EXPORT_ERROR', 'Failed to create export.', 500, $rateLimitResult);
        }
    }

    /**
     * Check rate limit for user.
     *
     * @param int $userId
     * @return array{allowed: bool, remaining: int, resetAt: int, limit: int}
     */
    private function checkRateLimit(int $userId): array
    {
        $limit = config('tools.export_rate_limit', 10);
        $window = config('tools.export_rate_window', 3600);
        $key = "export_rate_limit:{$userId}";
        
        $count = (int) Cache::get($key, 0);
        $remaining = max(0, $limit - $count);
        
        // Calculate reset time based on when the cache entry expires
        // If no entry exists, it's a fresh window
        if ($count === 0) {
            $resetAt = time() + $window;
        } else {
            // Get the TTL from cache
            $ttl = Cache::getStore()->getPrefix() ? null : $window;
            // For file/database cache, we track reset time separately
            $resetKey = "export_rate_limit_reset:{$userId}";
            $resetAt = (int) Cache::get($resetKey, time() + $window);
        }

        return [
            'allowed' => $count < $limit,
            'remaining' => $remaining,
            'resetAt' => $resetAt,
            'limit' => $limit,
        ];
    }

    /**
     * Increment rate limit counter for user.
     *
     * @param int $userId
     * @return int New count
     */
    private function incrementRateLimit(int $userId): int
    {
        $window = config('tools.export_rate_window', 3600);
        $key = "export_rate_limit:{$userId}";
        $resetKey = "export_rate_limit_reset:{$userId}";
        
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, $window);
        
        // Store reset time on first increment
        if ($count === 1) {
            Cache::put($resetKey, time() + $window, $window);
        }
        
        return $count;
    }

    /**
     * Build rate limit headers.
     *
     * @param array $rateLimitResult
     * @return array
     */
    private function buildRateLimitHeaders(array $rateLimitResult): array
    {
        return [
            'X-RateLimit-Limit' => (string) $rateLimitResult['limit'],
            'X-RateLimit-Remaining' => (string) max(0, $rateLimitResult['remaining'] - 1),
            'X-RateLimit-Reset' => (string) $rateLimitResult['resetAt'],
        ];
    }

    /**
     * Success response with rate limit headers.
     *
     * @param string $downloadUrl
     * @param array $rateLimitResult
     * @return JsonResponse
     */
    private function successResponse(string $downloadUrl, array $rateLimitResult): JsonResponse
    {
        return response()->json([
            'success' => true,
            'downloadUrl' => $downloadUrl,
            'expiresAt' => now()->addHour()->toIso8601String(),
        ])->withHeaders($this->buildRateLimitHeaders($rateLimitResult));
    }

    /**
     * Error response with rate limit headers.
     *
     * @param string $code
     * @param string $message
     * @param int $status
     * @param array $rateLimitResult
     * @return JsonResponse
     */
    private function errorResponse(string $code, string $message, int $status, array $rateLimitResult): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status)->withHeaders($this->buildRateLimitHeaders($rateLimitResult));
    }

    /**
     * Rate limit exceeded response.
     *
     * @param array $rateLimitResult
     * @return JsonResponse
     */
    private function rateLimitExceededResponse(array $rateLimitResult): JsonResponse
    {
        $retrySeconds = max(1, $rateLimitResult['resetAt'] - time());
        $retryMinutes = max(1, (int) ceil($retrySeconds / 60));
        
        return response()->json([
            'message' => "Export rate limit exceeded. Try again in {$retryMinutes} minutes.",
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => (string) $rateLimitResult['limit'],
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string) $rateLimitResult['resetAt'],
        ]);
    }

    /**
     * Download an export by token.
     *
     * GET /api/v1/tools/export/download/{token}
     *
     * @param string $token
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function download(string $token, Request $request): Response|JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'You must be logged in to download exports.',
                ],
            ], 401);
        }

        try {
            return $this->exportService->downloadExport($token, $user->id);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Invalid or expired download token.',
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOWNLOAD_ERROR',
                    'message' => 'Failed to download export.',
                ],
            ], 500);
        }
    }
}
