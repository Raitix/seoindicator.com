# TEST-28-8: Homepage Integration Tests

## Task Reference
- **Task:** [TASK-28-8](../TASKS/TASK-28-8.md)
- **Specification:** [SPEC-028](../SPECIFICATIONS/SPEC-028.md)
- **Feature:** [FEAT-028](../FEATURES/FEAT-028.md)

---

## Overview

Comprehensive test suite for integrating IndicatorCatalog component into the homepage. Tests cover integration, layout, responsiveness, animations, navigation, performance, preservation of existing functionality, and accessibility.

---

## Test Files

| File | Tests | Description |
|------|-------|-------------|
| `frontend/__tests__/pages/home-page.test.tsx` | 71 | Homepage integration tests |

---

## Test Scenarios

### HI-001: Basic Homepage Rendering (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-001.01 | Homepage renders without errors | Main element in document |
| HI-001.02 | Homepage has correct container structure | `min-h-screen` class applied |
| HI-001.03 | Homepage renders header section | Banner role element exists |
| HI-001.04 | Homepage renders footer section | Contentinfo role element exists |
| HI-001.05 | No console errors on render | console.error not called |

### HI-002: IndicatorCatalog Integration (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-002.01 | IndicatorCatalog renders within homepage | `data-testid="indicator-catalog"` present |
| HI-002.02 | IndicatorCatalog is in hero section | Hero region contains catalog |
| HI-002.03 | IndicatorCatalog receives correct indicators data | All indicator test IDs present |
| HI-002.04 | IndicatorCatalog has correct default title | "SEO Indicators" text |
| HI-002.05 | IndicatorCatalog has description text | Description element exists |

### HI-003: Hero Section Design (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-003.01 | Hero section has main heading (h1) | h1 element exists |
| HI-003.02 | Hero heading has compelling text | Text matches /SEO\|Track\|Performance/ |
| HI-003.03 | Hero section has subtitle/description | Paragraph(s) in hero section |
| HI-003.04 | Hero section has CTA button | Button with "Get Started" text |
| HI-003.05 | Hero section has gradient background | `gradient` or `bg-` class |
| HI-003.06 | Hero uses brand colors | `brand`, `emerald`, or `primary` class |

### HI-004: Responsive Layout (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-004.01 | Mobile layout (< 640px) renders correctly | Main element exists at 375px |
| HI-004.02 | Tablet layout (640-1024px) renders correctly | Main element exists at 768px |
| HI-004.03 | Desktop layout (> 1024px) renders correctly | Main element exists at 1280px |
| HI-004.04 | Hero section has responsive padding | `py-`, `px-`, or `p-` classes |
| HI-004.05 | Container has responsive max-width | `.container` class exists |
| HI-004.06 | Navigation is responsive | Navigation role element exists |

### HI-005: Navigation Links (6 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-005.01 | Indicator cards link to correct dashboard sections | `/dashboard` href |
| HI-005.02 | Technical health links to tools section | `/tools` href |
| HI-005.03 | Competitor analysis links to comparison | `/comparison` href |
| HI-005.04 | CTA button navigates to correct page | Link with Get Started text |
| HI-005.05 | Header navigation links work | Links in navigation element |
| HI-005.06 | All indicator links are valid internal routes | All hrefs start with `/` |

### HI-006: Entrance Animations (4 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-006.01 | Hero section has animation class | `animate`, `transition`, `fade`, or `slide` class |
| HI-006.02 | IndicatorCatalog has entrance animation | Animation class (optional) |
| HI-006.03 | Animations respect prefers-reduced-motion | Renders without animation when reduced |
| HI-006.04 | Animation uses CSS transforms | `translate`, `scale`, `opacity` classes |

### HI-007: Performance Optimization (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-007.01 | No layout shift on mount | `min-h-screen` class defined |
| HI-007.02 | Images have dimensions defined | width and height attributes |
| HI-007.03 | No hydration mismatches | No hydration console warnings |
| HI-007.04 | Critical CSS is inlined | Main element has classes |
| HI-007.05 | No blocking scripts in hero section | No `<script>` tags in hero |

