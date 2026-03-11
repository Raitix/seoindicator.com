<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentDecay\ExportCandidatesRequest;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayCandidateServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayExportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Content Decay Export Controller for SPEC-036.
 *
 * Endpoints for exporting the decay queue and generating AI briefs.
 */
class ContentDecayExportController extends Controller
{
    /**
     * GET /api/v1/sites/{siteId}/content-decay/export
     *
     * Export the decay queue for a site.
     */
    public function export(
        int $siteId,
        ExportCandidatesRequest $request,
        ContentDecayExportServiceInterface $service
    ): JsonResponse|StreamedResponse {
        $site = Site::find($siteId);

        if (!$site) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Site not found.',
                ],
            ], 404);
        }

        // Authorization: User must own the site
        if ($site->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this site.',
                ],
            ], 403);
        }

        // Get validated format (defaults to 'csv')
        $format = $request->validated('format', 'csv');

        // Build filters from validated request
        $filters = $this->buildFilters($request);

        $result = $service->exportQueue($siteId, $format, $filters);

        // Set appropriate headers based on format
        $headers = $this->getHeadersForFormat($format, $siteId);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($result) {
                echo $result;
            }, $this->getFilename($siteId, $format), $headers);
        }

        if ($format === 'pdf') {
            return response()->streamDownload(function () use ($result) {
                echo $result;
            }, $this->getFilename($siteId, $format), $headers);
        }

        // For xls and other formats, return JSON with download URL
        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $result,
            ],
        ]);
    }

    /**
     * POST /api/v1/content-decay/candidates/{id}/ai-brief
     *
     * Generate an AI refresh brief for a candidate.
     */
    public function generateAiBrief(
        int $id,
        Request $request,
        ContentDecayCandidateServiceInterface $candidateService,
        ContentDecayExportServiceInterface $exportService
    ): JsonResponse {
        $candidate = $candidateService->getCandidate($id);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Candidate not found.',
                ],
            ], 404);
        }

        // Check if candidate is ignored - cannot generate AI brief
        if (isset($candidate->status) && $candidate->status === 'ignored') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANDIDATE_IGNORED',
                    'message' => 'Cannot generate AI brief for an ignored candidate.',
                ],
            ], 400);
        }

        $result = $exportService->generateAiBrief($id);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $result['job_id'],
            ],
        ], 202);
    }

    /**
     * Build filters array from request query parameters.
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('status')) {
            $filters['status'] = $request->query('status');
        }

        if ($request->has('severity')) {
            $filters['severity'] = $request->query('severity');
        }

        if ($request->has('page_type')) {
            $filters['page_type'] = $request->query('page_type');
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->query('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->query('date_to');
        }

        if ($request->has('search')) {
            $filters['search'] = $request->query('search');
        }

        if ($request->has('recovery_report')) {
            $filters['recovery_report'] = filter_var($request->query('recovery_report'), FILTER_VALIDATE_BOOLEAN);
        }

        return $filters;
    }

    /**
     * Get HTTP headers for the export format.
     */
    private function getHeadersForFormat(string $format, int $siteId): array
    {
        return match ($format) {
            'csv' => [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $this->getFilename($siteId, $format) . '"',
            ],
            'pdf' => [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $this->getFilename($siteId, $format) . '"',
            ],
            'xls' => [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="' . $this->getFilename($siteId, $format) . '"',
            ],
            default => [
                'Content-Type' => 'application/octet-stream',
            ],
        };
    }

    /**
     * Get filename for the export.
     */
    private function getFilename(int $siteId, string $format): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $extension = $format === 'xls' ? 'xlsx' : $format;

        return "content-decay-site-{$siteId}-{$timestamp}.{$extension}";
    }
}
