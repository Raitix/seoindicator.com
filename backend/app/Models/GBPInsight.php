<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class GBPInsight extends Model
{
    use HasFactory;

    protected $table = 'gbp_insights';

    public const UPDATED_AT = null;

    protected $fillable = [
        'location_id',
        'views',
        'searches',
        'calls',
        'directions',
        'website_clicks',
        'photos_views',
        'insights_date',
    ];

    protected $casts = [
        'location_id' => 'integer',
        'views' => 'integer',
        'searches' => 'integer',
        'calls' => 'integer',
        'directions' => 'integer',
        'website_clicks' => 'integer',
        'photos_views' => 'integer',
        'insights_date' => 'date',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $insight): void {
            foreach (['views', 'searches', 'calls', 'directions', 'website_clicks', 'photos_views'] as $attribute) {
                if (($insight->{$attribute} ?? 0) < 0) {
                    throw new QueryException(
                        $insight->getConnection()->getName(),
                        'gbp_insights validation',
                        [$attribute => $insight->{$attribute}],
                        new \InvalidArgumentException(sprintf('%s must be zero or greater.', $attribute))
                    );
                }
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('insights_date', [
            $this->normalizeDateInput($startDate),
            $this->normalizeDateInput($endDate),
        ]);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function calculateTotalActions(): int
    {
        return (int) $this->calls + (int) $this->directions + (int) $this->website_clicks;
    }

    public function getFormattedDateAttribute(): ?string
    {
        return $this->insights_date?->format('Y-m-d');
    }

    public function getTotalViewsAttribute(): int
    {
        return (int) $this->views;
    }

    public function getEngagementRateAttribute(): float
    {
        $searches = (int) $this->searches;

        if ($searches <= 0) {
            return 0.0;
        }

        return ((int) $this->views / $searches) * 100;
    }

    public function setViewsAttribute($value): void
    {
        $this->attributes['views'] = $this->sanitizeInteger($value);
    }

    public function setSearchesAttribute($value): void
    {
        $this->attributes['searches'] = $this->sanitizeInteger($value);
    }

    public function setCallsAttribute($value): void
    {
        $this->attributes['calls'] = $this->sanitizeInteger($value);
    }

    public function setDirectionsAttribute($value): void
    {
        $this->attributes['directions'] = $this->sanitizeInteger($value);
    }

    public function setWebsiteClicksAttribute($value): void
    {
        $this->attributes['website_clicks'] = $this->sanitizeInteger($value);
    }

    public function setPhotosViewsAttribute($value): void
    {
        $this->attributes['photos_views'] = $this->sanitizeInteger($value);
    }

    public function setInsightsDateAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['insights_date'] = $value;
            return;
        }

        $this->attributes['insights_date'] = $this->normalizeDateInput($value);
    }

    private function sanitizeInteger($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim((string) $value);

        return preg_match('/^[+-]?\d+$/', $value) === 1 ? (int) $value : 0;
    }

    private function normalizeDateInput($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches) === 1) {
            return Carbon::parse($matches[0])->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
