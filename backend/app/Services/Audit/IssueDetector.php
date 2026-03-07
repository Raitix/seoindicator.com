<?php

namespace App\Services\Audit;

use App\Repositories\AuditIssueRepository;
use App\Repositories\IssueTypeRepository;
use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\Contracts\IssueDetectorInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * IssueDetector Service for SEO Audits.
 *
 * Orchestrates issue detection by running registered checkers
 * against crawl results and creating audit issue records.
 *
 * @see TASK-14-8
 */
class IssueDetector implements IssueDetectorInterface
{
    /**
     * Registered checkers.
     *
     * @var array<string, CheckerInterface>
     */
    protected array $checkers = [];

    /**
     * Duplicate title cache key pattern.
     */
    protected const TITLE_CACHE_KEY = 'audit:titles:{audit_id}';

    /**
     * Cache TTL for duplicate detection (1 hour).
     */
    protected const TITLE_CACHE_TTL = 3600;

    /**
     * Maximum title length for duplicate detection.
     */
    protected const MAX_TITLE_LENGTH = 1000;

    /**
     * Create a new IssueDetector instance.
     *
     * @param IssueTypeRepository $issueTypesRepo
     * @param AuditIssueRepository $issuesRepo
     */
    public function __construct(
        protected IssueTypeRepository $issueTypesRepo,
        protected AuditIssueRepository $issuesRepo,
    ) {}

    // =========================================================================
    // Checker Registration
    // =========================================================================

