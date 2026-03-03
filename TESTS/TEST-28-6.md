# TEST-28-6: IndicatorGrid Responsive Layout Tests

## Overview
Tests for the IndicatorGrid component - a responsive grid layout for displaying multiple indicator cards with proper spacing and breakpoints. Part of the Indicator Catalog (SPEC-028).

## Test File Location
`frontend/components/indicators/__tests__/IndicatorGrid.test.tsx`

## Test Count: 85 tests

---

## Test Categories

### TypeScript Type Definitions (2 tests)
| ID | Test | Description |
|----|------|-------------|
| TD-001 | Component props interface exists | Verifies typed props import correctly |
| TD-002 | IndicatorData interface defined | Data structure for indicators |

### Component Rendering (8 tests)
| ID | Test | Description |
|----|------|-------------|
| CR-001 | Renders without crashing | Basic mount test |
| CR-002 | Renders empty grid | Empty array handling |
| CR-003 | Renders single indicator | One card display |
| CR-004 | Renders multiple indicators | Multiple cards |
| CR-005 | Renders with data-testid attribute | Identifies grid element |
| CR-006 | Applies custom className | Class merging works |
| CR-007 | Grid container has proper role | role="grid" |
| CR-008 | Renders all 8 indicator types | Full indicator set |

### CSS Grid Layout (7 tests)
| ID | Test | Description |
|----|------|-------------|
| GL-001 | Uses CSS Grid display | display: grid |
| GL-002 | 12-column grid system | grid-template-columns |
| GL-003 | Grid auto-flow set correctly | Auto placement |
| GL-004 | Grid alignment properties | align-items, justify-items |
| GL-005 | Min card width constraint | Prevents too narrow cards |
| GL-006 | Grid item class applied | Each card has grid item class |
| GL-007 | CSS Grid shorthand applied | grid-template shorthand |

### Responsive Breakpoints (8 tests)
| ID | Test | Description |
|----|------|-------------|
| RB-001 | 1 column on mobile (<640px) | Base breakpoint |
| RB-002 | 2 columns on sm (640px+) | sm breakpoint |
| RB-003 | 3 columns on md (768px+) | md breakpoint |
| RB-004 | 4 columns on lg (1024px+) | lg breakpoint |
| RB-005 | Custom column config overrides | columns prop |
| RB-006 | xl breakpoint support | Optional xl column count |
| RB-007 | Responsive media query classes | Tailwind responsive |
| RB-008 | Viewport width detection | Correct column count |

### Gap Spacing (6 tests)
| ID | Test | Description |
|----|------|-------------|
| GS-001 | Default gap is 'md' | Medium spacing |
| GS-002 | 'sm' gap applies small spacing | 0.5rem / 8px |
| GS-003 | 'md' gap applies medium spacing | 1rem / 16px |
| GS-004 | 'lg' gap applies large spacing | 1.5rem / 24px |
| GS-005 | Gap applies to grid-gap | CSS property |
| GS-006 | Gap consistent across breakpoints | Same gap on all sizes |

### Animated Layout Transitions (7 tests)
| ID | Test | Description |
|----|------|-------------|
| AT-001 | Transition on resize | Animate layout change |
| AT-002 | Transition duration reasonable | 150-300ms |
| AT-003 | GPU-accelerated properties | transform, opacity |
| AT-004 | Respects prefers-reduced-motion | Accessibility |
| AT-005 | No layout shift during transition | Smooth animation |
| AT-006 | Width/height transition | Smooth resize |
| AT-007 | Transition timing function | ease-out or custom |

### Accessibility (8 tests)
| ID | Test | Description |
|----|------|-------------|
| AC-001 | Grid has role="grid" | ARIA landmark |
| AC-002 | aria-label on grid | Describes grid purpose |
| AC-003 | aria-describedby for description | Optional description |
| AC-004 | Items have role="gridcell" | Grid cell semantics |
| AC-005 | Keyboard navigation between items | Arrow keys |
| AC-006 | Focus visible on grid items | Focus indicator |
| AC-007 | aria-rowcount for item count | Screen reader info |
| AC-008 | High contrast mode support | Forced colors |

### Variable Indicator Count (7 tests)
| ID | Test | Description |
|----|------|-------------|
| VC-001 | Renders 1 indicator | Single item |
| VC-002 | Renders 2 indicators | Two items |
| VC-003 | Renders 4 indicators | One row at lg |
| VC-004 | Renders 8 indicators | Two rows at lg |
| VC-005 | Renders 12 indicators | Three rows at lg |
| VC-006 | Renders 20+ indicators | Many items |
| VC-007 | Empty array shows nothing | Graceful empty state |

