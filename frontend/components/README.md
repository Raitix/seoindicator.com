# Components — SEOIndicator UI Library

This directory contains all reusable React components for SEOIndicator, organized into categories.

---

## Directory Structure

```
frontend/components/
├── indicators/          # SEO indicator components
│   ├── IndicatorIcon.tsx
│   ├── IndicatorCard.tsx
│   ├── IndicatorGrid.tsx
│   ├── IndicatorCatalog.tsx
│   └── icons.ts
├── ui/                  # shadcn/ui base components
│   ├── button.tsx
│   ├── card.tsx
│   ├── badge.tsx
│   ├── tooltip.tsx
│   ├── skeleton.tsx
│   ├── progress.tsx
│   ├── tabs.tsx
│   ├── sheet.tsx
│   └── ...
└── README.md            # This file
```

---

## Indicator Components

### IndicatorIcon

A reusable icon wrapper for SEO indicators with consistent styling and accessibility support.

**Location:** `indicators/IndicatorIcon.tsx`

**Props:**

```typescript
interface IndicatorIconProps {
  /** Lucide icon name from the registry */
  icon?: LucideIconName;
  /** Custom SVG element to render instead of Lucide icon */
  customIcon?: React.ReactNode;
  /** Size variant */
  size?: 'sm' | 'md' | 'lg';
  /** Color variant */
  color?: 'default' | 'primary' | 'success' | 'warning' | 'error';
  /** Accessible label for screen readers */
  'aria-label'?: string;
  /** Additional CSS classes */
  className?: string;
}
```

**Size Variants:**

| Size | Dimensions | Stroke Width |
|------|------------|--------------|
| `sm` | 16x16px | 1.5 |
| `md` | 24x24px | 2 |
| `lg` | 32x32px | 2.5 |

**Color Variants:**

| Color | Tailwind Class |
|-------|----------------|
| `default` | `text-current` |
| `primary` | `text-primary` |
| `success` | `text-emerald-600 dark:text-emerald-400` |
| `warning` | `text-amber-500 dark:text-amber-400` |
| `error` | `text-destructive` |

**Examples:**

```tsx
import { IndicatorIcon } from '@/components/indicators';

// Basic Lucide icon
<IndicatorIcon icon="TrendingUp" />

// With size and color
<IndicatorIcon icon="CheckCircle" size="lg" color="success" />

// Custom SVG
<IndicatorIcon 
  customIcon={<MyCustomSvg />} 
  size="sm" 
/>

// With accessibility label
<IndicatorIcon 
  icon="Search" 
  aria-label="Search indicator" 
/>
```

---

### IndicatorCard

A card component for displaying SEO indicators with status, value, and navigation.

**Location:** `indicators/IndicatorCard.tsx`

**Props:**

```typescript
interface IndicatorCardProps {
  /** Unique identifier for the card */
  id: string;
  /** Lucide icon name or custom React node */
  icon: LucideIconName | React.ReactNode;
  /** Indicator title */
  title: string;
  /** Current value */
  value: string | number;
  /** Description text */
  description: string;
  /** Status indicator */
  status: 'good' | 'warning' | 'critical' | 'neutral';
  /** Link destination */
  href: string;
  /** Optional tooltip override */
  tooltip?: string;
  /** Loading state */
  isLoading?: boolean;
  /** Additional CSS classes */
  className?: string;
}
```

**Status Variants:**

| Status | Icon | Badge Variant | Color |
|--------|------|---------------|-------|
| `good` | CheckCircle | `default` | Emerald |
| `warning` | AlertTriangle | `secondary` | Amber |
| `critical` | AlertCircle | `destructive` | Red |
| `neutral` | Minus | `outline` | Muted |

**Examples:**

