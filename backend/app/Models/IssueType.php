<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueType extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'category', 'name', 'description', 'severity_default', 'recommendation_template'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity_default' => 'string',
        ];
    }

    /**
     * Get available issue type categories.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'meta' => 'SEO Meta',
            'content' => 'Content Quality',
            'backlink' => 'Backlinks',
            'performance' => 'Performance',
            'technical' => 'Technical SEO',
            'mobile' => 'Mobile',
        ];
    }

    /**
     * Get the audit issues for this issue type.
     */
    public function auditIssues()
    {
        return $this->hasMany(AuditIssue::class, 'issue_type_id');
    }

    /**
     * Get the severity attribute (alias for severity_default).
     *
     * @return string
     */
    public function getSeverityAttribute(): string
    {
        return $this->severity_default ?? 'notice';
    }
}
