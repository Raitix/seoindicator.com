# TEST-24-7: PWASuggestionService Tests

## Overview
Unit tests for PWASuggestionService. Tests cover suggestion generation for missing manifest fields, icon recommendations, priority sorting, and edge cases.

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Unit/Services/PWASuggestionServiceTest.php` | Unit | 40+ | Suggestion generation, priority, sorting |

---

## Service Interface

```php
class PWASuggestionService
{
    /**
     * Generate all suggestions for a PWA analysis.
     *
     * @return array<int, array{priority: string, field: string, message: string, guide: string}>
     */
    public function generateSuggestions(PWAAnalysis $analysis): array;

    /**
     * Get suggestion for a specific manifest field.
     *
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForField(string $field, bool $present): ?array;

    /**
     * Get suggestion for a specific icon size.
     *
     * @return array{priority: string, field: string, message: string, guide: string}|null
     */
    public function getSuggestionForIcon(string $size, bool $present): ?array;
}
```

---

## Suggestion Structure

```php
[
    'priority' => 'high',        // 'high', 'medium', or 'low'
    'field' => 'name',           // Field name or icon size (e.g., 'icon_192')
    'message' => 'Add a name field to your manifest', // Human-readable suggestion
    'guide' => '#manifest-name'  // Anchor link to guide section
]
```

---

## Priority Levels

| Priority | Triggers |
|----------|----------|
| **High** | No manifest, missing required fields (name, short_name, start_url, display), missing required icons (192x192, 512x512) |
| **Medium** | Missing recommended icons (180x180), missing theme_color, missing background_color |
| **Low** | Missing description |

---

## Test Cases

### Generate Suggestions (GS)

| ID | Test | Description |
|----|------|-------------|
| GS-001 | No manifest returns high priority suggestion | When has_manifest=false, first suggestion should be about adding manifest |
| GS-002 | Invalid manifest returns high priority suggestion | When manifest_valid=false, suggest fixing manifest |
| GS-003 | Missing name field returns suggestion | has_name=false triggers suggestion |
| GS-004 | Missing short_name field returns suggestion | has_short_name=false triggers suggestion |
| GS-005 | Missing start_url field returns suggestion | has_start_url=false triggers suggestion |
| GS-006 | Missing display field returns suggestion | has_display=false triggers suggestion |
| GS-007 | Missing theme_color returns medium priority | has_theme_color=false, priority=medium |
| GS-008 | Missing background_color returns medium priority | has_background_color=false, priority=medium |
| GS-009 | Missing description returns low priority | has_description=false, priority=low |
| GS-010 | Missing 192x192 icon returns high priority | icons_192_present=false, priority=high |
| GS-011 | Missing 512x512 icon returns high priority | icons_512_present=false, priority=high |
| GS-012 | Missing 180x180 icon returns medium priority | icons_180_present=false, priority=medium |
| GS-013 | Complete PWA returns empty array | All fields present, no suggestions |
| GS-014 | Suggestions sorted by priority | High before medium, medium before low |
| GS-015 | Multiple missing fields return multiple suggestions | Count matches missing fields |

### Get Suggestion For Field (GF)

| ID | Test | Description |
|----|------|-------------|
| GF-001 | Get suggestion for missing name | Returns correct structure with high priority |
| GF-002 | Get suggestion for missing short_name | Returns correct structure with high priority |
| GF-003 | Get suggestion for missing start_url | Returns correct structure with high priority |
| GF-004 | Get suggestion for missing display | Returns correct structure with high priority |
| GF-005 | Get suggestion for missing theme_color | Returns medium priority |
| GF-006 | Get suggestion for missing background_color | Returns medium priority |
| GF-007 | Get suggestion for missing description | Returns low priority |
| GF-008 | Get suggestion for present field returns null | When field is present, return null |
| GF-009 | Invalid field name returns null | Unknown field name handling |
| GF-010 | Suggestion includes guide anchor | Each suggestion has valid guide link |

### Get Suggestion For Icon (GI)

| ID | Test | Description |
|----|------|-------------|
| GI-001 | Get suggestion for missing 192x192 icon | Returns high priority |
| GI-002 | Get suggestion for missing 512x512 icon | Returns high priority |
| GI-003 | Get suggestion for missing 180x180 icon | Returns medium priority |
| GI-004 | Get suggestion for present icon returns null | When icon present, return null |
| GI-005 | Invalid icon size returns null | Unknown size handling |
| GI-006 | Icon field naming convention | Field should be 'icon_192', 'icon_512', 'icon_180' |

### Edge Cases (EC)

| ID | Test | Description |
|----|------|-------------|
| EC-001 | Empty analysis object | All fields false/default, generates all suggestions |
| EC-002 | Null manifest_json handled | Service handles null manifest gracefully |
| EC-003 | All fields present returns empty | No suggestions for complete PWA |
| EC-004 | Only manifest missing | Specific message for missing manifest |
| EC-005 | Only icons missing | Generates only icon suggestions |

### Suggestion Content (SC)

| ID | Test | Description |
|----|------|-------------|
| SC-001 | Message is human-readable | Each suggestion has clear message |
| SC-002 | Guide anchor is valid format | Matches pattern #section-name |
| SC-003 | Field name matches field or icon format | Either field name or icon_XXX |
| SC-004 | Priority is valid enum | One of: high, medium, low |

### Sorting (SO)

| ID | Test | Description |
|----|------|-------------|
| SO-001 | High priority suggestions first | High before medium and low |
| SO-002 | Medium priority after high | Medium before low |
| SO-003 | Low priority last | Low at end of array |
| SO-004 | Same priority order stable | Multiple high priority maintain order |

### Integration (INT)

| ID | Test | Description |
|----|------|-------------|
| INT-001 | Service can be resolved from container | DI works correctly |
| INT-002 | Works with real PWAAnalysis model | Integration with model |
| INT-003 | Suggestion count matches expected | For specific analysis states |

---

## Suggestion Templates

### Field Suggestions

| Field | Priority | Message Template | Guide Anchor |
|-------|----------|------------------|--------------|
| manifest | high | "Add a web app manifest to enable PWA features" | #manifest-basics |
| manifest_valid | high | "Fix your manifest.json - it contains invalid JSON or missing required fields" | #manifest-validation |
| name | high | "Add a 'name' field to your manifest for the full app name" | #manifest-name |
| short_name | high | "Add a 'short_name' field for home screen display (12 chars recommended)" | #manifest-short-name |
| start_url | high | "Add a 'start_url' field to define your app's entry point" | #manifest-start-url |
| display | high | "Add a 'display' mode (standalone recommended for app-like experience)" | #manifest-display |
| theme_color | medium | "Add a 'theme_color' to style the browser UI to match your brand" | #manifest-theme-color |
| background_color | medium | "Add a 'background_color' for the splash screen background" | #manifest-background-color |
| description | low | "Add a 'description' to improve app store presence and discoverability" | #manifest-description |

### Icon Suggestions

| Size | Priority | Message Template | Guide Anchor |
|------|----------|------------------|--------------|
| 192x192 | high | "Add a 192x192 icon for Android home screen display" | #icon-requirements |
| 512x512 | high | "Add a 512x512 icon for Android splash screens" | #icon-requirements |
| 180x180 | medium | "Add a 180x180 icon (apple-touch-icon) for iOS devices" | #ios-icons |

---

## Mock Data

### Complete PWA Analysis
```php
PWAAnalysis::factory()->fullPWA()->create([
    'has_manifest' => true,
    'manifest_valid' => true,
    'has_name' => true,
    'has_short_name' => true,
    'has_start_url' => true,
    'has_display' => true,
    'has_theme_color' => true,
    'has_background_color' => true,
    'has_description' => true,
    'icons_192_present' => true,
    'icons_512_present' => true,
    'icons_180_present' => true,
]);
// Expected: [] (no suggestions)
```

### Missing Manifest
```php
PWAAnalysis::factory()->withoutManifest()->create([
    'has_manifest' => false,
]);
// Expected: [suggestion about adding manifest]
```

### Missing Required Fields
```php
PWAAnalysis::factory()->withManifest()->create([
    'has_manifest' => true,
    'manifest_valid' => true,
    'has_name' => false,
    'has_short_name' => false,
    'has_start_url' => false,
    'has_display' => false,
]);
// Expected: 4 high priority suggestions
```

### Missing Icons
```php
PWAAnalysis::factory()->noIcons()->create([
    'has_manifest' => true,
    'manifest_valid' => true,
    'icons_192_present' => false,
    'icons_512_present' => false,
    'icons_180_present' => false,
]);
// Expected: 2 high priority + 1 medium priority suggestions
```

---

## Status
- Created: 2026-03-03
- Agent: PreTester
- Task: TASK-24-7
