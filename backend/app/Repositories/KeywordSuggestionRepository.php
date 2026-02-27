<?php

namespace App\Repositories;

use App\Models\KeywordSuggestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for KeywordSuggestion model.
 *
 * Provides data access layer for keyword discovery feature.
 *
 * @see TASK-5-2
 * @see SPEC-005
 */
class KeywordSuggestionRepository
{
    /**
     * Find a suggestion by ID.
     */
    public function find(int $id): ?KeywordSuggestion
    {
        return KeywordSuggestion::find($id);
    }

    /**
     * Create a new suggestion.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): KeywordSuggestion
    {
        return KeywordSuggestion::create($data);
    }

    /**
     * Update a suggestion.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $suggestion = KeywordSuggestion::find($id);

        if (!$suggestion) {
            return false;
        }

        return $suggestion->update($data);
    }

    /**
     * Delete a suggestion.
     */
    public function delete(int $id): bool
    {
        $suggestion = KeywordSuggestion::find($id);

        if (!$suggestion) {
            return false;
        }

        return KeywordSuggestion::where('id', $id)->delete() > 0;
    }

    /**
     * Find all suggestions for a site.
     *
     * @return Collection<int, KeywordSuggestion>
     */
    public function findBySite(int $siteId): Collection
    {
        return KeywordSuggestion::bySite($siteId)
            ->orderBy('opportunity_score', 'desc')
            ->orderBy('discovered_at', 'desc')
            ->get();
    }

    /**
     * Find dismissed suggestions for a site.
     *
     * @return Collection<int, KeywordSuggestion>
     */
    public function findDismissed(int $siteId): Collection
    {
        return KeywordSuggestion::bySite($siteId)
            ->where('is_dismissed', true)
            ->orderBy('discovered_at', 'desc')
            ->get();
    }

    /**
     * Mark a suggestion as tracking.
     */
    public function markAsTracking(int $id): bool
    {
        $suggestion = KeywordSuggestion::find($id);

        if (!$suggestion) {
            return false;
        }

        return $suggestion->update(['is_tracking' => true]);
    }

    /**
     * Dismiss a suggestion.
     */
    public function dismiss(int $id): bool
    {
        $suggestion = KeywordSuggestion::find($id);

        if (!$suggestion) {
            return false;
        }

        return $suggestion->update(['is_dismissed' => true]);
    }

    /**
     * Find a suggestion by keyword for a specific site.
     */
    public function findByKeyword(int $siteId, string $keyword): ?KeywordSuggestion
    {
        return KeywordSuggestion::bySite($siteId)
            ->where('keyword', $keyword)
            ->first();
    }

    /**
     * Bulk create suggestions.
     *
     * @param array<int, array<string, mixed>> $suggestions
     * @return int Number of suggestions created
     */
    public function bulkCreate(array $suggestions): int
    {
        $count = 0;
        $now = now();

        foreach ($suggestions as $suggestion) {
            $suggestion['discovered_at'] = $suggestion['discovered_at'] ?? $now;
            $suggestion['is_tracking'] = $suggestion['is_tracking'] ?? false;
            $suggestion['is_dismissed'] = $suggestion['is_dismissed'] ?? false;

            KeywordSuggestion::create($suggestion);
            $count++;
        }

        return $count;
    }

    /**
     * Check if a suggestion exists for a site and keyword.
     */
    public function exists(int $siteId, string $keyword): bool
    {
        return KeywordSuggestion::bySite($siteId)
            ->where('keyword', $keyword)
            ->exists();
    }

    /**
     * Get paginated suggestions for a site.
     *
     * @param array<string, mixed> $params
     */
    public function getPaginated(int $siteId, array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? 20;
        $source = $params['source'] ?? null;
        $excludeDismissed = $params['excludeDismissed'] ?? true;
        $includeTracking = $params['includeTracking'] ?? false;
        $minScore = $params['minScore'] ?? null;
        $maxScore = $params['maxScore'] ?? null;
        $sort = $params['sort'] ?? 'opportunity_score';
        $order = $params['order'] ?? 'desc';

        $query = KeywordSuggestion::bySite($siteId);

        if ($source) {
            $query->bySource($source);
        }

        if ($excludeDismissed) {
            $query->notDismissed();
        }

        // Exclude tracking suggestions by default (unless explicitly included)
        if (!$includeTracking) {
            $query->notTracking();
        }

        // Filter by min opportunity score
        if ($minScore !== null) {
            $query->where('opportunity_score', '>=', (int) $minScore);
        }

        // Filter by max opportunity score
        if ($maxScore !== null) {
            $query->where('opportunity_score', '<=', (int) $maxScore);
        }

        // Dynamic sorting
        $orderDirection = strtolower($order) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $orderDirection);

        // Secondary sort by discovered_at for consistent ordering
        if ($sort !== 'discovered_at') {
            $query->orderBy('discovered_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get suggestions by source for a site.
     *
     * @return Collection<int, KeywordSuggestion>
     */
    public function getBySource(int $siteId, string $source): Collection
    {
        return KeywordSuggestion::bySite($siteId)
            ->bySource($source)
            ->orderBy('opportunity_score', 'desc')
            ->orderBy('discovered_at', 'desc')
            ->get();
    }

    /**
     * Count suggestions for a site.
     *
     * @param bool $excludeDismissed Whether to exclude dismissed suggestions (default: true)
     */
    public function countBySite(int $siteId, bool $excludeDismissed = true): int
    {
        $query = KeywordSuggestion::bySite($siteId);

        if ($excludeDismissed) {
            $query->notDismissed();
        }

        return $query->count();
    }

    /**
     * Create or update a suggestion.
     *
     * If the suggestion exists (by site_id + keyword), it will be updated
     * and is_dismissed will be reset to false (rediscovery).
     *
     * @param array<string, mixed> $data
     */
    public function upsertOrCreate(array $data): KeywordSuggestion
    {
        $siteId = $data['site_id'];
        $keyword = $data['keyword'];

        $existing = $this->findByKeyword($siteId, $keyword);

        if ($existing) {
            // Update existing - reset dismissed status on rediscovery
            $existing->update([
                'source' => $data['source'] ?? $existing->source,
                'search_volume' => $data['search_volume'] ?? $existing->search_volume,
                'difficulty_score' => $data['difficulty_score'] ?? $existing->difficulty_score,
                'opportunity_score' => $data['opportunity_score'] ?? $existing->opportunity_score,
                'is_dismissed' => false, // Reset dismissed on rediscovery
            ]);

            return $existing;
        }

        // Create new
        return KeywordSuggestion::create($data);
    }
}
