<?php

namespace App\Services\SERP;

use App\Services\SERPFetcherInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;

class MockSERPFetcher implements SERPFetcherInterface
{
    public function fetch(string $keyword, SERPOptions $options): SERPResult
    {
        $start = microtime(true);

        if ($options->getSimulateError()) {
            return $this->buildErrorResult($keyword, $options, $options->getSimulateError(), $start);
        }

        $delayMs = $options->getDelayMs() ?? $this->randomDelay($options->getSeed());
        usleep($delayMs * 1000);

        $results = $this->buildResults($keyword, $options);
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            $results,
            $checkedAt,
            null,
            $durationMs
        );
    }

    private function buildResults(string $keyword, SERPOptions $options): array
    {
        $rng = $this->makeRandomizer($options->getSeed());
        $results = [];
        $targetUrl = $options->getTargetUrl();
        $includeTarget = $targetUrl && $options->shouldIncludeTargetUrl();
        $targetPosition = $includeTarget
            ? $this->resolveTargetPosition($options, $rng)
            : null;

        for ($position = 1; $position <= 100; $position++) {
            $url = sprintf('https://example-%d.com/%s', $position, $this->slugify($keyword));

            if ($includeTarget && $targetPosition === $position) {
                $url = $targetUrl;
            }

            $isOurSite = $this->isTargetMatch($targetUrl, $url);

            $results[] = new SERPEntryData(
                $position,
                $url,
                sprintf('Result %d for %s', $position, $keyword),
                sprintf('Mock description for %s #%d', $keyword, $position),
                $isOurSite
            );
        }

        return $results;
    }

    private function resolveTargetPosition(SERPOptions $options, Randomizer $rng): int
    {
        $targetPosition = $options->getTargetUrlPosition();
        if ($targetPosition !== null && $targetPosition >= 1 && $targetPosition <= 100) {
            return $targetPosition;
        }

        return $rng->getInt(1, 100);
    }

    private function randomDelay(?int $seed): int
    {
        $rng = $this->makeRandomizer($seed);
        return $rng->getInt(100, 500);
    }

    private function makeRandomizer(?int $seed): Randomizer
    {
        if ($seed === null) {
            return new Randomizer();
        }

        return new Randomizer(new Mt19937($seed));
    }

    private function buildErrorResult(string $keyword, SERPOptions $options, string $error, float $start): SERPResult
    {
        $checkedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        return new SERPResultData(
            $keyword,
            $options->getSearchEngine(),
            $options->getLocation(),
            $options->getDevice(),
            [],
            $checkedAt,
            $error,
            $durationMs
        );
    }

    private function isTargetMatch(?string $targetUrl, string $resultUrl): bool
    {
        if (!$targetUrl) {
            return false;
        }

        if (strcasecmp($targetUrl, $resultUrl) === 0) {
            return true;
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $resultHost = parse_url($resultUrl, PHP_URL_HOST);

        if (!$targetHost || !$resultHost) {
            return false;
        }

        return strcasecmp($targetHost, $resultHost) === 0;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        return trim($value, '-') ?: 'keyword';
    }
}
