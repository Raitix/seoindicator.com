<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SerpFeature model for tracking SERP feature presence.
 *
 * @property int $id
 * @property int $keyword_id
 * @property string $feature_type
 * @property bool $is_present
 * @property int|null $position
 * @property string|null $url
 * @property string|null $title
 * @property string|null $snippet
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Keyword $keyword
 */
class SerpFeature extends Model
{
    use HasFactory;

    /**
     * All supported SERP feature types.
     */
    public const FEATURE_TYPES = [
        'featured_snippet',
        'local_pack',
        'image_pack',
        'video',
        'people_also_ask',
        'knowledge_panel',
        'sitelinks',
        'reviews',
        'faq',
        'product',
    ];

    /**
     * Feature metadata (name, icon, description, CTR impact).
     */
    public const FEATURE_META = [
        'featured_snippet' => [
            'name' => 'Featured Snippet',
            'icon' => 'trophy',
            'description' => 'Answer box at top of search results',
            'ctrImpact' => '+30-50%',
        ],
        'local_pack' => [
            'name' => 'Local Pack',
            'icon' => 'map-pin',
            'description' => 'Map with 3 local business listings',
            'ctrImpact' => 'High for local',
        ],
        'image_pack' => [
            'name' => 'Image Pack',
            'icon' => 'image',
            'description' => 'Horizontal row of images in results',
            'ctrImpact' => 'Visual attraction',
        ],
        'video' => [
            'name' => 'Video Result',
            'icon' => 'play-circle',
            'description' => 'Video thumbnail in search results',
            'ctrImpact' => 'High engagement',
        ],
        'people_also_ask' => [
            'name' => 'People Also Ask',
            'icon' => 'help-circle',
            'description' => 'Expandable related questions',
            'ctrImpact' => 'Secondary visibility',
        ],
        'knowledge_panel' => [
            'name' => 'Knowledge Panel',
            'icon' => 'info',
            'description' => 'Entity information sidebar',
            'ctrImpact' => 'Brand authority',
        ],
        'sitelinks' => [
            'name' => 'Sitelinks',
            'icon' => 'link',
            'description' => 'Additional page links under result',
            'ctrImpact' => '+20-30%',
        ],
        'reviews' => [
            'name' => 'Reviews/Ratings',
            'icon' => 'star',
            'description' => 'Star ratings displayed with result',
            'ctrImpact' => '+10-15%',
        ],
        'faq' => [
            'name' => 'FAQ Rich Result',
            'icon' => 'list',
            'description' => 'FAQ accordion in search results',
            'ctrImpact' => 'Increased visibility',
        ],
        'product' => [
            'name' => 'Product Listing',
            'icon' => 'shopping-cart',
            'description' => 'Product rich result with price/availability',
            'ctrImpact' => 'Shopping intent',
        ],
    ];

    protected $fillable = [
        'keyword_id',
        'feature_type',
        'is_present',
        'position',
        'url',
        'title',
        'snippet',
        'checked_at',
    ];

    protected $casts = [
        'is_present' => 'boolean',
        'position' => 'integer',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the keyword that owns this SERP feature.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope features that are present.
     */
    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }

    /**
     * Scope by feature type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('feature_type', $type);
    }

    /**
     * Get feature metadata.
     */
    public function getMetaAttribute(): array
    {
        return self::FEATURE_META[$this->feature_type] ?? [
            'name' => ucfirst(str_replace('_', ' ', $this->feature_type)),
            'icon' => 'circle',
            'description' => '',
            'ctrImpact' => '',
        ];
    }

    /**
     * Format for API response.
     */
    public function toApiResponse(): array
    {
        return [
            'type' => $this->feature_type,
            'isPresent' => $this->is_present,
            'position' => $this->position,
            'url' => $this->url,
            'snippet' => $this->snippet,
        ];
    }
}