```tsx
import { IndicatorCard } from '@/components/indicators';

// Basic usage
<IndicatorCard
  id="keyword-rankings"
  icon="TrendingUp"
  title="Keyword Rankings"
  value="142"
  description="Keywords ranking in top 100"
  status="good"
  href="/dashboard/keywords"
/>

// With loading state
<IndicatorCard
  id="visibility-score"
  icon="Eye"
  title="Visibility Score"
  value="--"
  description="Overall SEO visibility"
  status="neutral"
  href="/dashboard/visibility"
  isLoading={true}
/>

// Critical status
<IndicatorCard
  id="technical-health"
  icon="Wrench"
  title="Technical Health"
  value="45%"
  description="Site audit score - needs attention"
  status="critical"
  href="/dashboard/technical"
/>
```

---

### IndicatorGrid

A responsive grid layout for displaying multiple indicator cards.

**Location:** `indicators/IndicatorGrid.tsx`

**Features:**
- Responsive columns (1 → 2 → 3 → 4)
- Automatic layout optimization
- Loading state support

**Example:**

```tsx
import { IndicatorGrid, IndicatorCard } from '@/components/indicators';

<IndicatorGrid>
  <IndicatorCard {...card1Props} />
  <IndicatorCard {...card2Props} />
  <IndicatorCard {...card3Props} />
  <IndicatorCard {...card4Props} />
</IndicatorGrid>
```

---

### IndicatorCatalog

Homepage component displaying all 8 SEO indicators in a branded layout.

**Location:** `indicators/IndicatorCatalog.tsx`

**Features:**
- Displays all 8 indicator types
- Responsive grid layout
- Call-to-action integration
- Accessible structure

**Indicator Types:**

| Indicator | Icon | Description |
|-----------|------|-------------|
| Keyword Rankings | TrendingUp | Track SERP positions |
| Visibility Score | Eye | Overall SEO visibility |
| Competitor Analysis | Users | Compare with competitors |
| Technical Health | Wrench | Site audit score |
| Backlink Profile | Link | Link analysis |
| Content Opportunities | FileText | Keyword gaps |
| SERP Features | Star | Featured snippets, etc. |
| Page Speed | Zap | Performance metrics |

---

## Icon Registry

### Available Icons

The `icons.ts` file exports a centralized icon registry:

```typescript
import { INDICATOR_ICONS, IndicatorIconName } from '@/components/indicators';

// Get icon component
const Icon = INDICATOR_ICONS['keyword-rankings'];

// Type-safe indicator names
const name: IndicatorIconName = 'visibility-score';
```

**Indicator-to-Icon Mapping:**

| Indicator Name | Lucide Icon |
|----------------|-------------|
| `keyword-rankings` | TrendingUp |
| `visibility-score` | Eye |
| `competitor-analysis` | Users |
| `technical-health` | Wrench |
| `backlink-profile` | Link |
| `content-opportunities` | FileText |
| `serp-features` | Star |
| `page-speed` | Zap |

### Lucide Icons

All Lucide icons are available through the `IndicatorIcon` component:

```tsx
// Available icons in registry
const iconRegistry = {
  Activity,
  AlertCircle,
  AlertTriangle,
  CheckCircle,
  ChevronDown,
  Eye,
  FileText,
  HelpCircle,
  Info,
  Link2,
  Search,
  Star,
  TrendingUp,
  Users,
  Warning: AlertTriangle,
  Wrench,
  XCircle,
  Zap,
};
```

---

## UI Components (shadcn/ui)

### Skeleton

Loading placeholder component with pulse animation.

**Location:** `ui/skeleton.tsx`

**Example:**

```tsx
import { Skeleton } from '@/components/ui/skeleton';

// Basic skeleton
<Skeleton className="h-4 w-[250px]" />

// Card skeleton
<div className="flex flex-col gap-3">
  <Skeleton className="h-12 w-12 rounded-full" />
  <Skeleton className="h-4 w-24" />
  <Skeleton className="h-4 w-full" />
</div>
```

---

### Progress

Linear progress indicator with value clamping.

**Location:** `ui/progress.tsx`, `ui/LinearProgress.tsx`, `ui/CircularProgress.tsx`

**Example:**

