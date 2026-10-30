<?php

namespace App\Services\Fetchers;

/**
 * Interface for keyword suggestion fetchers.
 *
 * Defines the contract for services that fetch keyword suggestions
 * from various sources (Google, Bing, etc.).
 *
 * @see TASK-5-4
 * @see SPEC-005
 */
interface SuggestFetcherInterface
{
    /**
     * Fetch suggestions for a keyword.
     *
     * @param string $keyword The keyword to get suggestions for
     * @return array<string> List of suggestion strings
     */
    public function fetch(string $keyword): array;
}
