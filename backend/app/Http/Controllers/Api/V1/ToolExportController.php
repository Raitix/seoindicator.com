<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tools\ToolExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controller for tool result exports.
 *
 * API endpoints for creating and downloading PDF/XLS exports.
 *
 * TASK-29-3: ToolExportService & Controller
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

        try {
            $downloadUrl = $this->exportService->createExport(
                tool: $validated['tool'],
                url: $validated['url'],
                format: $validated['format'],
                data: $validated['data'],
                userId: $user->id
            );

            return response()->json([
                'success' => true,
                'downloadUrl' => $downloadUrl,
                'expiresAt' => now()->addHour()->toIso8601String(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_FORMAT',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EXPORT_ERROR',
                    'message' => 'Failed to create export.',
                ],
            ], 500);
        }
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
