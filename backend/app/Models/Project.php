<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Project Model (placeholder for testing).
 *
 * @property int $id
 * @property string $name
 * @property string|null $domain
 * @property int|null $account_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
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
        'name',
        'domain',
        'account_id',
    ];

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
}