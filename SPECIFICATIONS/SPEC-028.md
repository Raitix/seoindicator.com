# SPEC-028: Modern UI/UX + Indicator Catalog

## STATUS
`Tests Coverage In Progress`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: [FEAT-028](../FEATURES/FEAT-028.md)

---

## Overview

Redesign the UI with a polished dark theme, responsive layout, and an engaging indicator catalog on the homepage. Create custom indicator icons and improve loading states for a professional, modern user experience.

---

## Current State Analysis

### Existing Infrastructure
- **Frontend:** Next.js 15 with App Router
- **Styling:** Tailwind CSS with CSS variables (shadcn/ui new-york style)
- **Components:** shadcn/ui (button, card, dialog, input, label, textarea, toast-banner)
- **Icons:** Lucide React icons
- **Theme:** Dark mode CSS variables already defined in `globals.css`
- **Current Homepage:** Basic hero + features grid with slate-900/800 gradient background

### Gaps Identified
1. No systematic design system (colors, spacing, typography defined but not documented)
2. Homepage lacks indicator catalog (key SEO metrics display)
3. No custom icon set for indicators
4. Loading states are basic (no skeleton loaders, progress indicators)
5. Mobile responsiveness needs improvement
6. No theme toggle (dark mode only currently)

---

## Technical Specification

### 1. Design System Foundation

#### Color System Enhancement
Extend existing CSS variables with semantic colors:

```css
:root {
  /* Brand Colors */
  --brand-primary: oklch(0.65 0.2 160);      /* Emerald green */
  --brand-secondary: oklch(0.55 0.15 200);   /* Teal accent */
  --brand-accent: oklch(0.75 0.18 50);       /* Orange accent */
  
  /* Semantic Colors */
  --success: oklch(0.65 0.2 145);
  --warning: oklch(0.75 0.18 85);
  --error: oklch(0.6 0.22 25);
  --info: oklch(0.6 0.15 230);
  
  /* Surface Layers (for depth) */
  --surface-0: oklch(0.145 0 0);   /* Base */
  --surface-1: oklch(0.18 0 0);    /* Elevated */
  --surface-2: oklch(0.22 0 0);    /* Cards */
  --surface-3: oklch(0.26 0 0);    /* Modals */
}
```

#### Typography Scale
```
Display: 4xl/5xl (hero headings)
H1: 3xl (page titles)
H2: 2xl (section titles)
H3: xl (card titles)
Body: base (paragraphs)
Small: sm (captions, metadata)
```

#### Spacing System
```
xs: 0.25rem (4px)
sm: 0.5rem (8px)
md: 1rem (16px)
lg: 1.5rem (24px)
xl: 2rem (32px)
2xl: 3rem (48px)
```

### 2. Responsive Layout Architecture

#### Breakpoint Strategy (Tailwind defaults)
```
sm: 640px   - Mobile landscape
md: 768px   - Tablet portrait
lg: 1024px  - Tablet landscape / Small desktop
xl: 1280px  - Desktop
2xl: 1536px - Large desktop
```

#### Layout Patterns
- **Mobile-First:** Base styles for mobile, use breakpoints for larger screens
- **Container:** Max-width 1280px, centered with responsive padding
- **Grid:** 12-column grid system with responsive gaps
- **Stack:** Vertical spacing component for consistent layouts

### 3. Homepage Indicator Catalog

#### Component Structure
```
components/
├── indicators/
│   ├── IndicatorCatalog.tsx      # Main catalog container
│   ├── IndicatorCard.tsx         # Individual indicator card
│   ├── IndicatorIcon.tsx         # Icon wrapper with custom icons
│   └── IndicatorGrid.tsx         # Responsive grid layout
```

#### Indicator Types (Initial Set)
| Indicator | Icon | Description | Link |
|-----------|------|-------------|------|
| Keyword Rankings | 📈 | Track SERP positions | /dashboard |
| Visibility Score | 👁️ | Overall SEO visibility | /dashboard |
| Competitor Analysis | 🎯 | Compare with competitors | /comparison |
| Technical Health | 🔧 | Site audit score | /tools |
| Backlink Profile | 🔗 | Link analysis | /dashboard |
| Content Opportunities | 💡 | Keyword gaps | /dashboard |
| SERP Features | ⭐ | Featured snippets, etc. | /dashboard |
| Page Speed | ⚡ | Performance metrics | /tools |

#### Interactive Features
- Hover animations (scale, glow)
- Click to navigate to relevant dashboard section
- Real-time value updates (connected to API)
- Status indicators (good/warning/critical)

### 4. Custom Icon Set

