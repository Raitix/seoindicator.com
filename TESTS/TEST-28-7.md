# TEST-28-7: IndicatorCatalog Main Container Tests

## Overview
Tests for the IndicatorCatalog component - the main container that orchestrates the display of all indicators with optional filtering, grouping, and refresh capabilities. Part of the Indicator Catalog (SPEC-028).

## Test File Location
`frontend/components/indicators/__tests__/IndicatorCatalog.test.tsx`

## Test Count: 108 tests

---

## Test Categories

### TypeScript Type Definitions (2 tests)
| ID | Test | Description |
|----|------|-------------|
| TD-001 | Component props interface exists | Verifies typed props import correctly |
| TD-002 | IndicatorData interface matches downstream | Same interface as IndicatorGrid |

### Component Rendering (9 tests)
| ID | Test | Description |
|----|------|-------------|
| CR-001 | Renders without crashing | Basic mount test |
| CR-002 | Renders with all required props | indicators array |
| CR-003 | Renders with data-testid attribute | Identifies catalog element |
| CR-004 | Applies custom className | Class merging works |
| CR-005 | Renders IndicatorGrid | Grid component present |
| CR-006 | Renders with default title | Default title fallback |
| CR-007 | Renders with custom title | Title prop displayed |
| CR-008 | Renders with description | Description prop displayed |
| CR-009 | Container has proper role | role="region" |

### Title and Description (8 tests)
| ID | Test | Description |
|----|------|-------------|
| TD-001 | Title renders with correct tag | h2 or h3 heading |
| TD-002 | Title text matches prop | Correct content |
| TD-003 | Description renders as paragraph | p tag |
| TD-004 | Description text matches prop | Correct content |
| TD-005 | No title when prop empty | Conditional render |
| TD-006 | No description when prop empty | Conditional render |
| TD-007 | Title has proper id | For aria-labelledby |
| TD-008 | Section uses aria-labelledby | Links title to section |

### Category Tabs (12 tests)
| ID | Test | Description |
|----|------|-------------|
| CT-001 | Tabs render when categories provided | Categories prop |
| CT-002 | No tabs when categories not provided | Conditional render |
| CT-003 | All categories render as tabs | Each category shown |
| CT-004 | "All" tab rendered by default | Default category |
| CT-005 | Selected tab highlighted | Active state |
| CT-006 | Tab click triggers onCategoryChange | Callback fired |
| CT-007 | selectedCategory prop controls selection | Controlled state |
| CT-008 | Keyboard navigation between tabs | Arrow keys |
| CT-009 | Tabs use shadcn/ui Tabs component | Component integration |
| CT-010 | Separator between tabs and grid | Visual divider |
| CT-011 | Tab list has proper ARIA | role="tablist" |
| CT-012 | Tab panels have proper ARIA | role="tabpanel" |

### Loading State (10 tests)
| ID | Test | Description |
|----|------|-------------|
| LS-001 | isLoading shows skeleton grid | Loading placeholder |
| LS-002 | isLoading hides real indicators | No content shown |
| LS-003 | Skeleton count matches expected | 4-8 skeleton cards |
| LS-004 | Skeleton respects grid layout | Same column count |
| LS-005 | Loading state disables refresh | Button disabled |
| LS-006 | Loading state disables category tabs | Tabs disabled |
| LS-007 | data-loading attribute on container | DOM attribute |
| LS-008 | Skeleton shows title skeleton | Title placeholder |
| LS-009 | Skeleton shows description skeleton | Description placeholder |
| LS-010 | Transition from loading to loaded | Smooth transition |

### Error State (9 tests)
| ID | Test | Description |
|----|------|-------------|
| ES-001 | Error state shows error message | error prop displayed |
| ES-002 | Error state shows retry button | Try again button |
| ES-003 | Retry button triggers onRefresh | Callback fired |
| ES-004 | Error hides IndicatorGrid | No grid on error |
| ES-005 | Error shows error icon | AlertCircle icon |
| ES-006 | data-error attribute on container | DOM attribute |
| ES-007 | Error message accessible | aria-live region |
| ES-008 | Retry button keyboard accessible | Focus and activate |
| ES-009 | Custom error message from error prop | Message from Error object |

