<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an audit fails.
 */
class AuditFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Audit $audit,
        public readonly string $errorMessage
    ) {}

    /**
     * Get the audit ID.
     */
    public function getAuditId(): int
    {
        return $this->audit->id;
    }

    /**
     * Get the error message.
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Get pages crawled before failure.
     */
    public function getPagesCrawled(): int
    {
        return $this->audit->pages_crawled ?? 0;
    }

    /**
     * Get issues found before failure.
     */
    public function getIssuesFound(): int
    {
        return $this->audit->issues_found ?? 0;
    }
}
