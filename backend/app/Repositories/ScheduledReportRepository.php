<?php

namespace App\Repositories;

use App\Models\ScheduledReport;
use Illuminate\Support\Collection;

/**
 * Repository for ScheduledReport model operations.
 */
class ScheduledReportRepository
{
    /**
     * Create a new scheduled report.
     */
    public function create(array $data): ScheduledReport
    {
        return ScheduledReport::create($data);
    }

    /**
     * Find a scheduled report by ID.
     */
    public function find(int $id): ?ScheduledReport
    {
        return ScheduledReport::find($id);
    }

    /**
     * Find all scheduled reports for a site.
     *
     * @return Collection<int, ScheduledReport>
     */
    public function findBySite(int $siteId): Collection
    {
        return ScheduledReport::where('site_id', $siteId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find all enabled scheduled reports due to run.
     *
     * @return Collection<int, ScheduledReport>
     */
    public function findDue(): Collection
    {
        return ScheduledReport::where('enabled', true)
            ->where('next_run', '<=', now())
            ->get();
    }

    /**
     * Update a scheduled report.
     */
    public function update(int $id, array $data): bool
    {
        $scheduled = $this->find($id);
        if (!$scheduled) {
            return false;
        }

        return $scheduled->update($data);
    }

    /**
     * Delete a scheduled report.
     */
    public function delete(int $id): bool
    {
        $scheduled = $this->find($id);
        if (!$scheduled) {
            return false;
        }

        return $scheduled->delete();
    }

    /**
     * Update last_run and calculate next_run.
     */
    public function markRun(int $id): bool
    {
        $scheduled = $this->find($id);
        if (!$scheduled) {
            return false;
        }

        $scheduled->last_run = now();
        $scheduled->next_run = $scheduled->calculateNextRun();

        return $scheduled->save();
    }
}
