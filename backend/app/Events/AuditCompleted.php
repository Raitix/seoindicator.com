<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an audit completes successfully.
 */
class AuditCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Audit $audit
    ) {}

    /**
     * Get the audit ID.
     */
    public function getAuditId(): int
    {
        return $this->audit->id;
    }

    /**
     * Get total pages crawled.
     */
    public function getPagesCrawled(): int
    {
        return $this->audit->pages_crawled ?? 0;
    }

    /**
     * Get total issues found.
     */
    public function getIssuesFound(): int
    {
        return $this->audit->issues_found ?? 0;
    }

    /**
     * Get audit duration in seconds.
     */
    public function getDuration(): ?int
    {
        if (!$this->audit->started_at || !$this->audit->completed_at) {
            return null;
        }

        return $this->audit->started_at->diffInSeconds($this->audit->completed_at);
    }
}