### Integration with IndicatorCard (8 tests)
| ID | Test | Description |
|----|------|-------------|
| IC-001 | Renders IndicatorCard for each item | Card component usage |
| IC-002 | Passes indicator data to card | Props flow correctly |
| IC-003 | Passes isLoading to cards | Loading state prop |
| IC-004 | Cards link to correct hrefs | Navigation |
| IC-005 | Cards have unique keys | React key prop |
| IC-006 | Cards inherit grid item role | Semantic structure |
| IC-007 | All status variants render | good/warning/critical/neutral |
| IC-008 | Card icons render | Lucide icons |

### Loading State (5 tests)
| ID | Test | Description |
|----|------|-------------|
| LS-001 | isLoading shows skeleton grid | Loading placeholder |
| LS-002 | isLoading hides real cards | No content shown |
| LS-003 | Skeleton count matches expected | 4-8 skeleton cards |
| LS-004 | Skeleton respects grid layout | Same column count |
| LS-005 | data-loading attribute on grid | DOM attribute |

### Custom Column Configuration (6 tests)
| ID | Test | Description |
|----|------|-------------|
| CC-001 | Override mobile columns | columns.mobile prop |
| CC-002 | Override sm columns | columns.sm prop |
| CC-003 | Override md columns | columns.md prop |
| CC-004 | Override lg columns | columns.lg prop |
| CC-005 | Override xl columns | columns.xl prop |
| CC-006 | Partial override uses defaults | Mix of custom/default |

### Edge Cases (8 tests)
| ID | Test | Description |
|----|------|-------------|
| EC-001 | Invalid column count defaults to 1 | Fallback handling |
| EC-002 | Negative column count defaults to 1 | Fallback handling |
| EC-003 | Zero indicators shows empty | Empty state |
| EC-004 | Very long indicator list | Performance |
| EC-005 | Duplicate indicator IDs | Unique keys still work |
| EC-006 | Missing optional props | Default values |
| EC-007 | Undefined columns prop | Default columns |
| EC-008 | Rapid viewport resize | No layout thrashing |

### Security (3 tests)
| ID | Test | Description |
|----|------|-------------|
| SC-001 | XSS in indicator title | Sanitization |
| SC-002 | XSS in indicator description | Sanitization |
| SC-003 | Malicious className prop | Class name sanitization |

### Module Exports (3 tests)
| ID | Test | Description |
|----|------|-------------|
| ME-001 | Exported from indicators/index | Barrel export |
| ME-002 | Named export available | Direct import |
| ME-003 | IndicatorGridProps type exported | TypeScript types |

### Snapshots (7 tests)
| ID | Test | Description |
|----|------|-------------|
| SN-001 | Default grid snapshot | Baseline |
| SN-002 | Custom columns snapshot | Baseline |
| SN-003 | sm gap snapshot | Baseline |
| SN-004 | lg gap snapshot | Baseline |
| SN-005 | Loading state snapshot | Baseline |
| SN-006 | Mobile layout snapshot | Baseline |
| SN-007 | Desktop layout snapshot | Baseline |

---

## Component Interface

```typescript
interface IndicatorData {
  id: string;                              // Unique identifier
  icon: LucideIconName | React.ReactNode;  // Lucide icon name or custom SVG
  title: string;                           // Indicator title
  value: string | number;                  // Current value
  description: string;                     // Description text
  status: 'good' | 'warning' | 'critical' | 'neutral';  // Status indicator
  href: string;                            // Link destination
  tooltip?: string;                        // Optional tooltip override
}

interface IndicatorGridProps {
  indicators: IndicatorData[];             // Array of indicator data
  columns?: {                              // Responsive column config
    mobile?: number;                       // < 640px (default: 1)
    sm?: number;                           // >= 640px (default: 2)
    md?: number;                           // >= 768px (default: 3)
    lg?: number;                           // >= 1024px (default: 4)
    xl?: number;                           // >= 1280px (default: 4)
  };
  gap?: 'sm' | 'md' | 'lg';               // Gap spacing (default: 'md')
  isLoading?: boolean;                     // Loading state
  className?: string;                      // Additional CSS classes
  'aria-label'?: string;                   // Custom aria-label
}
```

## Default Column Configuration

| Breakpoint | Min Width | Columns |
|------------|-----------|---------|
| mobile | 0 | 1 |
| sm | 640px | 2 |
| md | 768px | 3 |
| lg | 1024px | 4 |
| xl | 1280px | 4 |

## Gap Spacing Values

| Gap | Value | CSS Variable |
|-----|-------|--------------|
| `sm` | 0.5rem (8px) | --spacing-sm |
| `md` | 1rem (16px) | --spacing-md |
| `lg` | 1.5rem (24px) | --spacing-lg |

---

## Acceptance Criteria Coverage

