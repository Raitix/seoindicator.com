<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scheduled Report model for automated report generation.
 *
 * @property int $id
 * @property int $site_id
 * @property int|null $template_id
 * @property string $name
 * @property string $frequency
 * @property string $format
 * @property array $recipients
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $last_run
 * @property \Illuminate\Support\Carbon|null $next_run
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Site $site
 * @property-read \App\Models\ReportTemplate|null $template
 */
class ScheduledReport extends Model
{
    use HasFactory;

    public const FREQUENCIES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'biweekly' => 'Bi-weekly',
        'monthly' => 'Monthly',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'template_id',
        'name',
        'frequency',
        'format',
        'recipients',
        'enabled',
        'last_run',
        'next_run',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recipients' => 'array',
        'enabled' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns this scheduled report.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the template used for this scheduled report.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    /**
     * Calculate the next run date based on frequency.
     */
    public function calculateNextRun(): \Illuminate\Support\Carbon
    {
        $now = now();

        return match ($this->frequency) {
            'daily' => $now->addDay()->setTime(9, 0, 0),
            'weekly' => $now->next('monday')->setTime(9, 0, 0),
            'biweekly' => $now->addWeeks(2)->setTime(9, 0, 0),
            'monthly' => $now->addMonthNoOverflow()->startOfMonth()->setTime(9, 0, 0),
            default => $now->addWeek()->setTime(9, 0, 0),
        };
    }

    /**
     * Get the frequency label.
     */
    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? ucfirst($this->frequency);
    }
}
