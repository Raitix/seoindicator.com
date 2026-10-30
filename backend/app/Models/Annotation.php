<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Annotation model for keyword-level SEO event annotations.
 *
 * Allows users to document significant changes, updates, and events
 * that may impact keyword rankings.
 *
 * @property int $id
 * @property int $keyword_id
 * @property int $project_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $title
 * @property string|null $description
 * @property string $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Keyword $keyword
 * @property-read \App\Models\Project $project
 *
 * @see TASK-9-1
 */
class Annotation extends Model
{
    use HasFactory;

    /**
     * Common annotation categories.
     */
    public const CATEGORIES = [
        'algorithm-update',
        'content-update',
        'technical-change',
        'link-building',
        'competition',
        'seasonal',
        'manual-action',
        'recovery',
        'other',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyword_id',
        'project_id',
        'date',
        'title',
        'description',
        'category',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the keyword that this annotation belongs to.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Get the project that this annotation belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope annotations by keyword.
     */
    public function scopeForKeyword($query, int $keywordId)
    {
        return $query->where('keyword_id', $keywordId);
    }

    /**
     * Scope annotations by project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope annotations by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope annotations within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope annotations for a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }
}
