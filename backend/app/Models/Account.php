<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Account Model.
 *
 * Represents a team/organization with an owner and members.
 * 
 * @property int $id
 * @property string $name
 * @property int $owner_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<TeamMember> $members
 */
class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'owner_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'owner_id' => 'integer',
    ];

    /**
     * Get the owner of the account.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all members of the account/team.
     */
    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get projects associated with this account.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Check if a user is a member of this account.
     *
     * @param User $user
     * @return bool
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the owner of this account.
     *
     * @param User $user
     * @return bool
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Get the member record for a user.
     *
     * @param User $user
     * @return TeamMember|null
     */
    public function getMember(User $user): ?TeamMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }
}