#### Design Approach
- Consistent 24x24 viewBox
- 2px stroke width for consistency
- Rounded corners (1px radius)
- Single color (inherits from text color)

#### Icon Categories
1. **Navigation:** Dashboard, Projects, Keywords, Reports, Settings
2. **Indicators:** Rankings, Visibility, Traffic, Backlinks, Technical
3. **Actions:** Add, Edit, Delete, Export, Filter, Search
4. **Status:** Success, Warning, Error, Loading, Info

#### Implementation Options
- **Option A:** Custom SVG components (full control, no dependencies)
- **Option B:** Lucide icons with custom styling (faster, less code)
- **Recommendation:** Start with Option B (Lucide), create custom icons as needed

### 5. Loading States & UX

#### Skeleton Loaders
```
components/
├── ui/
│   ├── skeleton.tsx           # Base skeleton component
│   └── skeletons/
│       ├── CardSkeleton.tsx
│       ├── TableSkeleton.tsx
│       └── ChartSkeleton.tsx
```

#### Progress Indicators
- **Linear:** Top-of-page progress bar for page loads
- **Circular:** Button loading states
- **Skeleton:** Content placeholders during data fetch
- **Pulse:** Status indicators (e.g., "Checking...")

#### Loading UX Patterns
1. **Optimistic Updates:** Show expected result before API response
2. **Progressive Loading:** Load critical content first, enhance later
3. **Error Boundaries:** Graceful error display with retry option
4. **Empty States:** Helpful messaging when no data available

### 6. Component Library Extensions

#### New Components Needed
| Component | Purpose | Priority |
|-----------|---------|----------|
| Skeleton | Loading placeholders | High |
| Progress | Linear/circular progress | High |
| Badge | Status labels | Medium |
| Tooltip | Hover info | Medium |
| Avatar | User profile images | Low |
| Dropdown | Navigation menus | Medium |
| Sheet | Mobile navigation drawer | Medium |
| Tabs | Content organization | Medium |
| Separator | Visual division | Low |

#### shadcn/ui Components to Add
```bash
npx shadcn@latest add skeleton
npx shadcn@latest add progress
npx shadcn@latest add badge
npx shadcn@latest add tooltip
npx shadcn@latest add avatar
npx shadcn@latest add dropdown-menu
npx shadcn@latest add sheet
npx shadcn@latest add tabs
npx shadcn@latest add separator
```

### 7. Responsive Navigation

#### Desktop Navigation
- Horizontal header with logo + nav links + user menu
- Persistent across all pages
- Active state indicator

#### Mobile Navigation
- Hamburger menu → Sheet component (slide-in drawer)
- Full-screen menu overlay
- Touch-friendly tap targets (min 44px)

### 8. Visual Assets Plan

#### Option: AI-Generated Assets
Using OpenClaw's antigravity-image-gen skill:
- Hero illustrations
- Empty state illustrations
- Feature highlight graphics
- Social media preview images

#### Asset Types
| Type | Purpose | Format |
|------|---------|--------|
| Logo | Brand identity | SVG |
| Favicon | Browser tab | ICO, SVG |
| Hero Image | Homepage | WebP, 1920x1080 |
| OG Image | Social sharing | PNG, 1200x630 |
| Icons | UI elements | SVG sprite |

---

## Architecture

### Component Hierarchy
```
app/
├── layout.tsx              # Root layout (nav + theme)
├── page.tsx                # Homepage (indicator catalog)
├── globals.css             # Design system variables
└── (auth)/
    └── layout.tsx          # Auth layout (minimal)

components/
├── ui/                     # shadcn/ui primitives
├── layout/                 # Header, Footer, Sidebar
├── indicators/             # Indicator catalog components
├── loading/                # Skeleton, progress, spinners
└── shared/                 # Cross-cutting components
```

### State Management
- **Theme:** React Context + localStorage for persistence
- **Navigation:** URL-based (Next.js router)
- **UI State:** React useState for component-local state
- **Data:** React Query / SWR for API data (existing pattern)

---

## Technical Approach

### Phase 1: Design System
1. Document and extend CSS variables in globals.css
2. Create Tailwind config extensions if needed
3. Add missing shadcn/ui components

### Phase 2: Homepage Redesign
1. Build IndicatorCatalog component
2. Create IndicatorCard with hover interactions
3. Implement responsive grid layout
4. Add navigation from indicators to dashboard sections

### Phase 3: Loading States
1. Implement Skeleton component
2. Create skeleton variants for key layouts
3. Add Progress component
4. Integrate loading states into data fetching

### Phase 4: Responsive Polish
1. Mobile navigation (Sheet component)
2. Touch-friendly interactions
3. Tablet layout optimizations
4. Desktop refinements

