<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ToolLead Model for storing leads captured from SEO tools.
 * 
 * TASK-16-5: Email Capture Modal & API
 * TASK-16-6: Lead Storage Service
 * 
 * @property int $id
 * @property string $email
 * @property string $tool
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ToolLead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Valid tool names.
     */
    public const VALID_TOOLS = [
        'meta-tag-analyzer',
        'keyword-density',
        'mobile-friendly',
        'social-preview',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'tool',
        'url',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Set the email attribute (normalized to lowercase).
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower($value);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope by tool name.
     */
    public function scopeByTool($query, string $tool)
    {
        return $query->where('tool', $tool);
    }

    /**
     * Scope by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', strtolower($email));
    }

    /**
     * Scope for non-trashed records only.
     */
    public function scopeNotTrashed($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope for leads created today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for leads created this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for leads created this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Check if a tool name is valid.
     */
    public static function isValidTool(string $tool): bool
    {
        return in_array($tool, self::VALID_TOOLS, true);
    }
}
