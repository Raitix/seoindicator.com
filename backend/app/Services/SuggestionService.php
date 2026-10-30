<?php

namespace App\Services;

use App\Repositories\KeywordSuggestionRepository;
use App\Services\Fetchers\GoogleSuggestFetcher;
use App\Services\Fetchers\QuestionGenerator;
use Illuminate\Support\Facades\Log;

/**
 * Aggregation service for keyword suggestions.
 *
 * Combines suggestions from multiple sources (Google Suggest, Question Generator)
 * and stores them with calculated opportunity scores.
 *
 * @see TASK-5-6
 * @see SPEC-005
 */
class SuggestionService
{
    /**
     * Create a new SuggestionService instance.
     *
     * @param KeywordSuggestionRepository $repository
     * @param QuestionGenerator $questionGenerator
     * @param GoogleSuggestFetcher $googleSuggestFetcher
     * @param OpportunityScorer $opportunityScorer
     */
    public function __construct(
        protected KeywordSuggestionRepository $repository,
        protected QuestionGenerator $questionGenerator,
        protected GoogleSuggestFetcher $googleSuggestFetcher,
        protected OpportunityScorer $opportunityScorer
    ) {
    }

    /**
     * Generate suggestions for a site from specified sources.
     *
     * @param int $siteId
     * @param string $seedKeyword
     * @param array<string> $sources Sources to use: 'question', 'suggest'
     * @param int|null $maxResults Maximum number of results to store
     * @return int Number of suggestions stored
     */
    public function generateSuggestions(
        int $siteId,
        string $seedKeyword,
        array $sources = ['question'],
        ?int $maxResults = null
    ): int {
        $startTime = microtime(true);

        // Collect suggestions from all sources
        $allSuggestions = [];

        foreach ($sources as $source) {
            try {
                $suggestions = match ($source) {
                    'question' => $this->generateFromQuestions($seedKeyword),
                    'suggest' => $this->generateFromSuggest($seedKeyword),
                    default => [],
                };

                foreach ($suggestions as $keyword) {
                    // Deduplicate across sources
                    $key = strtolower($keyword);
                    if (!isset($allSuggestions[$key])) {
                        $allSuggestions[$key] = [
                            'keyword' => $keyword,
                            'source' => $source,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Suggestion source '{$source}' failed", [
                    'error' => $e->getMessage(),
                    'site_id' => $siteId,
                ]);
            }
        }

        // Apply maxResults limit
        if ($maxResults !== null && count($allSuggestions) > $maxResults) {
            $allSuggestions = array_slice($allSuggestions, 0, $maxResults, true);
        }

        // Store suggestions
        $storedCount = 0;
        foreach ($allSuggestions as $suggestion) {
            try {
                $opportunityScore = $this->opportunityScorer->calculate($suggestion['keyword']);

                $this->repository->upsertOrCreate([
                    'site_id' => $siteId,
                    'keyword' => $suggestion['keyword'],
                    'source' => $suggestion['source'],
                    'opportunity_score' => $opportunityScore,
                    'is_dismissed' => false, // Reset on rediscovery
                ]);

                $storedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to store suggestion', [
                    'keyword' => $suggestion['keyword'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log performance
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info('Suggestions generated', [
            'site_id' => $siteId,
            'seed_keyword' => $seedKeyword,
            'count' => $storedCount,
            'duration_ms' => $duration,
        ]);

        return $storedCount;
    }

    /**
     * Generate suggestions from question prefixes.
     *
     * @param string $seedKeyword
     * @return array<string>
     */
    protected function generateFromQuestions(string $seedKeyword): array
    {
        return $this->questionGenerator->generate($seedKeyword);
    }

    /**
     * Generate suggestions from Google Suggest API.
     *
     * @param string $seedKeyword
     * @return array<string>
     */
    protected function generateFromSuggest(string $seedKeyword): array
    {
        return $this->googleSuggestFetcher->fetch($seedKeyword);
    }
}
