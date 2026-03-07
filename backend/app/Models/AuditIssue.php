<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditIssue model for detailed SEO audit issues.
 *
 * @property int $id
 * @property int $audit_id
 * @property string $url
 * @property int $issue_type_id
 * @property string|null $issue_type
 * @property string $severity
 * @property string $description
 * @property string $recommendation
 * @property array|null $context
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AuditIssue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_issues';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_id',
        'url',
        'issue_type_id',
        'issue_type',
        'severity',
        'description',
        'recommendation',
        'context',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /**
     * Get the audit that owns this issue.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the issue type that owns this issue.
     */
    public function issueType(): BelongsTo
    {
        return $this->belongsTo(IssueType::class);
    }

    /**
     * Scope for critical severity issues.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for warning severity issues.
     */
    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    /**
     * Scope for notice severity issues.
     */
    public function scopeNotice($query)
    {
        return $query->where('severity', 'notice');
    }

    /**
     * Scope for open issues.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for fixed issues.
     */
    public function scopeFixed($query)
    {
        return $query->where('status', 'fixed');
    }

    /**
     * Scope for ignored issues.
     */
    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    /**
     * Mark the issue as fixed.
     */
    public function markAsFixed(): self
    {
        $this->status = 'fixed';
        $this->save();

        return $this;
    }

    /**
     * Mark the issue as ignored.
     */
    public function markAsIgnored(): self
    {
        $this->status = 'ignored';
        $this->save();

        return $this;
    }

    /**
     * Mark the issue as open.
     */
    public function markAsOpen(): self
    {
        $this->status = 'open';
        $this->save();

        return $this;
    }
}