### Empty State (8 tests)
| ID | Test | Description |
|----|------|-------------|
| EM-001 | Empty state when indicators empty | Zero items |
| EM-002 | Empty state shows helpful message | Guidance text |
| EM-003 | Empty state shows icon | Empty icon |
| EM-004 | Empty state hides grid | No grid shown |
| EM-005 | Empty state when filter returns empty | Category with no items |
| EM-006 | Empty state message for category | Context-specific message |
| EM-007 | Empty state accessible | aria-live region |
| EM-008 | data-empty attribute on container | DOM attribute |

### Refresh Capability (7 tests)
| ID | Test | Description |
|----|------|-------------|
| RC-001 | Refresh button renders | Button visible |
| RC-002 | Refresh button triggers onRefresh | Callback fired |
| RC-003 | Refresh button disabled during loading | Loading state |
| RC-004 | Refresh button shows spinner when loading | Visual feedback |
| RC-005 | Refresh button keyboard accessible | Focus and activate |
| RC-006 | Refresh button has aria-label | Accessibility |
| RC-007 | onRefresh is optional | Graceful handling |

### Responsive Padding and Margins (8 tests)
| ID | Test | Description |
|----|------|-------------|
| RM-001 | Mobile padding (<640px) | Compact padding |
| RM-002 | Tablet padding (640-1024px) | Medium padding |
| RM-003 | Desktop padding (>1024px) | Full padding |
| RM-004 | Section margins responsive | Spacing between sections |
| RM-005 | Title margins responsive | Heading spacing |
| RM-006 | Description margins responsive | Paragraph spacing |
| RM-007 | Tab margins responsive | Tab spacing |
| RM-008 | Container max-width | Prevents overflow |

### Integration with IndicatorGrid (9 tests)
| ID | Test | Description |
|----|------|-------------|
| IG-001 | Renders IndicatorGrid component | Grid present |
| IG-002 | Passes indicators to grid | Correct props |
| IG-003 | Passes filtered indicators by category | Category filtering |
| IG-004 | Passes isLoading to grid | Loading state |
| IG-005 | Grid columns responsive | Layout integration |
| IG-006 | Grid gap uses design system | Consistent spacing |
| IG-007 | Category filter works correctly | Filter logic |
| IG-008 | All indicators shown when no category | No filtering |
| IG-009 | Category "All" shows all indicators | All items |

### Integration with shadcn/ui Components (7 tests)
| ID | Test | Description |
|----|------|-------------|
| SC-001 | Uses Tabs component | shadcn integration |
| SC-002 | Uses Separator component | Visual divider |
| SC-003 | Uses Skeleton component | Loading placeholder |
| SC-004 | Uses Button component | Refresh button |
| SC-005 | Dark mode styling applied | Theme support |
| SC-006 | Components use CSS variables | Design system |
| SC-007 | All components have correct variants | Proper usage |

### Accessibility (10 tests)
| ID | Test | Description |
|----|------|-------------|
| AC-001 | Section has role="region" | ARIA landmark |
| AC-002 | aria-labelledby points to title | Links title |
| AC-003 | Tab list has proper ARIA | role="tablist" |
| AC-004 | Tabs have proper ARIA | role="tab" |
| AC-005 | Tab panels have proper ARIA | role="tabpanel" |
| AC-006 | Error message in aria-live region | Announced |
| AC-007 | Empty state in aria-live region | Announced |
| AC-008 | Keyboard navigation for tabs | Arrow keys |
| AC-009 | Focus management on state change | Focus retention |
| AC-010 | Reduced motion preference | Respects prefers-reduced-motion |

### Edge Cases (10 tests)
| ID | Test | Description |
|----|------|-------------|
| EC-001 | Empty indicators array | Graceful handling |
| EC-002 | Empty categories array | No tabs shown |
| EC-003 | Invalid selectedCategory | Default to first |
| EC-004 | Category not in list | Default to "All" |
| EC-005 | Very long title | Truncation |
| EC-006 | Very long description | Truncation |
| EC-007 | Many categories (10+) | Scrollable tabs |
| EC-008 | Error with no onRefresh | Retry button hidden |
| EC-009 | Loading with error | Loading takes priority |
| EC-010 | Rapid category changes | No flickering |

### Module Exports (3 tests)
| ID | Test | Description |
|----|------|-------------|
| ME-001 | Exported from indicators/index | Barrel export |
| ME-002 | Named export available | Direct import |
| ME-003 | IndicatorCatalogProps type exported | TypeScript types |

