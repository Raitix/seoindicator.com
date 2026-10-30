<?php

namespace App\Services\SERP;

use App\Services\SERPFetcherInterface;
use Illuminate\Support\Facades\Http;

class HttpSERPFetcher implements SERPFetcherInterface
{
    public function fetch(string $keyword, SERPOptions $options): SERPResult
    {
        $start = microtime(true);

        $response = Http::get('https://serp.local/mock', [
            'keyword' => $keyword,
            'searchEngine' => $options->getSearchEngine(),
            'location' => $options->getLocation(),
            'device' => $options->getDevice(),
        ]);

        $payload = $response->json() ?? [];
        $results = [];
        $targetUrl = $options->getTargetUrl();

        foreach (($payload['results'] ?? []) as $entry) {
            $url = $entry['url'] ?? '';
            $position = (int) ($entry['position'] ?? 0);

            if (!$url || $position <= 0) {
                continue;
            }

            $results[] = new SERPEntryData(
                $position,
                $url,
                $entry['title'] ?? '',
                $entry['description'] ?? '',
                $targetUrl ? str_contains($url, $targetUrl) : false
            );
        }

        $durationMs = $payload['duration_ms'] ?? (int) round((microtime(true) - $start) * 1000);
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            $results,
            $checkedAt,
            $payload['error'] ?? null,
            (int) $durationMs,
            is_array($payload['serp_features'] ?? null) ? $payload['serp_features'] : null
        );
    }
}
