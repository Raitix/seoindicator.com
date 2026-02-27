<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BacklinkSummary model for aggregated backlink metrics.
 *
 * @property int $id
 * @property int $site_id
 * @property string $domain
 * @property int $total_backlinks
 * @property int $referring_domains
 * @property float $domain_authority
 * @property \Illuminate\Support\Carbon $checked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BacklinkSummary extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'backlink_summaries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'domain',
        'total_backlinks',
        'referring_domains',
        'domain_authority',
        'checked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_backlinks' => 'integer',
            'referring_domains' => 'integer',
            'domain_authority' => 'float',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'total_backlinks' => 0,
        'referring_domains' => 0,
        'domain_authority' => 0.00,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->checked_at)) {
                $model->checked_at = now();
            }
        });
    }

    /**
     * Get the site that owns this backlink summary.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}