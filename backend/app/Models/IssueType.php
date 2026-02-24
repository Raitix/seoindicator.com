<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueType extends Model
{
    use HasFactory;

    protected $fillable = ['category', 'name', 'description', 'severity_default', 'recommendation_template'];

    public static function categories(): array
    {
        return [
            'meta' => 'SEO Meta',
            'content' => 'Content Quality',
            'backlink' => 'Backlinks',
            'performance' => 'Performance',
        ];
    }

    public function auditIssues()
    {
        return $this->hasMany(AuditIssue::class, 'issue_type_id');
    }
}