<?php

namespace App\Repositories;

use App\Models\Keyword;
use App\Services\Traits\EmitsWebhookEvents;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keyword Repository for SPEC-001-2.
 *
 * Emits webhook events for keyword lifecycle changes:
 * - keyword.added when a keyword is created
 * - keyword.removed when a keyword is deleted
 */
class KeywordRepository
{
    use EmitsWebhookEvents;

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
     *
     * Emits keyword.added webhook event after creation.
     */
    public function create(array $data): Keyword
    {
        $keyword = Keyword::create($data);

        // Emit webhook event for keyword creation
        if ($keyword->user_id) {
            $this->emitKeywordAddedEvent(
                userId: $keyword->user_id,
                keywordId: $keyword->id,
                keyword: $keyword->keyword,
                siteId: $keyword->site_id ?? 0,
                targetUrl: $keyword->target_url,
                searchEngine: $keyword->search_engine ?? 'google',
                location: $keyword->location ?? 'us',
                device: $keyword->device ?? 'desktop',
                status: $keyword->status ?? 'active'
            );
        }

        return $keyword;
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
     *
     * Emits keyword.removed webhook event before deletion.
     */
    public function delete(int $id, bool $hardDelete = false): bool
    {
        $keyword = Keyword::find($id);

        if (!$keyword) {
            return false;
        }

        // Store data for webhook before deletion
        $keywordId = $keyword->id;
        $keywordText = $keyword->keyword;
        $siteId = $keyword->site_id;
        $userId = $keyword->user_id;

        // Emit webhook event for keyword deletion
        if ($userId) {
            $this->emitKeywordRemovedEvent(
                userId: $userId,
                keywordId: $keywordId,
                keyword: $keywordText,
                siteId: $siteId ?? 0
            );
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
