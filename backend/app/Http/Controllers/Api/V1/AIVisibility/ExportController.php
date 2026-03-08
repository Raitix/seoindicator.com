<?php

namespace App\Http\Controllers\Api\V1\AIVisibility;

use App\Http\Controllers\Controller;
use App\Services\AIVisibility\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Controller
 *
 * Handles AI Visibility data export via API.
 *
 * @see TASK-34-22
 * @see SPEC-034
 */
class ExportController extends Controller
{
    /**
     * @var ExportService
     */
    protected ExportService $exportService;

    /**
     * Create a new ExportController instance.
     *
     * @param ExportService $exportService
     */
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export AI Visibility data.
     *
     * @param Request $request
     * @return Response|StreamedResponse
     *
     * @queryParam format string Export format: csv or xlsx. Default: csv
     * @queryParam project_id int Single project to export
     * @queryParam project_ids int[] Multiple projects to export
     * @queryParam from string Start date (Y-m-d or ISO 8601)
     * @queryParam to string End date (Y-m-d or ISO 8601)
     * @queryParam data_type string Data type: projects, queries, analytics, mentions, all. Default: all
     *
     * @response 200 binary File download
     * @response 401 {"message": "Unauthenticated"}
     * @response 403 {"message": "Unauthorized"}
     * @response 422 {"message": "Validation error", "errors": {...}}
     * @response 429 {"message": "Too many requests"}
     */
    public function export(Request $request): Response|StreamedResponse
    {
        // Validate input
        $validated = $request->validate([
            'format' => ['nullable', 'string', 'in:csv,xlsx,CSV,XLSX'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'data_type' => ['nullable', 'string', 'in:projects,queries,analytics,mentions,all'],
        ]);

        try {
            $userId = Auth::id();
            $format = strtolower($validated['format'] ?? 'csv');
            $filters = [
                'project_id' => $validated['project_id'] ?? null,
                'project_ids' => $validated['project_ids'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'data_type' => $validated['data_type'] ?? 'all',
            ];

            // CSV doesn't support multiple data types in one file
            if ($format === 'csv' && $filters['data_type'] === 'all') {
                $filters['data_type'] = 'queries'; // Default for CSV
            }

            return $this->exportService->export($userId, $filters, $format);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export a specific project's data.
     *
     * @param Request $request
     * @param int $id Project ID
     * @return Response|StreamedResponse
     *
     * @queryParam format string Export format: csv or xlsx. Default: csv
     * @queryParam from string Start date
     * @queryParam to string End date
     * @queryParam data_type string Data type. Default: all
     *
     * @response 200 binary File download
     */
    public function exportProject(Request $request, int $id): Response|StreamedResponse
    {
        // Merge project_id into the request
        $request->merge(['project_id' => $id]);

        return $this->export($request);
    }
}
