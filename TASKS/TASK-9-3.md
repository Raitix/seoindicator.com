# TASK-9-3: Annotation Model

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Define the `Annotation` model in Laravel, representing the database schema and business logic around keyword annotations.

## Responsibilities
1. Relationships:
   - Belongs to `Keyword`.
   - Belongs to `Project`.

2. Dynamic Accessors:
   - `category_label`: Maps the category value to its label.
   - `category_icon`: Maps the category value to its icon.

3. Query Scopes:
   - `forDateRange`: Filters annotations between two dates.
   - `byCategory`: Filters by category.

## Code Example
```php
class Annotation extends Model
{
    protected $fillable = [
        'keyword_id',
        'project_id',
        'date',
        'title',
        'description',
        'category',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return AnnotationCategory::tryFrom($this->category)?->label() ?? 'Other';
    }

    public function getCategoryIconAttribute(): string
    {
        return AnnotationCategory::tryFrom($this->category)?->icon() ?? '📝';
    }
}
```

## Acceptance Criteria
- [ ] Model correctly defines relationships to `Keyword` and `Project`.
- [ ] Accessors dynamically return `category_label` and `category_icon`.
- [ ] Scopes filter annotations by date range and category.
- [ ] Test cases validate all relationships, accessors, and scopes.

## Dependencies
- TASK-9-1: Schema must be created first.
- TASK-9-2: PHP enum must exist for categories.

## Effort Estimate
2-3 hours.

---

## Comments
### [2026-02-20 17:51] Agent: SpecSystemAnalyst
Task for implementing the backend model to support the annotation feature.