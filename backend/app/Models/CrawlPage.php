<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrawlPage model for the Headless Crawler Service.
 *
 * Represents an individual page crawled during a crawl job,
 * containing rendered HTML, structured text, and metadata.
 *
 * @see TASK-22-2
 * @see SPEC-022
 *
 * @property int $id
 * @property int $crawl_job_id
 * @property string $url
 * @property int $depth
 * @property string|null $title
 * @property string|null $rendered_html
 * @property string|null $structured_text
 * @property array|null $meta
 * @property array|null $links
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $crawled_at
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CrawlPage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'crawl_job_id',
        'url',
        'depth',
        'title',
        'rendered_html',
        'structured_text',
        'meta',
        'links',
        'size',
        'crawled_at',
        'error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'links' => 'array',
        'crawled_at' => 'datetime',
        'depth' => 'integer',
        'size' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the crawl job that owns this page.
     */
    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class, 'crawl_job_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to get pages for a specific job.
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('crawl_job_id', $jobId);
    }

    /**
     * Scope to get pages at a specific depth.
     */
    public function scopeAtDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }

    /**
     * Scope to get pages without errors.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereNull('error');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the human-readable size.
     */
    public function getSizeFormattedAttribute(): string
    {
        return $this->formatSize($this->size);
    }

    /**
     * Get internal links from the page.
     */
    public function getInternalLinksAttribute(): array
    {
        if (empty($this->links)) {
            return [];
        }

        return array_filter($this->links, fn ($link) => $link['internal'] ?? false);
    }

    /**
     * Get external links from the page.
     */
    public function getExternalLinksAttribute(): array
    {
        if (empty($this->links)) {
            return [];
        }

        return array_filter($this->links, fn ($link) => !($link['internal'] ?? true));
    }

    /**
     * Format size in human-readable format.
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 1) . ' ' . $units[$unitIndex];
    }
}
