# TEST-24-7: PWASuggestionService Tests

## Task Reference
- Task: [TASK-24-7](../TASKS/TASK-24-7.md)
- Specification: [SPEC-024](../SPECIFICATIONS/SPEC-024.md)
- Feature: [FEAT-024](../FEATURES/FEAT-024.md)

## Overview
Unit tests for PWASuggestionService that generates actionable improvement suggestions based on PWA analysis results. Tests cover suggestion generation logic, priority mapping, and sorting.

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Unit/Services/PWASuggestionServiceTest.php` | Unit | Service method tests |

## Test Scenarios

### Category 1: generateSuggestions() Method Tests (GS-001 to GS-015)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GS-001 | No manifest | `has_manifest=false` | Single high priority suggestion for manifest |
| GS-002 | Invalid manifest | `has_manifest=true, manifest_valid=false` | Suggestion for invalid manifest |
| GS-003 | Missing required field (name) | `has_name=false` | High priority suggestion |
| GS-004 | Missing required field (short_name) | `has_short_name=false` | High priority suggestion |
| GS-005 | Missing required field (start_url) | `has_start_url=false` | High priority suggestion |
| GS-006 | Missing required field (display) | `has_display=false` | High priority suggestion |
| GS-007 | Missing recommended field (theme_color) | `has_theme_color=false` | Medium priority suggestion |
| GS-008 | Missing recommended field (background_color) | `has_background_color=false` | Medium priority suggestion |
| GS-009 | Missing recommended field (description) | `has_description=false` | Low priority suggestion |
| GS-010 | Missing required icon (192x192) | `icons_192_present=false` | High priority suggestion |
| GS-011 | Missing required icon (512x512) | `icons_512_present=false` | High priority suggestion |
| GS-012 | Missing recommended icon (180x180) | `icons_180_present=false` | Medium priority suggestion |
| GS-013 | Perfect PWA | All fields present | Empty array (no suggestions needed) |
| GS-014 | Multiple missing items | Multiple missing fields/icons | Multiple suggestions sorted by priority |
| GS-015 | Suggestions sorted by priority | Mixed missing items | High → Medium → Low order |

### Category 2: getSuggestionForField() Method Tests (SF-001 to SF-014)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SF-001 | Field name present | `field='name', present=true` | `null` |
| SF-002 | Field name missing | `field='name', present=false` | High priority suggestion with guide anchor |
| SF-003 | Field short_name missing | `field='short_name', present=false` | High priority suggestion |
| SF-004 | Field start_url missing | `field='start_url', present=false` | High priority suggestion |
| SF-005 | Field display missing | `field='display', present=false` | High priority suggestion |
| SF-006 | Field theme_color missing | `field='theme_color', present=false` | Medium priority suggestion |
| SF-007 | Field background_color missing | `field='background_color', present=false` | Medium priority suggestion |
| SF-008 | Field description missing | `field='description', present=false` | Low priority suggestion |
| SF-009 | Invalid field name | `field='invalid', present=false` | `null` |
| SF-010 | Suggestion has correct structure | Any missing field | Array with priority, field, message, guide keys |
| SF-011 | Guide anchor is correct for name | `field='name'` | Guide contains `#manifest-name` or similar |
| SF-012 | Guide anchor is correct for display | `field='display'` | Guide contains `#manifest-display` or similar |
| SF-013 | Message is human-readable | Any missing field | Non-empty string without technical jargon |
| SF-014 | Case-insensitive field name | `field='NAME', present=false` | Returns suggestion (case normalized) |

