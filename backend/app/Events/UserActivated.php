<?php

namespace App\Events;

use App\Models\User;
use App\Models\Keyword;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivated
{
    use Dispatchable, SerializesModels;

    /**
     * The user instance.
     */
    public User $user;

    /**
     * The keyword ID (if created during onboarding).
     */
    public ?int $keywordId;

    /**
     * The project ID (if linked during onboarding).
     */
    public ?int $projectId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ?int $keywordId = null, ?int $projectId = null)
    {
        $this->user = $user;
        $this->keywordId = $keywordId;
        $this->projectId = $projectId;
    }
}
