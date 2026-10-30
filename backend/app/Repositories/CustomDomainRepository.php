<?php

namespace App\Repositories;

use App\Models\CustomDomain;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for CustomDomain model.
 *
 * @see TASK-15-2
 * @see SPEC-015
 * @see FEAT-015
 */
class CustomDomainRepository
{
    /**
     * Create a new custom domain.
     *
     * @param array $data
     * @return CustomDomain
     */
    public function create(array $data): CustomDomain
    {
        $domain = CustomDomain::create($data);
        return $domain->fresh();
    }

    /**
     * Find a custom domain by ID.
     *
     * @param int $id
     * @return CustomDomain|null
     */
    public function find(int $id): ?CustomDomain
    {
        return CustomDomain::find($id);
    }

    /**
     * Find a custom domain by domain name.
     *
     * @param string $domain
     * @return CustomDomain|null
     */
    public function findByDomain(string $domain): ?CustomDomain
    {
        return CustomDomain::byDomain($domain)->first();
    }

    /**
     * Find all custom domains for an account.
     *
     * @param int $accountId
     * @return Collection<int, CustomDomain>
     */
    public function findByAccount(int $accountId): Collection
    {
        return CustomDomain::forAccount($accountId)->get();
    }

    /**
     * Update a custom domain.
     *
     * @param int $id
     * @param array $data
     * @return CustomDomain|false
     */
    public function update(int $id, array $data): CustomDomain|false
    {
        $domain = $this->find($id);

        if (!$domain) {
            return false;
        }

        $domain->update($data);
        return $domain->fresh();
    }

    /**
     * Delete a custom domain.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $domain = $this->find($id);

        if (!$domain) {
            return false;
        }

        return $domain->delete();
    }

    /**
     * Find all verified custom domains for an account.
     *
     * @param int $accountId
     * @return Collection<int, CustomDomain>
     */
    public function findVerifiedByAccount(int $accountId): Collection
    {
        return CustomDomain::forAccount($accountId)
            ->verified()
            ->get();
    }

    /**
     * Check if a domain exists.
     *
     * @param string $domain
     * @return bool
     */
    public function exists(string $domain): bool
    {
        return CustomDomain::byDomain($domain)->exists();
    }

    /**
     * Get the primary (first verified) domain for an account.
     *
     * @param int $accountId
     * @return CustomDomain|null
     */
    public function getPrimaryDomain(int $accountId): ?CustomDomain
    {
        return CustomDomain::forAccount($accountId)
            ->verified()
            ->first();
    }

    /**
     * Find all SSL-ready custom domains for an account.
     *
     * @param int $accountId
     * @return Collection<int, CustomDomain>
     */
    public function findSslReadyByAccount(int $accountId): Collection
    {
        return CustomDomain::forAccount($accountId)
            ->sslReady()
            ->get();
    }
}