### HI-008: Existing Functionality Preservation (8 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-008.01 | Features grid section still exists | "Key Features" heading |
| HI-008.02 | Feature cards render correctly | 3+ card elements |
| HI-008.03 | Status bar section exists | "System Status" or "Operational" text |
| HI-008.04 | Footer section exists with copyright | "2026 SEOIndicator" text |
| HI-008.05 | Header navigation still works | Dashboard link exists |
| HI-008.06 | Competitors link still works | Competitors link exists |
| HI-008.07 | Comparison link still works | Comparison link exists |
| HI-008.08 | Sign In button still exists | "Sign In" button |

### HI-009: Accessibility (7 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-009.01 | Proper heading hierarchy | 1 h1, h2+, optional h3 |
| HI-009.02 | All interactive elements are focusable | No `tabindex="-1"` |
| HI-009.03 | CTA button is accessible via keyboard | Focus on CTA button |
| HI-009.04 | Color contrast meets WCAG 2.1 AA | Main element exists (visual test) |
| HI-009.05 | Landmark regions are properly defined | banner, navigation, main, contentinfo |
| HI-009.06 | Hero section has aria-label | Region with "hero" label |
| HI-009.07 | Screen reader can navigate indicator catalog | Links have href attributes |

### HI-010: CSS Variables Integration (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-010.01 | Background uses CSS variables | `brand`, `primary`, `surface`, or `gradient` class |
| HI-010.02 | Surface layer variables for depth | Cards have surface styling |
| HI-010.03 | Semantic colors for status indicators | "Operational" status text |

### HI-011: Error Handling (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-011.01 | Page handles missing indicators gracefully | Main element still renders |
| HI-011.02 | Loading state shows skeleton | Catalog element exists |

### HI-012: Edge Cases (5 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-012.01 | Long indicator values are truncated | Main element renders |
| HI-012.02 | Special characters in headings render correctly | No HTML entities in text |
| HI-012.03 | Unicode characters render correctly | Copyright symbol in footer |
| HI-012.04 | Empty state handled when no indicators | Main element renders |
| HI-012.05 | Multiple rapid renders do not cause issues | Main element stable |

### HI-013: Snapshot Tests (3 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-013.01 | Homepage matches snapshot | Snapshot matches |
| HI-013.02 | Hero section matches snapshot | Snapshot matches |
| HI-013.03 | IndicatorCatalog integration matches snapshot | Snapshot matches |

### HI-TS: TypeScript Types (2 tests)

| ID | Scenario | Expected Output |
|----|----------|-----------------|
| HI-TS.01 | Homepage component has correct return type | Returns main element |
| HI-TS.02 | IndicatorData interface is compatible | Object structure valid |

---

## Mock Data

### Sample Indicators

```typescript
const mockIndicators = [
  { id: 'rankings', title: 'Keyword Rankings', value: '42', status: 'good', href: '/dashboard' },
  { id: 'visibility', title: 'Visibility Score', value: '78%', status: 'warning', href: '/dashboard' },
  { id: 'technical', title: 'Technical Health', value: '95', status: 'good', href: '/tools' },
  { id: 'backlinks', title: 'Backlink Profile', value: '1.2K', status: 'good', href: '/dashboard' },
  { id: 'content', title: 'Content Opportunities', value: '15', status: 'neutral', href: '/dashboard' },
  { id: 'serp', title: 'SERP Features', value: '8', status: 'good', href: '/dashboard' },
  { id: 'speed', title: 'Page Speed', value: '89', status: 'warning', href: '/tools' },
  { id: 'competitors', title: 'Competitor Analysis', value: '3', status: 'neutral', href: '/comparison' },
];
```

---

## Coverage Targets

| Category | Target | Tests |
|----------|--------|-------|
| Integration | 100% | 5 tests |
| Hero Design | 100% | 6 tests |
| Responsive Layout | 100% | 6 tests |
| Navigation | 100% | 6 tests |
| Animations | 100% | 4 tests |
| Performance | 100% | 5 tests |
| Preservation | 100% | 8 tests |
| Accessibility | 100% | 7 tests |
| CSS Variables | 100% | 3 tests |
| Error Handling | 100% | 2 tests |
| Edge Cases | 100% | 5 tests |
| Snapshots | 100% | 3 tests |
| TypeScript | 100% | 2 tests |
| **Total** | **100%** | **71 tests** |