### Category 3: getSuggestionForIcon() Method Tests (SI-001 to SI-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SI-001 | Icon 192x192 present | `size='192x192', present=true` | `null` |
| SI-002 | Icon 192x192 missing | `size='192x192', present=false` | High priority suggestion |
| SI-003 | Icon 512x512 missing | `size='512x512', present=false` | High priority suggestion |
| SI-004 | Icon 180x180 missing | `size='180x180', present=false` | Medium priority suggestion |
| SI-005 | Invalid icon size | `size='999x999', present=false` | `null` |
| SI-006 | Suggestion has correct structure | Any missing icon | Array with priority, field, message, guide keys |
| SI-007 | Guide anchor for iOS icon | `size='180x180'` | Guide contains `#ios-icons` or similar |
| SI-008 | Guide anchor for Android icons | `size='192x192'` | Guide contains `#icon-requirements` or similar |
| SI-009 | Message mentions device type | `size='180x180'` | Message mentions iOS devices |
| SI-010 | Size format variations | `size='192', present=false` | Normalized to '192x192' |

### Category 4: Suggestion Structure Tests (SS-001 to SS-008)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SS-001 | Has priority key | Any suggestion | `priority` key exists with 'high'|'medium'|'low' |
| SS-002 | Has field key | Any suggestion | `field` key exists with field name or icon size |
| SS-003 | Has message key | Any suggestion | `message` key exists with non-empty string |
| SS-004 | Has guide key | Any suggestion | `guide` key exists with anchor link starting with # |
| SS-005 | Priority is valid enum | Any suggestion | Priority is one of: high, medium, low |
| SS-006 | Message describes action | Any suggestion | Message contains actionable guidance |
| SS-007 | Guide is valid anchor | Any suggestion | Guide starts with '#' and is lowercase with hyphens |
| SS-008 | All required keys present | Any suggestion | Array has exactly 4 keys: priority, field, message, guide |

### Category 5: Priority Sorting Tests (PS-001 to PS-006)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PS-001 | All high priority | 3 high priority items | Ordered by field name alphabetically |
| PS-002 | High before medium | 1 high, 1 medium | High comes first |
| PS-003 | Medium before low | 1 medium, 1 low | Medium comes first |
| PS-004 | Mixed priorities | 2 high, 3 medium, 1 low | High → Medium → Low order |
| PS-005 | Same priority sorted by field | 2 medium priority items | Sorted alphabetically by field |
| PS-006 | Empty suggestions | Perfect PWA | Empty array |

### Category 6: Edge Cases (EC-001 to EC-010)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Null PWAAnalysis | `analysis=null` | Throws TypeError or returns empty array |
| EC-002 | No manifest but has other fields | `has_manifest=false, has_name=true` | Only manifest suggestion (fields irrelevant without manifest) |
| EC-003 | Invalid manifest with missing fields | `manifest_valid=false, has_name=false` | Suggestion for invalid manifest only |
| EC-004 | All fields missing | All `has_*=false` | All suggestions returned |
| EC-005 | Only icons missing | All fields present, icons missing | Icon suggestions only |
| EC-006 | Icon errors present | `icon_errors` has entries | Suggestions for invalid icons |
| EC-007 | Empty analysis object | New PWAAnalysis() with defaults | Suggestions for all missing items |
| EC-008 | Very long manifest values | Large manifest_json | Does not affect suggestion generation |
| EC-009 | Special characters in field values | Unicode in manifest | Suggestions generated normally |
| EC-010 | Rapid successive calls | Multiple calls same analysis | Same results each time |

### Category 7: Integration with PWAAnalysis Model (INT-001 to INT-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-001 | Model with factory data | PWAAnalysis::factory()->make() | Suggestions generated correctly |
| INT-002 | Model from database | PWAAnalysis::create() persisted | Suggestions use database values |
| INT-003 | Model relationships | $analysis->site exists | No dependency on site relationship |
| INT-004 | Full PWA factory state | PWAAnalysis::factory()->fullPWA()->make() | Empty suggestions array |
| INT-005 | Basic PWA factory state | PWAAnalysis::factory()->basicPWA()->make() | Suggestions for missing items |

## Mock Data