| Criteria | Tests |
|----------|-------|
| AC-001: CSS Grid with 12-column system | GL-001 to GL-007 |
| AC-002: Responsive breakpoints | RB-001 to RB-008 |
| AC-003: Consistent gap spacing | GS-001 to GS-006 |
| AC-004: Variable number of indicators | VC-001 to VC-007 |
| AC-005: Animated layout transitions | AT-001 to AT-007 |
| AC-006: TypeScript types defined | TD-001 to TD-002 |
| AC-007: Accessible (ARIA labels) | AC-001 to AC-008 |

---

## Test Patterns Used

1. **Mocked Dependencies**: IndicatorCard mocked for isolated testing
2. **Responsive Testing**: Viewport-based tests for breakpoints
3. **Matrix Testing**: All gap variants tested
4. **Accessibility First**: ARIA and keyboard navigation tested
5. **Integration Testing**: Card component usage verified
6. **Snapshot Regression**: Visual baseline for future changes
7. **Edge Case Coverage**: Invalid inputs, empty states, performance

---

## Dependencies

### Task Dependencies
- TASK-28-1 (CSS Variables Enhancement) - Spacing variables
- TASK-28-3 (shadcn/ui Components) - Skeleton for loading
- TASK-28-5 (IndicatorCard Component) - Card rendering
- TASK-28-4 (IndicatorIcon Wrapper) - Icon rendering (via Card)

### Package Dependencies
- `lucide-react` - Icon library
- `@/lib/utils` - cn() utility
- `@/components/ui/skeleton` - Skeleton component
- `@/components/indicators/IndicatorCard` - Card component

---

## Notes for Developer

1. Create `frontend/components/indicators/IndicatorGrid.tsx`
2. Use CSS Grid with `grid-template-columns` for responsive layout
3. Use Tailwind responsive classes: `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4`
4. Apply gap via `gap-{sm|md|lg}` Tailwind classes
5. Implement transitions with `transition-all duration-200 ease-out`
6. Use `prefers-reduced-motion` media query for accessibility
7. Ensure `data-testid="indicator-grid"` attribute for testing
8. Add `role="grid"` and `aria-label` for accessibility
9. Each grid item should have `role="gridcell"`
10. Map indicators with unique `key={indicator.id}`

### CSS Grid Implementation Suggestion

```tsx
// Using Tailwind utility classes
const getGridCols = (columns: number) => {
  const colMap: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-3',
    4: 'grid-cols-4',
    5: 'grid-cols-5',
    6: 'grid-cols-6',
  };
  return colMap[columns] || 'grid-cols-1';
};

// Responsive classes
const gridClasses = cn(
  'grid',
  getGridCols(columns.mobile),
  columns.sm && `sm:${getGridCols(columns.sm)}`,
  columns.md && `md:${getGridCols(columns.md)}`,
  columns.lg && `lg:${getGridCols(columns.lg)}`,
  columns.xl && `xl:${getGridCols(columns.xl)}`,
  gap === 'sm' && 'gap-2',
  gap === 'md' && 'gap-4',
  gap === 'lg' && 'gap-6',
  'transition-all duration-200 ease-out',
  className
);
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
  },
  {
    id: 'visibility-score',
    icon: 'Eye',
    title: 'Visibility Score',
    value: '78%',
    description: 'Overall SEO visibility',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'competitor-analysis',
    icon: 'Users',
    title: 'Competitor Analysis',
    value: '5',
    description: 'Tracked competitors',
    status: 'neutral',
    href: '/comparison',
  },
  {
    id: 'technical-health',
    icon: 'Wrench',
    title: 'Technical Health',
    value: '85/100',
    description: 'Site audit score',
    status: 'warning',
    href: '/tools',
  },
  {
    id: 'backlink-profile',
    icon: 'Link',
    title: 'Backlink Profile',
    value: '1,234',
    description: 'Total backlinks',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'content-opportunities',
    icon: 'FileText',
    title: 'Content Opportunities',
    value: '23',
    description: 'Keyword gaps found',
    status: 'warning',
    href: '/dashboard',
  },
  {
    id: 'serp-features',
    icon: 'Star',
    title: 'SERP Features',
    value: '8',
    description: 'Featured snippets won',
    status: 'good',
    href: '/dashboard',
  },
  {
    id: 'page-speed',
    icon: 'Zap',
    title: 'Page Speed',
    value: '92/100',
    description: 'Performance score',
    status: 'good',
    href: '/tools',
  },
];

const defaultColumns = {
  mobile: 1,
  sm: 2,
  md: 3,
  lg: 4,
  xl: 4,
};

const customColumns = {
  mobile: 2,
  sm: 3,
  md: 4,
  lg: 5,
  xl: 6,
};
```

---

## Test Execution

```bash
# Run all IndicatorGrid tests
npm test -- IndicatorGrid.test.tsx

# Run with coverage
npm test -- --coverage IndicatorGrid.test.tsx

# Update snapshots
npm test -- -u IndicatorGrid.test.tsx

# Run specific test category
npm test -- -t "Responsive Breakpoints"
```

---

Created by: PreTester
Date: 2026-03-03
