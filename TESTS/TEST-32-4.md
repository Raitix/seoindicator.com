# TEST-32-4: Comparison Index Page Tests

## Task Reference
- **Task:** TASK-32-4 - Implement Comparison Index Page
- **Specification:** SPEC-32 (Competitor Comparison Landing Pages)
- **Component:** `frontend/app/compare/page.tsx`

## Overview
Tests for the `/compare` index page that lists all available SEO tool comparisons. The page organizes comparisons into two sections: primary (SEOIndicator vs competitors) and cross-competitor (competitor vs competitor).

## Test Files

| File | Location | Tests |
|------|----------|-------|
| `index-page.test.tsx` | `frontend/__tests__/app/compare/` | 48 |

## Test Scenarios

### Rendering Tests (CP-001 to CP-005)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-001 | Page renders main structure | Render page | `<main>` element with proper classes |
| CP-002 | Page heading displays | Render page | H1 with "SEO Tool Comparisons" |
| CP-003 | Page description displays | Render page | Description paragraph visible |
| CP-004 | Breadcrumb navigation renders | Render page | Nav element with Home link |
| CP-005 | Breadcrumb shows current page | Render page | "Compare" with aria-current="page" |

### Primary Comparisons Section (CP-010 to CP-014)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-010 | Section heading renders | Render page | H2 "SEOIndicator Comparisons" |
| CP-011 | All primary comparison links render | Render page | 4 SEOIndicator vs * links |
| CP-012 | Primary links have correct hrefs | Render page | Links to /compare/seoindicator-vs-* |
| CP-013 | Links show view text | Render page | "View detailed comparison" text |
| CP-014 | Primary section uses 2-column grid | Render page | Grid with md:grid-cols-2 |

### Cross-Competitor Comparisons Section (CP-020 to CP-023)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-020 | Section heading renders | Render page | H2 "Other Comparisons" |
| CP-021 | All cross-competitor links render | Render page | 3 competitor vs competitor links |
| CP-022 | Cross-competitor links have correct hrefs | Render page | Links to /compare/ahrefs-vs-* etc |
| CP-023 | Cross section uses 3-column grid | Render page | Grid with lg:grid-cols-3 |

### Title Formatting Tests (CP-030 to CP-033)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-030 | SEOIndicator vs Ahrefs formatted | Render page | "SEOIndicator vs Ahrefs" text |
| CP-031 | SEOIndicator vs Semrush formatted | Render page | "SEOIndicator vs Semrush" text |
| CP-032 | Ahrefs vs Moz formatted | Render page | "Ahrefs vs Moz" text |
| CP-033 | Moz vs Semrush formatted | Render page | "Moz vs Semrush" text |

### Accessibility Tests (CP-040 to CP-044)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-040 | Proper heading hierarchy | Render page | 1 H1, 2 H2 elements |
| CP-041 | Sections have aria-labelledby | Render page | Regions with accessible names |
| CP-042 | Links are keyboard navigable | Render page | Links without tabindex=-1 |
| CP-043 | Proper landmark roles | Render page | main and nav landmarks |
| CP-044 | Breadcrumb aria-current | Render page | Current page marked correctly |

### Styling Tests (CP-050 to CP-053)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-050 | Dark theme classes applied | Render page | bg-slate-950, text-white |
| CP-051 | Primary cards have hover effects | Render page | hover:border-emerald-500/50 |
| CP-052 | Cross-competitor cards have hover | Render page | hover:border-blue-500/50 |
| CP-053 | Responsive grid classes | Render page | md:grid-cols-2, lg:grid-cols-3 |

### Edge Cases (CP-060 to CP-062)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-060 | Empty COMPARISON_SLUGS | Empty array mock | Page renders with heading |
| CP-061 | Missing competitor data | Null return from registry | Page renders without errors |
| CP-062 | Invalid slug format | Slug without -vs- | Displayed as-is |

### Integration Tests (CP-070 to CP-072)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-070 | Link count matches registry | Render page | 7 total comparison links |
| CP-071 | All links have valid URL format | Render page | /compare/*-vs-* pattern |
| CP-072 | Page renders without errors | Render page | Container in document |

### SEO Tests (CP-080 to CP-082)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-080 | Metadata exported | Import page | Title and description defined |
| CP-081 | OpenGraph tags present | Import page | OG title, type defined |
| CP-082 | Meta description length | Import page | 50-160 characters |

### Security Tests (CP-090 to CP-091)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CP-090 | XSS in competitor names | Malicious script in name | Script escaped, not executed |
| CP-091 | Hrefs are safe URLs | Render page | No dangerous characters in hrefs |

## Mock Data

### Registry Mock
```typescript
COMPARISON_SLUGS: [
  'seoindicator-vs-ahrefs',
  'seoindicator-vs-semrush',
  'seoindicator-vs-moz',
  'seoindicator-vs-ubersuggest',
  'ahrefs-vs-moz',
  'ahrefs-vs-semrush',
  'moz-vs-semrush',
]
```

### Competitor Mock
```typescript
getCompetitorById: (id) => ({
  seoindicator: { name: 'SEOIndicator' },
  ahrefs: { name: 'Ahrefs' },
  semrush: { name: 'Semrush' },
  moz: { name: 'Moz' },
  ubersuggest: { name: 'Ubersuggest' },
}[id] || null)
```

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Rendering | 100% | 5 |
| Primary Section | 100% | 5 |
| Cross Section | 100% | 4 |
| Title Formatting | 100% | 4 |
| Accessibility | 100% | 5 |
| Styling | 100% | 4 |
| Edge Cases | 100% | 3 |
| Integration | 100% | 3 |
| SEO | 100% | 3 |
| Security | 100% | 2 |
| **Total** | **100%** | **48** |

## Test Execution

```bash
# Run all tests for this file
npm test -- frontend/__tests__/app/compare/index-page.test.tsx

# Run with coverage
npm run test:coverage -- frontend/__tests__/app/compare/index-page.test.tsx

# Run specific test
npm test -- -t "CP-001"
```

## Dependencies
- TASK-32-2 (Registry) - Provides COMPARISON_SLUGS and getCompetitorById
- TASK-32-1 (Types) - Type definitions

## Notes for Developer
- Page uses inline Link components (no separate ComparisonCard component)
- Title formatting uses `formatComparisonTitle()` helper
- Primary comparisons use emerald accent, cross-competitor use blue accent
- Breadcrumb follows standard pattern with aria-current

## Acceptance Criteria Status

- [x] Page accessible at `/compare`
- [x] All comparisons displayed in organized sections
- [x] ComparisonCard component renders correctly (N/A - inline links used)
- [x] Links to individual comparison pages work
- [x] Mobile responsive grid layout
- [x] SEO metadata properly set
- [ ] FAQ schema for common questions (not implemented in current page)

---

## Comments
### [2026-03-05 21:15 GMT+2] Agent: PreTester
Test coverage created for CompareIndexPage component. Tests cover rendering, accessibility, SEO, security, and edge cases. Note that the task description mentioned a ComparisonCard component but the actual implementation uses inline Link components, so tests reflect the actual implementation.