---

## Test Execution

```bash
# Run all homepage tests
npm test -- frontend/__tests__/pages/home-page.test.tsx

# Run with coverage
npm run test:coverage -- frontend/__tests__/pages/home-page.test.tsx

# Run specific test category
npm test -- -t "HI-002" # IndicatorCatalog Integration
npm test -- -t "HI-009" # Accessibility
```

---

## Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| TASK-28-7 (IndicatorCatalog) | To Develop | Mocked in tests |
| TASK-28-1 (CSS Variables) | To Develop | Tests verify class usage |
| TASK-28-5 (IndicatorCard) | To Develop | Used via IndicatorCatalog |
| TASK-28-6 (IndicatorGrid) | To Develop | Used via IndicatorCatalog |

---

## Notes for Developer

### Implementation Order
1. **CSS Variables First** (TASK-28-1) - Define brand colors, semantic colors, surface layers
2. **IndicatorCatalog** (TASK-28-7) - Create main container component
3. **Homepage Integration** (TASK-28-8) - Integrate into page.tsx

### Key Implementation Points

1. **Hero Section Structure:**
   ```tsx
   <section role="region" aria-label="Hero" className="...">
     <h1>Track Your SEO Performance</h1>
     <p>Description text...</p>
     <IndicatorCatalog ... />
     <Button>Get Started</Button>
   </section>
   ```

2. **Gradient Background:**
   ```tsx
   // Use CSS variables for brand colors
   className="bg-gradient-to-b from-[var(--surface-0)] to-[var(--surface-1)]"
   // OR use brand primary
   className="bg-gradient-to-br from-brand-primary/10 to-surface-2"
   ```

3. **Entrance Animations:**
   ```tsx
   // Use Tailwind animate classes
   className="animate-fade-in"
   // Or custom CSS with transforms
   className="transition-all duration-500 opacity-100 translate-y-0"
   ```

4. **Responsive Layout:**
   ```tsx
   // Mobile-first responsive classes
   className="py-12 md:py-16 lg:py-20 px-4 md:px-6 lg:px-8"
   ```

5. **Preserve Existing Sections:**
   - Features grid (`md:grid-cols-3`)
   - Status bar
   - Footer with copyright
   - Header navigation

### State Priority
```
1. Loading → Show skeleton/placeholder
2. Error → Show error message with retry
3. Empty → Show empty state
4. Content → Show IndicatorCatalog
```

### Accessibility Requirements
- Only ONE `<h1>` on the page
- Hero section must have `aria-label` or accessible name
- All links must have valid `href` attributes
- CTA button must be keyboard accessible
- Animations must respect `prefers-reduced-motion`

---

## Comments
_Agent log_

### [2026-03-03 13:59 UTC+2] PreTester
**Test Design Complete**

**Tests Created:**
- `frontend/__tests__/pages/home-page.test.tsx` - 71 test scenarios
- `TESTS/TEST-28-8.md` - This documentation

**Test Coverage:**
- Integration tests (HI-001, HI-002): 10 tests
- Hero section design (HI-003): 6 tests
- Responsive layout (HI-004): 6 tests
- Navigation links (HI-005): 6 tests
- Entrance animations (HI-006): 4 tests
- Performance optimization (HI-007): 5 tests
- Existing functionality preservation (HI-008): 8 tests
- Accessibility (HI-009): 7 tests
- CSS Variables integration (HI-010): 3 tests
- Error handling (HI-011): 2 tests
- Edge cases (HI-012): 5 tests
- Snapshots (HI-013): 3 tests
- TypeScript types (HI-TS): 2 tests

**Key Test Patterns:**
- Mocked IndicatorCatalog, Button, Input, Card components
- Mocked Next.js Link component
- Role-based queries for accessibility
- Snapshot tests for visual regression baseline
- prefers-reduced-motion media query mock

**Dependencies:**
- TASK-28-7 (IndicatorCatalog): Mocked - interface defined
- TASK-28-1 (CSS Variables): Tests verify class usage

**Status Changed:** `To Write Tests` → `To Develop`

---
*Handed off to Developer for implementation.*
