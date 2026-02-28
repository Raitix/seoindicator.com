<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report Branding model for customizing report appearance.
 *
 * @property int $id
 * @property int $site_id
 * @property string|null $logo_url
 * @property string|null $logo_file
 * @property string $primary_color
 * @property string $secondary_color
 * @property string $font_family
 * @property string|null $company_name
 * @property string|null $company_website
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 */
class ReportBranding extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report_branding';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'logo_url',
        'logo_file',
        'primary_color',
        'secondary_color',
        'font_family',
        'company_name',
        'company_website',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns this branding.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get default branding values.
     */
    public static function getDefaults(): array
    {
        return [
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'font_family' => 'Inter',
        ];
    }
}
