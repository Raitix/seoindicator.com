<?php

namespace App\Repositories;

use App\Models\Keyword;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keyword Repository for SPEC-001-2.
 */
class KeywordRepository
{
    /**
     * Find keyword by ID.
     */
    public function findById(int $id): ?Keyword
    {
        return Keyword::find($id);
    }

    /**
     * Find keywords by site, optionally paginated and/or filtered by status.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function findBySite(int $siteId, ?string $status = null, ?int $perPage = null, ?int $page = null)
    {
        $query = Keyword::where('site_id', $siteId)->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($perPage) {
            return $query->paginate($perPage, ['*'], 'page', $page ?? 1);
        }

        return $query->get();
    }

    /**
     * Create a keyword.
     */
    public function create(array $data): Keyword
    {
        return Keyword::create($data);
    }

    /**
     * Update a keyword.
     */
    public function update(int $id, array $data): bool
    {
        $keyword = Keyword::find($id);

        if (!$keyword) {
            return false;
        }

        return $keyword->update($data);
    }

    /**
     * Delete a keyword.
     */
    public function delete(int $id, bool $hardDelete = false): bool
    {
        $keyword = Keyword::find($id);

        if (!$keyword) {
            return false;
        }

        if ($hardDelete) {
            return Keyword::where('id', $id)->delete() > 0;
        }

        return $keyword->delete();
    }

    /**
     * Count active keywords for a site.
     */
    public function getActiveCount(int $siteId): int
    {
        return Keyword::where('site_id', $siteId)->where('status', 'active')->count();
    }

    /**
     * Get keywords that need to be checked.
     */
    public function getKeywordsNeedingCheck(int $siteId): Collection
    {
        return Keyword::bySite($siteId)->needsCheck()->get();
    }
}
