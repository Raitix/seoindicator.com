<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisibilityScore Model.
 *
 * Stores daily calculated visibility scores for each project.
 * Used for trend analysis and dashboard metrics.
 *
 * @property int $id
 * @property int $project_id
 * @property int $score Overall visibility score (0-100)
 * @property float $position_component Position-based component
 * @property float $serp_component SERP feature component
 * @property float $volume_component Search volume component
 * @property int $keywords_count Number of keywords used in calculation
 * @property \Carbon\Carbon $calculated_at When the score was calculated
 * @property-read \App\Models\Project $project
 */
class VisibilityScore extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * This model uses calculated_at instead of created_at/updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'score',
        'position_component',
        'serp_component',
        'volume_component',
        'keywords_count',
        'calculated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'position_component' => 'decimal:2',
        'serp_component' => 'decimal:2',
        'volume_component' => 'decimal:2',
        'keywords_count' => 'integer',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the project this visibility score belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope to get scores for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get scores within a date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('calculated_at', [$from, $to]);
    }

    /**
     * Scope to get the latest score for each project.
     */
    public function scopeLatestPerProject($query)
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->selectRaw('MAX(id)')
                ->from('visibility_scores')
                ->groupBy('project_id');
        });
    }

    /**
     * Get the latest score for a project.
     */
    public static function getLatestForProject(int $projectId): ?self
    {
        return static::forProject($projectId)
            ->orderByDesc('calculated_at')
            ->first();
    }

    /**
     * Get trend data for a project (last N days).
     */
    public static function getTrendForProject(int $projectId, int $days = 30): array
    {
        return static::forProject($projectId)
            ->where('calculated_at', '>=', now()->subDays($days))
            ->orderBy('calculated_at')
            ->get(['id', 'score', 'calculated_at', 'position_component', 'serp_component', 'volume_component'])
            ->toArray();
    }
}