```tsx
import { Progress } from '@/components/ui/progress';

// Determinate progress
<Progress value={75} />

// CircularProgress
import { CircularProgress } from '@/components/ui/CircularProgress';
<CircularProgress value={75} size={120} />
```

---

### Badge

Status label component with variants.

**Location:** `ui/badge.tsx`

**Variants:**

| Variant | Use Case |
|---------|----------|
| `default` | Primary status |
| `secondary` | Secondary status |
| `destructive` | Error/critical |
| `outline` | Neutral |

**Example:**

```tsx
import { Badge } from '@/components/ui/badge';

<Badge>Default</Badge>
<Badge variant="secondary">Secondary</Badge>
<Badge variant="destructive">Error</Badge>
<Badge variant="outline">Neutral</Badge>
```

---

### Tooltip

Hover information display.

**Location:** `ui/tooltip.tsx`

**Example:**

```tsx
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';

<Tooltip>
  <TooltipTrigger asChild>
    <Button>Hover me</Button>
  </TooltipTrigger>
  <TooltipContent>
    <p>Tooltip content</p>
  </TooltipContent>
</Tooltip>
```

---

### Tabs

Content organization component.

**Location:** `ui/tabs.tsx`

**Example:**

```tsx
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

<Tabs defaultValue="overview">
  <TabsList>
    <TabsTrigger value="overview">Overview</TabsTrigger>
    <TabsTrigger value="details">Details</TabsTrigger>
  </TabsList>
  <TabsContent value="overview">Overview content</TabsContent>
  <TabsContent value="details">Details content</TabsContent>
</Tabs>
```

---

### Sheet

Mobile navigation drawer.

**Location:** `ui/sheet.tsx`

**Example:**

```tsx
import {
  Sheet,
  SheetContent,
  SheetTrigger,
} from '@/components/ui/sheet';

<Sheet>
  <SheetTrigger asChild>
    <Button variant="ghost">Menu</Button>
  </SheetTrigger>
  <SheetContent side="left">
    <nav>Navigation items</nav>
  </SheetContent>
</Sheet>
```

---

## Accessibility

### ARIA Labels

All interactive components support `aria-label`:

```tsx
<IndicatorIcon icon="Search" aria-label="Search" />
<Button aria-label="Close menu">
  <X className="h-4 w-4" />
</Button>
```

### Keyboard Navigation

| Component | Keys |
|-----------|------|
| Button | Tab, Enter, Space |
| Tabs | Tab, Arrow keys |
| Sheet | Tab, Escape |
| Dropdown | Tab, Arrow keys, Enter, Escape |

### Focus Management

All interactive elements have visible focus indicators:

```tsx
className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
```

### Screen Reader Support

- Decorative icons use `aria-hidden="true"`
- Meaningful icons use `role="img"` with `aria-label`
- Dynamic content uses `aria-live` regions

---

## Usage Patterns

### Creating a New Indicator

```tsx
import { IndicatorCard } from '@/components/indicators';

function MyIndicator() {
  return (
    <IndicatorCard
      id="my-indicator"
      icon="Activity"
      title="My Indicator"
      value="100"
      description="Custom indicator description"
      status="good"
      href="/my-indicator"
    />
  );
}
```

### Loading State

```tsx
import { IndicatorCard } from '@/components/indicators';
import { Skeleton } from '@/components/ui/skeleton';

function IndicatorWithLoading({ isLoading, data }) {
  if (isLoading) {
    return <IndicatorCard {...data} isLoading={true} />;
  }
  
  return <IndicatorCard {...data} />;
}
```

### Status-Based Coloring

```tsx
function getStatusFromScore(score: number): 'good' | 'warning' | 'critical' {
  if (score >= 80) return 'good';
  if (score >= 50) return 'warning';
  return 'critical';
}

<IndicatorCard
  status={getStatusFromScore(analysisScore)}
  // ...
/>
```

---

## Related Documentation

- **DESIGN-SYSTEM.md** — Design tokens and guidelines
- **DOCUMENTATION.md** — Feature documentation
- **frontend/app/globals.css** — CSS variable definitions

---

Last updated: 2026-03-07
