<?php

namespace App\Repositories;

use App\Models\Competitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Competitor Repository for SPEC-003-2.
 *
 * Handles database operations for competitor entities.
 */
class CompetitorRepository
{
    /**
     * Create a new competitor.
     *
     * @param int $userId
     * @param array $data
     * @return Competitor
     */
    public function create(int $userId, array $data): Competitor
    {
        return Competitor::create([
            'user_id' => $userId,
            'domain' => $data['domain'],
            'display_name' => $data['displayName'] ?? $data['display_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get all competitors for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function listByUser(int $userId): Collection
    {
        return Competitor::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a competitor by ID.
     *
     * @param int $id
     * @return Competitor|null
     */
    public function find(int $id): ?Competitor
    {
        return Competitor::find($id);
    }

    /**
     * Find a competitor by ID for a specific user.
     *
     * @param int $id
     * @param int $userId
     * @return Competitor|null
     */
    public function findForUser(int $id, int $userId): ?Competitor
    {
        return Competitor::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Update a competitor.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $competitor = Competitor::find($id);
        
        if (!$competitor) {
            return false;
        }

        return $competitor->update([
            'display_name' => $data['displayName'] ?? $data['display_name'] ?? $competitor->display_name,
            'notes' => $data['notes'] ?? $competitor->notes,
        ]);
    }

    /**
     * Delete a competitor (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $competitor = Competitor::find($id);
        
        if (!$competitor) {
            return false;
        }

        return $competitor->delete();
    }

    /**
     * Check if a competitor exists for a user by domain.
     *
     * @param int $userId
     * @param string $domain
     * @return bool
     */
    public function existsForUser(int $userId, string $domain): bool
    {
        return Competitor::where('user_id', $userId)
            ->where('domain', $domain)
            ->exists();
    }

    /**
     * Count competitors for a user.
     *
     * @param int $userId
     * @return int
     */
    public function countByUser(int $userId): int
    {
        return Competitor::where('user_id', $userId)->count();
    }
}
