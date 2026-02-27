# TEST-16-7: Mobile-Friendly Checker Tests

## STATUS
`Tests Coverage In Progress`

## Related
- Task: [TASK-16-7](../TASKS/TASK-16-7.md)
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)

---

## Test Coverage Summary

| Category | Tests | Assertions | Status |
|----------|-------|------------|--------|
| Viewport Detection | 15 | 15 | ✅ |
| Responsive Indicators | 12 | 12 | ✅ |
| Font Size Analysis | 10 | 10 | ✅ |
| Tap Target Analysis | 14 | 14 | ✅ |
| Horizontal Scroll Detection | 8 | 8 | ✅ |
| Mobile Score Calculation | 10 | 10 | ✅ |
| Error Handling | 8 | 8 | ✅ |
| Frontend Component | 12 | 12 | ✅ |
| **Total** | **89** | **89** | ✅ |

---

## Test Scenarios

### Viewport Detection (VP)

| ID | Scenario | HTML Input | Expected |
|----|----------|------------|----------|
| VP-001 | Standard viewport | `<meta name="viewport" content="width=device-width, initial-scale=1">` | Present, valid |
| VP-002 | Missing viewport | (no viewport meta) | Not present, warning |
| VP-003 | Empty content | `<meta name="viewport" content="">` | Present, invalid |
| VP-004 | Fixed width | `content="width=960"` | Present, not responsive |
| VP-005 | Initial-scale only | `content="initial-scale=1"` | Partial config |
| VP-006 | User-scalable=no | `content="..., user-scalable=no"` | Accessibility warning |
| VP-007 | Maximum-scale=1 | `content="..., maximum-scale=1"` | Accessibility warning |
| VP-008 | Minimum-scale high | `content="..., minimum-scale=2"` | Potential issue |
| VP-009 | Multiple viewports | Two viewport tags | Warning: duplicates |
| VP-010 | Viewport in body | Meta in body | Still detected |
| VP-011 | Case insensitive | `<META NAME="VIEWPORT"...>` | Detected |
| VP-012 | Width=device-width | Standard mobile config | Valid responsive |
| VP-013 | Width=100% | Percentage width | Valid |
| VP-014 | Shrink-to-fit=no | iOS-specific attribute | Included in output |
| VP-015 | Complex content | Multiple directives | All parsed |

### Responsive Indicators (RESP)

| ID | Scenario | CSS Input | Expected |
|----|----------|-----------|----------|
| RESP-001 | Media queries present | `@media (max-width: 768px) {...}` | hasMediaQueries: true |
| RESP-002 | No media queries | CSS without media queries | hasMediaQueries: false |
| RESP-003 | Flexbox layout | `display: flex` | hasFlexibleGrid: true |
| RESP-004 | CSS Grid layout | `display: grid` | hasFlexibleGrid: true |
| RESP-005 | Fixed width layout | `width: 960px` | hasFlexibleGrid: false |
| RESP-006 | Percentage widths | `width: 100%` | hasFlexibleGrid: true |
| RESP-007 | Responsive images | `img { max-width: 100% }` | hasFlexibleImages: true |
| RESP-008 | Fixed image sizes | `img { width: 500px }` | hasFlexibleImages: false |
| RESP-009 | Picture element | `<picture>` tags | hasFlexibleImages: true |
| RESP-010 | Srcset attribute | `<img srcset="...">` | hasFlexibleImages: true |
| RESP-011 | Inline styles | Style attributes checked | Parsed correctly |
| RESP-012 | External stylesheets | Link to external CSS | Noted (not fetched in MVP) |

### Font Size Analysis (FONT)

| ID | Scenario | CSS Input | Expected |
|----|----------|-----------|----------|
| FONT-001 | Adequate base size | `body { font-size: 16px }` | fontSizeMin: 16, no warning |
| FONT-002 | Small base size | `body { font-size: 12px }` | Warning: too small |
| FONT-003 | Mixed font sizes | Various sizes | Min reported |
| FONT-004 | Rem units | `font-size: 1rem` | Converted to px |
| FONT-005 | Em units | `font-size: 1.2em` | Noted (relative) |
| FONT-006 | Percentage fonts | `font-size: 87.5%` | Converted |
| FONT-007 | Viewport units | `font-size: 4vw` | Noted (responsive) |
| FONT-008 | Font in pixels | `font-size: 14px` | Direct value |
| FONT-009 | Inheritance | Nested elements | Smallest detected |
| FONT-010 | Line height check | `line-height: 1.2` | Readability note |

### Tap Target Analysis (TAP)

| ID | Scenario | HTML/CSS Input | Expected |
|----|----------|----------------|----------|
| TAP-001 | Adequate link size | `<a style="width:48px;height:48px">` | No warning |
| TAP-002 | Small link size | `<a style="width:20px;height:20px">` | Warning: too small |
| TAP-003 | Adequate button size | `<button style="width:48px;height:48px">` | No warning |
| TAP-004 | Small button | `<button style="width:30px;height:30px">` | Warning |
| TAP-005 | Inline links | `<a>text</a>` (no size) | Uses text size |
| TAP-006 | Padding contributes | Small but with padding | Effective size larger |
| TAP-007 | Touch action CSS | `touch-action: manipulation` | Noted |
| TAP-008 | Close tap targets | Links close together | Proximity warning |
| TAP-009 | Hidden targets | `display: none` | Not counted |
| TAP-010 | Checkbox/radio size | Form controls | Checked |
| TAP-011 | Input field sizes | Text inputs | Checked |
| TAP-012 | Select dropdown | `<select>` elements | Checked |
| TAP-013 | Icon buttons | Icon-only buttons | Min size check |
| TAP-014 | Overflow scroll | Targets in scrollable | Noted |

### Horizontal Scroll Detection (SCROLL)

