<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Models\Site;
use App\Repositories\ScheduledReportRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Scheduled Report Controller for SPEC-004-11.
 */
class ScheduledReportController extends Controller
{
    protected ScheduledReportRepository $scheduledReportRepository;

    protected array $validFrequencies = ['daily', 'weekly', 'biweekly', 'monthly'];
    protected array $validFormats = ['pdf', 'csv'];

    public function __construct(ScheduledReportRepository $scheduledReportRepository)
    {
        $this->scheduledReportRepository = $scheduledReportRepository;
    }

    /**
     * List scheduled reports for a site (SPEC-004-11).
     */
    public function index(int $siteId): JsonResponse
    {
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

        $scheduled = $this->scheduledReportRepository->findBySite($siteId);

        return response()->json([
            'success' => true,
            'data' => $scheduled->map(fn (ScheduledReport $report) => $this->formatScheduledReport($report)),
        ]);
    }

    /**
     * Get a single scheduled report (SPEC-004-11).
     */
    public function show(int $scheduledId): JsonResponse
    {
        $scheduled = $this->scheduledReportRepository->find($scheduledId);
        if (!$scheduled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Scheduled report not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatScheduledReport($scheduled),
        ]);
    }

    /**
     * Create a scheduled report (SPEC-004-11).
     */
    public function store(Request $request, int $siteId): JsonResponse
    {
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'templateId' => 'nullable|integer|exists:report_templates,id',
            'frequency' => 'required|string|in:' . implode(',', $this->validFrequencies),
            'format' => 'required|string|in:' . implode(',', $this->validFormats),
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email|max:255',
        ]);

        // Sanitize name
        $name = strip_tags($validated['name']);

        // Calculate next run
        $tempScheduled = new ScheduledReport(['frequency' => $validated['frequency']]);
        $nextRun = $tempScheduled->calculateNextRun();

        $scheduled = $this->scheduledReportRepository->create([
            'site_id' => $siteId,
            'template_id' => $validated['templateId'] ?? null,
            'name' => $name,
            'frequency' => $validated['frequency'],
            'format' => $validated['format'],
            'recipients' => $validated['recipients'],
            'enabled' => true,
            'next_run' => $nextRun,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatScheduledReport($scheduled),
        ], 201);
    }

    /**
     * Update a scheduled report (SPEC-004-11).
     */
    public function update(Request $request, int $scheduledId): JsonResponse
    {
        $scheduled = $this->scheduledReportRepository->find($scheduledId);
        if (!$scheduled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Scheduled report not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'templateId' => 'nullable|integer|exists:report_templates,id',
            'frequency' => 'sometimes|string|in:' . implode(',', $this->validFrequencies),
            'format' => 'sometimes|string|in:' . implode(',', $this->validFormats),
            'recipients' => 'sometimes|array|min:1',
            'recipients.*' => 'required|email|max:255',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = strip_tags($validated['name']);
        }
        if (array_key_exists('templateId', $validated)) {
            $updateData['template_id'] = $validated['templateId'];
        }
        if (isset($validated['frequency'])) {
            $updateData['frequency'] = $validated['frequency'];
            // Recalculate next run
            $tempScheduled = new ScheduledReport(['frequency' => $validated['frequency']]);
            $updateData['next_run'] = $tempScheduled->calculateNextRun();
        }
        if (isset($validated['format'])) {
            $updateData['format'] = $validated['format'];
        }
        if (isset($validated['recipients'])) {
            $updateData['recipients'] = $validated['recipients'];
        }

        if (!empty($updateData)) {
            $this->scheduledReportRepository->update($scheduledId, $updateData);
            $scheduled->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatScheduledReport($scheduled),
        ]);
    }

    /**
     * Delete a scheduled report (SPEC-004-11).
     */
    public function destroy(int $scheduledId): Response
    {
        $scheduled = $this->scheduledReportRepository->find($scheduledId);
        if (!$scheduled) {
            return response()->noContent(404);
        }

        $this->scheduledReportRepository->delete($scheduledId);

        return response()->noContent();
    }

    /**
     * Pause a scheduled report (SPEC-004-11).
     */
    public function pause(int $scheduledId): JsonResponse
    {
        $scheduled = $this->scheduledReportRepository->find($scheduledId);
        if (!$scheduled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Scheduled report not found.',
                ],
            ], 404);
        }

        $this->scheduledReportRepository->update($scheduledId, ['enabled' => false]);
        $scheduled->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatScheduledReport($scheduled),
        ]);
    }

    /**
     * Resume a scheduled report (SPEC-004-11).
     */
    public function resume(int $scheduledId): JsonResponse
    {
        $scheduled = $this->scheduledReportRepository->find($scheduledId);
        if (!$scheduled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Scheduled report not found.',
                ],
            ], 404);
        }

        // Recalculate next run when resuming
        $nextRun = $scheduled->calculateNextRun();

        $this->scheduledReportRepository->update($scheduledId, [
            'enabled' => true,
            'next_run' => $nextRun,
        ]);
        $scheduled->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatScheduledReport($scheduled),
        ]);
    }

    /**
     * Format scheduled report for API response.
     */
    protected function formatScheduledReport(ScheduledReport $scheduled): array
    {
        return [
            'id' => $scheduled->id,
            'siteId' => $scheduled->site_id,
            'templateId' => $scheduled->template_id,
            'name' => $scheduled->name,
            'frequency' => $scheduled->frequency,
            'frequencyLabel' => $scheduled->frequency_label,
            'format' => $scheduled->format,
            'recipients' => $scheduled->recipients,
            'enabled' => $scheduled->enabled,
            'lastRun' => $scheduled->last_run?->toISOString(),
            'nextRun' => $scheduled->next_run?->toISOString(),
            'createdAt' => $scheduled->created_at?->toISOString(),
            'updatedAt' => $scheduled->updated_at?->toISOString(),
        ];
    }
}
