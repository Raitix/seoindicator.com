<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Backlink model for detailed backlink data.
 *
 * @property int $id
 * @property int $site_id
 * @property string $source_url
 * @property string $target_url
 * @property string $anchor_text
 * @property string $link_type
 * @property float $domain_authority
 * @property float $page_authority
 * @property \Illuminate\Support\Carbon $first_seen
 * @property \Illuminate\Support\Carbon $last_checked
 * @property bool $is_active
 * @property bool $is_toxic
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
        'site_id',
        'source_url',
        'target_url',
        'anchor_text',
        'link_type',
        'domain_authority',
        'page_authority',
        'first_seen',
        'last_checked',
        'is_active',
        'is_toxic',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'domain_authority' => 'float',
            'page_authority' => 'float',
            'first_seen' => 'date',
            'last_checked' => 'date',
            'is_active' => 'boolean',
            'is_toxic' => 'boolean',
        ];
    }

    /**
     * Get the site that owns this backlink.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the changes for this backlink.
     */
    public function changes(): HasMany
    {
        return $this->hasMany(BacklinkChange::class);
    }

    /**
     * Scope for active backlinks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for toxic backlinks.
     */
    public function scopeToxic($query)
    {
        return $query->where('is_toxic', true);
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
