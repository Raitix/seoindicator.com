<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserOnboarding extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_onboarding';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'current_step',
        'completed_steps',
        'website_url',
        'first_keyword',
        'keyword_id',
        'alert_preferences',
        'completed_at',
        'skipped_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'completed_steps' => 'array',
        'alert_preferences' => 'array',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'current_step' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the onboarding.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the onboarding.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the keyword associated with the onboarding.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute(): int
    {
        // If completed, return 100%
        if ($this->completed_at) {
            return 100;
        }

        // 5 steps total, each step = 20%
        return min(100, $this->current_step * 20);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if onboarding is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if onboarding was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->skipped_at !== null;
    }
}
