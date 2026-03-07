<?php

namespace App\Services\Audit\DTOs;

/**
 * Data Transfer Object for issue findings.
 * 
 * Represents a detected SEO issue with severity,
 * location, and remediation suggestion.
 */
class IssueFinding implements \JsonSerializable
{
    /**
     * @param int $issueTypeId Foreign key to issue_types table
     * @param string $severity Severity level: 'critical', 'high', 'medium', 'low'
     * @param string $message Human-readable issue description
     * @param string $url URL where the issue was found
     * @param array $details Additional context about the issue
     * @param string|null $suggestion Suggested fix for the issue
     */
    public function __construct(
        public readonly int $issueTypeId,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $url,
        public readonly array $details = [],
        public readonly ?string $suggestion = null,
    ) {}

    /**
     * Check if this is a critical issue.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Check if this is a high severity issue.
     */
    public function isHigh(): bool
    {
        return $this->severity === 'high';
    }

    /**
     * Check if this is a medium severity issue.
     */
    public function isMedium(): bool
    {
        return $this->severity === 'medium';
    }

    /**
     * Check if this is a low severity issue.
     */
    public function isLow(): bool
    {
        return $this->severity === 'low';
    }

    /**
     * Get numeric severity score (higher = more severe).
     */
    public function getSeverityScore(): int
    {
        return match ($this->severity) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'issue_type_id' => $this->issueTypeId,
            'severity' => $this->severity,
            'message' => $this->message,
            'url' => $this->url,
            'details' => $this->details,
            'suggestion' => $this->suggestion,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            issueTypeId: $data['issue_type_id'] ?? $data['issueTypeId'],
            severity: $data['severity'],
            message: $data['message'],
            url: $data['url'],
            details: $data['details'] ?? [],
            suggestion: $data['suggestion'] ?? null,
        );
    }

    /**
     * JSON serialize.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
