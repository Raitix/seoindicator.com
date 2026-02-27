<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin Lead Controller.
 * 
 * Handles lead management for administrators.
 * 
 * TASK-16-6: Lead Storage Service
 */
class LeadController extends Controller
{
    protected LeadService $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * List leads with pagination.
     *
     * GET /api/v1/admin/leads
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'tool' => $request->input('tool'),
            'email' => $request->input('email'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = (int) $request->input('per_page', 15);

        $result = $this->leadService->getLeads($perPage, array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Get lead statistics.
     *
     * GET /api/v1/admin/leads/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->leadService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export leads as CSV.
     *
     * GET /api/v1/admin/leads/export
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'tool' => $request->input('tool'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $csv = $this->leadService->exportCsv(array_filter($filters));

        $filename = 'leads-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]
        );
    }

    /**
     * Soft delete a lead.
     *
     * DELETE /api/v1/admin/leads/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->leadService->delete($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'Lead not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully',
        ]);
    }

    /**
     * Restore a soft-deleted lead.
     *
     * POST /api/v1/admin/leads/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $restored = $this->leadService->restore($id);

        if (!$restored) {
            return response()->json([
                'success' => false,
                'error' => 'Lead not found or not deleted',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead restored successfully',
        ]);
    }
}
