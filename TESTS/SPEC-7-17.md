# TESTS/SPEC-7-17.md — Tests for SERP Feature History Component

## Task Reference
- Task: [TASK-7-17](../TASKS/TASK-7-17.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Tests for the React component that displays a timeline of SERP feature changes for a specific keyword.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/components/SERPFeatureHistory.test.tsx` | Component unit tests |
| `tests/integration/serp-feature-history.test.tsx` | Integration tests |

---

## Test Scenarios

### 1. Component Rendering

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Renders without error | keywordId: 1 | Component rendered |
| CR-002 | Loading state | API pending | Loading indicator |
| CR-003 | Error state | API error | Error message |
| CR-004 | Empty history | No changes | Empty message |

### 2. Timeline Display

| ID | Scenario | Data | Expected Output |
|----|----------|------|-----------------|
| TL-001 | Single gain | One gained event | Gain shown with icon |
| TL-002 | Single loss | One lost event | Loss shown with icon |
| TL-003 | Multiple events | 5 changes | 5 entries displayed |
| TL-004 | Ordered by date | Multiple events | Most recent first |
| TL-005 | Date formatting | Date object | Human-readable date |

### 3. Feature Icons in Timeline

| ID | Scenario | Event Type | Expected Icon |
|----|----------|------------|---------------|
| TI-001 | Featured snippet gained | featured_snippet + gained | 🏆 ➕ |
| TI-002 | Local pack lost | local_pack + lost | 📍 ➖ |
| TI-003 | Sitelinks gained | sitelinks + gained | 🔗 ➕ |

### 4. Recent Changes Widget

| ID | Scenario | Data | Expected Output |
|----|----------|------|-----------------|
| RC-001 | Shows last 7 days | Changes within 7 days | Recent changes section |
| RC-002 | No recent changes | No changes in 7 days | Empty state |
| RC-003 | Changes older than 7 days | All changes older | Not in recent section |

### 5. API Integration

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| AI-001 | Fetches on mount | API called with keywordId |
| AI-002 | Correct endpoint | GET /api/v1/keywords/{id}/serp-features |
| AI-003 | Handles response | Data rendered correctly |
| AI-004 | Handles error | Error state shown |

### 6. Responsive Design

| ID | Scenario | Viewport | Expected Behavior |
|----|----------|----------|-------------------|
| RD-001 | Desktop layout | 1200px width | Full timeline |
| RD-002 | Tablet layout | 768px width | Adapted layout |
| RD-003 | Mobile layout | 375px width | Compact view |

### 7. Chart Integration (if applicable)

| ID | Scenario | Data | Expected Behavior |
|----|----------|------|-------------------|
| CH-001 | Feature trend chart | Historical data | Chart rendered |
| CH-002 | Chart library used | Any data | Shared chart component |
| CH-003 | Chart responsive | Resize | Chart adapts |

### 8. Accessibility

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| AC-001 | Keyboard navigation | Tab through entries |
| AC-002 | Screen reader support | ARIA labels present |
| AC-003 | Focus management | Focus on new content |

---

## Mock Data

### API Response
```json
{
  "keyword": {
    "id": 1,
    "keyword": "seo tools"
  },
  "history": [
    {
      "date": "2026-02-26",
      "features": {
        "featured_snippet": true,
        "sitelinks": true
      }
    },
    {
      "date": "2026-02-25",
      "features": {
        "featured_snippet": false,
        "sitelinks": true
      }
    }
  ],
  "changes": [
    {
      "type": "featured_snippet",
      "action": "gained",
      "detectedAt": "2026-02-26T10:00:00Z"
    }
  ]
}
```

### Timeline Display
```
2026-02-26   🏆 ➕ Featured Snippet Gained
2026-02-24   📍 ➖ Local Pack Lost
2026-02-20   🔗 ➕ Sitelinks Gained
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| Rendering states | 100% |
| Timeline display | 100% |
| API integration | 100% |
| Responsive design | 100% |
| Accessibility | 100% |

---

## Test Execution

```bash
npm test -- tests/unit/components/SERPFeatureHistory.test.tsx
npm test -- tests/integration/serp-feature-history.test.tsx
```

---

## Dependencies

- TASK-7-10 (Feature API endpoint)
- Chart library (if used)

---

## Comments

### [2026-02-26 19:05] Agent: PreTester
Created test documentation for TASK-7-17 SERP Feature History Component.

Test coverage: 23 scenarios covering rendering, timeline display, API integration, responsive design, and accessibility.

Ready for Developer implementation.
