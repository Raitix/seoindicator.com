# TESTS/SPEC-7-18.md — Tests for Feature Filter UI

## Task Reference
- Task: [TASK-7-18](../TASKS/TASK-7-18.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Tests for the frontend UI components that allow users to filter keywords by SERP feature presence.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/components/KeywordFilterBar.test.tsx` | Filter component tests |
| `tests/integration/keyword-filter-serp.test.tsx` | Filter integration tests |

---

## Test Scenarios

### 1. Filter Dropdown

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| FD-001 | Dropdown renders | Component mounted | Dropdown visible |
| FD-002 | Label correct | Default | "SERP Features" label |
| FD-003 | Options loaded | Mount | All feature types listed |
| FD-004 | "All" option | Dropdown open | "All" as first option |
| FD-005 | Feature icons in options | Dropdown open | Icons next to labels |

### 2. Filter Selection

| ID | Scenario | Action | Expected Behavior |
|----|----------|--------|-------------------|
| FS-001 | Select featured snippet | Click option | Filter applied |
| FS-002 | Select local pack | Click option | Filter applied |
| FS-003 | Select "All" | Click "All" | Filter cleared |
| FS-004 | Selection updates URL | Select option | URL param updated |
| FS-005 | Selection updates state | Select option | Filter state changed |

### 3. "Show Only With Features" Checkbox

| ID | Scenario | Action | Expected Behavior |
|----|----------|--------|-------------------|
| CB-001 | Checkbox renders | Component mounted | Checkbox visible |
| CB-002 | Check the box | Click checkbox | present=true filter |
| CB-003 | Uncheck the box | Click again | present=false filter |
| CB-004 | Default unchecked | Mount | Unchecked state |

### 4. Combined Filters

| ID | Scenario | Filters Applied | Expected Behavior |
|----|----------|-----------------|-------------------|
| CF-001 | Feature + position | serpFeature + position | Both filters applied |
| CF-002 | Feature + search engine | serpFeature + searchEngine | Both filters applied |
| CF-003 | Feature + status | serpFeature + status | Both filters applied |
| CF-004 | Multiple selections | Multiple features | OR logic applied |

### 5. API Integration

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| AI-001 | Fetches filtered results | API called with params |
| AI-002 | Correct endpoint | GET /api/v1/sites/:id/keywords |
| AI-003 | Correct params | serpFeature in query |
| AI-004 | Loading state | Loading indicator shown |
| AI-005 | Results displayed | Keywords table updated |

### 6. Dynamic Options

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| DO-001 | Fetches from metadata | GET /api/v1/metadata |
| DO-002 | Handles metadata error | Fallback options |
| DO-003 | Caches metadata | Single fetch |

### 7. State Management

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| SM-001 | Persists across navigation | State retained |
| SM-002 | URL reflects state | URL params sync |
| SM-003 | Clear filters button | Resets all filters |

### 8. Accessibility

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| AC-001 | Keyboard accessible | Tab to dropdown |
| AC-002 | ARIA labels | Proper labeling |
| AC-003 | Focus state | Visible focus |

---

## Mock Data

### Filter Options
```javascript
const featureOptions = [
  { value: 'all', label: 'All Features' },
  { value: 'featured_snippet', label: 'Featured Snippet', icon: '🏆' },
  { value: 'local_pack', label: 'Local Pack', icon: '📍' },
  { value: 'sitelinks', label: 'Sitelinks', icon: '🔗' },
  { value: 'reviews', label: 'Reviews', icon: '⭐' }
];
```

### API Request
```
GET /api/v1/sites/1/keywords?serpFeature=featured_snippet&present=true
```

### Component Props
```tsx
interface KeywordFilterBarProps {
  onFilterChange: (filter: FilterState) => void;
  initialFilter?: FilterState;
  siteId: number;
}
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| Dropdown rendering | 100% |
| Selection behavior | 100% |
| Combined filters | 100% |
| API integration | 100% |
| Accessibility | 100% |

---

## Test Execution

```bash
npm test -- tests/unit/components/KeywordFilterBar.test.tsx
npm test -- tests/integration/keyword-filter-serp.test.tsx
```

---

## Dependencies

- TASK-7-12 (Backend filter parameter)
- React testing library

---

## Comments

### [2026-02-26 19:08] Agent: PreTester
Created test documentation for TASK-7-18 Feature Filter UI.

Test coverage: 27 scenarios covering dropdown, checkbox, combined filters, API integration, state management, and accessibility.

Ready for Developer implementation.
