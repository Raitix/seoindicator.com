# TESTS/SPEC-7-16.md — Tests for SERP Feature Icons and Badges

## Task Reference
- Task: [TASK-7-16](../TASKS/TASK-7-16.md)
- Feature: FEAT-007
- Specification: SPEC-007
- Risk: Low
- Priority: Medium

---

## Overview

Tests for React components that display SERP feature icons and badges in the dashboard and keyword lists.

---

## Test Files

| File | Purpose |
|------|---------|
| `tests/unit/components/SERPFeatureIcon.test.tsx` | Icon component tests |
| `tests/unit/components/SERPFeatureBadge.test.tsx` | Badge component tests |

---

## Test Scenarios

### 1. SERPFeatureIcon Component

#### Rendering

| ID | Scenario | Props | Expected Output |
|----|----------|-------|-----------------|
| IC-001 | Featured snippet icon | feature='featured_snippet' | 🏆 icon |
| IC-002 | Local pack icon | feature='local_pack' | 📍 icon |
| IC-003 | Image pack icon | feature='image_pack' | 🖼️ icon |
| IC-004 | Video icon | feature='video' | 🎬 icon |
| IC-005 | People also ask icon | feature='people_also_ask' | ❓ icon |
| IC-006 | Knowledge panel icon | feature='knowledge_panel' | 📋 icon |
| IC-007 | Sitelinks icon | feature='sitelinks' | 🔗 icon |
| IC-008 | Reviews icon | feature='reviews' | ⭐ icon |
| IC-009 | FAQ icon | feature='faq' | 📝 icon |
| IC-010 | Product icon | feature='product' | 🛒 icon |

#### Invalid Input

| ID | Scenario | Props | Expected Behavior |
|----|----------|-------|-------------------|
| IC-011 | Invalid feature type | feature='invalid' | Fallback or empty |
| IC-012 | Missing feature prop | {} | Fallback or error |
| IC-013 | Null feature | feature={null} | Fallback |

#### Accessibility

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| IC-014 | Has aria-label | Accessibility label present |
| IC-015 | Has title attribute | Tooltip on hover |

### 2. SERPFeatureBadge Component

#### Single Feature

| ID | Scenario | Props | Expected Output |
|----|----------|-------|-----------------|
| BG-001 | Single feature | features=[{ type: 'featured_snippet', isPresent: true }] | One icon |
| BG-002 | Feature not present | features=[{ type: 'featured_snippet', isPresent: false }] | No icon |

#### Multiple Features

| ID | Scenario | Props | Expected Output |
|----|----------|-------|-----------------|
| BG-003 | Two features | 2 present features | 2 icons |
| BG-004 | Five features | 5 present features | 5 icons |
| BG-005 | Mixed presence | 3 present, 2 absent | 3 icons |

#### Hover State

| ID | Scenario | Action | Expected Behavior |
|----|----------|--------|-------------------|
| BG-006 | Show feature names | Hover on badge | Tooltip with names |
| BG-007 | Multiple names | Hover on multi-badge | All names listed |

#### Styling

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| BG-008 | Consistent size | All icons same size |
| BG-009 | Proper spacing | Gap between icons |
| BG-010 | Container styling | Badge container styles applied |

### 3. Component Reusability

| ID | Scenario | Context | Expected Behavior |
|----|----------|---------|-------------------|
| CR-001 | Use in keyword table | Table cell | Renders correctly |
| CR-002 | Use in modal | Modal content | Renders correctly |
| CR-003 | Use in report | PDF component | Renders correctly |
| CR-004 | Custom className | className prop | Styles applied |

### 4. Performance

| ID | Scenario | Data | Expected Behavior |
|----|----------|------|-------------------|
| PF-001 | Many features | 10 features | Renders efficiently |
| PF-002 | Rapid updates | Features changing | No memory leak |

---

## Mock Data

### Features Array
```typescript
const features = [
  { type: 'featured_snippet', isPresent: true },
  { type: 'local_pack', isPresent: false },
  { type: 'sitelinks', isPresent: true },
  { type: 'reviews', isPresent: true }
];
```

### Expected Badge Output
```tsx
<div className="serp-feature-badge" title="Featured Snippet, Sitelinks, Reviews">
  <SERPFeatureIcon feature="featured_snippet" />
  <SERPFeatureIcon feature="sitelinks" />
  <SERPFeatureIcon feature="reviews" />
</div>
```

---

## Coverage Targets

| Component | Target |
|-----------|--------|
| SERPFeatureIcon | 100% |
| SERPFeatureBadge | 100% |
| Accessibility | 100% |
| Edge cases | 100% |

---

## Test Execution

```bash
npm test -- tests/unit/components/SERPFeatureIcon.test.tsx
npm test -- tests/unit/components/SERPFeatureBadge.test.tsx
```

---

## Dependencies

- TASK-7-10 (API endpoints)
- React testing library

---

## Comments

### [2026-02-26 19:02] Agent: PreTester
Created test documentation for TASK-7-16 SERP Feature Icons and Badges.

Test coverage: 24 scenarios covering icon rendering, badge behavior, hover states, reusability, and performance.

Ready for Developer implementation.
