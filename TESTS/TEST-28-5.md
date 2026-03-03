# TEST-28-5: IndicatorCard Component Tests

## Overview
Tests for the IndicatorCard component - a card displaying individual SEO indicators with icon, title, value, status, and hover interactions. Part of the Indicator Catalog (SPEC-028).

## Test File Location
`frontend/components/indicators/__tests__/IndicatorCard.test.tsx`

## Test Count: 95 tests

---

## Test Categories

### TypeScript Type Definitions (1 test)
| ID | Test | Description |
|----|------|-------------|
| TD-001 | Component props interface exists | Verifies typed props import correctly |

### Component Rendering (9 tests)
| ID | Test | Description |
|----|------|-------------|
| CR-001 | Renders without crashing | Basic mount test |
| CR-002 | Renders all required props | id, icon, title, value, description, status, href |
| CR-003 | Renders with data-testid attribute | Identifies card element |
| CR-004 | Renders icon via IndicatorIcon | Icon component used |
| CR-005 | Renders title text | Title displayed |
| CR-006 | Renders value text | Value displayed |
| CR-007 | Renders description text | Description displayed |
| CR-008 | Applies custom className | Class merging works |
| CR-009 | Renders as anchor/link | Clickable card |

### Status Indicator (8 tests)
| ID | Test | Description |
|----|------|-------------|
| SI-001 | 'good' status renders green badge | Success color |
| SI-002 | 'warning' status renders amber badge | Warning color |
| SI-003 | 'critical' status renders red badge | Error color |
| SI-004 | 'neutral' status renders gray badge | Neutral color |
| SI-005 | Status badge shows status text | Badge content |
| SI-006 | data-status attribute on card | DOM attribute |
| SI-007 | Good status icon (CheckCircle) | Success indicator |
| SI-008 | Critical status icon (AlertCircle) | Error indicator |

### Hover Interactions (7 tests)
| ID | Test | Description |
|----|------|-------------|
| HI-001 | Hover triggers scale animation | Scale on hover |
| HI-002 | Hover triggers glow effect | Box-shadow change |
| HI-003 | Hover shows tooltip | Tooltip component |
| HI-004 | Tooltip displays tooltip text | Custom tooltip prop |
| HI-005 | Tooltip displays description | Default tooltip |
| HI-006 | Mouse leave removes hover state | Animation out |
| HI-007 | Focus triggers hover styles | Keyboard accessible |

### Click Navigation (6 tests)
| ID | Test | Description |
|----|------|-------------|
| CN-001 | Click navigates to href | Link navigation |
| CN-002 | href prop sets link target | Correct URL |
| CN-003 | Keyboard Enter triggers navigation | Accessibility |
| CN-004 | Link has proper rel attributes | Security (noopener) |
| CN-005 | Internal link uses Next.js Link | Client-side navigation |
| CN-006 | Card is focusable | tabindex attribute |

### Loading State (Skeleton) (8 tests)
| ID | Test | Description |
|----|------|-------------|
| LS-001 | isLoading shows skeleton for icon | Icon placeholder |
| LS-002 | isLoading shows skeleton for title | Title placeholder |
| LS-003 | isLoading shows skeleton for value | Value placeholder |
| LS-004 | isLoading shows skeleton for description | Description placeholder |
| LS-005 | isLoading hides status badge | No badge during loading |
| LS-006 | isLoading disables click | Non-interactive |
| LS-007 | isLoading removes hover effects | No animations |
| LS-008 | data-loading attribute | DOM attribute |

### Responsive Design (8 tests)
| ID | Test | Description |
|----|------|-------------|
| RD-001 | Mobile layout (<640px) | Stacked layout |
| RD-002 | Tablet layout (640-1024px) | Side-by-side |
| RD-003 | Desktop layout (>1024px) | Full width |
| RD-004 | Icon size responsive | Smaller on mobile |
| RD-005 | Text size responsive | Readable on all sizes |
| RD-006 | Touch target size (44px min) | Mobile accessibility |
| RD-007 | Card min-height on mobile | Readable content |
| RD-008 | Card max-width constraint | Prevents overflow |

### Accessibility (9 tests)
| ID | Test | Description |
|----|------|-------------|
| AC-001 | Role is link or article | Semantic element |
| AC-002 | aria-label describes card | Accessible name |
| AC-003 | Status has aria-label | Status accessible |
| AC-004 | Keyboard focusable | Tab navigation |
| AC-005 | Focus visible indicator | Focus ring |
| AC-006 | High contrast mode support | Forced colors |
| AC-007 | Screen reader reads status | Status announced |
| AC-008 | Tooltip accessible | ARIA tooltip |
| AC-009 | Reduced motion preference | Respects prefers-reduced-motion |

