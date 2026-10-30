# TEST-9-3: Annotation Model Tests

## Task Reference
- **Task:** [TASK-9-3](../TASKS/TASK-9-3.md)
- **Feature:** FEAT-009 Keyword Annotations
- **Specification:** SPEC-009

---

## Overview

Tests for the `Annotation` model's relationships, dynamic accessors, and query scopes. This model provides the business logic layer for keyword annotations, enabling users to document SEO events and correlate them with ranking changes.

---

## Test Files

| File | Type | Purpose |
|------|------|---------|
| `backend/tests/Unit/Models/AnnotationTest.php` | Unit | Model relationships, scopes, factory (existing) |
| `backend/tests/Unit/Models/Annotation/AnnotationAccessorTest.php` | Unit | category_label and category_icon accessors |
| `backend/tests/Unit/Models/Annotation/AnnotationScopeTest.php` | Unit | forDateRange, byCategory scopes |
| `backend/tests/Unit/Models/Annotation/AnnotationRelationshipTest.php` | Unit | belongsTo Keyword, belongsTo Project |

---

## Test Scenarios

### Relationship Tests (RL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RL-001 | Model belongs to Keyword | `$annotation->keyword` | Returns Keyword model instance |
| RL-002 | BelongsTo returns correct relationship type | `$annotation->keyword()` | Returns `BelongsTo` relationship |
| RL-003 | Model belongs to Project | `$annotation->project` | Returns Project model instance |
| RL-004 | BelongsTo returns correct relationship type | `$annotation->project()` | Returns `BelongsTo` relationship |
| RL-005 | Keyword has many annotations | `$keyword->annotations` | Returns Collection of Annotation models |
| RL-006 | Project has many annotations | `$project->annotations` | Returns Collection of Annotation models |
| RL-007 | Eager loading keyword works | `Annotation::with('keyword')->get()` | No N+1 queries, keyword loaded |
| RL-008 | Eager loading project works | `Annotation::with('project')->get()` | No N+1 queries, project loaded |
| RL-009 | Deleting keyword cascades to annotations | Delete keyword | All related annotations deleted |
| RL-010 | Deleting project cascades to annotations | Delete project | All related annotations deleted |

---

### Accessor Tests (AC) - category_label

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | category_label accessor exists | `$annotation->category_label` | Returns string |
| AC-002 | Content update category | `category = 'content_update'` | `category_label = 'Content Update'` |
| AC-003 | Link building category | `category = 'link_building'` | `category_label = 'Link Building'` |
| AC-004 | Technical fix category | `category = 'technical_fix'` | `category_label = 'Technical Fix'` |
| AC-005 | Algorithm update category | `category = 'algorithm_update'` | `category_label = 'Algorithm Update'` |
| AC-006 | Competitor action category | `category = 'competitor_action'` | `category_label = 'Competitor Action'` |
| AC-007 | Seasonal category | `category = 'seasonal'` | `category_label = 'Seasonal'` |
| AC-008 | Manual action category | `category = 'manual_action'` | `category_label = 'Manual Action'` |
| AC-009 | Other category | `category = 'other'` | `category_label = 'Other'` |
| AC-010 | Unknown category fallback | `category = 'unknown_category'` | `category_label = 'Other'` |
| AC-011 | Legacy category with hyphen | `category = 'content-update'` | `category_label = 'Other'` (or mapped) |
| AC-012 | Empty category fallback | `category = ''` | `category_label = 'Other'` |
| AC-013 | Case sensitivity test | `category = 'CONTENT_UPDATE'` | `category_label = 'Other'` (case sensitive) |
| AC-014 | Accessor works with fresh model | `$annotation->fresh()->category_label` | Returns correct label |

---

