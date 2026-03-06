<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PublicAudit model for managing public audit sharing.
 *
 * This model handles public audit data retrieval, hash generation,
 * and visibility management for the public SEO audit feature.
 *
 * @property int $id
 * @property int $audit_id
 * @property string|null $public_hash
 * @property string $visibility
 * @property \Illuminate\Support\Carbon|null $public_hash_created_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Audit $audit
 * @property-read string $public_url
 */
class PublicAudit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'public_audits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_id',
        'public_hash',
        'visibility',
        'public_hash_created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audit_id' => 'integer',
            'public_hash_created_at' => 'datetime',
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

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the audit that owns this public audit record.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope for public audits only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for private audits only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope to find by hash.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $hash
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHash($query, string $hash)
    {
        return $query->where('public_hash', $hash);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Generate a unique 32-character alphanumeric hash.
     *
     * Uses Laravel's Str::random() for cryptographic randomness.
     * Automatically regenerates on collision detection.
     *
     * @return string
     */
    public static function generateHash(): string
    {
        do {
            $hash = Str::random(32);
        } while (self::where('public_hash', $hash)->exists());

        return $hash;
    }

    /**
     * Find a public audit by its hash.
     *
     * Only returns audits that are publicly visible.
     * Returns null for non-existent, private, or invalid hashes.
     *
     * @param string|null $hash
     * @return static|null
     */
    public static function findByHash(?string $hash): ?static
    {
        if (empty($hash)) {
            return null;
        }

        return static::with('audit')
            ->where('public_hash', $hash)
            ->where('visibility', 'public')
            ->first();
    }

    // =========================================================================
    // Instance Methods
    // =========================================================================

    /**
     * Publish the audit (make it public).
     *
     * Generates a hash if not already set.
     * Sets visibility to public and records the timestamp.
     * Wrapped in a database transaction for safety.
     *
     * @return $this
     */
    public function publish(): self
    {
        return DB::transaction(function () {
            if (!$this->public_hash) {
                $this->public_hash = self::generateHash();
                $this->public_hash_created_at = now();
            }

            $this->visibility = 'public';
            $this->save();

            return $this;
        });
    }

    /**
     * Unpublish the audit (make it private).
     *
     * Sets visibility to private and clears the hash.
     *
     * @return $this
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
     * Check if the audit is publicly visible.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the public URL for this audit.
     *
     * Returns empty string if not public.
     *
     * @return string
     */
    public function getPublicUrlAttribute(): string
    {
        if (!$this->public_hash || !$this->isPublic()) {
            return '';
        }

        return url("/audit/public/{$this->public_hash}");
    }
}
