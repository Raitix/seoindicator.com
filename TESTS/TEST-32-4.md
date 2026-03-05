# TEST-32-4: Comparison Index Page Tests

## Task Reference
- **Task:** [TASK-32-4](../PROJECT-SEOINDICATOR/TASKS/TASK-32-4.md)
- **Spec:** SPEC-32 (Competitor Comparison Landing Pages)

## Overview
Test suite for the comparison index page (`/compare`) and the ComparisonCard component. Tests verify page rendering, comparison grouping, mobile responsiveness, SEO metadata, and accessibility.

---

## Test Files

| File | Tests | Status |
|------|-------|--------|
| `frontend/__tests__/app/compare/page.test.tsx` | 60+ | TDD |
| `frontend/__tests__/components/compare/ComparisonCard.test.tsx` | 40+ | TDD |

---

## Test Scenarios

### Compare Index Page Tests (page.test.tsx)

#### Page Rendering (PR-001 to PR-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PR-001 | Page renders without error | Render page | Page mounts successfully |
| PR-002 | Main element exists | Render page | `<main>` element present |
| PR-003 | Hero section renders | Render page | H1 with "SEO Tool Comparisons" |
| PR-004 | Breadcrumb navigation | Render page | Nav with aria-label="Breadcrumb" |
| PR-005 | Primary comparisons section | Render page | Section with SEOIndicator comparisons |
| PR-006 | Cross-competitor section | Render page | Section with other comparisons |
| PR-007 | All comparison slugs rendered | Render page | All COMPARISON_SLUGS have links |
| PR-008 | Links point to correct URLs | Render page | href="/compare/[slug]" format |
| PR-009 | Page is client component | Check imports | No 'use client' needed (Server Component) |
| PR-010 | Dark theme styling | Render page | bg-slate-950 class present |

#### Comparison Grouping (CG-001 to CG-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CG-001 | Primary comparisons filtered correctly | Filter slugs | Only seoindicator-vs-* |
| CG-002 | Cross-competitor comparisons filtered | Filter slugs | Only non-seoindicator-* |
| CG-003 | Primary section has correct heading | Render page | "SEOIndicator Comparisons" |
| CG-004 | Cross-competitor section heading | Render page | "Other Comparisons" |
| CG-005 | Primary uses 2-column grid | Render page | md:grid-cols-2 class |
| CG-006 | Cross-competitor uses 3-column grid | Render page | lg:grid-cols-3 class |
| CG-007 | Primary cards have emerald hover | Render page | hover:border-emerald-500/50 |
| CG-008 | Cross cards have blue hover | Render page | hover:border-blue-500/50 |

#### SEO Metadata (SM-001 to SM-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SM-001 | Title metadata | Check metadata | "SEO Tool Comparisons \| SEOIndicator" |
| SM-002 | Description metadata | Check metadata | Contains "Compare SEOIndicator with" |
| SM-003 | OpenGraph title | Check metadata | og:title set correctly |
| SM-004 | OpenGraph description | Check metadata | og:description set |
| SM-005 | OpenGraph type | Check metadata | type: 'website' |
| SM-006 | Metadata is exported | Check exports | `export const metadata` exists |
| SM-007 | Meta description length | Check metadata | Under 160 characters |
| SM-008 | Title includes brand | Check metadata | "SEOIndicator" in title |

#### Mobile Responsive (MR-001 to MR-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| MR-001 | Responsive grid classes | Render page | gap-4 md:grid-cols-2 lg:grid-cols-3 |
| MR-002 | Mobile single column | Small viewport | Single column layout |
| MR-003 | Tablet two columns | Medium viewport | Two column layout |
| MR-004 | Desktop three columns | Large viewport | Three columns (cross-competitor) |
| MR-005 | Touch-friendly links | Render page | Adequate padding for touch |
| MR-006 | No horizontal scroll | Render page | No overflow-x issues |