### Accessor Tests (AI) - category_icon

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AI-001 | category_icon accessor exists | `$annotation->category_icon` | Returns string (emoji) |
| AI-002 | Content update icon | `category = 'content_update'` | `category_icon = '✏️'` |
| AI-003 | Link building icon | `category = 'link_building'` | `category_icon = '🔗'` |
| AI-004 | Technical fix icon | `category = 'technical_fix'` | `category_icon = '🔧'` |
| AI-005 | Algorithm update icon | `category = 'algorithm_update'` | `category_icon = '🤖'` |
| AI-006 | Competitor action icon | `category = 'competitor_action'` | `category_icon = '👀'` |
| AI-007 | Seasonal icon | `category = 'seasonal'` | `category_icon = '📅'` |
| AI-008 | Manual action icon | `category = 'manual_action'` | `category_icon = '⚠️'` |
| AI-009 | Other icon | `category = 'other'` | `category_icon = '📝'` |
| AI-010 | Unknown category fallback | `category = 'unknown_category'` | `category_icon = '📝'` |
| AI-011 | Legacy category with hyphen | `category = 'content-update'` | `category_icon = '📝'` (or mapped) |
| AI-012 | Empty category fallback | `category = ''` | `category_icon = '📝'` |
| AI-013 | Case sensitivity test | `category = 'CONTENT_UPDATE'` | `category_icon = '📝'` (case sensitive) |
| AI-014 | Accessor works with fresh model | `$annotation->fresh()->category_icon` | Returns correct icon |

---

### Scope Tests (SC) - forDateRange

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-001 | Scope exists on model | `Annotation::forDateRange(...)` | Scope callable |
| SC-002 | Filters by date range inclusive | Dates: 2026-02-01 to 2026-02-28 | Returns annotations within range |
| SC-003 | Excludes dates before range | Dates: 2026-02-01 to 2026-02-28 | Excludes 2026-01-31 |
| SC-004 | Excludes dates after range | Dates: 2026-02-01 to 2026-02-28 | Excludes 2026-03-01 |
| SC-005 | Single date range | Dates: 2026-02-15 to 2026-02-15 | Returns only 2026-02-15 annotations |
| SC-006 | Works with Carbon dates | Carbon instances for from/to | Returns correct results |
| SC-007 | Works with string dates | Y-m-d string format | Returns correct results |
| SC-008 | Chained with other scopes | `forDateRange()->byCategory()` | Both filters applied |
| SC-009 | Empty result for no matches | Non-existent date range | Returns empty collection |
| SC-010 | Orders by date descending | Multiple annotations in range | Can chain orderBy('date', 'desc') |

---

### Scope Tests (SC) - byCategory

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SC-011 | Filters by category | `category = 'algorithm_update'` | Returns only matching annotations |
| SC-012 | Case sensitive matching | `category = 'ALGORITHM_UPDATE'` | Returns empty (case sensitive) |
| SC-013 | Chained with forDateRange | `forDateRange()->byCategory()` | Both filters applied |
| SC-014 | Multiple calls | `byCategory('a')->byCategory('b')` | Returns only category 'b' |
| SC-015 | Invalid category | `category = 'invalid'` | Returns empty (no match) |

---

### Edge Case Tests (EC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Accessor with null category | `category = null` | Returns 'Other' label, '📝' icon |
| EC-002 | Accessor performance | 1000 annotations | Accessors execute efficiently |
| EC-003 | Mass assignment with accessor | Create via factory, read accessor | Accessor computed correctly |
| EC-004 | JSON serialization includes accessors | `$annotation->toArray()` | May or may not include accessors |
| EC-005 | Scope with invalid date string | `forDateRange('invalid', 'date')` | Throws exception or returns empty |
| EC-006 | Scope with reversed dates | `forDateRange(future, past)` | Returns empty or handles gracefully |
| EC-007 | Accessor on unsaved model | New model, not persisted | Returns correct label/icon |

---

### Integration Tests (IT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IT-001 | Full annotation lifecycle | Create, update, delete | All operations succeed |
| IT-002 | Accessor in JSON response | Return annotation via API | category_label, category_icon included |
| IT-003 | Scope in controller | Filter annotations in controller | Correct filtered results |
| IT-004 | Relationship with constraints | Keyword with project | Annotation linked to both |

---

## Mock Data

### Sample Annotation Categories (Enum Format)

```php
// From TASK-9-2: AnnotationCategory enum
enum AnnotationCategory: string
{
    case CONTENT_UPDATE = 'content_update';
    case LINK_BUILDING = 'link_building';
    case TECHNICAL_FIX = 'technical_fix';
    case ALGORITHM_UPDATE = 'algorithm_update';
    case COMPETITOR_ACTION = 'competitor_action';
    case SEASONAL = 'seasonal';
    case MANUAL_ACTION = 'manual_action';
    case OTHER = 'other';
}
```

