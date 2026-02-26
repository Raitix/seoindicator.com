<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectMember Model.
 *
 * Represents a user's membership in a project with a specific role.
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $role
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Project $project
 * @property-read User $user
 */
class ProjectMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => 'string',
    ];

    /**
     * Valid roles for project membership.
     */
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Get all valid roles.
     *
     * @return array<string>
     */
    public static function getValidRoles(): array
    {
        return [
            self::ROLE_EDITOR,
            self::ROLE_VIEWER,
        ];
    }

    /**
     * Check if the role is valid.
     *
     * @param string $role
     * @return bool
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getValidRoles(), true);
    }

    /**
     * Check if the member is an editor.
     *
     * @return bool
     */
    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    /**
     * Check if the member is a viewer.
     *
     * @return bool
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Get the project this membership belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user this membership belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
