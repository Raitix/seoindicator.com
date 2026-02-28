# TASK-16-7: Mobile-Friendly Checker Tool

## STATUS
`Fix Needed`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Implement the Mobile-Friendly Checker tool that analyzes a page's mobile readiness. Checks viewport configuration, responsive design indicators, and provides a mobile preview.

---

## Acceptance Criteria
- [ ] Detect viewport meta tag presence and configuration
- [ ] Check for responsive design indicators (media queries, flexible grids)
- [ ] Analyze font sizes for mobile readability
- [ ] Check tap target sizes (links, buttons)
- [ ] Provide mobile viewport preview (320px width simulation)
- [ ] Detect horizontal scroll issues
- [ ] Return structured JSON with mobile score
- [ ] Frontend component with mobile preview frame
- [ ] Unit tests for mobile detection logic

---

## Technical Details

### Input
```json
{
  "url": "https://example.com/page"
}
```

### Output Structure
```json
{
  "mobileScore": 85,
  "viewport": {
    "present": true,
    "content": "width=device-width, initial-scale=1",
    "valid": true
  },
  "responsiveIndicators": {
    "hasMediaQueries": true,
    "hasFlexibleGrid": true,
    "hasFlexibleImages": true
  },
  "readability": {
    "fontSizeMin": 14,
    "fontSizeWarning": false
  },
  "tapTargets": {
    "links": 45,
    "smallTargets": 3,
    "warning": "3 tap targets may be too small for mobile"
  },
  "issues": [
    {
      "severity": "warning",
      "message": "Some tap targets are smaller than 48x48 pixels"
    }
  ],
  "recommendations": [
    "Viewport meta tag is properly configured ✓",
    "Consider increasing font size for better mobile readability"
  ]
}
```

### Mobile Score Calculation
| Factor | Weight | Points |
|--------|--------|--------|
| Viewport tag present | 30% | 0-30 |
| Responsive indicators | 25% | 0-25 |
| Font readability | 20% | 0-20 |
| Tap target sizes | 25% | 0-25 |

### Preview Generation
- Use iframe with 320px width
- Apply mobile user-agent for fetch
- Capture screenshot (optional, future)

---

## Dependencies
- TASK-16-1 (Tools Infrastructure)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. This is Phase 3 - Full Tool Suite. Depends on infrastructure being stable.

### [2026-02-28 21:43] Agent: LocalTester
**LOCAL TEST RESULTS: 2 FAILURES**

Ran 49 tests, 47 passed, 2 failed.

**Failure 1: `test_hidden_targets_not_counted`**
- Location: `tests/Unit/Services/Tools/MobileFriendlyCheckerTest.php:559`
- Issue: Hidden links (with `display: none`) are being counted in tap targets when they should be excluded.
- Expected: 1 link
- Actual: 2 links
- Root cause: The implementation does not check for `display: none` style on elements before counting them as tap targets.

**Failure 2: `test_empty_html`**
- Location: `tests/Unit/Services/Tools/MobileFriendlyCheckerTest.php:789`
- Issue: Empty HTML response returns `success=false` instead of handling gracefully.
- Expected: `success=true` with low score (< 50)
- Actual: `success=false`
- Root cause: The checker does not handle empty HTML content gracefully; should return a valid response with minimal score rather than failing.

**Action Required:**
1. Fix hidden element detection in tap target counting
2. Add graceful handling for empty/invalid HTML responses

**Test Command Used:**
```bash
php artisan test --filter=MobileFriendlyCheckerTest
```
