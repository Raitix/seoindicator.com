<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Annotation;
use App\Models\PWAAnalysis;

/**
 * Project Model.
 *
 * Represents a project for organizing multi-site SEO tracking data.
 * Each project belongs to a user and can contain GSC data, keywords, etc.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $domain
 * @property string|null $notes
 * @property bool $is_default
 * @property \Carbon\Carbon|null $archived_at
 * @property \Carbon\Carbon|null $backlinks_last_synced_at
 * @property int|null $account_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Account|null $account
 */
class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'notes',
        'is_default',
        'archived_at',
        'backlinks_last_synced_at',
        'account_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'archived_at' => 'datetime',
        'backlinks_last_synced_at' => 'datetime',
    ];

    /**
     * Get the user that owns this project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account this project belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the GSC data for the project.
     */
    public function gscData(): HasMany
    {
        return $this->hasMany(GSCData::class);
    }

    /**
     * Get the GSC connections for the project.
     */
    public function gscConnections(): HasMany
    {
        return $this->hasMany(GSCConnection::class);
    }

    /**
     * Get the members of the project.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Get the users of the project (many-to-many through project_members).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the annotations for this project.
     */
    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    /**
     * Get the visibility scores for this project.
     */
    public function visibilityScores(): HasMany
    {
        return $this->hasMany(VisibilityScore::class);
    }

    /**
     * Get the latest visibility score for this project.
     */
    public function latestVisibilityScore()
    {
        return $this->visibilityScores()->orderByDesc('calculated_at');
    }

    /**
     * Get the PWA analysis for this project.
     */
    public function pwaAnalysis()
    {
        return $this->hasOne(PWAAnalysis::class);
    }

    /**
     * Check if the project is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Archive the project.
     */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Unarchive the project.
     */
    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Scope to get only active (non-archived) projects.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to get only archived projects.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope to get default projects.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}