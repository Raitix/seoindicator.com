<?php

namespace App\Repositories;

use App\Models\AuditIssue;
use Illuminate\Support\Facades\DB;

/**
 * Repository for AuditIssue model operations.
 *
 * @see TASK-14-8
 */
class AuditIssueRepository
{
    /**
     * Create a new audit issue.
     *
     * @param array $data Issue data
     * @return AuditIssue The created issue
     */
    public function create(array $data): AuditIssue
    {
        return AuditIssue::create($data);
    }

    /**
     * Create multiple issues in bulk.
     *
     * @param array $issues Array of issue data
     * @return array Array of created issue IDs
     */
    public function createMany(array $issues): array
    {
        $ids = [];
        $now = now();

        foreach ($issues as $issue) {
            $issue['created_at'] = $now;
            $issue['updated_at'] = $now;
            
            $id = DB::table('audit_issues')->insertGetId($issue);
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Find an issue by ID.
     *
     * @param int $id The issue ID
     * @return AuditIssue|null
     */
    public function find(int $id): ?AuditIssue
    {
        return AuditIssue::find($id);
    }

    /**
     * Get all issues for an audit.
     *
     * @param int $auditId The audit ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByAuditId(int $auditId)
    {
        return AuditIssue::where('audit_id', $auditId)->get();
    }

    /**
     * Get issues by severity.
     *
     * @param int $auditId The audit ID
     * @param string $severity The severity level
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySeverity(int $auditId, string $severity)
    {
        return AuditIssue::where('audit_id', $auditId)
            ->where('severity', $severity)
            ->get();
    }

    /**
     * Get issue counts by severity for an audit.
     *
     * @param int $auditId The audit ID
     * @return array ['critical' => X, 'warning' => Y, 'notice' => Z]
     */
    public function getSeverityCounts(int $auditId): array
    {
        $counts = AuditIssue::where('audit_id', $auditId)
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->all();

        return [
            'critical' => $counts['critical'] ?? 0,
            'warning' => $counts['warning'] ?? 0,
            'notice' => $counts['notice'] ?? 0,
        ];
    }

    /**
     * Delete all issues for an audit.
     *
     * @param int $auditId The audit ID
     * @return int Number of deleted issues
     */
    public function deleteByAuditId(int $auditId): int
    {
        return AuditIssue::where('audit_id', $auditId)->delete();
    }

    /**
     * Get total issue count for an audit.
     *
     * @param int $auditId The audit ID
     * @return int
     */
    public function countByAuditId(int $auditId): int
    {
        return AuditIssue::where('audit_id', $auditId)->count();
    }
}