    /**
     * Register a checker.
     *
     * @param string $name The checker name
     * @param CheckerInterface $checker The checker instance
     * @return self Returns $this for chaining
     * @throws \InvalidArgumentException If name is empty
     */
    public function registerChecker(string $name, CheckerInterface $checker): self
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Checker name cannot be empty');
        }

        $this->checkers[$name] = $checker;

        return $this;
    }

    /**
     * Register multiple checkers.
     *
     * @param array<string, CheckerInterface> $checkers Associative array of name => checker
     */
    public function registerMany(array $checkers): void
    {
        foreach ($checkers as $name => $checker) {
            $this->registerChecker($name, $checker);
        }
    }

    /**
     * Get available checkers.
     *
     * @return array<string> Array of checker names
     */
    public function getAvailableChecks(): array
    {
        return array_keys($this->checkers);
    }

    // =========================================================================
    // Analysis Methods
    // =========================================================================

    /**
     * Analyze a crawl result and create audit issues.
     *
     * Runs all registered checkers in priority order and creates
     * AuditIssue records for any findings.
     *
     * @param CrawlResult $crawlResult The crawl result to analyze
     * @param int $auditId The audit ID to associate issues with
     * @return array<int> Array of created issue IDs
     * @throws \InvalidArgumentException If crawl result or audit ID is invalid
     */
    public function analyze(CrawlResult $crawlResult, int $auditId): array
    {
        $this->validateAnalyzeInput($crawlResult, $auditId);

        $issueIds = [];

        // Check for duplicate titles first
        $duplicateFinding = $this->detectDuplicateTitle($crawlResult, $auditId);
        if ($duplicateFinding !== null) {
            $issueId = $this->createIssue($duplicateFinding, $auditId, $crawlResult->url);
            if ($issueId !== null) {
                $issueIds[] = $issueId;
            }
        }

        // Get checkers sorted by priority (higher first)
        $sortedCheckers = $this->getSortedCheckers();

        // Run each checker
        foreach ($sortedCheckers as $name => $checker) {
            try {
                $finding = $checker->check($crawlResult);

                if ($finding !== null) {
                    $issueId = $this->createIssue($finding, $auditId, $crawlResult->url);
                    if ($issueId !== null) {
                        $issueIds[] = $issueId;
                    }
                }
            } catch (\Exception $e) {
                Log::error('IssueDetector: Checker threw exception', [
                    'checker' => $name,
                    'url' => $crawlResult->url,
                    'error' => $e->getMessage(),
                ]);
                // Continue to next checker
            }
        }

        // Store title for duplicate detection
        $this->storeTitleForDuplicateDetection($crawlResult, $auditId);

        return $issueIds;
    }

    /**
     * Run a single checker by name.
     *
     * @param string $checkName The checker name
     * @param CrawlResult $crawlResult The crawl result to check
     * @return IssueFinding|null The finding or null if no issue
     * @throws \InvalidArgumentException If checker not found
     */
    public function runCheck(string $checkName, CrawlResult $crawlResult): ?IssueFinding
    {
        if (!isset($this->checkers[$checkName])) {
            throw new \InvalidArgumentException("Checker not found: {$checkName}");
        }

        return $this->checkers[$checkName]->check($crawlResult);
    }

    // =========================================================================
    // Legacy Interface Methods
    // =========================================================================

    /**
     * Detect SEO issues in a crawl result (legacy method).
     *
     * @param CrawlResult $crawlResult The crawl result to analyze
     * @return array<int, IssueFinding> Array of detected issues
     * @deprecated Use analyze() instead
     */
    public function detect(CrawlResult $crawlResult): array
    {
        $issues = [];

        foreach ($this->checkers as $checker) {
            try {
                $finding = $checker->check($crawlResult);
                if ($finding !== null) {
                    $issues[] = $finding;
                }
            } catch (\Exception $e) {
                Log::warning('IssueDetector: Checker failed', [
                    'checker' => $checker->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $issues;
    }

    /**
     * Get the issue type ID for a given issue name.
     *
     * @param string $issueName The issue name
     * @return int|null The issue type ID or null if not found
     */
    public function getIssueTypeId(string $issueName): ?int
    {
        $issueType = $this->issueTypesRepo->findByType($issueName);
        return $issueType?->id;
    }

    /**
     * Register a custom issue detector (legacy method).
     *
     * @param string $name The detector name
     * @param callable $detector The detector function
     * @deprecated Use registerChecker() instead
     */
    public function registerDetector(string $name, callable $detector): void
    {
        // Convert callable to CheckerInterface-compatible wrapper
        $this->customDetectors[$name] = $detector;
    }

    // =========================================================================
    // Issue Creation
    // =========================================================================

    /**
     * Create an audit issue from a finding.
     *
     * @param IssueFinding $finding The issue finding
     * @param int $auditId The audit ID
     * @param string $url The URL where the issue was found
     * @return int|null The created issue ID or null on failure
     */
    protected function createIssue(IssueFinding $finding, int $auditId, string $url): ?int
    {
        // Get issue type from database
        $issueType = $this->issueTypesRepo->findByType($finding->issueType);

        // Determine severity (database default or from finding)
        $severity = $issueType?->severity ?? $finding->getNormalizedSeverity();

        // Sanitize description for security
        $description = $this->sanitizeString($finding->description);
        $recommendation = $this->sanitizeString($finding->recommendation);

        try {
            $issue = $this->issuesRepo->create([
                'audit_id' => $auditId,
                'url' => $url,
                'issue_type' => $finding->issueType,
                'issue_type_id' => $issueType?->id,
                'severity' => $severity,
                'description' => $description,
                'recommendation' => $recommendation,
                'context' => $finding->context,
                'status' => 'open',
            ]);

            return $issue->id;
        } catch (\Exception $e) {
            Log::error('IssueDetector: Failed to create issue', [
                'audit_id' => $auditId,
                'issue_type' => $finding->issueType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // Duplicate Title Detection
    // =========================================================================

    /**
     * Detect duplicate titles across pages.
     *
     * @param CrawlResult $crawlResult The crawl result
     * @param int $auditId The audit ID
     * @return IssueFinding|null Finding if duplicate detected, null otherwise
     */
    protected function detectDuplicateTitle(CrawlResult $crawlResult, int $auditId): ?IssueFinding
    {
        $title = $this->normalizeTitle($crawlResult->getTitle());

        if (empty($title)) {
            return null;
        }

        $cacheKey = $this->getTitleCacheKey($auditId);
        $titles = Cache::get($cacheKey, []);

        // Check if title already exists (case-insensitive)
        $normalizedTitle = strtolower($title);
        foreach ($titles as $existingTitle => $existingUrl) {
            if (strtolower($existingTitle) === $normalizedTitle) {
                return new IssueFinding(
                    issueType: 'duplicate_title',
                    severity: 'warning',
                    description: "Duplicate page title detected: \"{$title}\"",
                    recommendation: 'Ensure each page has a unique title tag',
                    context: [
                        'title' => $title,
                        'original_url' => $existingUrl,
                        'duplicate_url' => $crawlResult->url,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Store title for duplicate detection.
     *
     * @param CrawlResult $crawlResult The crawl result
     * @param int $auditId The audit ID
     */
    protected function storeTitleForDuplicateDetection(CrawlResult $crawlResult, int $auditId): void
    {
        $title = $this->normalizeTitle($crawlResult->getTitle());

        if (empty($title)) {
            return;
        }

        $cacheKey = $this->getTitleCacheKey($auditId);
        $titles = Cache::get($cacheKey, []);

        // Truncate title if too long
        if (strlen($title) > self::MAX_TITLE_LENGTH) {
            $title = substr($title, 0, self::MAX_TITLE_LENGTH);
        }

        $titles[$title] = $crawlResult->url;

        Cache::put($cacheKey, $titles, self::TITLE_CACHE_TTL);
    }

    /**
     * Normalize a title for comparison.
     *
     * @param string|null $title The title to normalize
     * @return string The normalized title
     */
    protected function normalizeTitle(?string $title): string
    {
        if ($title === null) {
            return '';
        }

        // Normalize whitespace
        $title = preg_replace('/\s+/', ' ', trim($title));

        return $title ?? '';
    }

    /**
     * Get the cache key for title storage.
     *
     * @param int $auditId The audit ID
     * @return string
     */
    protected function getTitleCacheKey(int $auditId): string
    {
        return str_replace('{audit_id}', (string) $auditId, self::TITLE_CACHE_KEY);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Validate analyze input.
     *
     * @param mixed $crawlResult The crawl result
     * @param mixed $auditId The audit ID
     * @throws \InvalidArgumentException If input is invalid
     */
    protected function validateAnalyzeInput($crawlResult, $auditId): void
    {
        if (!$crawlResult instanceof CrawlResult) {
            throw new \InvalidArgumentException('CrawlResult is required');
        }

        if (!is_int($auditId) || $auditId <= 0) {
            throw new \InvalidArgumentException('Valid audit ID is required');
        }
    }

    /**
     * Get checkers sorted by priority (highest first).
     *
     * @return array<string, CheckerInterface>
     */
    protected function getSortedCheckers(): array
    {
        $checkers = $this->checkers;

        uasort($checkers, function (CheckerInterface $a, CheckerInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $checkers;
    }

    /**
     * Sanitize a string for storage (XSS prevention).
     *
     * @param string $input The input string
     * @return string The sanitized string
     */
    protected function sanitizeString(string $input): string
    {
        // Remove script tags and other dangerous HTML
        $sanitized = strip_tags($input);

        // Encode special characters
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $sanitized;
    }
}