### Snapshots (8 tests)
| ID | Test | Description |
|----|------|-------------|
| SN-001 | Default catalog snapshot | Baseline |
| SN-002 | With categories snapshot | Tabs visible |
| SN-003 | Loading state snapshot | Skeleton |
| SN-004 | Error state snapshot | Error message |
| SN-005 | Empty state snapshot | Empty message |
| SN-006 | Mobile layout snapshot | Responsive |
| SN-007 | Desktop layout snapshot | Responsive |
| SN-008 | Custom title/description snapshot | Props applied |

---

## Component Interface

```typescript
interface IndicatorCatalogProps {
  indicators: IndicatorData[];             // Array of indicator data
  title?: string;                          // Section title (default: "SEO Indicators")
  description?: string;                    // Section description
  categories?: string[];                   // Optional categories for filtering
  selectedCategory?: string;               // Currently selected category
  onCategoryChange?: (category: string) => void;  // Category change callback
  onRefresh?: () => void;                  // Refresh callback
  isLoading?: boolean;                     // Loading state
  error?: Error | null;                    // Error state
  className?: string;                      // Additional CSS classes
}

interface IndicatorData {
  id: string;                              // Unique identifier
  icon: LucideIconName | React.ReactNode;  // Lucide icon name or custom SVG
  title: string;                           // Indicator title
  value: string | number;                  // Current value
  description: string;                     // Description text
  status: 'good' | 'warning' | 'critical' | 'neutral';  // Status indicator
  href: string;                            // Link destination
  category?: string;                       // Optional category for filtering
  tooltip?: string;                        // Optional tooltip override
}
```

## Default Values

| Prop | Default Value |
|------|---------------|
| `title` | `"SEO Indicators"` |
| `description` | `undefined` |
| `categories` | `undefined` |
| `selectedCategory` | `"All"` |
| `isLoading` | `false` |
| `error` | `null` |
| `className` | `undefined` |

## State Priority

1. **Loading** - Shows skeleton grid
2. **Error** - Shows error message with retry
3. **Empty** - Shows empty state message
4. **Content** - Shows IndicatorGrid

---

## Acceptance Criteria Coverage

| Criteria | Tests |
|----------|-------|
| AC-001: Container wraps IndicatorGrid with header and controls | CR-001 to CR-009, IG-001 to IG-009 |
| AC-002: Section title and description configurable | TD-001 to TD-008 |
| AC-003: Optional category tabs for grouping | CT-001 to CT-012 |
| AC-004: Loading state with skeleton grid | LS-001 to LS-010 |
| AC-005: Error state with retry option | ES-001 to ES-009 |
| AC-006: Empty state with helpful message | EM-001 to EM-008 |
| AC-007: Refresh capability (manual trigger) | RC-001 to RC-007 |
| AC-008: Responsive padding and margins | RM-001 to RM-008 |
| AC-009: TypeScript types properly defined | TD-001, TD-002, ME-003 |

---

## Test Patterns Used

1. **Mocked Dependencies**: IndicatorGrid, Tabs, Separator, Skeleton, Button mocked
2. **State Priority Testing**: Loading > Error > Empty > Content
3. **Matrix Testing**: All state combinations tested
4. **Responsive Testing**: Viewport-based tests
5. **Accessibility First**: ARIA and keyboard navigation tested
6. **Integration Testing**: Grid component usage verified
7. **Snapshot Regression**: Visual baseline for future changes

---

## Dependencies

### Task Dependencies
- TASK-28-1 (CSS Variables Enhancement) - Color/spacing variables
- TASK-28-3 (shadcn/ui Components) - Tabs, Separator, Skeleton, Button
- TASK-28-5 (IndicatorCard Component) - Via IndicatorGrid
- TASK-28-6 (IndicatorGrid Responsive Layout) - Grid rendering

### Package Dependencies
- `lucide-react` - Icons (RefreshCw, AlertCircle, FolderOpen)
- `next/link` - Client-side navigation
- `@/lib/utils` - cn() utility
- `@/components/ui/tabs` - Tabs component
- `@/components/ui/separator` - Separator component
- `@/components/ui/skeleton` - Skeleton component
- `@/components/ui/button` - Button component
- `@/components/indicators/IndicatorGrid` - Grid component

