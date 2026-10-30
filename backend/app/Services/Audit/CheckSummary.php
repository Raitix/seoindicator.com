<?php

namespace App\Services\Audit;

/**
 * Data Transfer Object for aggregated broken link check results.
 *
 * Provides a summary of all links checked on a page, including
 * counts by type and status code for easy analysis.
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class CheckSummary
{
    /**
     * @param int $totalChecked Total number of unique URLs checked
     * @param int $validCount URLs returning 2xx status
     * @param int $brokenCount URLs returning 404/410
     * @param int $redirectCount URLs returning 3xx status
     * @param int $timeoutCount URLs that timed out
     * @param int $errorCount URLs with other errors (5xx, connection errors)
     * @param array<string, int> $byStatusCode Counts grouped by HTTP status code
     * @param array<string, int> $byType Counts grouped by result type
     * @param array<BrokenLinkResult> $results Individual results for each URL
     */
    public function __construct(
        public int $totalChecked = 0,
        public int $validCount = 0,
        public int $brokenCount = 0,
        public int $redirectCount = 0,
        public int $timeoutCount = 0,
        public int $errorCount = 0,
        public array $byStatusCode = [],
        public array $byType = [],
        public array $results = [],
    ) {}

    /**
     * Add a result to the summary.
     */
    public function addResult(BrokenLinkResult $result): void
    {
        $this->results[] = $result;
        $this->totalChecked++;

        // Count by type
        $this->byType[$result->type] = ($this->byType[$result->type] ?? 0) + 1;

        // Count by status code
        if ($result->statusCode !== null) {
            $statusKey = (string) $result->statusCode;
            $this->byStatusCode[$statusKey] = ($this->byStatusCode[$statusKey] ?? 0) + 1;
        }

        // Update type-specific counters
        match ($result->type) {
            'valid' => $this->validCount++,
            'broken', 'gone' => $this->brokenCount++,
            'redirect' => $this->redirectCount++,
            'timeout' => $this->timeoutCount++,
            'error', 'access_denied', 'auth_required', 'rate_limited', 'blocked' => $this->errorCount++,
            default => $this->errorCount++,
        };
    }

    /**
     * Check if there are any broken links.
     */
    public function hasBrokenLinks(): bool
    {
        return $this->brokenCount > 0;
    }

    /**
     * Check if there are any redirects.
     */
    public function hasRedirects(): bool
    {
        return $this->redirectCount > 0;
    }

    /**
     * Check if there are any issues (broken, redirects, or errors).
     */
    public function hasIssues(): bool
    {
        return $this->brokenCount > 0 || $this->redirectCount > 0 || $this->errorCount > 0 || $this->timeoutCount > 0;
    }

    /**
     * Get the success rate as a percentage.
     *
     * @return float Percentage of valid links (0-100)
     */
    public function getSuccessRate(): float
    {
        if ($this->totalChecked === 0) {
            return 100.0;
        }

        return round(($this->validCount / $this->totalChecked) * 100, 2);
    }

    /**
     * Magic getter for backwards compatibility.
     *
     * Allows accessing getSuccessRate() as $summary->successRate
     */
    public function __get(string $name)
    {
        if ($name === 'successRate') {
            return $this->getSuccessRate();
        }

        throw new \InvalidArgumentException("Property {$name} does not exist on " . self::class);
    }

    /**
     * Get results filtered by type.
     *
     * @param string|array $types One or more types to filter by
     * @return array<BrokenLinkResult>
     */
    public function getResultsByType(string|array $types): array
    {
        $types = is_array($types) ? $types : [$types];
        return array_filter($this->results, fn($r) => in_array($r->type, $types));
    }

    /**
     * Get only broken link results.
     *
     * @return array<BrokenLinkResult>
     */
    public function getBrokenResults(): array
    {
        return $this->getResultsByType(['broken', 'gone']);
    }

    /**
     * Get only redirect results.
     *
     * @return array<BrokenLinkResult>
     */
    public function getRedirectResults(): array
    {
        return $this->getResultsByType('redirect');
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'totalChecked' => $this->totalChecked,
            'validCount' => $this->validCount,
            'brokenCount' => $this->brokenCount,
            'redirectCount' => $this->redirectCount,
            'timeoutCount' => $this->timeoutCount,
            'errorCount' => $this->errorCount,
            'byStatusCode' => $this->byStatusCode,
            'byType' => $this->byType,
            'results' => array_map(fn($r) => $r->toArray(), $this->results),
        ];
    }
}
