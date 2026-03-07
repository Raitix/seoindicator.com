<?php

namespace App\Events;

use App\Models\Audit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an audit starts.
 */
class AuditStarted
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
     * Get the base URL being audited.
     */
    public function getBaseUrl(): string
    {
        return $this->audit->site->url ?? '';
    }
}
