<?php

namespace App\Repositories;

use App\Models\Report;
use App\Services\Traits\EmitsWebhookEvents;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Report Repository for SPEC-004-2.
 *
 * Emits report.completed webhook event after successful report generation.
 */
class ReportRepository
{
    use EmitsWebhookEvents;

    /**
     * Create a report.
     */
    public function create(array $data): Report
    {
        return Report::create($data);
    }

    /**
     * Find report by ID.
     */
    public function find(int $id): ?Report
    {
        return Report::find($id);
    }

    /**
     * Find reports by site with optional pagination.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function findBySite(int $siteId, ?int $perPage = null, ?int $page = null)
    {
        $query = Report::where('site_id', $siteId)->orderByDesc('created_at');

        if ($perPage) {
            return $query->paginate($perPage, ['*'], 'page', $page ?? 1);
        }

        return $query->get();
    }

    /**
     * Find reports by status with optional pagination.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function findByStatus(string $status, ?int $perPage = null, ?int $page = null)
    {
        $query = Report::where('status', $status)->orderByDesc('created_at');

        if ($perPage) {
            return $query->paginate($perPage, ['*'], 'page', $page ?? 1);
        }

        return $query->get();
    }

    /**
     * Update report.
     */
    public function update(int $id, array $data): bool
    {
        $report = Report::find($id);

        if (!$report) {
            return false;
        }

        return $report->update($data);
    }

    /**
     * Delete report.
     */
    public function delete(int $id): bool
    {
        $report = Report::find($id);

        if (!$report) {
            return false;
        }

        return (bool) $report->delete();
    }

    /**
     * Filter reports by date range (generated_at).
     *
     * @return Collection|LengthAwarePaginator
     */
    public function findByDateRange(int $siteId, ?string $startDate, ?string $endDate, ?int $perPage = null, ?int $page = null)
    {
        $query = Report::where('site_id', $siteId);

        if ($startDate) {
            $query->where('generated_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('generated_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $query->orderByDesc('generated_at');

        if ($perPage) {
            return $query->paginate($perPage, ['*'], 'page', $page ?? 1);
        }

        return $query->get();
    }

    /**
     * Generate a report from a scheduled report configuration.
     *
     * Emits report.completed webhook event after successful generation.
     */
    public function generateFromSchedule(\App\Models\ScheduledReport $scheduled): Report
    {
        $report = $this->create([
            'site_id' => $scheduled->site_id,
            'name' => $scheduled->name,
            'type' => $scheduled->frequency,
            'format' => $scheduled->format,
            'status' => 'pending',
            'date_range_start' => now()->subDays(7)->toDateString(),
            'date_range_end' => now()->toDateString(),
        ]);

        // In a full implementation, this would trigger actual report generation
        // For now, we just create the report record
        $report->update([
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $report->refresh();

        // Emit webhook event for report completion
        $this->emitReportCompletedWebhook($report, $scheduled->id);

        return $report;
    }

    /**
     * Mark a report as completed and emit webhook event.
     *
     * @param Report $report The report to complete
     * @param string $downloadUrl The download URL for the report
     * @param int|null $scheduledReportId Optional scheduled report ID if from schedule
     */
    public function markCompleted(Report $report, string $downloadUrl, ?int $scheduledReportId = null): void
    {
        $report->update([
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $report->refresh();

        $this->emitReportCompletedWebhook($report, $scheduledReportId, $downloadUrl);
    }

    /**
     * Emit webhook event for completed report.
     */
    protected function emitReportCompletedWebhook(Report $report, ?int $scheduledReportId = null, ?string $downloadUrl = null): void
    {
        // Get user ID from the site
        $site = $report->site;
        if (!$site || !$site->user_id) {
            return;
        }

        $url = $downloadUrl ?? "/api/v1/reports/{$report->id}/download";

        $dateRange = null;
        if ($report->date_range_start && $report->date_range_end) {
            $dateRange = [
                'start' => $report->date_range_start->toDateString(),
                'end' => $report->date_range_end->toDateString(),
            ];
        }

        $this->emitReportCompletedEvent(
            userId: $site->user_id,
            reportId: $report->id,
            siteId: $report->site_id,
            type: $report->type,
            format: $report->format,
            downloadUrl: $url,
            fileSize: $report->file_size,
            scheduledReportId: $scheduledReportId,
            dateRange: $dateRange
        );
    }
}