### Perfect PWA Analysis
```php
PWAAnalysis::factory()->fullPWA()->make([
    'has_manifest' => true,
    'manifest_valid' => true,
    'has_name' => true,
    'has_short_name' => true,
    'has_start_url' => true,
    'has_display' => true,
    'has_background_color' => true,
    'has_theme_color' => true,
    'has_description' => true,
    'icons_192_present' => true,
    'icons_512_present' => true,
    'icons_180_present' => true,
]);
```

### No Manifest Analysis
```php
PWAAnalysis::factory()->withoutManifest()->make();
```

### Partial PWA Analysis
```php
PWAAnalysis::factory()->make([
    'has_manifest' => true,
    'manifest_valid' => true,
    'has_name' => true,
    'has_short_name' => false, // Missing
    'has_start_url' => true,
    'has_display' => false, // Missing
    'has_theme_color' => false, // Missing
    'icons_192_present' => true,
    'icons_512_present' => false, // Missing
]);
```

## Coverage Targets

| Category | Target Coverage |
|----------|-----------------|
| generateSuggestions() | 100% |
| getSuggestionForField() | 100% |
| getSuggestionForIcon() | 100% |
| Priority sorting | 100% |
| Edge cases | 100% |
| **Overall** | **100%** |

## Test Execution

```bash
# Run all PWA Suggestion tests
cd backend
php artisan test --filter=PWASuggestionServiceTest

# Run with coverage
php artisan test --filter=PWASuggestionServiceTest --coverage

# Run specific test category
php artisan test --filter="GS-001|GS-002|GS-003"
```

## Dependencies

- PHPUnit 10.x
- Laravel Testing Framework
- PWAAnalysis model and factory
- Mockery (for mocking if needed)

## Notes for Developer

### Expected Service Structure
```php
class PWASuggestionService
{
    /**
     * Generate all improvement suggestions for a PWA analysis.
     * 
     * @param PWAAnalysis $analysis
     * @return array<int, array{priority: string, field: string, message: string, guide: string}>
     */
    public function generateSuggestions(PWAAnalysis $analysis): array;
    
    /**
     * Get suggestion for a specific manifest field.
     * 
     * @param string $field Field name (name, short_name, start_url, display, theme_color, background_color, description)
     * @param bool $present Whether the field is present
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForField(string $field, bool $present): ?array;
    
    /**
     * Get suggestion for a specific icon size.
     * 
     * @param string $size Icon size (192x192, 512x512, 180x180)
     * @param bool $present Whether the icon is present
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForIcon(string $size, bool $present): ?array;
}
```

### Priority Mapping (per SPEC-024)
| Condition | Priority |
|-----------|----------|
| No manifest | High |
| Missing required field (name, short_name, start_url, display) | High |
| Missing required icon (192x192, 512x512) | High |
| Invalid manifest | High |
| Missing recommended icon (180x180) | Medium |
| Missing theme_color | Medium |
| Missing background_color | Medium |
| Missing description | Low |

### Guide Anchor Links
| Field/Icon | Guide Anchor |
|------------|--------------|
| manifest (missing) | `#manifest-basics` |
| name | `#manifest-name` |
| short_name | `#manifest-short-name` |
| start_url | `#manifest-start-url` |
| display | `#manifest-display` |
| theme_color | `#manifest-theme-color` |
| background_color | `#manifest-background-color` |
| description | `#manifest-description` |
| 192x192 icon | `#icon-requirements` |
| 512x512 icon | `#icon-requirements` |
| 180x180 icon | `#ios-icons` |

---

## Comments
_Agent communication log_

### [2026-03-03 14:08 GMT+2] Agent: PreTester
Created test documentation for PWASuggestionService. Test coverage includes:
- 70+ test scenarios across 7 categories
- 100% coverage target for all service methods
- Edge cases and integration tests with PWAAnalysis model
- Priority sorting validation
- Suggestion structure validation

Ready for test implementation.
