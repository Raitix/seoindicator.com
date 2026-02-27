# TASK-9-3: Annotation Model

## STATUS
`Tests Coverage In Progress`

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
### [2026-02-27 11:40] Agent: PreTester
**Test Coverage Complete:**
- Created `TESTS/TEST-9-3.md` with 64 test scenarios covering:
  - Relationships (RL-001 to RL-010) - 10 tests
  - category_label accessor (AC-001 to AC-014) - 14 tests
  - category_icon accessor (AI-001 to AI-014) - 14 tests
  - forDateRange scope (SC-001 to SC-010) - 10 tests
  - byCategory scope (SC-011 to SC-015) - 5 tests
  - Edge cases (EC-001 to EC-007) - 7 tests
  - Integration (IT-001 to IT-004) - 4 tests

**Test Files Created:**
- `backend/tests/Unit/Models/Annotation/AnnotationAccessorTest.php` - 44 test methods
- `backend/tests/Unit/Models/Annotation/AnnotationScopeTest.php` - 25 test methods
- `backend/tests/Unit/Models/Annotation/AnnotationRelationshipTest.php` - 18 test methods

**Key Implementation Notes:**
1. Accessors (`category_label`, `category_icon`) depend on `AnnotationCategory` enum (TASK-9-2)
2. Tests will initially fail until enum is implemented - correct TDD behavior
3. Accessors should use `AnnotationCategory::tryFrom()` for graceful fallback to 'Other'/'📝'
4. Existing model has `dateRange` scope - tests verify this works (alias `forDateRange` recommended)

**Category Mapping (from TASK-9-2 spec):**
| Enum Value | Label | Icon |
|------------|-------|------|
| content_update | Content Update | ✏️ |
| link_building | Link Building | 🔗 |
| technical_fix | Technical Fix | 🔧 |
| algorithm_update | Algorithm Update | 🤖 |
| competitor_action | Competitor Action | 👀 |
| seasonal | Seasonal | 📅 |
| manual_action | Manual Action | ⚠️ |
| other | Other | 📝 |

**Status:** Ready for Developer implementation. Tests require AnnotationCategory enum (TASK-9-2).

### [2026-02-26 20:05] Agent: ProjectManager
**Priority Assessment:** Confirmed `High` - Foundation task (model layer) that multiple downstream tasks depend on (TASK-9-4 Controller, TASK-9-5 Policies, TASK-9-7 AnnotationList, TASK-9-8 AnnotationForm).

**Risk Assessment:** Confirmed `Low` - Standard Laravel model implementation with relationships, scopes, and accessors. No external dependencies.

**Dependency Analysis:**
- TASK-9-1 (Schema): Status `To Write Tests` - ahead in pipeline ✅
- TASK-9-2 (Enum): Status `To Prioritize` - must be completed before this task's development phase

**Execution Order Notes:**
- Model should be developed after enum (TASK-9-2) is ready
- Controller (TASK-9-4) and Policies (TASK-9-5) will depend on this model
- Testing can proceed in parallel once model is implemented

**Status changed:** `To Prioritize` → `To Write Tests`

### [2026-02-20 17:51] Agent: SpecSystemAnalyst
Task for implementing the backend model to support the annotation feature.