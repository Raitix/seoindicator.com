<?php

namespace App\Repositories;

use App\Models\Backlink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BacklinkRepository for TASK-23-2.
 *
 * Provides data access layer for backlink management.
 */
class BacklinkRepository
{
    /**
     * Find a backlink by ID.
     */
    public function findById(int $id): ?Backlink
    {
        return Backlink::find($id);
    }

    /**
     * Create a new backlink.
     */
    public function create(array $data): Backlink
    {
        return Backlink::create($data);
    }

    /**
     * Update a backlink.
     */
    public function update(int $id, array $data): bool
    {
        $backlink = Backlink::find($id);

        if (!$backlink) {
            return false;
        }

        return $backlink->update($data);
    }

    /**
     * Delete a backlink.
     */
    public function delete(int $id): bool
    {
        $backlink = Backlink::find($id);

        if (!$backlink) {
            return false;
        }

        return $backlink->delete();
    }

    /**
     * Get all backlinks for a project.
     */
    public function getByProject(int $projectId): Collection
    {
        return Backlink::where('project_id', $projectId)->get();
    }

    /**
     * Insert multiple backlinks at once.
     */
    public function insertBatch(array $backlinks): bool
    {
        if (empty($backlinks)) {
            return true;
        }

        $now = now();
        $records = array_map(function ($backlink) use ($now) {
            return array_merge($backlink, [
                'created_at' => $backlink['created_at'] ?? $now,
                'updated_at' => $backlink['updated_at'] ?? $now,
            ]);
        }, $backlinks);

        return DB::table('backlinks')->insert($records);
    }

    /**
     * Deactivate stale backlinks (no longer found in recent crawl).
     */
    public function deactivateStale(int $projectId, array $activeSourceUrls): int
    {
        return Backlink::where('project_id', $projectId)
            ->whereNotIn('source_url', $activeSourceUrls)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Get count of active backlinks for a project.
     */
    public function getActiveCount(int $projectId): int
    {
        return Backlink::where('project_id', $projectId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get count of dofollow backlinks for a project.
     */
    public function getDofollowCount(int $projectId): int
    {
        return Backlink::where('project_id', $projectId)
            ->where('is_active', true)
            ->where('link_type', 'dofollow')
            ->count();
    }

    /**
     * Get backlinks by referring domain.
     */
    public function getByDomain(int $projectId, string $domain): Collection
    {
        return Backlink::where('project_id', $projectId)
            ->where('source_domain', $domain)
            ->get();
    }

    /**
     * Get recent backlinks for a project.
     * Only returns active backlinks.
     */
    public function getRecent(int $projectId, int $limit = 10): Collection
    {
        return Backlink::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('first_seen_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated backlinks for a project.
     */
    public function getPaginated(int $projectId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return Backlink::where('project_id', $projectId)
            ->orderBy('first_seen_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Count backlinks by link type.
     */
    public function countByLinkType(int $projectId): array
    {
        return Backlink::where('project_id', $projectId)
            ->select('link_type', DB::raw('count(*) as count'))
            ->groupBy('link_type')
            ->pluck('count', 'link_type')
            ->toArray();
    }

    /**
     * Get unique referring domains count for a project.
     */
    public function getUniqueReferringDomainsCount(int $projectId): int
    {
        return Backlink::where('project_id', $projectId)
            ->where('is_active', true)
            ->distinct('source_domain')
            ->count('source_domain');
    }
}
