<?php

namespace App\Repositories;

use App\Models\ReferringDomain;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ReferringDomainRepository for TASK-23-2.
 *
 * Provides data access layer for referring domain management.
 */
class ReferringDomainRepository
{
    /**
     * Find a referring domain by ID.
     */
    public function findById(int $id): ?ReferringDomain
    {
        return ReferringDomain::find($id);
    }

    /**
     * Create a new referring domain.
     */
    public function create(array $data): ReferringDomain
    {
        return ReferringDomain::create($data);
    }

    /**
     * Update a referring domain.
     */
    public function update(int $id, array $data): bool
    {
        $domain = ReferringDomain::find($id);

        if (!$domain) {
            return false;
        }

        return $domain->update($data);
    }

    /**
     * Delete a referring domain.
     */
    public function delete(int $id): bool
    {
        $domain = ReferringDomain::find($id);

        if (!$domain) {
            return false;
        }

        return $domain->delete();
    }

    /**
     * Get all referring domains for a project.
     */
    public function getByProject(int $projectId): Collection
    {
        return ReferringDomain::where('project_id', $projectId)->get();
    }

    /**
     * Insert or update a referring domain by project and domain name.
     */
    public function upsert(array $data): ReferringDomain
    {
        $existing = ReferringDomain::where('project_id', $data['project_id'])
            ->where('domain', $data['domain'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return ReferringDomain::create($data);
    }

    /**
     * Find domain by project and domain name.
     */
    public function findByProjectAndDomain(int $projectId, string $domain): ?ReferringDomain
    {
        return ReferringDomain::where('project_id', $projectId)
            ->where('domain', $domain)
            ->first();
    }

    /**
     * Get domains ordered by backlinks count (descending).
     */
    public function getOrderedByBacklinksCount(int $projectId, string $direction = 'desc'): Collection
    {
        return ReferringDomain::where('project_id', $projectId)
            ->orderBy('backlinks_count', $direction)
            ->get();
    }

    /**
     * Get domains ordered by trust score (descending).
     */
    public function getOrderedByTrustScore(int $projectId, string $direction = 'desc'): Collection
    {
        return ReferringDomain::where('project_id', $projectId)
            ->orderBy('domain_trust_score', $direction)
            ->get();
    }

    /**
     * Get top domains by backlinks count.
     */
    public function getTopByBacklinks(int $projectId, int $limit = 10): Collection
    {
        return ReferringDomain::where('project_id', $projectId)
            ->orderBy('backlinks_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get total count for a project.
     */
    public function getTotalCount(int $projectId): int
    {
        return ReferringDomain::where('project_id', $projectId)->count();
    }

    /**
     * Count referring domains for a project (alias for getTotalCount).
     * Used by BacklinkSnapshotService.
     */
    public function countByProject(int $projectId): int
    {
        return $this->getTotalCount($projectId);
    }

    /**
     * Calculate total backlinks from all domains.
     */
    public function getTotalBacklinks(int $projectId): int
    {
        return (int) ReferringDomain::where('project_id', $projectId)
            ->sum('backlinks_count');
    }

    /**
     * Calculate average trust score for a project.
     */
    public function getAverageTrustScore(int $projectId): float
    {
        return (float) ReferringDomain::where('project_id', $projectId)
            ->avg('domain_trust_score');
    }

    /**
     * Get domains filtered by trust score threshold.
     */
    public function getByTrustThreshold(int $projectId, float $minTrust, ?float $maxTrust = null): Collection
    {
        $query = ReferringDomain::where('project_id', $projectId)
            ->where('domain_trust_score', '>=', $minTrust);

        if ($maxTrust !== null) {
            $query->where('domain_trust_score', '<=', $maxTrust);
        }

        return $query->orderBy('domain_trust_score', 'desc')->get();
    }
}
