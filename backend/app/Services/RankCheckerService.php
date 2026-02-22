<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Services\SERP\SERPEntry;
use App\Services\SERP\SERPOptionsData;
use App\Services\SERP\SERPResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RankCheckerService
{
    public function __construct(private readonly SERPFetcherInterface $fetcher)
    {
    }

    /**
     * Check a single keyword against user + competitor domains.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function checkKeyword(string $keyword, string $userDomain, array $competitorDomains = [], array $options = []): array
    {
        $start = microtime(true);
        $sanitizedUser = $this->sanitizeDomain($userDomain);
        $sanitizedCompetitors = array_values(array_filter(array_map([$this, 'sanitizeDomain'], $competitorDomains)));

        $targetUrl = $sanitizedUser ? $this->normalizeUrl($sanitizedUser) : null;
        $serpOptions = new SERPOptionsData(targetUrl: $targetUrl);

        $result = $this->fetcher->fetch($keyword, $serpOptions);
        $payload = $this->normalizeResult($result);

        $results = $payload['results'] ?? [];
        $userMatch = $this->findDomainMatch($results, $sanitizedUser);

        $competitorResults = [];
        foreach ($sanitizedCompetitors as $domain) {
            $match = $this->findDomainMatch($results, $domain);
            $competitorEntry = [
                'domain' => $domain,
                'position' => $match['position'],
                'url' => $match['url'],
            ];

            if (($options['throwOnCompetitorFailure'] ?? true) === false && $match['position'] === null) {
                $message = sprintf('Competitor ranking not found for domain: %s', $domain);
                Log::error($message, ['domain' => $domain, 'keyword' => $keyword]);
                $competitorEntry['error'] = $message;
            }

            $competitorResults[] = $competitorEntry;
        }

        $durationMs = $payload['duration_ms'] ?? (int) round((microtime(true) - $start) * 1000);

        $response = [
            'keyword' => $keyword,
            'user' => [
                'domain' => $sanitizedUser,
                'position' => $userMatch['position'],
                'url' => $userMatch['url'],
            ],
            'competitors' => $competitorResults,
            'duration_ms' => (int) $durationMs,
        ];

        if (isset($payload['serp_features'])) {
            $response['serp_features'] = $payload['serp_features'];
        }

        return $response;
    }

    /**
     * @param array<int, string> $keywords
     * @param array<int, string> $competitorDomains
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function checkKeywords(array $keywords, string $userDomain, array $competitorDomains = [], array $options = []): array
    {
        $results = [];
        foreach ($keywords as $keyword) {
            $results[] = $this->checkKeyword((string) $keyword, $userDomain, $competitorDomains, $options);
        }

        return $results;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Competitor>
     */
    public function getCompetitorsForSite(int $userId)
    {
        return Competitor::where('user_id', $userId)->orderBy('id')->get();
    }

    /**
     * Run a keyword check and store ranking + competitor rankings.
     *
     * @param array<int, string>|null $competitorDomains
     */
    public function checkAndStore(int $keywordId, ?array $competitorDomains = null): bool
    {
        $keyword = Keyword::find($keywordId);
        if (!$keyword) {
            throw new \RuntimeException('Keyword not found.');
        }

        $userDomain = '';
        if (!empty($keyword->target_url)) {
            $userDomain = $this->sanitizeDomain($keyword->target_url);
        } elseif ($keyword->site_id && $keyword->site) {
            $userDomain = $this->sanitizeDomain($keyword->site->url);
        }

        if ($competitorDomains === null) {
            $competitorDomains = $this->getCompetitorsForSite($keyword->user_id)
                ->pluck('domain')
                ->all();
        }

        $result = $this->checkKeyword($keyword->keyword, $userDomain, $competitorDomains);

        $checkedAt = now();
        $serpFeatures = $result['serp_features'] ?? null;
        $serpFeatures = is_array($serpFeatures) ? json_encode($serpFeatures) : $serpFeatures;

        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => $result['user']['position'],
            'url_found' => $result['user']['url'],
            'serp_features' => $serpFeatures,
            'checked_at' => $checkedAt,
            'check_duration_ms' => $result['duration_ms'] ?? null,
            'error' => null,
        ]);

        $keyword->last_checked_at = $checkedAt;
        $keyword->save();

        if (!empty($competitorDomains)) {
            $competitors = Competitor::where('user_id', $keyword->user_id)
                ->whereIn('domain', $competitorDomains)
                ->get()
                ->keyBy('domain');

            foreach ($result['competitors'] as $competitorResult) {
                $domain = $competitorResult['domain'] ?? null;
                if (!$domain || !$competitors->has($domain)) {
                    continue;
                }

                CompetitorRanking::create([
                    'competitor_id' => $competitors[$domain]->id,
                    'keyword_id' => $keyword->id,
                    'position' => $competitorResult['position'],
                    'url' => $competitorResult['url'],
                    'checked_at' => $checkedAt,
                ]);
            }
        }

        return true;
    }

    /**
     * @param SERPResult|array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalizeResult($result): array
    {
        if ($result instanceof SERPResult) {
            $results = array_map(function (SERPEntry $entry) {
                return [
                    'position' => $entry->getPosition(),
                    'url' => $entry->getUrl(),
                ];
            }, $result->getResults());

            return [
                'keyword' => $result->getKeyword(),
                'results' => $results,
                'duration_ms' => $result->getDurationMs(),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array{position: int|null, url: string|null}
     */
    private function findDomainMatch(array $results, ?string $domain): array
    {
        if (!$domain) {
            return ['position' => null, 'url' => null];
        }

        foreach ($results as $entry) {
            $url = $entry['url'] ?? null;
            $position = $entry['position'] ?? null;

            if (!$url || !$position) {
                continue;
            }

            $host = $this->sanitizeDomain($url);
            if ($this->domainMatches($domain, $host)) {
                return ['position' => (int) $position, 'url' => $url];
            }
        }

        return ['position' => null, 'url' => null];
    }

    private function sanitizeDomain(?string $domain): ?string
    {
        if (!$domain) {
            return null;
        }

        $domain = trim(strtolower($domain));
        if (str_contains($domain, '://')) {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        } elseif (str_contains($domain, '/')) {
            $domain = parse_url('https://' . $domain, PHP_URL_HOST) ?: $domain;
        }

        $domain = preg_replace('/[^a-z0-9\.-]/', '', $domain) ?? '';
        $domain = ltrim($domain, '.');
        $domain = rtrim($domain, '.');
        $domain = preg_replace('/^www\./', '', $domain) ?? $domain;

        return $domain ?: null;
    }

    private function normalizeUrl(string $domain): string
    {
        if (Str::startsWith($domain, ['http://', 'https://'])) {
            return $domain;
        }

        return 'https://' . $domain;
    }

    private function domainMatches(string $expected, ?string $actual): bool
    {
        if (!$actual) {
            return false;
        }

        if ($actual === $expected) {
            return true;
        }

        return str_ends_with($actual, '.' . $expected);
    }
}
