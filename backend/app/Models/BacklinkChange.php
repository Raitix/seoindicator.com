<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BacklinkChange model for tracking backlink changes.
 *
 * @property int $id
 * @property int $site_id
 * @property int $backlink_id
 * @property string $change_type
 * @property array $details
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BacklinkChange extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'backlink_changes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'backlink_id',
        'change_type',
        'details',
        'detected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    /**
     * Get the site that owns this change.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the backlink that owns this change.
     */
    public function backlink(): BelongsTo
    {
        return $this->belongsTo(Backlink::class);
    }

    /**
     * Scope for new backlinks.
     */
    public function scopeNew($query)
    {
        return $query->where('change_type', 'new');
    }

    /**
     * Scope for lost backlinks.
     */
    public function scopeLost($query)
    {
        return $query->where('change_type', 'lost');
    }

    /**
     * Scope for modified backlinks.
     */
    public function scopeModified($query)
    {
        return $query->where('change_type', 'modified');
    }

    /**
     * Scope for recent changes.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }
}
