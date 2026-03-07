<?php

namespace App\Exporters\DTOs;

use JsonSerializable;

/**
 * Data Transfer Object for audit section in PDF reports.
 *
 * TASK-14-18: Integrate Audit Results into PDF Reports
 * 
 * Encapsulates audit data for PDF rendering with:
 * - Summary with severity counts
 * - Critical issues (limited to 10)
 * - Warning issues (limited to 5)
 * - Recommendations (limited to 5)
 */
class AuditReportSection implements JsonSerializable
{
    /**
     * Maximum number of critical issues to include.
     */
    public const MAX_CRITICAL_ISSUES = 10;

    /**
     * Maximum number of warning issues to include.
     */
    public const MAX_WARNING_ISSUES = 5;

    /**
     * Maximum number of recommendations to include.
     */
    public const MAX_RECOMMENDATIONS = 5;

    /**
     * @param array $summary Summary with pages_crawled, total_issues, critical, warning, notice counts
     * @param array $criticalIssues Array of critical issue objects
     * @param array $warningIssues Array of warning issue objects
     * @param array $recommendations Array of recommendation strings
     */
    public function __construct(
        public readonly array $summary,
        public readonly array $criticalIssues = [],
        public readonly array $warningIssues = [],
        public readonly array $recommendations = []
    ) {}

    /**
     * Create an AuditReportSection from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $summary = $data['summary'] ?? [
            'pages_crawled' => 0,
            'total_issues' => 0,
            'critical' => 0,
            'warning' => 0,
            'notice' => 0,
        ];

        $criticalIssues = array_slice(
            $data['criticalIssues'] ?? [],
            0,
            self::MAX_CRITICAL_ISSUES
        );

        $warningIssues = array_slice(
            $data['warningIssues'] ?? [],
            0,
            self::MAX_WARNING_ISSUES
        );

        $recommendations = array_slice(
            $data['recommendations'] ?? [],
            0,
            self::MAX_RECOMMENDATIONS
        );

        return new self($summary, $criticalIssues, $warningIssues, $recommendations);
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'criticalIssues' => $this->criticalIssues,
            'warningIssues' => $this->warningIssues,
            'recommendations' => $this->recommendations,
        ];
    }

    /**
     * Specify data for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get pages crawled count.
     *
     * @return int
     */
    public function getPagesCrawled(): int
    {
        return (int) ($this->summary['pages_crawled'] ?? 0);
    }

    /**
     * Get total issues count.
     *
     * @return int
     */
    public function getTotalIssues(): int
    {
        return (int) ($this->summary['total_issues'] ?? 0);
    }

    /**
     * Get critical issues count.
     *
     * @return int
     */
    public function getCriticalCount(): int
    {
        return (int) ($this->summary['critical'] ?? 0);
    }

    /**
     * Get warning issues count.
     *
     * @return int
     */
    public function getWarningCount(): int
    {
        return (int) ($this->summary['warning'] ?? 0);
    }

    /**
     * Get notice issues count.
     *
     * @return int
     */
    public function getNoticeCount(): int
    {
        return (int) ($this->summary['notice'] ?? 0);
    }

    /**
     * Check if there are any critical issues.
     *
     * @return bool
     */
    public function hasCriticalIssues(): bool
    {
        return !empty($this->criticalIssues);
    }

    /**
     * Check if there are any warning issues.
     *
     * @return bool
     */
    public function hasWarningIssues(): bool
    {
        return !empty($this->warningIssues);
    }

    /**
     * Check if there are any recommendations.
     *
     * @return bool
     */
    public function hasRecommendations(): bool
    {
        return !empty($this->recommendations);
    }

    /**
     * Check if audit section has any content.
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return $this->getTotalIssues() > 0 
            || $this->getPagesCrawled() > 0 
            || $this->hasCriticalIssues() 
            || $this->hasWarningIssues() 
            || $this->hasRecommendations();
    }

    /**
     * Validate summary structure.
     *
     * @return bool
     */
    public function isValidSummary(): bool
    {
        $requiredKeys = ['pages_crawled', 'total_issues', 'critical', 'warning', 'notice'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $this->summary)) {
                return false;
            }
        }
        
        // Verify severity counts sum to total
        $sum = $this->getCriticalCount() + $this->getWarningCount() + $this->getNoticeCount();
        return $sum === $this->getTotalIssues();
    }
}