### Badge Component Integration (6 tests)
| ID | Test | Description |
|----|------|-------------|
| BC-001 | Uses Badge component from shadcn | shadcn integration |
| BC-002 | Badge variant maps to status | Correct variant |
| BC-003 | Badge has status icon | Icon in badge |
| BC-004 | Badge is visually distinct | Color contrast |
| BC-005 | Badge text is readable | Font size |
| BC-006 | Badge dark mode styling | Dark theme |

### Tooltip Component Integration (5 tests)
| ID | Test | Description |
|----|------|-------------|
| TC-001 | Uses Tooltip component from shadcn | shadcn integration |
| TC-002 | Tooltip content is description | Default content |
| TC-003 | Tooltip content override | Custom tooltip prop |
| TC-004 | Tooltip delay before show | Reasonable delay |
| TC-005 | Tooltip dark mode styling | Dark theme |

### Edge Cases (10 tests)
| ID | Test | Description |
|----|------|-------------|
| EC-001 | Empty title | Graceful handling |
| EC-002 | Empty value | Shows placeholder |
| EC-003 | Empty description | Graceful handling |
| EC-004 | Very long title | Truncation |
| EC-005 | Very long value | Truncation |
| EC-006 | Very long description | Truncation |
| EC-007 | Null tooltip | No tooltip rendered |
| EC-008 | Invalid href | Safe fallback |
| EC-009 | Invalid status | Default to neutral |
| EC-010 | Rapid hover enter/leave | No flickering |

### Module Exports (3 tests)
| ID | Test | Description |
|----|------|-------------|
| ME-001 | Exported from indicators/index | Barrel export |
| ME-002 | Named export available | Direct import |
| ME-003 | IndicatorCardProps type exported | TypeScript types |

### Integration with Dependencies (8 tests)
| ID | Test | Description |
|----|------|-------------|
| ID-001 | Uses IndicatorIcon for icon | TASK-28-4 integration |
| ID-002 | Passes icon prop to IndicatorIcon | Correct prop flow |
| ID-003 | Uses Badge component | TASK-28-3 integration |
| ID-004 | Uses Tooltip component | TASK-28-3 integration |
| ID-005 | Uses Skeleton component | TASK-28-3 integration |
| ID-006 | CSS variables for colors | TASK-28-1 integration |
| ID-007 | Dark mode toggle works | Theme integration |
| ID-008 | All 8 indicator types render | Full coverage |

### Snapshots (7 tests)
| ID | Test | Description |
|----|------|-------------|
| SN-001 | Good status card snapshot | Baseline |
| SN-002 | Warning status card snapshot | Baseline |
| SN-003 | Critical status card snapshot | Baseline |
| SN-004 | Neutral status card snapshot | Baseline |
| SN-005 | Loading state snapshot | Skeleton |
| SN-006 | Mobile layout snapshot | Responsive |
| SN-007 | Desktop layout snapshot | Responsive |

---

## Component Interface

```typescript
interface IndicatorCardProps {
  id: string;                              // Unique identifier
  icon: LucideIconName | React.ReactNode;  // Lucide icon name or custom SVG
  title: string;                           // Indicator title
  value: string | number;                  // Current value
  description: string;                     // Description text
  status: 'good' | 'warning' | 'critical' | 'neutral';  // Status indicator
  href: string;                            // Link destination
  tooltip?: string;                        // Optional tooltip override
  isLoading?: boolean;                     // Loading state
  className?: string;                      // Additional CSS classes
}
```

## Status Color Mappings

| Status | Badge Color | Icon | Description |
|--------|-------------|------|-------------|
| `good` | Green (emerald) | CheckCircle | Positive/healthy indicator |
| `warning` | Amber | AlertTriangle | Needs attention |
| `critical` | Red (destructive) | AlertCircle | Urgent issue |
| `neutral` | Gray | Minus | No data or N/A |

## Indicator Types (Initial Set)

