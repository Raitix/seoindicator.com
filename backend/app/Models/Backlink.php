<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Backlink model for detailed backlink data.
 *
 * @property int $id
 * @property int $project_id
 * @property string $source_url
 * @property string $source_domain
 * @property string $target_url
 * @property string|null $anchor_text
 * @property string $link_type
 * @property \Illuminate\Support\Carbon|null $first_seen_at
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Backlink extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'backlinks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'source_url',
        'source_domain',
        'target_url',
        'anchor_text',
        'link_type',
        'first_seen_at',
        'last_checked_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the project that owns this backlink.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for active backlinks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for dofollow backlinks.
     */
    public function scopeDofollow($query)
    {
        return $query->where('link_type', 'dofollow');
    }

    /**
     * Scope for nofollow backlinks.
     */
    public function scopeNofollow($query)
    {
        return $query->where('link_type', 'nofollow');
    }
}