### Phase 5: Icon Set
1. Select appropriate Lucide icons for indicators
2. Create custom SVG icons where needed
3. Build Icon wrapper component
4. Document icon usage guidelines

---

## Database Schema

No database changes required for this feature.

---

## API Changes

No new API endpoints required. Indicator values will be derived from existing endpoints:
- `/api/v1/projects/{id}` - Project stats
- `/api/v1/keywords` - Keyword rankings
- `/api/v1/competitors` - Competitor data
- `/api/v1/projects/{id}/gsc` - GSC metrics

---

## Free Tools Compliance

All proposed technologies are free and open-source:
- **Tailwind CSS:** MIT licensed
- **shadcn/ui:** MIT licensed
- **Lucide Icons:** ISC licensed
- **Next.js:** MIT licensed
- **Custom SVG icons:** Self-created, no cost

---

## Phased Rollout

### Phase 1: Foundation (MVP)
- Design system documentation
- Add shadcn/ui skeleton, progress, badge components
- Basic indicator catalog with Lucide icons

### Phase 2: Enhanced UX
- Loading states throughout application
- Mobile navigation (Sheet)
- Responsive optimizations

### Phase 3: Visual Polish
- Custom icon set
- Animation improvements
- AI-generated visual assets (optional)

### Phase 4: Accessibility
- ARIA labels audit
- Keyboard navigation
- Screen reader testing
- Color contrast verification

---

## Performance Considerations

### Bundle Size
- shadcn/ui components are tree-shakeable
- Lucide icons support individual imports
- Lazy load non-critical components

### Rendering
- Use CSS transforms for animations (GPU accelerated)
- Implement virtualization for large indicator lists
- Optimize images with Next.js Image component

---

## Accessibility Requirements

- WCAG 2.1 AA compliance target
- Keyboard navigation for all interactive elements
- Focus indicators visible and clear
- Color contrast ratio ≥ 4.5:1 for text
- Alt text for all images and icons
- ARIA labels for interactive components

---

## Comments
_Agent log_

### [2026-02-28 09:28 UTC] Agent: Architect
Created SPEC-028 for Modern UI/UX + Indicator Catalog feature.

**Key Decisions:**
1. Leverage existing shadcn/ui + Tailwind stack (no new dependencies)
2. Build on existing dark mode CSS variables
3. Use Lucide icons initially, custom icons as enhancement
4. Mobile-first responsive approach
5. No database changes needed
6. Integrate with existing API endpoints

**Risk Assessment: Medium**
- Frontend-only changes reduce backend risk
- Design decisions may require iteration
- Mobile responsiveness needs thorough testing

Ready for SpecSystemAnalyst to break down into implementation tasks.

### 2026-03-03 11:45 UTC+2 - SpecSystemAnalyst
- Created 19 tasks (TASK-28-1 through TASK-28-19)
- **Phase 1 - Design System (Tasks 1-3):**
  - CSS variables enhancement (brand colors, semantic colors, surface layers)
  - Typography and spacing documentation
  - shadcn/ui components (skeleton, progress, badge, tooltip, avatar, dropdown-menu, sheet, tabs, separator)
- **Phase 2 - Indicator Catalog (Tasks 4-8):**
  - IndicatorIcon wrapper component
  - IndicatorCard with hover interactions
  - IndicatorGrid responsive layout
  - IndicatorCatalog main container
  - Homepage integration
- **Phase 3 - Loading States (Tasks 9-11):**
  - Skeleton loader variants (card, table, chart, catalog)
  - Progress indicators (linear, circular)
  - Loading state integration with data fetching
- **Phase 4 - Navigation (Tasks 12-13):**
  - Desktop navigation enhancement
  - Mobile navigation (Sheet component)
- **Phase 5 - Icons (Tasks 14-15):**
  - Lucide icon mapping for indicators
  - Custom SVG icons (as needed)
- **Phase 6 - Accessibility (Tasks 16-17):**
  - ARIA labels and keyboard navigation
  - Color contrast and screen reader testing
- **Phase 7 - Testing & Documentation (Tasks 18-19):**
  - Unit and feature tests
  - Documentation update
- **Key Decisions:**
  - All changes are frontend-only (no database changes)
  - Leverage existing shadcn/ui + Tailwind stack
  - Start with Lucide icons, create custom icons only where needed
  - Mobile-first responsive approach
- **Risk Notes:**
  - All tasks Low/Medium risk (frontend-only)
  - Homepage integration (Task 8) has Medium risk due to layout changes
- Status updated: `To Prioritize`
