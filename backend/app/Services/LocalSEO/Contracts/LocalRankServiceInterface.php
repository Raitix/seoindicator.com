<?php

namespace App\Services\LocalSEO\Contracts;

interface LocalRankServiceInterface
{
    /**
     * Get the last ranking for a business location.
     */
    public function getLastRanking(int $locationId): ?object;

    /**
     * Get the previous ranking (for comparison) for a business location.
     */
    public function getPreviousRanking(int $locationId): ?object;

    /**
     * Check ranking for a specific keyword.
     */
    public function checkRanking(int $locationId, string $keyword): ?object;
}
