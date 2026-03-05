<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Audit model for SEO site audit metadata.
 *
 * @property int $id
 * @property int $site_id
 * @property string $status
 * @property int $pages_crawled
 * @property int $issues_found
 * @property string $visibility
 * @property string|null $public_hash
 * @property \Illuminate\Support\Carbon|null $public_hash_created_at
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
        'visibility',
        'public_hash',
        'public_hash_created_at',
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
            'visibility' => 'string',
            'public_hash_created_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'private',
    ];

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

    // =========================================================================
    // Scopes
    // =========================================================================

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
     * Scope for public audits.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for private audits.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    // =========================================================================
    // Status Methods
    // =========================================================================

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

    // =========================================================================
    // Visibility Methods
    // =========================================================================

    /**
     * Check if the audit is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Check if the audit is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * Generate a unique 32-character hash for public sharing.
     */
    public function generateHash(): string
    {
        do {
            $hash = Str::random(32);
        } while (self::where('public_hash', $hash)->exists());

        return $hash;
    }

    /**
     * Publish the audit (make it public).
     */
    public function publish(): self
    {
        if (!$this->public_hash) {
            $this->public_hash = $this->generateHash();
            $this->public_hash_created_at = now();
        }

        $this->visibility = 'public';
        $this->save();

        return $this;
    }

    /**
     * Unpublish the audit (make it private).
     */
    public function unpublish(): self
    {
        $this->visibility = 'private';
        $this->public_hash = null;
        $this->public_hash_created_at = null;
        $this->save();

        return $this;
    }

    /**
     * Get the public URL for this audit.
     */
    public function getPublicUrl(): string
    {
        if (!$this->public_hash) {
            return '';
        }

        return url("/audit/public/{$this->public_hash}");
    }

    /**
     * Find an audit by its public hash.
     */
    public static function findByPublicHash(string $hash): ?self
    {
        return static::where('public_hash', $hash)
            ->where('visibility', 'public')
            ->first();
    }
}
