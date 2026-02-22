<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * IssueType model for SEO issue type templates.
 *
 * @property int $id
 * @property string $category
 * @property string $name
 * @property string $description
 * @property string $severity_default
 * @property string $recommendation_template
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class IssueType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'issue_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category',
        'name',
        'description',
        'severity_default',
        'recommendation_template',
    ];

    /**
     * Get the audit issues for this issue type.
     */
    public function auditIssues(): HasMany
    {
        return $this->hasMany(AuditIssue::class);
    }

    /**
     * Scope for critical severity issues.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity_default', 'critical');
    }

    /**
     * Scope for warning severity issues.
     */
    public function scopeWarning($query)
    {
        return $query->where('severity_default', 'warning');
    }

    /**
     * Scope for notice severity issues.
     */
    public function scopeNotice($query)
    {
        return $query->where('severity_default', 'notice');
    }

    /**
     * Scope for specific category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Common categories.
     */
    public static function categories(): array
    {
        return [
            'meta' => 'Meta Tags',
            'content' => 'Content Issues',
            'technical' => 'Technical SEO',
            'performance' => 'Performance',
            'security' => 'Security',
            'accessibility' => 'Accessibility',
            'mobile' => 'Mobile Optimization',
        ];
    }

    /**
     * Generate a recommendation from the template.
     */
    public function generateRecommendation(array $context = []): string
    {
        $recommendation = $this->recommendation_template;
        
        foreach ($context as $key => $value) {
            $recommendation = str_replace("{{{$key}}}", $value, $recommendation);
        }
        
        return $recommendation;
    }
}