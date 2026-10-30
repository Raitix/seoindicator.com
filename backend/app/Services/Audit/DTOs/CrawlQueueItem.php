<?php

namespace App\Services\Audit\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Data Transfer Object for crawl queue items.
 *
 * Represents a URL in the crawl queue with metadata.
 *
 * @see TASK-14-5
 */
class CrawlQueueItem implements Arrayable, JsonSerializable
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public string $status;
    public ?int $retryCount;
    public ?string $errorMessage = null;

    /**
     * Create a new CrawlQueueItem instance.
     */
    public function __construct(
        public readonly string $url,
        public readonly int $depth = 0,
        public readonly int $priority = 0,
        public readonly string $addedAt,
    ) {
        $this->status = self::STATUS_PENDING;
        $this->retryCount = 0;
    }

    /**
     * Create a pending queue item.
     */
    public static function pending(string $url, int $depth = 0, int $priority = 0): self
    {
        return new self(
            url: $url,
            depth: $depth,
            priority: $priority,
            addedAt: now()->toIso8601String(),
        );
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;
        return $this;
    }

    /**
     * Mark as done.
     */
    public function markAsDone(): self
    {
        $this->status = self::STATUS_DONE;
        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry(): self
    {
        $this->retryCount = ($this->retryCount ?? 0) + 1;
        $this->status = self::STATUS_PENDING;
        return $this;
    }

    /**
     * Check if item is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if item is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if item is done.
     */
    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    /**
     * Check if item is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if can retry.
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->isFailed() && ($this->retryCount ?? 0) < $maxRetries;
    }

    /**
     * Get sort priority (lower = higher priority).
     */
    public function getSortPriority(): int
    {
        // Priority: lower depth first, then higher priority value
        return ($this->depth * 1000) - $this->priority;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'depth' => $this->depth,
            'priority' => $this->priority,
            'status' => $this->status,
            'retryCount' => $this->retryCount,
            'errorMessage' => $this->errorMessage,
            'addedAt' => $this->addedAt,
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
        $item = new self(
            url: $data['url'],
            depth: $data['depth'] ?? 0,
            priority: $data['priority'] ?? 0,
            addedAt: $data['addedAt'] ?? now()->toIso8601String(),
        );

        $item->status = $data['status'] ?? self::STATUS_PENDING;
        $item->retryCount = $data['retryCount'] ?? 0;
        $item->errorMessage = $data['errorMessage'] ?? null;

        return $item;
    }
}
