<?php

namespace App\Repositories;

use App\Models\ToolLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lead Repository for CRUD operations on tool leads.
 * 
 * TASK-16-6: Lead Storage Service
 */
class LeadRepository
{
    /**
     * Create a new lead.
     */
    public function create(array $data): ToolLead
    {
        return ToolLead::create($data);
    }

    /**
     * Find a lead by ID.
     */
    public function find(int $id): ?ToolLead
    {
        return ToolLead::find($id);
    }

    /**
     * Find a lead by email and tool.
     */
    public function findByEmailAndTool(string $email, string $tool): ?ToolLead
    {
        return ToolLead::byEmail($email)
            ->byTool($tool)
            ->notTrashed()
            ->first();
    }

    /**
     * Check for duplicate lead within time period.
     */
    public function findRecentDuplicate(string $email, string $tool, int $hoursAgo = 24): ?ToolLead
    {
        return ToolLead::byEmail($email)
            ->byTool($tool)
            ->notTrashed()
            ->where('created_at', '>=', now()->subHours($hoursAgo))
            ->first();
    }

    /**
     * Get all leads with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = ToolLead::query()->notTrashed();

        // Apply filters
        if (!empty($filters['tool'])) {
            $query->byTool($filters['tool']);
        }

        if (!empty($filters['email'])) {
            $query->byEmail($filters['email']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all leads (for export).
     */
    public function all(array $filters = []): Collection
    {
        $query = ToolLead::query()->notTrashed();

        if (!empty($filters['tool'])) {
            $query->byTool($filters['tool']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Soft delete a lead.
     */
    public function softDelete(int $id): bool
    {
        $lead = $this->find($id);
        if ($lead) {
            return $lead->delete();
        }
        return false;
    }

    /**
     * Restore a soft-deleted lead.
     */
    public function restore(int $id): bool
    {
        $lead = ToolLead::withTrashed()->find($id);
        if ($lead && $lead->trashed()) {
            return $lead->restore();
        }
        return false;
    }

    /**
     * Permanently delete a lead.
     */
    public function forceDelete(int $id): bool
    {
        $lead = ToolLead::withTrashed()->find($id);
        if ($lead) {
            return $lead->forceDelete();
        }
        return false;
    }

    /**
     * Get lead statistics.
     */
    public function getStatistics(): array
    {
        $total = ToolLead::notTrashed()->count();
        $thisWeek = ToolLead::notTrashed()->thisWeek()->count();
        $thisMonth = ToolLead::notTrashed()->thisMonth()->count();

        // By tool
        $byTool = ToolLead::notTrashed()
            ->select('tool', DB::raw('count(*) as count'))
            ->groupBy('tool')
            ->pluck('count', 'tool')
            ->toArray();

        // By date (last 30 days)
        $byDate = ToolLead::notTrashed()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $total,
            'thisWeek' => $thisWeek,
            'thisMonth' => $thisMonth,
            'byTool' => $byTool,
            'byDate' => $byDate,
        ];
    }

    /**
     * Get leads for CSV export.
     */
    public function getForExport(array $filters = []): \Generator
    {
        $query = ToolLead::notTrashed();

        if (!empty($filters['tool'])) {
            $query->byTool($filters['tool']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        foreach ($query->orderBy('created_at', 'desc')->cursor() as $lead) {
            yield [
                'id' => $lead->id,
                'email' => $lead->email,
                'tool' => $lead->tool,
                'url' => $lead->url,
                'ip_address' => $lead->ip_address,
                'created_at' => $lead->created_at->toISOString(),
            ];
        }
    }
}