| Indicator | Icon | Description | Link |
|-----------|------|-------------|------|
| Keyword Rankings | TrendingUp | Track SERP positions | /dashboard |
| Visibility Score | Eye | Overall SEO visibility | /dashboard |
| Competitor Analysis | Users | Compare with competitors | /comparison |
| Technical Health | Wrench | Site audit score | /tools |
| Backlink Profile | Link | Link analysis | /dashboard |
| Content Opportunities | FileText | Keyword gaps | /dashboard |
| SERP Features | Star | Featured snippets, etc. | /dashboard |
| Page Speed | Zap | Performance metrics | /tools |

---

## Acceptance Criteria Coverage

| Criteria | Tests |
|----------|-------|
| AC-001: Card displays icon, title, value, description | CR-001 to CR-009 |
| AC-002: Status indicator with color coding | SI-001 to SI-008 |
| AC-003: Hover animations (scale, glow) | HI-001 to HI-007 |
| AC-004: Click navigates to href | CN-001 to CN-006 |
| AC-005: Responsive design | RD-001 to RD-008 |
| AC-006: Skeleton loading state | LS-001 to LS-008 |
| AC-007: Badge component for status | BC-001 to BC-006 |
| AC-008: Tooltip on hover | TC-001 to TC-005 |
| AC-009: TypeScript types defined | TD-001, ME-003 |

---

## Test Patterns Used

1. **Mocked Dependencies**: IndicatorIcon, Badge, Tooltip, Skeleton mocked
2. **Matrix Testing**: All status variants tested
3. **Responsive Testing**: Viewport-based tests
4. **Accessibility First**: ARIA and keyboard navigation tested
5. **Integration Testing**: Dependency component usage verified
6. **Snapshot Regression**: Visual baseline for future changes

---

## Dependencies

### Task Dependencies
- TASK-28-1 (CSS Variables Enhancement) - Color variables
- TASK-28-3 (shadcn/ui Components) - Badge, Tooltip, Skeleton
- TASK-28-4 (IndicatorIcon Wrapper) - Icon rendering

### Package Dependencies
- `lucide-react` - Icon library
- `next/link` - Client-side navigation
- `@/lib/utils` - cn() utility
- `@/components/ui/badge` - Badge component
- `@/components/ui/tooltip` - Tooltip component
- `@/components/ui/skeleton` - Skeleton component
- `@/components/indicators/IndicatorIcon` - Icon wrapper

---

## Notes for Developer

1. Create `frontend/components/indicators/IndicatorCard.tsx`
2. Use `IndicatorIcon` component for icon rendering
3. Use `Badge` from shadcn for status display
4. Use `Tooltip` from shadcn for hover info
5. Use `Skeleton` from shadcn for loading state
6. Implement hover animations with Tailwind `hover:scale-[1.02]` and `hover:shadow-lg`
7. Use Next.js `Link` component for internal navigation
8. Ensure `data-testid`, `data-status`, `data-loading` attributes for testing
9. Apply `focus-visible:ring-2` for keyboard focus indicator
10. Respect `prefers-reduced-motion` for animations

---

## Mock Data

```typescript
const mockIndicatorCard = {
  id: 'keyword-rankings',
  icon: 'TrendingUp',
  title: 'Keyword Rankings',
  value: '142',
  description: 'Keywords ranking in top 100',
  status: 'good' as const,
  href: '/dashboard',
  tooltip: 'Track your keyword positions in SERPs',
};

const allStatusVariants = ['good', 'warning', 'critical', 'neutral'] as const;

const allIndicatorTypes = [
  { id: 'keyword-rankings', icon: 'TrendingUp', title: 'Keyword Rankings' },
  { id: 'visibility-score', icon: 'Eye', title: 'Visibility Score' },
  { id: 'competitor-analysis', icon: 'Users', title: 'Competitor Analysis' },
  { id: 'technical-health', icon: 'Wrench', title: 'Technical Health' },
  { id: 'backlink-profile', icon: 'Link', title: 'Backlink Profile' },
  { id: 'content-opportunities', icon: 'FileText', title: 'Content Opportunities' },
  { id: 'serp-features', icon: 'Star', title: 'SERP Features' },
  { id: 'page-speed', icon: 'Zap', title: 'Page Speed' },
];
```

---

## Test Execution

```bash
# Run all IndicatorCard tests
npm test -- IndicatorCard.test.tsx

# Run with coverage
npm test -- --coverage IndicatorCard.test.tsx

# Update snapshots
npm test -- -u IndicatorCard.test.tsx

# Run specific test category
npm test -- -t "Status Indicator"
```

---

Created by: PreTester
Date: 2026-03-03
