<?php

namespace App\Services\AIVisibility\Events;

use App\Models\AIVisibilityAlert;
use App\Models\AIVisibilityProject;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an AI visibility alert is triggered
 * 
 * This event is dispatched after a new alert is created (not on updates).
 * Listeners can handle notifications via in-app, email, or webhook channels.
 * 
 * @see TASK-34-19
 */
class AIVisibilityAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AIVisibilityAlert $alert,
        public AIVisibilityProject $project
    ) {}

    /**
     * Get the user that owns the project (for notifications)
     */
    public function getUser(): ?\App\Models\User
    {
        return $this->project->user;
    }

    /**
     * Get the alert type
     */
    public function getAlertType(): string
    {
        return $this->alert->alert_type;
    }

    /**
     * Get the change percentage
     */
    public function getChangePercent(): float
    {
        return $this->alert->change_percent;
    }
}