---

## Notes for Developer

1. Create `frontend/components/indicators/IndicatorCatalog.tsx`
2. Use `IndicatorGrid` component for indicator display
3. Use `Tabs` from shadcn for category filtering
4. Use `Separator` from shadcn for visual division
5. Use `Skeleton` from shadcn for loading state
6. Use `Button` from shadcn for refresh button
7. Implement state priority: Loading > Error > Empty > Content
8. Use Next.js `Link` component for internal navigation
9. Ensure `data-testid`, `data-loading`, `data-error`, `data-empty` attributes
10. Apply `focus-visible:ring-2` for keyboard focus indicator
11. Respect `prefers-reduced-motion` for animations
12. Filter indicators by `indicator.category` when category selected

### Category Filtering Logic

```typescript
const filteredIndicators = selectedCategory === 'All'
  ? indicators
  : indicators.filter(i => i.category === selectedCategory);
```

### State Rendering Logic

```typescript
if (isLoading) {
  return <CatalogSkeleton />;
}

if (error) {
  return <CatalogError error={error} onRetry={onRefresh} />;
}

if (filteredIndicators.length === 0) {
  return <CatalogEmptyState category={selectedCategory} />;
}

return <IndicatorGrid indicators={filteredIndicators} />;
```

---

## Mock Data

```typescript
const mockIndicators: IndicatorData[] = [
  {
    id: 'keyword-rankings',
    icon: 'TrendingUp',
    title: 'Keyword Rankings',
    value: '142',
    description: 'Keywords ranking in top 100',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'visibility-score',
    icon: 'Eye',
    title: 'Visibility Score',
    value: '78%',
    description: 'Overall SEO visibility',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'competitor-analysis',
    icon: 'Users',
    title: 'Competitor Analysis',
    value: '5',
    description: 'Tracked competitors',
    status: 'neutral',
    href: '/comparison',
    category: 'competition',
  },
  {
    id: 'technical-health',
    icon: 'Wrench',
    title: 'Technical Health',
    value: '85/100',
    description: 'Site audit score',
    status: 'warning',
    href: '/tools',
    category: 'technical',
  },
  {
    id: 'backlink-profile',
    icon: 'Link',
    title: 'Backlink Profile',
    value: '1,234',
    description: 'Total backlinks',
    status: 'good',
    href: '/dashboard',
    category: 'backlinks',
  },
  {
    id: 'content-opportunities',
    icon: 'FileText',
    title: 'Content Opportunities',
    value: '23',
    description: 'Keyword gaps found',
    status: 'warning',
    href: '/dashboard',
    category: 'content',
  },
  {
    id: 'serp-features',
    icon: 'Star',
    title: 'SERP Features',
    value: '8',
    description: 'Featured snippets won',
    status: 'good',
    href: '/dashboard',
    category: 'rankings',
  },
  {
    id: 'page-speed',
    icon: 'Zap',
    title: 'Page Speed',
    value: '92/100',
    description: 'Performance score',
    status: 'good',
    href: '/tools',
    category: 'technical',
  },
];

const mockCategories = ['rankings', 'competition', 'technical', 'backlinks', 'content'];

const defaultProps: IndicatorCatalogProps = {
  indicators: mockIndicators,
  title: 'SEO Indicators',
  description: 'Track your SEO performance at a glance',
};

const withCategoriesProps: IndicatorCatalogProps = {
  ...defaultProps,
  categories: mockCategories,
  onCategoryChange: jest.fn(),
};

const loadingProps: IndicatorCatalogProps = {
  ...defaultProps,
  isLoading: true,
};

const errorProps: IndicatorCatalogProps = {
  ...defaultProps,
  error: new Error('Failed to load indicators'),
  onRefresh: jest.fn(),
};

const emptyProps: IndicatorCatalogProps = {
  indicators: [],
};
```

---

## Test Execution

```bash
# Run all IndicatorCatalog tests
npm test -- IndicatorCatalog.test.tsx

# Run with coverage
npm test -- --coverage IndicatorCatalog.test.tsx

# Update snapshots
npm test -- -u IndicatorCatalog.test.tsx

# Run specific test category
npm test -- -t "Category Tabs"
```

---

Created by: PreTester
Date: 2026-03-03
