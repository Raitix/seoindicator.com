<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Audit model for SEO site audit metadata.
 *
 * @property int $id
 * @property int $site_id
 * @property string $status
 * @property int $pages_crawled
 * @property int $issues_found
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Audit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'status',
        'pages_crawled',
        'issues_found',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pages_crawled' => 'integer',
            'issues_found' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the site that owns this audit.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the issues for this audit.
     */
    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class);
    }

    /**
     * Scope for pending audits.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for running audits.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for completed audits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed audits.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark the audit as started.
     */
    public function markAsStarted(): self
    {
        $this->status = 'running';
        $this->started_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the audit as completed.
     */
    public function markAsCompleted(int $pagesCrawled, int $issuesFound): self
    {
        $this->status = 'completed';
        $this->pages_crawled = $pagesCrawled;
        $this->issues_found = $issuesFound;
        $this->completed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the audit as failed.
     */
    public function markAsFailed(): self
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->save();

        return $this;
    }
}