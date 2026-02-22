<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReportTemplate model for reusable SEO report configurations.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $name
 * @property bool $is_default
 * @property array $sections
 * @property array|null $branding
 * @property string|null $cover_page_text
 * @property string|null $executive_summary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 */
class ReportTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'name',
        'is_default',
        'sections',
        'branding',
        'cover_page_text',
        'executive_summary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'sections' => 'array',
        'branding' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns this template.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Mark this template as the default for its site.
     */
    public function makeDefault(): void
    {
        if ($this->site_id) {
            self::where('site_id', $this->site_id)->update(['is_default' => false]);
        }

        $this->is_default = true;
        $this->save();
    }
}
