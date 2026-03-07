<?php

namespace App\Services\Audit\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Data Transfer Object for crawl state.
 *
 * Tracks the overall state of a site crawl.
 *
 * @see TASK-14-5
 */
class CrawlState implements Arrayable, JsonSerializable
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Create a new CrawlState instance.
     */
    public function __construct(
        public readonly int $auditId,
        public readonly string $baseUrl,
        public int $totalPages = 0,
        public int $pagesCrawled = 0,
        public int $pagesFailed = 0,
        public string $status = self::STATUS_PENDING,
        public readonly string $startedAt = '',
        public ?string $completedAt = null,
        public array $errors = [],
        public array $config = [],
    ) {
        if (empty($this->startedAt)) {
            $this->startedAt = now()->toIso8601String();
        }
    }

    /**
     * Create a new pending state.
     */
    public static function pending(int $auditId, string $baseUrl, array $config = []): self
    {
        return new self(
            auditId: $auditId,
            baseUrl: $baseUrl,
            status: self::STATUS_PENDING,
            config: $config,
        );
    }

    /**
     * Mark as running.
     */
    public function markAsRunning(): self
    {
        $this->status = self::STATUS_RUNNING;
        return $this;
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = now()->toIso8601String();
        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->completedAt = now()->toIso8601String();
        if ($errorMessage) {
            $this->errors[] = [
                'message' => $errorMessage,
                'timestamp' => now()->toIso8601String(),
            ];
        }
        return $this;
    }

    /**
     * Mark as cancelled.
     */
    public function markAsCancelled(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->completedAt = now()->toIso8601String();
        return $this;
    }

    /**
     * Increment pages crawled.
     */
    public function incrementPagesCrawled(): self
    {
        $this->pagesCrawled++;
        return $this;
    }

    /**
     * Increment pages failed.
     */
    public function incrementPagesFailed(string $errorMessage = null): self
    {
        $this->pagesFailed++;
        if ($errorMessage) {
            $this->errors[] = [
                'message' => $errorMessage,
                'timestamp' => now()->toIso8601String(),
            ];
        }
        return $this;
    }

    /**
     * Set total pages.
     */
    public function setTotalPages(int $total): self
    {
        $this->totalPages = $total;
        return $this;
    }

    /**
     * Check if pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if finished (completed, failed, or cancelled).
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Get progress percentage.
     */
    public function getProgress(): float
    {
        if ($this->totalPages === 0) {
            return 0.0;
        }
        return round(($this->pagesCrawled / $this->totalPages) * 100, 1);
    }

    /**
     * Get duration in seconds.
     */
    public function getDuration(): int
    {
        $start = strtotime($this->startedAt);
        $end = $this->completedAt ? strtotime($this->completedAt) : time();
        return max(0, $end - $start);
    }

    /**
     * Get max depth from config.
     */
    public function getMaxDepth(): int
    {
        return $this->config['max_depth'] ?? 3;
    }

    /**
     * Get max pages from config.
     */
    public function getMaxPages(): int
    {
        return $this->config['max_pages'] ?? 500;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'auditId' => $this->auditId,
            'baseUrl' => $this->baseUrl,
            'totalPages' => $this->totalPages,
            'pagesCrawled' => $this->pagesCrawled,
            'pagesFailed' => $this->pagesFailed,
            'status' => $this->status,
            'startedAt' => $this->startedAt,
            'completedAt' => $this->completedAt,
            'errors' => $this->errors,
            'config' => $this->config,
            'progress' => $this->getProgress(),
            'duration' => $this->getDuration(),
        ];
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            auditId: $data['auditId'],
            baseUrl: $data['baseUrl'],
            totalPages: $data['totalPages'] ?? 0,
            pagesCrawled: $data['pagesCrawled'] ?? 0,
            pagesFailed: $data['pagesFailed'] ?? 0,
            status: $data['status'] ?? self::STATUS_PENDING,
            startedAt: $data['startedAt'] ?? '',
            completedAt: $data['completedAt'] ?? null,
            errors: $data['errors'] ?? [],
            config: $data['config'] ?? [],
        );
    }
}
