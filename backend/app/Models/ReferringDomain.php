<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReferringDomain model for aggregated domain-level backlink metrics.
 *
 * @property int $id
 * @property int $project_id
 * @property string $domain
 * @property int $backlinks_count
 * @property int $domain_trust_score
 * @property \Illuminate\Support\Carbon|null $first_seen_at
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ReferringDomain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referring_domains';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'domain',
        'backlinks_count',
        'domain_trust_score',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'backlinks_count' => 'integer',
            'domain_trust_score' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns this referring domain.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope to order by backlinks count descending.
     */
    public function scopeOrderByBacklinksCount($query)
    {
        return $query->orderByDesc('backlinks_count');
    }

    /**
     * Scope to order by domain trust score descending.
     */
    public function scopeOrderByTrustScore($query)
    {
        return $query->orderByDesc('domain_trust_score');
    }
}
