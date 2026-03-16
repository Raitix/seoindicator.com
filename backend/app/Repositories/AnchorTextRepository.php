<?php

namespace App\Repositories;

use App\Models\AnchorText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AnchorTextRepository for TASK-23-2.
 *
 * Provides data access layer for anchor text management.
 */
class AnchorTextRepository
{
    /**
     * Find an anchor text by ID.
     */
    public function findById(int $id): ?AnchorText
    {
        return AnchorText::find($id);
    }

    /**
     * Create a new anchor text.
     */
    public function create(array $data): AnchorText
    {
        return AnchorText::create($data);
    }

    /**
     * Update an anchor text.
     */
    public function update(int $id, array $data): bool
    {
        $anchorText = AnchorText::find($id);

        if (!$anchorText) {
            return false;
        }

        return $anchorText->update($data);
    }

    /**
     * Delete an anchor text.
     */
    public function delete(int $id): bool
    {
        $anchorText = AnchorText::find($id);

        if (!$anchorText) {
            return false;
        }

        return $anchorText->delete();
    }

    /**
     * Get all anchor texts for a project.
     */
    public function getByProject(int $projectId): Collection
    {
        return AnchorText::where('project_id', $projectId)->get();
    }

    /**
     * Update classification flags for an anchor text.
     */
    public function updateClassification(int $id, bool $isBranded, bool $isExactMatch): bool
    {
        $anchorText = AnchorText::find($id);

        if (!$anchorText) {
            return false;
        }

        return $anchorText->update([
            'is_branded' => $isBranded,
            'is_exact_match' => $isExactMatch,
        ]);
    }

    /**
     * Bulk update classification for multiple anchor texts.
     */
    public function bulkUpdateClassification(array $ids, bool $isBranded, bool $isExactMatch): int
    {
        return AnchorText::whereIn('id', $ids)
            ->update([
                'is_branded' => $isBranded,
                'is_exact_match' => $isExactMatch,
            ]);
    }

    /**
     * Classify an anchor text as branded.
     */
    public function classifyAsBranded(int $id): bool
    {
        $anchorText = AnchorText::find($id);

        if (!$anchorText) {
            return false;
        }

        return $anchorText->update(['is_branded' => true]);
    }

    /**
     * Classify an anchor text as exact match.
     */
    public function classifyAsExactMatch(int $id): bool
    {
        $anchorText = AnchorText::find($id);

        if (!$anchorText) {
            return false;
        }

        return $anchorText->update(['is_exact_match' => true]);
    }

    /**
     * Get all branded anchor texts for a project.
     */
    public function getBranded(int $projectId): Collection
    {
        return AnchorText::where('project_id', $projectId)
            ->where('is_branded', true)
            ->get();
    }

    /**
     * Get all exact match anchor texts for a project.
     */
    public function getExactMatch(int $projectId): Collection
    {
        return AnchorText::where('project_id', $projectId)
            ->where('is_exact_match', true)
            ->get();
    }

    /**
     * Get top anchor texts by backlinks count.
     */
    public function getTopByBacklinks(int $projectId, int $limit = 10): Collection
    {
        return AnchorText::where('project_id', $projectId)
            ->orderBy('backlinks_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get total count for a project.
     */
    public function getTotalCount(int $projectId): int
    {
        return AnchorText::where('project_id', $projectId)->count();
    }

    /**
     * Calculate total backlinks from all anchor texts.
     */
    public function getTotalBacklinks(int $projectId): int
    {
        return (int) AnchorText::where('project_id', $projectId)
            ->sum('backlinks_count');
    }

    /**
     * Count unique anchor texts for a project.
     */
    public function countUnique(int $projectId): int
    {
        return AnchorText::where('project_id', $projectId)
            ->distinct('anchor_text')
            ->count('anchor_text');
    }

    /**
     * Calculate anchor text diversity ratio.
     */
    public function calculateDiversity(int $projectId): float
    {
        $total = $this->getTotalBacklinks($projectId);

        if ($total === 0) {
            return 0.0;
        }

        $unique = $this->countUnique($projectId);

        return round($unique / $total, 4);
    }

    /**
     * Get the percentage of the top anchor text for a project.
     * Used by BacklinkSnapshotService to calculate anchor diversity score.
     *
     * @param int $projectId The project ID
     * @return float The percentage as a decimal (0.0 to 1.0)
     */
    public function getTopAnchorPercentage(int $projectId): float
    {
        $totalBacklinks = $this->getTotalBacklinks($projectId);

        if ($totalBacklinks === 0) {
            return 0.0;
        }

        $topAnchor = AnchorText::where('project_id', $projectId)
            ->orderBy('backlinks_count', 'desc')
            ->first();

        if (!$topAnchor) {
            return 0.0;
        }

        return round($topAnchor->backlinks_count / $totalBacklinks, 4);
    }

    /**
     * Get branded vs non-branded ratio.
     */
    public function getBrandedRatio(int $projectId): array
    {
        $brandedCount = AnchorText::where('project_id', $projectId)
            ->where('is_branded', true)
            ->sum('backlinks_count');

        $nonBrandedCount = AnchorText::where('project_id', $projectId)
            ->where('is_branded', false)
            ->sum('backlinks_count');

        $total = $brandedCount + $nonBrandedCount;

        return [
            'branded' => $total > 0 ? round($brandedCount / $total, 4) : 0.0,
            'non_branded' => $total > 0 ? round($nonBrandedCount / $total, 4) : 0.0,
            'branded_count' => (int) $brandedCount,
            'non_branded_count' => (int) $nonBrandedCount,
        ];
    }

    /**
     * Insert or update an anchor text by project and text.
     */
    public function upsert(array $data): AnchorText
    {
        $existing = AnchorText::where('project_id', $data['project_id'])
            ->where('anchor_text', $data['anchor_text'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return AnchorText::create($data);
    }
}
