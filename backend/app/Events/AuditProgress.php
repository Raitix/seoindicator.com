<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched periodically during an audit to report progress.
 */
class AuditProgress
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Audit $audit,
        public readonly array $progress
    ) {}

    /**
     * Get the audit ID.
     */
    public function getAuditId(): int
    {
        return $this->audit->id;
    }

    /**
     * Get pages crawled count.
     */
    public function getPagesCrawled(): int
    {
        return $this->progress['pages_crawled'] ?? 0;
    }

    /**
     * Get issues found count.
     */
    public function getIssuesFound(): int
    {
        return $this->progress['issues_found'] ?? 0;
    }

    /**
     * Get current URL being processed.
     */
    public function getCurrentUrl(): ?string
    {
        return $this->progress['current_url'] ?? null;
    }

    /**
     * Get progress percentage (if total is known).
     */
    public function getPercentage(): ?float
    {
        $pagesCrawled = $this->progress['pages_crawled'] ?? 0;
        $totalPages = $this->progress['total_pages'] ?? null;

        if ($totalPages === null || $totalPages === 0) {
            return null;
        }

        return ($pagesCrawled / $totalPages) * 100;
    }
}