#### Accessibility (AC-001 to AC-010)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AC-001 | Main landmark | Render page | role="main" on main element |
| AC-002 | Breadcrumb nav landmark | Render page | aria-label="Breadcrumb" |
| AC-003 | Breadcrumb structure | Render page | ol with li items |
| AC-004 | Current page indicator | Render page | aria-current="page" on Compare |
| AC-005 | Section headings | Render page | h2 with id for aria-labelledby |
| AC-006 | Heading hierarchy | Render page | h1 → h2 → h3 proper nesting |
| AC-007 | Link text is descriptive | Render page | "View detailed comparison →" |
| AC-008 | Keyboard navigable | Tab through | All links focusable |
| AC-009 | Focus visible | Focus link | Visible focus indicator |
| AC-010 | Color contrast | Check contrast | WCAG AA compliant |

#### Link Behavior (LB-001 to LB-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LB-001 | Links are Next.js Link | Render page | Uses Link from next/link |
| LB-002 | Correct href format | Click link | /compare/[slug] pattern |
| LB-003 | Slug format validation | Check slugs | Contains "-vs-" separator |
| LB-004 | All slugs from registry | Compare slugs | Matches COMPARISON_SLUGS |
| LB-005 | No broken links | Check registry | All slugs exist in registry |
| LB-006 | Link hover effects | Hover link | Transition-all class applied |

#### Edge Cases (EC-001 to EC-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| EC-001 | Empty COMPARISON_SLUGS | Empty array | Page renders without error |
| EC-002 | Missing competitor in registry | Invalid slug | Uses fallback capitalization |
| EC-003 | Unicode in competitor names | Unicode name | Renders correctly |
| EC-004 | Long competitor name | Long name | Text wraps appropriately |
| EC-005 | Many comparisons | 20+ slugs | Grid handles overflow |
| EC-006 | Single comparison | 1 slug | Grid still works |
| EC-007 | No cross-competitor comparisons | Filtered empty | Section not rendered |
| EC-008 | Malformed slug | "invalid-slug" | Fallback formatting |

---

### ComparisonCard Component Tests (ComparisonCard.test.tsx)

#### Component Rendering (CR-001 to CR-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CR-001 | Component renders | Render with props | Card element present |
| CR-002 | Title displays | Render with title | Title text visible |
| CR-003 | Description displays | Render with description | Description text visible |
| CR-004 | Slug in href | Render with slug | Link href contains slug |
| CR-005 | Competitor A name | Render with props | Name visible in card |
| CR-006 | Competitor B name | Render with props | Name visible in card |
| CR-007 | Logo placeholder | No logo provided | Fallback placeholder |
| CR-008 | Logo image | Logo URL provided | Image renders |

#### Props Handling (PH-001 to PH-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| PH-001 | All props provided | Full props | Renders completely |
| PH-002 | Optional logo omitted | No logo | Card still renders |
| PH-003 | Long title | 100+ char title | Text truncates/wraps |
| PH-004 | Long description | 200+ char desc | Text truncates/wraps |
| PH-005 | Empty description | Empty string | Handles gracefully |
| PH-006 | Special characters | Unicode title | Renders correctly |

#### Styling (ST-001 to ST-006)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| ST-001 | Card has hover effect | Hover card | Transition animation |
| ST-002 | Card has border | Render card | Border class present |
| ST-003 | Link styling | Render card | Uses Next.js Link |
| ST-004 | Group class for hover | Render card | `group` class on parent |
| ST-005 | Hover text color | Hover card | Text color changes |
| ST-006 | Dark theme support | Render card | Dark theme classes |

#### Accessibility (CA-001 to CA-008)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| CA-001 | Link has accessible name | Render card | Descriptive link text |
| CA-002 | Keyboard focusable | Tab to card | Focus ring visible |
| CA-003 | Image alt text | Logo provided | alt attribute set |
| CA-004 | Semantic structure | Render card | Proper heading levels |
| CA-005 | ARIA attributes | Render card | No missing ARIA |
| CA-006 | Screen reader text | Render card | Announces correctly |
| CA-007 | Focus visible | Focus card | Visible focus indicator |
| CA-008 | No aria-hidden on content | Render card | Content accessible |

