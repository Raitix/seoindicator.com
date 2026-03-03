# TEST-28-5: IndicatorCard Component Tests

## Overview
Comprehensive test suite for the IndicatorCard component that displays individual SEO indicators with icon, title, value, status, and hover interactions.

## Component Under Test
- **File:** `frontend/components/indicators/IndicatorCard.tsx`
- **Task:** TASK-28-5
- **Spec:** SPEC-028

---

## Test Categories

### 1. Component Rendering (CR) - 12 tests

| ID | Test | Description |
|----|------|-------------|
| CR-001 | Renders with all required props | Card displays icon, title, value, description |
| CR-002 | Renders with status good | Green color coding applied |
| CR-003 | Renders with status warning | Yellow/amber color coding applied |
| CR-004 | Renders with status critical | Red color coding applied |
| CR-005 | Renders with status neutral | Default/gray color coding applied |
| CR-006 | Renders with Lucide icon | Icon component renders correctly |
| CR-007 | Renders with custom SVG icon | Custom icon renders correctly |
| CR-008 | Renders with tooltip | Tooltip wrapper present when tooltip prop provided |
| CR-009 | Renders without tooltip | No tooltip wrapper when tooltip prop omitted |
| CR-010 | Renders with badge | Status badge displays status |
| CR-011 | Renders with all 8 indicator types | Keyword Rankings, Visibility Score, Competitor Analysis, Technical Health, Backlink Profile, Content Opportunities, SERP Features, Page Speed |
| CR-012 | Renders with numeric value | Numbers display correctly |

### 2. Loading State (LS) - 5 tests

| ID | Test | Description |
|----|------|-------------|
| LS-001 | Shows skeleton when isLoading=true | Skeleton replaces content |
| LS-002 | Hides content when loading | Title, value, description hidden |
| LS-003 | Shows content after loading | Content appears when isLoading=false |
| LS-004 | Skeleton matches card dimensions | Skeleton has same layout as loaded card |
| LS-005 | Loading state transitions smoothly | No layout shift during transition |

### 3. Interactions (IN) - 8 tests

| ID | Test | Description |
|----|------|-------------|
| IN-001 | Click navigates to href | Router.push called with correct URL |
| IN-002 | Hover shows tooltip | Tooltip content visible on hover |
| IN-003 | Hover applies scale animation | Card scales up on hover |
| IN-004 | Hover applies glow effect | Subtle glow/shadow on hover |
| IN-005 | Keyboard navigation works | Tab + Enter triggers navigation |
| IN-006 | Click is keyboard accessible | Enter/Space triggers action |
| IN-007 | Focus ring is visible | Clear focus indicator for accessibility |
| IN-008 | Mouse leave removes hover effects | Scale/glow reset on mouse leave |

### 4. Responsive Design (RD) - 6 tests

| ID | Test | Description |
|----|------|-------------|
| RD-001 | Mobile layout (< 640px) | Card stacks vertically, full width |
| RD-002 | Tablet layout (640-1024px) | 2-column grid possible |
| RD-003 | Desktop layout (> 1024px) | 3-4 column grid, side-by-side |
| RD-004 | Touch targets are 44x44px min | Mobile tap targets accessible |
| RD-005 | Text remains readable at all sizes | No text overflow or truncation |
| RD-006 | Icon scales appropriately | Icon size responsive to container |

### 5. Status Variants (SV) - 8 tests

| ID | Test | Description |
|----|------|-------------|
| SV-001 | good status color | Green badge, success styling |
| SV-002 | warning status color | Yellow/amber badge, warning styling |
| SV-003 | critical status color | Red badge, error styling |
| SV-004 | neutral status color | Gray badge, default styling |
| SV-005 | Status badge text content | Badge shows status label |
| SV-006 | Status affects icon color | Icon color matches status |
| SV-007 | Status affects border/border | Visual status indicator |
| SV-008 | All statuses have same layout | Consistent card structure |

### 6. Accessibility (AC) - 8 tests

