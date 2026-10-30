<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GBPPost extends Model
{
    use HasFactory;

    protected $table = 'gbp_posts';

    public const UPDATED_AT = null;

    protected $fillable = [
        'location_id',
        'google_post_id',
        'post_type',
        'title',
        'content',
        'media_url',
        'call_to_action',
        'call_to_action_url',
        'published_at',
    ];

    protected $casts = [
        'location_id' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '>', now());
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('post_type', strtoupper($type));
    }

    public function scopeRecent($query, ?int $limit = null)
    {
        $query->orderByDesc('published_at');

        return $limit !== null ? $query->limit($limit) : $query;
    }

    public function getPostTypeLabelAttribute(): string
    {
        return match ($this->post_type) {
            'EVENT' => 'Event',
            'OFFER' => 'Offer',
            'UPDATE' => 'Update',
            'COVID' => 'Covid Update',
            'PRODUCT' => 'Product',
            'STANDARD' => 'Standard',
            default => (string) $this->post_type,
        };
    }

    public function setGooglePostIdAttribute($value): void
    {
        $this->attributes['google_post_id'] = $this->truncate($value, 255);
    }

    public function setPostTypeAttribute($value): void
    {
        $normalized = $value === null ? null : strtoupper((string) $value);
        $this->attributes['post_type'] = $this->truncate($normalized, 50);
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $this->nullableTruncate($value, 255);
    }

    public function setMediaUrlAttribute($value): void
    {
        $this->attributes['media_url'] = $this->nullableTruncate($value, 500);
    }

    public function setCallToActionAttribute($value): void
    {
        $this->attributes['call_to_action'] = $this->nullableTruncate($value, 100);
    }

    public function setCallToActionUrlAttribute($value): void
    {
        $this->attributes['call_to_action_url'] = $this->nullableTruncate($value, 500);
    }

    private function truncate($value, int $length): string
    {
        return mb_substr((string) $value, 0, $length);
    }

    private function nullableTruncate($value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->truncate($value, $length);
    }
}
