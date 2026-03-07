<?php

namespace App\Services\Audit\Checkers;

use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

/**
 * Checker for performance-related SEO issues.
 *
 * Detection Rules:
 * - Response time 1000-2000ms → Notice (acceptable)
 * - Response time 2000-3000ms → Warning (needs improvement)
 * - Response time 3000-5000ms → Warning (slow page)
 * - Response time > 5000ms → Critical (very slow page)
 * - Page size > 500KB → Notice (large page)
 *
 * @see TASK-14-11
 */
class PerformanceChecker extends BaseChecker
{
    /**
     * Default thresholds (in milliseconds).
     */
    private const DEFAULT_SLOW_THRESHOLD_MS = 3000;
    private const DEFAULT_VERY_SLOW_THRESHOLD_MS = 5000;
    private const DEFAULT_ACCEPTABLE_THRESHOLD_MS = 1000;
    private const DEFAULT_NEEDS_IMPROVEMENT_THRESHOLD_MS = 2000;
    private const DEFAULT_MAX_PAGE_SIZE_KB = 500;

    /**
     * Issue type identifiers.
     */
    private const ISSUE_TYPE_SLOW_PAGE = 'slow_page';
    private const ISSUE_TYPE_LARGE_PAGE = 'large_page';

    /**
     * Issue type identifier.
     *
     * @var string
     */
    protected string $issueType = self::ISSUE_TYPE_SLOW_PAGE;

    /**
     * Checker name.
     *
     * @var string
     */
    protected string $name = 'performance_checker';

    /**
     * Checker priority.
     *
     * @var int
     */
    protected int $priority = 50;

    /**
     * Configuration values.
     *
     * @var array|null
     */
    protected ?array $config = null;

    /**
     * Create a new PerformanceChecker instance.
     */
    public function __construct()
    {
        $config = config('audit.performance');
        $this->config = is_array($config) ? $config : [];
    }

    /**
     * Check a crawl result for performance issues.
     *
     * @param mixed $result The crawl result to check
     * @return IssueFinding|null Returns an issue finding if detected, null otherwise
     */
    public function check($result): ?IssueFinding
    {
        // Handle null result
        if ($result === null) {
            return null;
        }

        // Handle error results - skip performance checking
        if ($result->errorMessage !== null) {
            return null;
        }

        // Get response time - handle invalid values
        $responseTimeMs = $result->responseTimeMs;
        
        // Skip invalid response times (zero or negative)
        if ($responseTimeMs <= 0) {
            return null;
        }

        // Check response time first (higher priority)
        $responseTimeFinding = $this->checkResponseTime($responseTimeMs);
        
        // Check page size if we have HTML
        $pageSizeFinding = $this->checkPageSize($result->html);

        // Return the higher severity issue
        // Response time issues take priority over page size issues
        if ($responseTimeFinding !== null && $pageSizeFinding !== null) {
            // Return the one with higher severity
            if ($responseTimeFinding->getSeverityScore() >= $pageSizeFinding->getSeverityScore()) {
                return $responseTimeFinding;
            }
            return $pageSizeFinding;
        }

        return $responseTimeFinding ?? $pageSizeFinding;
    }