#### Integration (IN-001 to IN-004)
| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| IN-001 | Works with registry data | Real slug | Renders from registry |
| IN-002 | Links to correct page | Click card | Navigates to /compare/[slug] |
| IN-003 | Multiple cards in grid | Render multiple | All cards visible |
| IN-004 | Performance with many cards | 20 cards | Renders quickly |

---

## Mock Data

### ComparisonCard Props
```typescript
const mockComparisonCardProps = {
  slug: 'seoindicator-vs-ahrefs',
  title: 'SEOIndicator vs Ahrefs',
  description: 'Compare SEOIndicator with Ahrefs for SEO analysis',
  competitorA: { name: 'SEOIndicator', logo: '/logos/seoindicator.svg' },
  competitorB: { name: 'Ahrefs', logo: '/logos/ahrefs.svg' },
};
```

### Registry Mock
```typescript
jest.mock('@/lib/compare/registry', () => ({
  COMPARISON_SLUGS: [
    'seoindicator-vs-ahrefs',
    'seoindicator-vs-semrush',
    'seoindicator-vs-moz',
    'ahrefs-vs-semrush',
  ],
  getCompetitorById: (id: string) => ({
    seoindicator: { name: 'SEOIndicator', slug: 'seoindicator' },
    ahrefs: { name: 'Ahrefs', slug: 'ahrefs' },
    semrush: { name: 'Semrush', slug: 'semrush' },
    moz: { name: 'Moz', slug: 'moz' },
  }[id]),
}));
```

---

## Coverage Targets

| Category | Target | Current |
|----------|--------|---------|
| Statements | 80% | TDD |
| Branches | 75% | TDD |
| Functions | 80% | TDD |
| Lines | 80% | TDD |

---

## Test Execution

```bash
# Run all tests for TASK-32-4
npm test -- --testPathPattern="compare/(page|ComparisonCard)"

# Run with coverage
npm run test:coverage -- --testPathPattern="compare/(page|ComparisonCard)"

# Watch mode
npm test -- --watch --testPathPattern="compare/(page|ComparisonCard)"
```

---

## Dependencies
- TASK-32-1 (Types) - Done ✅
- TASK-32-2 (Registry) - Done ✅

---

## Notes for Developer

### Current Implementation Status
1. **Compare Index Page** - Already implemented at `frontend/app/compare/page.tsx`
   - Uses inline Link elements instead of ComparisonCard component
   - Functional and tested by this test suite

2. **ComparisonCard Component** - NOT YET IMPLEMENTED
   - Tests are written in TDD style
   - Component should be created at `frontend/components/compare/ComparisonCard.tsx`
   - After implementation, consider refactoring page.tsx to use ComparisonCard

### Component Interface
```typescript
interface ComparisonCardProps {
  slug: string;
  title: string;
  description: string;
  competitorA: { name: string; logo?: string };
  competitorB: { name: string; logo?: string };
}
```

### Refactor Suggestion
After ComparisonCard is implemented, the page.tsx can be refactored to:
```tsx
{primaryComparisons.map((slug) => (
  <ComparisonCard
    key={slug}
    slug={slug}
    title={formatComparisonTitle(slug)}
    description="View detailed comparison"
    competitorA={getCompetitorById(slug.split('-vs-')[0])}
    competitorB={getCompetitorById(slug.split('-vs-')[1])}
  />
))}
```

---

## Comments

### [2026-03-05 21:35 GMT+2] Agent: PreTester
Started test design for TASK-32-4. Two test files created:
- Page tests verify existing implementation
- ComparisonCard tests follow TDD pattern for future component

### [2026-03-05 21:36 GMT+2] Agent: PreTester
Note: The existing page.tsx doesn't use a ComparisonCard component - it uses inline Link elements. Tests accommodate both scenarios:
1. Page tests verify the current implementation works correctly
2. ComparisonCard tests are TDD for the component mentioned in task deliverables

---

Last updated: 2026-03-05
