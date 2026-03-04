<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Tools\ToolExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for tool export API endpoints.
 *
 * TASK-29-3: ToolExportService & Controller
 * SPEC-029: Export Results (PDF/XLS)
 *
 * Endpoints:
 * - POST /api/v1/tools/export - Create export, returns download URL
 * - GET /api/v1/tools/export/download/{token} - Download file
 */
class ToolExportController extends Controller
{
    /**
     * Create a new tool export controller instance.
     *
     * @param ToolExportService $exportService
     */
    public function __construct(
        private ToolExportService $exportService
    ) {}

    /**
     * Create a new export and return the download URL.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tool' => 'required|string',
            'url' => 'required|url',
            'format' => 'required|in:pdf,xls',
            'data' => 'required|array',
        ]);

        $downloadUrl = $this->exportService->createExport(
            tool: $validated['tool'],
            url: $validated['url'],
            format: $validated['format'],
            data: $validated['data'],
            userId: Auth::id()
        );

        return response()->json([
            'success' => true,
            'downloadUrl' => $downloadUrl,
            'expiresAt' => now()->addHour()->toIso8601String(),
        ]);
    }

    /**
     * Download an export by token.
     *
     * @param string $token
     * @return Response
     */
    public function download(string $token): Response
    {
        return $this->exportService->downloadExport($token, Auth::id());
    }
}
