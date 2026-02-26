<?php

namespace App\Repositories;

use App\Models\KeywordSuggestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for KeywordSuggestion model operations.
 *
 * Handles CRUD operations, queries, and state changes for keyword suggestions.
 *
 * @see TASK-5-2
 * @see SPEC-005
 */
class KeywordSuggestionRepository
{
    // =========================================================================
    // FIND OPERATIONS
    // =========================================================================

    /**
     * Find a suggestion by ID.
     *
     * @param int $id
     * @return KeywordSuggestion|null
     */
    public function find(int $id): ?KeywordSuggestion
    {
        return KeywordSuggestion::find($id);
    }

    /**
     * Find a suggestion by keyword for a specific site.
     *
     * @param int $siteId
     * @param string $keyword
     * @return KeywordSuggestion|null
     */
    public function findByKeyword(int $siteId, string $keyword): ?KeywordSuggestion
    {
        return KeywordSuggestion::where('site_id', $siteId)
            ->where('keyword', $keyword)
            ->first();
    }

    /**
     * Find all suggestions for a site.
     *
     * @param int $siteId
     * @return Collection
     */
    public function findBySite(int $siteId): Collection
    {
        return KeywordSuggestion::bySite($siteId)->get();
    }

    /**
     * Find dismissed suggestions for a site.
     *
     * @param int $siteId
     * @return Collection
     */
    public function findDismissed(int $siteId): Collection
    {
        return KeywordSuggestion::bySite($siteId)
            ->where('is_dismissed', true)
            ->get();
    }

    /**
     * Get suggestions by source.
     *
     * @param int $siteId
     * @param string $source
     * @return Collection
     */
    public function getBySource(int $siteId, string $source): Collection
    {
        return KeywordSuggestion::bySite($siteId)
            ->bySource($source)
            ->get();
    }

    // =========================================================================
    // CREATE OPERATIONS
    // =========================================================================

    /**
     * Create a new suggestion.
     *
     * @param array $data
     * @return KeywordSuggestion
     */
    public function create(array $data): KeywordSuggestion
    {
        return KeywordSuggestion::create($data);
    }

    /**
     * Bulk create suggestions.
     *
     * @param array $suggestions Array of suggestion data arrays
     * @return int Number of created suggestions
     */
    public function bulkCreate(array $suggestions): int
    {
        $count = 0;
        foreach ($suggestions as $data) {
            KeywordSuggestion::create($data);
            $count++;
        }
        return $count;
    }

    /**
     * Create or update a suggestion (upsert).
     *
     * @param array $data Must include site_id and keyword
     * @return KeywordSuggestion
     */
    public function upsertOrCreate(array $data): KeywordSuggestion
    {
        $existing = $this->findByKeyword($data['site_id'], $data['keyword']);

        if ($existing) {
            // Update existing, reset is_dismissed on rediscovery
            $existing->update(array_merge($data, ['is_dismissed' => false]));
            $existing->wasRecentlyCreated = false;
            return $existing;
        }

        $suggestion = KeywordSuggestion::create($data);
        $suggestion->wasRecentlyCreated = true;
        return $suggestion;
    }

    // =========================================================================
    // UPDATE OPERATIONS
    // =========================================================================

    /**
     * Update a suggestion.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $suggestion = $this->find($id);
        if (!$suggestion) {
            return false;
        }
        return $suggestion->update($data);
    }

    /**
     * Mark a suggestion as tracking.
     *
     * @param int $id
     * @return bool
     */
    public function markAsTracking(int $id): bool
    {
        return $this->update($id, ['is_tracking' => true]);
    }

    /**
     * Dismiss a suggestion.
     *
     * @param int $id
     * @return bool
     */
    public function dismiss(int $id): bool
    {
        return $this->update($id, ['is_dismissed' => true]);
    }

    // =========================================================================
    // DELETE OPERATIONS
    // =========================================================================

    /**
     * Delete a suggestion.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $suggestion = $this->find($id);
        if (!$suggestion) {
            return false;
        }
        return $suggestion->delete();
    }

    // =========================================================================
    // QUERY OPERATIONS
    // =========================================================================

    /**
     * Check if a suggestion exists for a site/keyword.
     *
     * @param int $siteId
     * @param string $keyword
     * @return bool
     */
    public function exists(int $siteId, string $keyword): bool
    {
        return KeywordSuggestion::where('site_id', $siteId)
            ->where('keyword', $keyword)
            ->exists();
    }

    /**
     * Count suggestions for a site.
     *
     * @param int $siteId
     * @param bool $excludeDismissed
     * @return int
     */
    public function countBySite(int $siteId, bool $excludeDismissed = false): int
    {
        $query = KeywordSuggestion::where('site_id', $siteId);

        if ($excludeDismissed) {
            $query->where('is_dismissed', false);
        }

        return $query->count();
    }

    /**
     * Get paginated suggestions with optional filters.
     *
     * @param int $siteId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $siteId, array $filters = []): LengthAwarePaginator
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 20;

        $query = KeywordSuggestion::where('site_id', $siteId)
            ->orderBy('opportunity_score', 'desc');

        // Filter by source
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Exclude dismissed by default
        $excludeDismissed = $filters['excludeDismissed'] ?? true;
        if ($excludeDismissed) {
            $query->where('is_dismissed', false);
        }

        // Exclude tracking by default
        $includeTracking = $filters['includeTracking'] ?? false;
        if (!$includeTracking) {
            $query->where('is_tracking', false);
        }

        // Min/max score filters
        if (isset($filters['minScore'])) {
            $query->where('opportunity_score', '>=', $filters['minScore']);
        }
        if (isset($filters['maxScore'])) {
            $query->where('opportunity_score', '<=', $filters['maxScore']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'opportunity_score';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
