<?php

namespace App\Services;

use App\Models\GSCData;
use App\Models\Keyword;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * QueryMatcher service for fuzzy matching keywords to GSC queries.
 */
class QueryMatcher
{
    /**
     * Default similarity threshold for auto-matching.
     */
    protected float $threshold;

    public function __construct(float $threshold = 0.90)
    {
        $this->threshold = $threshold;
    }

    /**
     * Find best matching GSC queries for a keyword.
     *
     * @return array<int, array{query: string, score: float}>
     */
    public function findMatches(Keyword $keyword, int $limit = 5): array
    {
        $queries = $this->getGscQueriesForKeyword($keyword);

        if ($queries->isEmpty()) {
            return [];
        }

        $scored = [];
        foreach ($queries as $query) {
            $score = $this->calculateSimilarity($keyword->keyword, $query);
            $scored[] = ['query' => $query, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Calculate similarity score between two strings (0..1).
     */
    public function calculateSimilarity(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $distance = levenshtein($a, $b);
        $maxLen = max(mb_strlen($a), mb_strlen($b));

        if ($maxLen === 0) {
            return 1.0;
        }

        $score = 1 - ($distance / $maxLen);

        return max(0.0, min(1.0, $score));
    }

    /**
     * Auto-match keywords for a given site.
     */
    public function autoMatchForSite(int $siteId, ?float $threshold = null): int
    {
        $threshold = $threshold ?? $this->threshold;

        $keywords = Keyword::where('site_id', $siteId)
            ->where(function ($query) {
                $query->whereNull('gsc_query_match')
                    ->orWhere('gsc_query_match', '');
            })
            ->get();

        $updated = 0;
        foreach ($keywords as $keyword) {
            $matches = $this->findMatches($keyword, 1);
            if (empty($matches)) {
                continue;
            }

            $best = $matches[0];
            if ($best['score'] >= $threshold) {
                $keyword->update(['gsc_query_match' => $best['query']]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Manual override for a keyword match.
     */
    public function setManualMatch(Keyword $keyword, string $query): bool
    {
        return $keyword->update([
            'gsc_query_match' => $this->normalize($query),
        ]);
    }

    /**
     * Normalize query strings.
     */
    protected function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = trim($value);
        $value = preg_replace('/[^\pL\pN\s]+/u', '', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return $value;
    }

    /**
     * Get distinct GSC queries for the keyword's site.
     */
    protected function getGscQueriesForKeyword(Keyword $keyword): Collection
    {
        $query = GSCData::query();

        if (Schema::hasColumn('gsc_data', 'site_id') && $keyword->site_id) {
            $query->where('site_id', $keyword->site_id);
        } elseif (Schema::hasColumn('gsc_data', 'project_id') && $keyword->site_id) {
            // Fallback for legacy schema (project_id)
            $query->where('project_id', $keyword->site_id);
        } else {
            return collect();
        }

        return $query->distinct()->pluck('query');
    }
}