| ID | Test | Description |
|----|------|-------------|
| AC-001 | Has role="button" | Card is interactive element |
| AC-002 | Has aria-label | Descriptive label for screen readers |
| AC-003 | Has aria-describedby | Links to description element |
| AC-004 | Tabindex="0" | Card is focusable |
| AC-045 | Keyboard Enter triggers click | Enter key activates card |
| AC-006 | Keyboard Space triggers click | Space key activates card |
| AC-007 | Focus indicator visible | Clear focus ring |
| AC-008 | Status badge has aria-label | Status readable by screen readers |

### 7. TypeScript Types (TT) - 5 tests

| ID | Test | Description |
|----|------|-------------|
| TT-001 | id prop is required string | TypeScript enforces required prop |
| TT-002 | icon prop accepts LucideIconName | String icon name accepted |
| TT-003 | icon prop accepts ReactNode | Custom SVG accepted |
| TT-004 | status prop is union type | Only valid statuses accepted |
| TT-005 | Optional props handled | isLoading, tooltip optional |

### 8. Edge Cases (EC) - 8 tests

| ID | Test | Description |
|----|------|-------------|
| EC-001 | Long title truncates gracefully | Text overflow handled |
| EC-002 | Long description truncates | Multi-line description limited |
| EC-003 | Very long value handled | Large numbers/strings display |
| EC-004 | Empty string value | Empty value renders without error |
| EC-005 | null icon renders placeholder | Fallback when icon is null |
| EC-006 | Special characters in title | Unicode, emoji handled |
| EC-007 | Rapid hover on/off | No animation stutter |
| EC-008 | Multiple cards in grid | Layout stable with many cards |

### 9. Integration (IG) - 6 tests

| ID | Test | Description |
|----|------|-------------|
| IG-001 | Badge component renders | shadcn Badge displays status |
| IG-002 | Tooltip component renders | shadcn Tooltip shows on hover |
| IG-003 | Skeleton component renders | shadcn Skeleton in loading state |
| IG-004 | IndicatorIcon renders | Icon wrapper displays icon |
| IG-005 | Dark theme applied | Card styles in dark mode |
| IG-006 | Works in IndicatorGrid | Card integrates with grid layout |

### 10. Snapshots (SN) - 4 tests

| ID | Test | Description |
|----|------|-------------|
| SN-001 | Default card snapshot | Baseline for visual regression |
| SN-002 | Loading card snapshot | Skeleton state snapshot |
| SN-003 | All status variants snapshot | good/warning/critical/neutral |
| SN-004 | Dark mode snapshot | Dark theme applied |

---

## Test Summary

| Category | Tests | Priority |
|----------|-------|----------|
| Component Rendering | 12 | High |
| Loading State | 5 | High |
| Interactions | 8 | High |
| Responsive Design | 6 | Medium |
| Status Variants | 8 | High |
| Accessibility | 8 | High |
| TypeScript Types | 5 | Medium |
| Edge Cases | 8 | Medium |
| Integration | 6 | Medium |
| Snapshots | 4 | Low |
| **Total** | **70** | |

---

## Mock Dependencies

The following dependencies should be mocked in tests:

### Lucide Icons (Partial Mock)
```typescript
jest.mock('lucide-react', () => ({
  TrendingUp: () => <svg data-testid="icon-trending-up" />,
  Eye: () => <svg data-testid="icon-eye" />,
  Users: () => <svg data-testid="icon-users" />,
  Wrench: () => <svg data-testid="icon-wrench" />,
  Link: () => <svg data-testid="icon-link" />,
  FileText: () => <svg data-testid="icon-file-text" />,
  Star: () => <svg data-testid="icon-star" />,
  Zap: () => <svg data-testid="icon-zap" />,
}));
```

### Next.js Router
```typescript
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: jest.fn(),
  }),
}));
```

### shadcn/ui Components (Mock or Real)
- Badge: Real component if installed, mock otherwise
- Tooltip: Mock with TooltipProvider wrapper
- Skeleton: Mock with animated placeholder

### IndicatorIcon (Mock)
```typescript
jest.mock('@/components/indicators/IndicatorIcon', () => ({
  IndicatorIcon: ({ icon, size, color }) => (
    <div data-testid="indicator-icon" data-icon={icon} data-size={size} data-color={color} />
  ),
}));
```

