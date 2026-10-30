<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository for API Key data access.
 *
 * TASK-6-2: API Key Model and Repository.
 */
class ApiKeyRepository
{
    /**
     * Create a new API key.
     *
     * @param array $data Key data including user_id, name, key_hash, key_prefix
     * @return ApiKey
     */
    public function create(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    /**
     * Find an API key by ID.
     *
     * @param int $id
     * @return ApiKey|null
     */
    public function findById(int $id): ?ApiKey
    {
        return ApiKey::find($id);
    }

    /**
     * Find an API key by its hash.
     *
     * @param string $hash
     * @return ApiKey|null
     */
    public function findByHash(string $hash): ?ApiKey
    {
        return ApiKey::where('key_hash', $hash)->first();
    }

    /**
     * Find an API key by its prefix.
     *
     * @param string $prefix
     * @return ApiKey|null
     */
    public function findByPrefix(string $prefix): ?ApiKey
    {
        return ApiKey::where('key_prefix', $prefix)->first();
    }

    /**
     * Get all API keys for a user.
     *
     * @param User $user
     * @param array $filters Optional filters (status)
     * @param int $perPage Pagination size
     * @return LengthAwarePaginator
     */
    public function getForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ApiKey::where('user_id', $user->id);

        // Apply status filter
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Order by most recent
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Update an API key.
     *
     * @param ApiKey $apiKey
     * @param array $data
     * @return ApiKey
     */
    public function update(ApiKey $apiKey, array $data): ApiKey
    {
        $apiKey->update($data);
        return $apiKey->fresh();
    }

    /**
     * Revoke an API key.
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function revoke(ApiKey $apiKey): bool
    {
        if ($apiKey->revoked_at !== null) {
            return true; // Already revoked
        }

        return $apiKey->update(['revoked_at' => now()]);
    }

    /**
     * Mark an API key as used.
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function markAsUsed(ApiKey $apiKey): bool
    {
        return $apiKey->update(['last_used_at' => now()]);
    }

    /**
     * Delete an API key.
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function delete(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }

    /**
     * Check if an API key is valid for use.
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function isValidForUse(ApiKey $apiKey): bool
    {
        return $apiKey->isActive();
    }

    /**
     * Count API keys for a user.
     *
     * @param User $user
     * @return int
     */
    public function countForUser(User $user): int
    {
        return ApiKey::where('user_id', $user->id)->count();
    }
}
