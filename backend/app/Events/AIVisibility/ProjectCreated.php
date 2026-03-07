<?php

namespace App\Events\AIVisibility;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\AIVisibility\AIVisibilityProject;

class ProjectCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AIVisibilityProject $project;

    public function __construct(AIVisibilityProject $project)
    {
        $this->project = $project;
    }
}
