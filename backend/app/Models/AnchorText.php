<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnchorText model for anchor text distribution analysis.
 *
 * @property int $id
 * @property int $project_id
 * @property string $anchor_text
 * @property int $backlinks_count
 * @property bool $is_branded
 * @property bool $is_exact_match
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AnchorText extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anchor_texts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'anchor_text',
        'backlinks_count',
        'is_branded',
        'is_exact_match',
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
            'is_branded' => 'boolean',
            'is_exact_match' => 'boolean',
        ];
    }

    /**
     * Get the project that owns this anchor text.
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
     * Scope for branded anchors.
     */
    public function scopeBranded($query)
    {
        return $query->where('is_branded', true);
    }

    /**
     * Scope for exact match anchors.
     */
    public function scopeExactMatch($query)
    {
        return $query->where('is_exact_match', true);
    }
}