| ID | Scenario | CSS Input | Expected |
|----|----------|-----------|----------|
| SCROLL-001 | No overflow | `overflow-x: hidden` or no overflow | No issue |
| SCROLL-002 | Horizontal overflow | `overflow-x: auto` | Potentially flagged |
| SCROLL-003 | Wide fixed element | Element > viewport width | Warning |
| SCROLL-004 | Wide image | `<img width="1200">` on mobile | Warning |
| SCROLL-005 | Wide table | Table wider than screen | Warning |
| SCROLL-006 | White-space: nowrap | Long non-wrapping text | Potential issue |
| SCROLL-007 | Negative margin | Element outside container | Checked |
| SCROLL-008 | Viewport meta affects | Without proper viewport | More likely |

### Mobile Score Calculation (SCORE)

| ID | Scenario | Input Conditions | Expected Score |
|----|----------|------------------|----------------|
| SCORE-001 | Perfect mobile | All checks pass | 100 |
| SCORE-002 | Missing viewport | No viewport tag | -30 points |
| SCORE-003 | No responsive indicators | No media queries/flex | -25 points |
| SCORE-004 | Small fonts | Font < 12px | -20 points |
| SCORE-005 | Small tap targets | Targets < 48px | -25 points |
| SCORE-006 | Multiple issues | 2+ issues | Cumulative deduction |
| SCORE-007 | Minimum score | All issues | Floor of 0 |
| SCORE-008 | Weighted calculation | Partial issues | Correct weighting |
| SCORE-009 | Decimal rounding | Score calculation | Rounded to integer |
| SCORE-010 | Score categories | 0-49, 50-79, 80-100 | Categorized |

### Error Handling (ERR)

| ID | Scenario | Condition | Expected |
|----|----------|-----------|----------|
| ERR-001 | Invalid URL | Bad URL format | Error with message |
| ERR-002 | Connection failure | Site unreachable | Error with message |
| ERR-003 | HTTP error | 404/500 response | Error with code |
| ERR-004 | Timeout | Slow response | Timeout error |
| ERR-005 | Malformed HTML | Invalid HTML | Graceful handling |
| ERR-006 | Empty page | No content | Empty result |
| ERR-007 | Non-HTML content | PDF/image/etc | Error: not HTML |
| ERR-008 | URL blocked | SSRF protection | Error: blocked |

### Frontend Component Tests (FE)

| ID | Scenario | Component Behavior | Expected |
|----|----------|-------------------|----------|
| FE-001 | Renders form | Initial load | URL input visible |
| FE-002 | Shows loading | Analyze clicked | Loading state |
| FE-003 | Displays results | Analysis complete | Results shown |
| FE-004 | Shows score | Results available | Score badge |
| FE-005 | Shows viewport section | Viewport data | Viewport card |
| FE-006 | Shows issues list | Issues found | Issue cards |
| FE-007 | Shows mobile preview | Results available | 320px iframe |
| FE-008 | Handles error | API error | Error message |
| FE-009 | Retry on error | Error state | Retry button |
| FE-010 | Copy results | Results shown | Copy button |
| FE-011 | Accessibility | Screen reader | ARIA labels |
| FE-012 | Responsive design | Mobile viewport | Layout adapts |

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| MobileFriendlyCheckerTest.php | `backend/tests/Unit/Services/Tools/` | 77 |
| MobileFriendlyChecker.test.tsx | `frontend/components/tools/__tests__/` | 12 |

---

## Output Structure Reference

```json
{
  "mobileScore": 85,
  "viewport": {
    "present": true,
    "content": "width=device-width, initial-scale=1",
    "valid": true,
    "warnings": []
  },
  "responsiveIndicators": {
    "hasMediaQueries": true,
    "hasFlexibleGrid": true,
    "hasFlexibleImages": true,
    "details": {
      "mediaQueryCount": 3,
      "flexboxUsage": true,
      "gridUsage": false,
      "responsiveImages": 5
    }
  },
  "readability": {
    "fontSizeMin": 14,
    "fontSizeWarning": false,
    "lineHeight": 1.5,
    "fontSizes": [12, 14, 16, 18, 24]
  },
  "tapTargets": {
    "links": 45,
    "buttons": 12,
    "smallTargets": 3,
    "smallTargetDetails": [
      { "element": "a", "selector": ".nav-link", "width": 32, "height": 28 }
    ],
    "warning": "3 tap targets may be too small for mobile"
  },
  "horizontalScroll": {
    "detected": false,
    "elements": []
  },
  "issues": [
    {
      "severity": "warning",
      "category": "tap_targets",
      "message": "Some tap targets are smaller than 48x48 pixels",
      "recommendation": "Ensure all clickable elements are at least 48x48 pixels"
    }
  ],
  "recommendations": [
    {
      "type": "success",
      "message": "Viewport meta tag is properly configured"
    },
    {
      "type": "suggestion",
      "message": "Consider increasing font size for better mobile readability"
    }
  ],
  "previewUrl": null
}
```

---

## Mobile Score Calculation Weights

| Factor | Weight | Points |
|--------|--------|--------|
| Viewport tag present | 30% | 0-30 |
| Responsive indicators | 25% | 0-25 |
| Font readability | 20% | 0-20 |
| Tap target sizes | 25% | 0-25 |

**Score Categories:**
- 80-100: Good (Green)
- 50-79: Needs Improvement (Yellow)
- 0-49: Poor (Red)

---

## Security Considerations

- URL validation prevents SSRF
- No JavaScript execution in preview
- Sanitized HTML in output
- No data stored (stateless analysis)

---

## Dependencies

- TASK-16-1 (Tools Infrastructure)
- Cheerio for HTML parsing
- CSS parser for style analysis

---

## Completion Date
2026-02-27