### Sample Annotation Categories (Legacy Format - in model)

```php
// From Annotation model (existing)
public const CATEGORIES = [
    'algorithm-update',
    'content-update',
    'technical-change',
    'link-building',
    'competition',
    'seasonal',
    'manual-action',
    'recovery',
    'other',
];
```

### Category Label Mapping

| Enum Value | Label | Icon |
|------------|-------|------|
| `content_update` | Content Update | ✏️ |
| `link_building` | Link Building | 🔗 |
| `technical_fix` | Technical Fix | 🔧 |
| `algorithm_update` | Algorithm Update | 🤖 |
| `competitor_action` | Competitor Action | 👀 |
| `seasonal` | Seasonal | 📅 |
| `manual_action` | Manual Action | ⚠️ |
| `other` | Other | 📝 |

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Relationships | 100% | RL-001 to RL-010 (10 tests) |
| category_label Accessor | 100% | AC-001 to AC-014 (14 tests) |
| category_icon Accessor | 100% | AI-001 to AI-014 (14 tests) |
| forDateRange Scope | 100% | SC-001 to SC-010 (10 tests) |
| byCategory Scope | 100% | SC-011 to SC-015 (5 tests) |
| Edge Cases | 100% | EC-001 to EC-007 (7 tests) |
| Integration | 100% | IT-001 to IT-004 (4 tests) |

**Total: 64 test scenarios**

---

## Test Execution

```bash
# Run all annotation model tests
php artisan test --filter=Annotation

# Run specific test file
php artisan test tests/Unit/Models/Annotation/AnnotationAccessorTest.php

# Run with coverage
php artisan test --coverage --filter=Annotation
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Keywords table | ✅ Exists | Required for keyword_id FK |
| Projects table | ✅ Exists | Required for project_id FK |
| Keyword model | ✅ Exists | For relationship tests |
| Project model | ✅ Exists | For relationship tests |
| AnnotationCategory enum | ⏳ TASK-9-2 | Not yet implemented - tests will use fallback |

---

## Notes for Developer

### Implementation Checklist

The Annotation model already exists with:
- ✅ Relationships (belongsTo Keyword, belongsTo Project)
- ✅ Scopes (forKeyword, forProject, byCategory, dateRange)
- ❌ Accessors (category_label, category_icon) - **NEED TO ADD**
- ❌ Scope rename: `dateRange` → `forDateRange` (to match spec)

### Required Changes

1. **Add accessors to Annotation model:**
```php
public function getCategoryLabelAttribute(): string
{
    return AnnotationCategory::tryFrom($this->category)?->label() ?? 'Other';
}

public function getCategoryIconAttribute(): string
{
    return AnnotationCategory::tryFrom($this->category)?->icon() ?? '📝';
}
```

2. **Add alias scope or rename:**
```php
// Option A: Add alias (preferred for backward compatibility)
public function scopeForDateRange($query, $from, $to)
{
    return $this->scopeDateRange($query, $from, $to);
}

// Option B: Rename existing scope
// Rename dateRange to forDateRange
```

3. **Create AnnotationCategory enum (TASK-9-2):**
```php
// backend/app/Enums/AnnotationCategory.php
enum AnnotationCategory: string
{
    // ... cases and methods
}
```

### Accessor Behavior Notes

- Accessors should use `AnnotationCategory::tryFrom()` for graceful fallback
- Unknown categories return 'Other' label and '📝' icon
- Accessors are computed at access time, not stored in DB

---

## Comments

### [2026-02-27 11:35] Agent: PreTester
**Test Documentation Created:**
- 64 test scenarios covering relationships, accessors, scopes, edge cases, and integration
- Focus on category_label and category_icon accessors (depends on TASK-9-2 enum)
- forDateRange scope tests (alias for existing dateRange scope)
- Fallback behavior tests for unknown categories

**Key Testing Priorities:**
1. AC-010, AI-010: Unknown category fallback (critical for robustness)
2. SC-002 to SC-004: Date range filtering accuracy
3. RL-009, RL-010: CASCADE delete verification (data integrity)

**Dependency Note:**
TASK-9-2 (AnnotationCategory enum) is required for full accessor functionality. Tests will initially fail until enum is implemented, which is correct TDD behavior.

**Status:** `Tests Coverage In Progress` → Ready for test implementation