    /**
     * Check response time against thresholds.
     *
     * @param float $responseTimeMs Response time in milliseconds
     * @return IssueFinding|null
     */
    protected function checkResponseTime(float $responseTimeMs): ?IssueFinding
    {
        $thresholds = $this->getThresholds();
        
        // Critical: Very slow page (> very_slow_threshold)
        if ($responseTimeMs >= $thresholds['very_slow_threshold_ms']) {
            return $this->createFinding(
                issueType: self::ISSUE_TYPE_SLOW_PAGE,
                severity: 'critical',
                description: "Page loaded in {$responseTimeMs}ms (threshold: {$thresholds['very_slow_threshold_ms']}ms) - very slow page",
                recommendation: 'Optimize page performance immediately. Consider image compression, caching, CDN, database optimization, or code profiling.',
                context: ['response_time_ms' => $responseTimeMs, 'threshold_ms' => $thresholds['very_slow_threshold_ms']]
            );
        }

        // Warning: Slow page (> slow_threshold, 3000ms)
        if ($responseTimeMs >= $thresholds['slow_threshold_ms']) {
            return $this->createFinding(
                issueType: self::ISSUE_TYPE_SLOW_PAGE,
                severity: 'warning',
                description: "Page loaded in {$responseTimeMs}ms (threshold: {$thresholds['slow_threshold_ms']}ms) - slow page",
                recommendation: 'Optimize page performance. Consider image compression, caching, or code optimization.',
                context: ['response_time_ms' => $responseTimeMs, 'threshold_ms' => $thresholds['slow_threshold_ms']]
            );
        }

        // Warning: Needs improvement (>= 2000ms)
        if ($responseTimeMs >= $thresholds['needs_improvement_threshold_ms']) {
            return $this->createFinding(
                issueType: self::ISSUE_TYPE_SLOW_PAGE,
                severity: 'warning',
                description: "Page loaded in {$responseTimeMs}ms - needs performance improvement",
                recommendation: 'Consider optimizing page load time. Review server response time and page assets.',
                context: ['response_time_ms' => $responseTimeMs]
            );
        }

        // Notice: Acceptable (>= 1000ms)
        if ($responseTimeMs >= $thresholds['acceptable_threshold_ms']) {
            return $this->createFinding(
                issueType: self::ISSUE_TYPE_SLOW_PAGE,
                severity: 'notice',
                description: "Page loaded in {$responseTimeMs}ms - acceptable but could be faster",
                recommendation: 'Consider optimizing page performance for better user experience.',
                context: ['response_time_ms' => $responseTimeMs]
            );
        }

        // Fast response - no issue
        return null;
    }

    /**
     * Check page size against threshold.
     *
     * @param string|null $html HTML content
     * @return IssueFinding|null
     */
    protected function checkPageSize(?string $html): ?IssueFinding
    {
        // Skip if no HTML
        if ($html === null || $html === '') {
            return null;
        }

        $thresholds = $this->getThresholds();
        $sizeInBytes = strlen($html);
        $sizeInKB = $sizeInBytes / 1024;
        $maxPageSizeKB = $thresholds['max_page_size_kb'];

        // Check if over threshold
        if ($sizeInKB > $maxPageSizeKB) {
            return $this->createFinding(
                issueType: self::ISSUE_TYPE_LARGE_PAGE,
                severity: 'notice',
                description: "Page size is " . $this->formatSize($sizeInKB) . " (threshold: {$maxPageSizeKB}KB)",
                recommendation: 'Consider reducing page size for faster loading. Optimize images, minify CSS/JS, and reduce unnecessary content.',
                context: ['page_size_kb' => round($sizeInKB, 2), 'threshold_kb' => $maxPageSizeKB]
            );
        }

        return null;
    }

    /**
     * Get configured thresholds with defaults.
     *
     * @return array
     */
    protected function getThresholds(): array
    {
        return [
            'slow_threshold_ms' => (int) ($this->config['slow_threshold_ms'] ?? self::DEFAULT_SLOW_THRESHOLD_MS),
            'very_slow_threshold_ms' => (int) ($this->config['very_slow_threshold_ms'] ?? self::DEFAULT_VERY_SLOW_THRESHOLD_MS),
            'acceptable_threshold_ms' => (int) ($this->config['acceptable_threshold_ms'] ?? self::DEFAULT_ACCEPTABLE_THRESHOLD_MS),
            'needs_improvement_threshold_ms' => (int) ($this->config['needs_improvement_threshold_ms'] ?? self::DEFAULT_NEEDS_IMPROVEMENT_THRESHOLD_MS),
            'max_page_size_kb' => (int) ($this->config['max_page_size_kb'] ?? self::DEFAULT_MAX_PAGE_SIZE_KB),
        ];
    }

    /**
     * Format response time for display.
     *
     * @param float $ms Milliseconds
     * @return string
     */
    protected function formatResponseTime(float $ms): string
    {
        if ($ms >= 1000) {
            $seconds = $ms / 1000;
            return round($seconds, 2) . 's';
        }
        return round($ms) . 'ms';
    }

    /**
     * Format size for display.
     *
     * @param float $kb Kilobytes
     * @return string
     */
    protected function formatSize(float $kb): string
    {
        if ($kb >= 1024) {
            $mb = $kb / 1024;
            return round($mb, 2) . 'MB';
        }
        return round($kb, 2) . 'KB';
    }

    /**
     * Get the issue type this checker detects.
     *
     * @return string
     */
    public function getIssueType(): string
    {
        return $this->issueType;
    }

    /**
     * Get the checker name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the checker priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
