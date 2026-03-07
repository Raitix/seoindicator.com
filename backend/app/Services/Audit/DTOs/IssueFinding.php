<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for issue findings.
 *
 * Represents a detected SEO issue with severity,
 * location, and remediation suggestion.
 *
 * @see TASK-14-8
 */
class IssueFinding implements \JsonSerializable
{
    /**
     * Maximum lengths for string fields.
     */
    private const MAX_DESCRIPTION_LENGTH = 1000;
    private const MAX_RECOMMENDATION_LENGTH = 500;
    private const MAX_CONTEXT_SIZE = 10240; // 10KB

    /**
     * Valid severity levels.
     */
    private const VALID_SEVERITIES = ['critical', 'warning', 'notice'];

    /**
     * @param string $issueType The issue type identifier (e.g., 'missing_title_tag')
     * @param string $severity Severity level: 'critical', 'warning', 'notice'
     * @param string $description Human-readable issue description
     * @param string $recommendation Suggested fix for the issue
     * @param array $context Additional context about the issue
     */
    public function __construct(
        public readonly string $issueType,
        public readonly string $severity,
        public readonly string $description,
        public readonly string $recommendation,
        public readonly array $context = [],
    ) {
        $this->validate();
    }

    /**
     * Validate the issue finding data.
     *
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validate(): void
    {
        if (empty($this->issueType)) {
            throw new \InvalidArgumentException('Issue type is required');
        }

        if (empty($this->description)) {
            throw new \InvalidArgumentException('Description is required');
        }

        if (empty($this->recommendation)) {
            throw new \InvalidArgumentException('Recommendation is required');
        }

        // Severity validation - accept any value but normalize unknown ones
        // Invalid severity will be normalized to 'notice' in getNormalizedSeverity()
    }

    /**
     * Check if this is a critical issue.
     */
    public function isCritical(): bool
    {
        return $this->getNormalizedSeverity() === 'critical';
    }

    /**
     * Check if this is a warning issue.
     */
    public function isWarning(): bool
    {
        return $this->getNormalizedSeverity() === 'warning';
    }

    /**
     * Check if this is a notice issue.
     */
    public function isNotice(): bool
    {
        return $this->getNormalizedSeverity() === 'notice';
    }

    /**
     * Get normalized severity (lowercase, defaults unknown to notice).
     */
    public function getNormalizedSeverity(): string
    {
        $normalized = strtolower($this->severity);
        
        if (!in_array($normalized, self::VALID_SEVERITIES)) {
            return 'notice';
        }
        
        return $normalized;
    }

    /**
     * Get numeric severity score (higher = more severe).
     */
    public function getSeverityScore(): int
    {
        return match ($this->getNormalizedSeverity()) {
            'critical' => 4,
            'warning' => 2,
            'notice' => 1,
            default => 0,
        };
    }

    /**
     * Get a context value by key.
     *
     * @param string $key The context key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Check if a context key exists.
     *
     * @param string $key The context key
     * @return bool
     */
    public function hasContext(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'issueType' => $this->issueType,
            'severity' => $this->getNormalizedSeverity(),
            'description' => $this->getTruncatedDescription(),
            'recommendation' => $this->getTruncatedRecommendation(),
            'context' => $this->getTruncatedContext(),
        ];
    }

    /**
     * Create from array.
     *
     * @param array $data The data array
     * @return self
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        $issueType = $data['issueType'] ?? $data['issue_type'] ?? null;
        $severity = $data['severity'] ?? 'notice';
        $description = $data['description'] ?? $data['message'] ?? null;
        $recommendation = $data['recommendation'] ?? $data['suggestion'] ?? '';
        $context = $data['context'] ?? $data['details'] ?? [];

        if (empty($issueType)) {
            throw new \InvalidArgumentException('Issue type is required');
        }

        if (empty($description)) {
            throw new \InvalidArgumentException('Description is required');
        }

        return new self(
            issueType: $issueType,
            severity: $severity,
            description: $description,
            recommendation: $recommendation,
            context: $context,
        );
    }

    /**
     * JSON serialize.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->description;
    }

    /**
     * Get truncated description.
     */
    protected function getTruncatedDescription(): string
    {
        if (strlen($this->description) <= self::MAX_DESCRIPTION_LENGTH) {
            return $this->description;
        }

        return substr($this->description, 0, self::MAX_DESCRIPTION_LENGTH) . '...';
    }

    /**
     * Get truncated recommendation.
     */
    protected function getTruncatedRecommendation(): string
    {
        if (strlen($this->recommendation) <= self::MAX_RECOMMENDATION_LENGTH) {
            return $this->recommendation;
        }

        return substr($this->recommendation, 0, self::MAX_RECOMMENDATION_LENGTH) . '...';
    }

    /**
     * Get truncated context.
     */
    protected function getTruncatedContext(): array
    {
        $encoded = json_encode($this->context);
        
        if ($encoded === false || strlen($encoded) <= self::MAX_CONTEXT_SIZE) {
            return $this->context;
        }

        // Truncate context by removing items
        $context = $this->context;
        while (strlen(json_encode($context)) > self::MAX_CONTEXT_SIZE && count($context) > 1) {
            array_pop($context);
        }

        return $context;
    }
}