---

## Test Data

### Default Props
```typescript
const defaultProps: IndicatorCardProps = {
  id: 'keyword-rankings',
  icon: 'TrendingUp',
  title: 'Keyword Rankings',
  value: '124',
  description: 'Track SERP positions for your target keywords',
  status: 'good',
  href: '/dashboard',
};
```

### All Indicator Types
```typescript
const indicatorTypes = [
  { id: 'keyword-rankings', icon: 'TrendingUp', title: 'Keyword Rankings', href: '/dashboard' },
  { id: 'visibility-score', icon: 'Eye', title: 'Visibility Score', href: '/dashboard' },
  { id: 'competitor-analysis', icon: 'Users', title: 'Competitor Analysis', href: '/comparison' },
  { id: 'technical-health', icon: 'Wrench', title: 'Technical Health', href: '/tools' },
  { id: 'backlink-profile', icon: 'Link', title: 'Backlink Profile', href: '/dashboard' },
  { id: 'content-opportunities', icon: 'FileText', title: 'Content Opportunities', href: '/dashboard' },
  { id: 'serp-features', icon: 'Star', title: 'SERP Features', href: '/dashboard' },
  { id: 'page-speed', icon: 'Zap', title: 'Page Speed', href: '/tools' },
];
```

### Status Variants
```typescript
const statuses: Array<'good' | 'warning' | 'critical' | 'neutral'> = [
  'good', 'warning', 'critical', 'neutral'
];
```

---

## Acceptance Criteria Coverage

| Criteria | Test IDs |
|----------|----------|
| Card displays icon, title, value, description | CR-001, CR-006, CR-007, CR-012 |
| Status indicator with color coding | SV-001 to SV-008 |
| Hover animations (scale, glow) | IN-003, IN-004, IN-008 |
| Click navigates to dashboard section | IN-001, IN-005, IN-006 |
| Responsive design | RD-001 to RD-006 |
| Skeleton loading state | LS-001 to LS-005 |
| Badge component for status | SV-001 to SV-005, IG-001 |
| Tooltip on hover | IN-002, CR-008, IG-002 |
| TypeScript types defined | TT-001 to TT-005 |

---

## Implementation Notes for Developer

### Component Structure
```typescript
interface IndicatorCardProps {
  id: string;
  icon: LucideIconName | React.ReactNode;
  title: string;
  value: string | number;
  description: string;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  href: string;
  tooltip?: string;
  isLoading?: boolean;
}
```

### Key Implementation Points

1. **Use class-variance-authority (cva)** for status variants:
```typescript
const cardVariants = cva('...', {
  variants: {
    status: {
      good: 'border-green-500/20 bg-green-500/5',
      warning: 'border-amber-500/20 bg-amber-500/5',
      critical: 'border-red-500/20 bg-red-500/5',
      neutral: 'border-slate-500/20 bg-slate-500/5',
    },
  },
});
```

2. **Hover animations** via Tailwind:
```typescript
className="... hover:scale-105 hover:shadow-lg transition-all duration-200"
```

3. **Skeleton layout** matches content:
```typescript
{isLoading ? (
  <div className="space-y-2">
    <Skeleton className="h-6 w-6" /> {/* Icon */}
    <Skeleton className="h-4 w-24" /> {/* Title */}
    <Skeleton className="h-8 w-16" /> {/* Value */}
  </div>
) : (
  <ActualContent />
)}
```

4. **Accessibility** with proper roles:
```typescript
<div
  role="button"
  tabIndex={0}
  aria-label={`${title}: ${value}`}
  onKeyDown={(e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      onClick();
    }
  }}
>
```

5. **Tooltip integration**:
```typescript
{tooltip ? (
  <Tooltip>
    <TooltipTrigger>{content}</TooltipTrigger>
    <TooltipContent>{tooltip}</TooltipContent>
  </Tooltip>
) : (
  content
)}
```

---

## Dependencies

- TASK-28-3 (shadcn/ui Components: badge, tooltip, skeleton)
- TASK-28-4 (IndicatorIcon Wrapper)

---

*Created by PreTester for TASK-28-5*
*Status: Tests Coverage In Progress*
