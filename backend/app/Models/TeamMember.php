<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * TeamMember Model.
 *
 * Represents a user's membership in an account (team) with a specific role.
 *
 * @property int $id
 * @property int $account_id
 * @property int $user_id
 * @property string $role
 * @property int|null $invited_by
 * @property \Carbon\Carbon|null $invited_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property string|null $invitation_token
 * @property \Carbon\Carbon|null $invitation_expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 * @property-read User $user
 * @property-read User|null $inviter
 */
class TeamMember extends Model
{
    use HasFactory;

    /**
     * Role constants.
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Invitation token length.
     */
    public const TOKEN_LENGTH = 64;

    /**
     * Default invitation expiry in hours.
     */
    public const INVITATION_EXPIRY_HOURS = 24;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'role',
        'invited_by',
        'invited_at',
        'accepted_at',
        'invitation_token',
        'invitation_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'account_id' => 'integer',
        'user_id' => 'integer',
        'invited_by' => 'integer',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
    ];

    /**
     * Get all valid roles.
     *
     * @return array<string>
     */
    public static function getValidRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_VIEWER,
        ];
    }

    /**
     * Check if a role is valid.
     *
     * @param string $role
     * @return bool
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getValidRoles(), true);
    }

    /**
     * Generate a secure invitation token.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Get default invitation expiry time.
     *
     * @return \Carbon\Carbon
     */
    public static function getDefaultExpiry(): \Carbon\Carbon
    {
        return now()->addHours(self::INVITATION_EXPIRY_HOURS);
    }

    /**
     * Get the account this membership belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user this membership belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this member.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the member is an owner.
     *
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if the member is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
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
     * Check if the member can manage other members.
     *
     * @return bool
     */
    public function canManageTeam(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    /**
     * Check if the invitation is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null;
    }

    /**
     * Check if the invitation is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->invitation_expires_at !== null 
            && $this->invitation_expires_at->isPast();
    }

    /**
     * Check if the invitation token is valid.
     *
     * @param string $token
     * @return bool
     */
    public function isValidToken(string $token): bool
    {
        if ($this->invitation_token === null) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        // Use timing-safe comparison
        return hash_equals($this->invitation_token, $token);
    }

    /**
     * Mark the invitation as accepted.
     *
     * @return bool
     */
    public function accept(): bool
    {
        if ($this->accepted_at !== null) {
            return false;
        }

        $this->accepted_at = now();
        $this->invitation_token = null;
        $this->invitation_expires_at = null;

        return $this->save();
    }

    /**
     * Clear the invitation token.
     *
     * @return void
     */
    public function clearInvitation(): void
    {
        $this->invitation_token = null;
        $this->invitation_expires_at = null;
    }
}
