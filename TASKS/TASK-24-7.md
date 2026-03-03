# TASK-24-7: PWASuggestionService

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create service to generate actionable improvement suggestions based on PWA analysis results.

## Acceptance Criteria
- [ ] PWASuggestionService created with methods:
  - generateSuggestions(PWAAnalysis $analysis): array
  - getSuggestionForField(string $field, bool $present): ?array
  - getSuggestionForIcon(string $size, bool $present): ?array
- [ ] Suggestion structure:
  - priority (high, medium, low)
  - field (field name or icon size)
  - message (human-readable suggestion)
  - guide (anchor link to guide page section)
- [ ] Generates suggestions for all missing items per SPEC-024:
  - High priority: no manifest, missing required fields, missing required icons
  - Medium priority: missing recommended icons, theme_color, background_color
  - Low priority: missing description
- [ ] Suggestions sorted by priority
- [ ] Unit tests for all suggestion scenarios

## Dependencies
- TASK-24-1 (Database migration)
- TASK-24-2 (PWAAnalysis model)

## Effort Estimate
`Small` (2-3 hours)

## Technical Notes
- Use suggestion templates from SPEC-024
- Consider i18n support for messages
- Link to relevant guide sections

---

## Comments
_Agent communication log_

### [2026-02-28] Agent: SpecSystemAnalyst
Created task for suggestion generation. Pure logic service that transforms validation gaps into actionable user guidance. Low risk, follows established suggestion patterns from other indicators.

### [2026-03-03 14:22 GMT+2] Agent: PreTester
**Test Coverage Complete**

Created comprehensive test suite for PWASuggestionService:

**Test Documentation:** `TESTS/TEST-24-7.md`
**Test Implementation:** `backend/tests/Unit/Services/PWASuggestionServiceTest.php`

**Test Categories (47 tests):**
1. **Generate Suggestions - No Manifest (GS-NM) - 2 tests:** No manifest returns high priority, invalid manifest returns high priority
2. **Generate Suggestions - Required Fields (GS-RF) - 4 tests:** Missing name, short_name, start_url, display
3. **Generate Suggestions - Recommended Fields (GS-RC) - 3 tests:** Missing theme_color (medium), background_color (medium), description (low)
4. **Generate Suggestions - Icons (GS-IC) - 6 tests:** Missing 192x192 (high), 512x512 (high), 180x180 (medium), complete PWA, sorting, multiple missing
5. **Get Suggestion For Field (GF) - 10 tests:** All field types, present field returns null, invalid field, guide anchor
6. **Get Suggestion For Icon (GI) - 6 tests:** All icon sizes, present icon returns null, invalid size, naming convention
7. **Edge Cases (EC) - 5 tests:** Empty analysis, null manifest JSON, all fields present, only manifest missing, only icons missing
8. **Suggestion Content (SC) - 4 tests:** Human-readable message, valid guide format, field format, valid priority
9. **Sorting (SO) - 4 tests:** High first, medium after high, low last, stable order
10. **Integration (INT) - 3 tests:** Container resolution, real model, count matching
11. **Additional Field Suggestions - 4 tests:** Manifest guide, invalid manifest message, all fields structure, all icons structure

**Suggestion Structure Expected:**
```php
[
    'priority' => 'high',        // 'high', 'medium', or 'low'
    'field' => 'name',           // Field name or icon size (e.g., 'icon_192')
    'message' => 'Add a name field to your manifest',
    'guide' => '#manifest-name'  // Anchor link to guide section
]
```

**Priority Mapping:**
| Priority | Triggers |
|----------|----------|
| High | No manifest, invalid manifest, missing required fields (name, short_name, start_url, display), missing required icons (192x192, 512x512) |
| Medium | Missing theme_color, background_color, missing 180x180 icon |
| Low | Missing description |

**Notes for Developer:**
- Service should be created at `backend/app/Services/PWASuggestionService.php`
- Methods required: `generateSuggestions()`, `getSuggestionForField()`, `getSuggestionForIcon()`
- Suggestions must be sorted by priority (high → medium → low)
- Field naming for icons: `icon_192`, `icon_512`, `icon_180`
- Guide anchors should follow pattern: `#section-name`

Status updated to `Tests Coverage In Progress`. Ready for Developer to implement service.
