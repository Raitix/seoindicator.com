# TESTS/TASK-20-2.md — Guide Index Page Component Tests

## Overview
Test documentation for the SEO Guides Index Page component at `/seo-guides/`.

---

## Component Under Test
- **File:** `app/seo-guides/page.tsx`
- **Purpose:** Display all 17 SEO guides in a filterable grid layout

---

## Test Categories

### 1. Page Rendering Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| R1 | Page renders without errors | Component mounts successfully |
| R2 | All 17 guides displayed in grid | 17 guide cards visible |
| R3 | Page title set correctly | "SEO Guides" heading visible |
| R4 | Page description displayed | Subtitle/description text shown |

### 2. Guide Card Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| C1 | Each card shows icon | Icon emoji displayed |
| C2 | Each card shows title | Guide title displayed |
| C3 | Each card shows summary | Summary text truncated appropriately |
| C4 | Each card shows read time | "X min read" text visible |
| C5 | Cards with tools show badge | "Has Tool" badge visible |
| C6 | Cards without tools show no badge | No badge displayed |
| C7 | Card links to correct guide page | href="/seo-guides/{slug}" |
| C8 | Card is keyboard accessible | Tab navigation works |

### 3. Category Filter Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| F1 | All tab selected by default | Shows all 17 guides |
| F2 | On-Page tab filters correctly | Shows 8 on-page guides |
| F3 | Technical tab filters correctly | Shows 7 technical guides |
| F4 | Content tab filters correctly | Shows 2 content guides |
| F5 | Active tab shows visual indicator | Active state styling applied |
| F6 | Tab navigation is keyboard accessible | Arrow keys work |
| F7 | Filter preserves search query | Search not cleared on tab change |

### 4. Search Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| S1 | Search input renders | Input field visible |
| S2 | Search filters by title | Typing "meta" shows 2 guides |
| S3 | Search is case-insensitive | "META" and "meta" same results |
| S4 | Empty search shows all guides | Clear input shows all 17 |
| S5 | No results message displayed | "No guides found" shown |
| S6 | Search works with category filter | Combined filtering works |
| S7 | Search debounced | Not filtering on every keystroke |
| S8 | Search input clears button | X button clears input |

### 5. Responsive Grid Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| G1 | 3 columns on desktop (>=1024px) | grid-cols-3 class applied |
| G2 | 2 columns on tablet (>=768px) | grid-cols-2 class applied |
| G3 | 1 column on mobile (<768px) | grid-cols-1 class applied |
| G4 | Grid gap consistent | Gap applied between cards |
| G5 | Cards maintain aspect ratio | Cards don't stretch oddly |

### 6. Tool Badge Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| T1 | Guides with toolSlug show badge | Badge visible |
| T2 | Guides without toolSlug hide badge | No badge |
| T3 | Badge text correct | "Has Tool" or similar |
| T4 | Badge styling consistent | Consistent color/size |
| T5 | Correct guides have badges | 5 guides with tools |

### 7. SEO Meta Tags Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| M1 | Page title correct | "SEO Guides - SEOIndicator" |
| M2 | Meta description set | Description in head |
| M3 | Canonical URL set | /seo-guides/ canonical |
| M4 | Open Graph tags set | og:title, og:description |
| M5 | Twitter cards set | twitter:card, twitter:title |

### 8. Accessibility Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| A1 | All cards have accessible names | aria-label or aria-labelledby |
| A2 | Filter tabs have proper roles | role="tablist", role="tab" |
| A3 | Search input has label | aria-label or associated label |
| A4 | Focus visible on all interactive | focus:ring styles applied |
| A5 | Screen reader announces filter changes | aria-live region |
| A6 | Color contrast meets WCAG AA | Contrast ratio >= 4.5:1 |

### 9. Navigation Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| N1 | Card click navigates to guide | Router.push called with slug |
| N2 | Back button works | Returns to index page |
| N3 | Direct URL access works | /seo-guides loads correctly |
| N4 | Breadcrumb shows current location | Home > SEO Guides |

### 10. Edge Cases Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| E1 | Empty guide list handled | No crashes, shows empty state |
| E2 | Invalid category handled | Falls back to "all" |
| E3 | Search with special chars | No XSS, no crashes |
| E4 | Long guide titles | Text truncates, no overflow |
| E5 | Long summaries | Text truncates with ellipsis |
| E6 | Unicode in search | Works correctly |
| E7 | Rapid filter changes | No race conditions |
| E8 | Network error fallback | Graceful degradation |

### 11. Performance Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| P1 | Initial render < 100ms | Fast load time |
| P2 | Filter change < 50ms | Smooth interaction |
| P3 | Search debounce delay 300ms | Not too sensitive |
| P4 | No unnecessary re-renders | useMemo for guides array |

### 12. Integration Tests

| ID | Test | Expected Result |
|----|------|-----------------|
| I1 | getAllGuides() called on mount | Registry integration |
| I2 | Category filter uses getGuidesByCategory() | Registry filter used |
| I3 | Tool badge checks toolSlug | Registry data used |
| I4 | Links use correct slug format | /seo-guides/{slug} |

---

## Test File Structure

```
frontend/__tests__/
├── app/
│   └── seo-guides/
│       └── page.test.tsx       # Main page tests
└── components/
    └── guides/
        ├── GuideCard.test.tsx  # Card component tests
        ├── CategoryFilter.test.tsx  # Filter tabs tests
        └── GuideSearch.test.tsx     # Search input tests
```

---

## Mock Requirements

### Mock `getAllGuides()` and `getGuidesByCategory()`
```typescript
jest.mock('@/lib/guides/registry', () => ({
  getAllGuides: jest.fn(() => mockGuides),
  getGuidesByCategory: jest.fn((category) => 
    mockGuides.filter(g => g.category === category)
  ),
}));
```

### Mock Next.js Router
```typescript
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
  }),
  usePathname: () => '/seo-guides',
}));
```

### Mock Window Resize (for responsive tests)
```typescript
Object.defineProperty(window, 'innerWidth', {
  writable: true,
  value: 1024, // or 768, 375 for different breakpoints
});
```

---

## Test Data

Use the actual 17 guide definitions from `lib/guides/registry.ts`:
- 8 on-page guides
- 7 technical guides
- 2 content guides
- 5 guides with toolSlug

---

## Notes for Developer

1. **Shadcn/UI Components:**
   - Use `Card` component for guide cards
   - Use `Input` component for search
   - Use `Tabs` component for category filter
   - Use `Badge` component for tool indicator

2. **State Management:**
   - Use `useState` for category filter
   - Use `useState` for search query
   - Use `useMemo` for filtered guides

3. **Responsive Design:**
   - Use Tailwind responsive classes: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
   - Test at 375px, 768px, 1024px, 1440px widths

4. **Accessibility:**
   - Add `aria-label` to search input
   - Use proper heading hierarchy (h1 > h2 > h3)
   - Ensure focus states are visible

---

## Status
`Ready for Implementation`

Created by PreTester on 2026-03-01
