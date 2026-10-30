<?php

namespace App\Models\AIVisibility;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * AI Visibility Project Model.
 *
 * Represents a project for tracking brand visibility in AI platforms.
 *
 * @property int $id
 * @property int $user_id
 * @property string $brand_name
 * @property array $keywords
 * @property array $competitors
 * @property array|null $query_templates
 * @property string $tracking_frequency
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_queried_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read array $competitor_list
 *
 * @see TASK-34-2
 * @see SPEC-034
 */
class AIVisibilityProject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AIVisibility\AIVisibilityProjectFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_visibility_projects';

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_active' => true,
        'tracking_frequency' => 'weekly',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'brand_name',
        'keywords',
        'competitors',
        'query_templates',
        'tracking_frequency',
        'is_active',
        'last_queried_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'competitors' => 'array',
            'query_templates' => 'array',
            'is_active' => 'boolean',
            'last_queried_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the query results for this project.
     */
    public function queryResults(): HasMany
    {
        return $this->hasMany(AIQueryResult::class, 'project_id');
    }

    /**
     * Get the analytics for this project.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(AIMentionAnalytics::class, 'project_id');
    }

    /**
     * Get the alerts for this project.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AIVisibilityAlert::class, 'project_id');
    }

    /**
     * Scope to get only active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get projects that are due for querying.
     * 
     * A project is due for query if:
     * - It's active
     * - It has never been queried (last_queried_at is null), OR
     * - The time since last query exceeds the tracking frequency
     */
    public function scopeDueForQuery($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_queried_at')
                    ->orWhere(function ($subQ) {
                        $subQ->where('tracking_frequency', 'daily')
                            ->where('last_queried_at', '<', now()->subDay());
                    })
                    ->orWhere(function ($subQ) {
                        $subQ->where('tracking_frequency', 'weekly')
                            ->where('last_queried_at', '<', now()->subWeek());
                    })
                    ->orWhere(function ($subQ) {
                        $subQ->where('tracking_frequency', 'monthly')
                            ->where('last_queried_at', '<', now()->subMonth());
                    });
            });
    }

    /**
     * Check if the project belongs to a specific user.
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Get the competitor names as a flat array.
     * 
     * This is a method version for programmatic access.
     */
    public function getCompetitorNames(): array
    {
        return collect($this->competitors ?? [])
            ->pluck('name')
            ->filter()
            ->toArray();
    }

    /**
     * Accessor: Get the competitor names as a flat array.
     * 
     * Provides $project->competitor_list syntax.
     *
     * @return array
     */
    public function getCompetitorListAttribute(): array
    {
        return $this->getCompetitorNames();
    }

    /**
     * Get all brands to track (brand name + competitors).
     */
    public function getAllBrands(): array
    {
        $brands = [$this->brand_name];
        
        foreach ($this->getCompetitorNames() as $name) {
            $brands[] = $name;
        }
        
        return array_filter($brands);
    }
}
