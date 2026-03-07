# DESIGN-SYSTEM.md — SEOIndicator Design System

> **Source of Truth:** This document describes the design tokens and guidelines used throughout SEOIndicator. All CSS variables are defined in `frontend/app/globals.css`.

---

## Table of Contents

1. [Overview](#overview)
2. [Color System](#color-system)
3. [Typography](#typography)
4. [Spacing](#spacing)
5. [Components](#components)
6. [Dark Mode](#dark-mode)
7. [Accessibility Guidelines](#accessibility-guidelines)
8. [Tailwind Integration](#tailwind-integration)

---

## Overview

SEOIndicator uses a modern design system built on:

- **oklch color space** — Perceptually uniform colors with consistent lightness
- **CSS custom properties** — Themeable design tokens
- **Tailwind CSS v4** — Utility-first CSS with `@theme` inline mapping
- **shadcn/ui** — Accessible component primitives

### Design Principles

| Principle | Description |
|-----------|-------------|
| **Consistency** | Reusable tokens across all components |
| **Accessibility** | WCAG 2.1 AA compliance by default |
| **Dark Mode** | First-class dark mode support |
| **Performance** | Minimal CSS overhead with utility classes |

---

## Color System

### Brand Colors

Brand colors define the primary identity of SEOIndicator.

| Token | Value (Light) | Value (Dark) | Usage |
|-------|---------------|--------------|-------|
| `--brand-primary` | `oklch(0.65 0.2 160)` | `oklch(0.7 0.22 160)` | Primary brand color (emerald green) |
| `--brand-primary-foreground` | `oklch(0.145 0 0)` | `oklch(0.145 0 0)` | Text on primary background |
| `--brand-secondary` | `oklch(0.55 0.15 200)` | `oklch(0.6 0.17 200)` | Secondary brand color (teal) |
| `--brand-accent` | `oklch(0.75 0.18 50)` | `oklch(0.8 0.2 50)` | Accent color (orange) |

#### Color Values Explained

The oklch format uses three components:
- **L (Lightness)**: 0-1 scale (0 = black, 1 = white)
- **C (Chroma)**: Color intensity (0 = gray, higher = more saturated)
- **H (Hue)**: Color hue in degrees (0-360)

```
--brand-primary: oklch(L C H)
                        │ │ │
                        │ │ └── Hue: 160° (green-cyan range)
                        │ └──── Chroma: 0.2 (moderate saturation)
                        └────── Lightness: 0.65 (65% light)
```

### Semantic Colors

Semantic colors convey meaning and status.

| Token | Value (Light) | Value (Dark) | Usage |
|-------|---------------|--------------|-------|
| `--success` | `oklch(0.65 0.2 145)` | `oklch(0.7 0.22 145)` | Success states, positive indicators |
| `--warning` | `oklch(0.75 0.18 85)` | `oklch(0.8 0.2 85)` | Warning states, caution alerts |
| `--error` | `oklch(0.6 0.22 25)` | `oklch(0.65 0.24 25)` | Error states, destructive actions |
| `--info` | `oklch(0.6 0.15 230)` | `oklch(0.65 0.17 230)` | Information, help text |

#### Hue Ranges

| Color | Hue Range | Example |
|-------|-----------|---------|
| Red (error) | 0-50 | H: 25 |
| Orange (warning) | 50-100 | H: 85 |
| Green (success) | 130-170 | H: 145 |
| Teal (brand secondary) | 180-210 | H: 200 |
| Blue (info) | 200-260 | H: 230 |

### Surface Layers

Surface layers create depth and hierarchy in the UI.

| Token | Value | Usage |
|-------|-------|-------|
| `--surface-0` | `oklch(0.145 0 0)` | Darkest surface (background) |
| `--surface-1` | `oklch(0.18 0 0)` | Elevated surface 1 |
| `--surface-2` | `oklch(0.22 0 0)` | Elevated surface 2 |
| `--surface-3` | `oklch(0.26 0 0)` | Lightest surface (hover states) |

**Properties:**
- **Zero chroma (C = 0)** — All surface layers are neutral grays
- **Progressive lightness** — Each surface is lighter than the previous
- **Consistent in both modes** — Same values in light and dark mode

### Theme Colors

Standard shadcn/ui theme colors with oklch values.

| Token | Description |
|-------|-------------|
| `--background` | Page background |
| `--foreground` | Primary text color |
| `--card` | Card background |
| `--card-foreground` | Card text |
| `--popover` | Popover/dropdown background |
| `--popover-foreground` | Popover text |
| `--primary` | Primary button/action color |
| `--primary-foreground` | Text on primary |
| `--secondary` | Secondary button color |
| `--secondary-foreground` | Text on secondary |
| `--muted` | Muted backgrounds |
| `--muted-foreground` | Muted text |
| `--accent` | Accent highlights |
| `--accent-foreground` | Text on accent |
| `--destructive` | Destructive actions |
| `--border` | Border color |
| `--input` | Input border color |
| `--ring` | Focus ring color |

---

## Typography

### Font Stack

```css
--font-sans: var(--font-geist-sans);  /* Primary font */
--font-mono: var(--font-geist-mono);  /* Monospace font */
```

### Typography Scale

| Size | Tailwind Class | Usage |
|------|----------------|-------|
| Display | `text-4xl`, `text-5xl` | Hero headings |
| H1 | `text-3xl` | Page titles |
| H2 | `text-2xl` | Section headings |
| H3 | `text-xl` | Subsection headings |
| H4 | `text-lg` | Card titles |
| H5 | `text-base` | Small headings |
| H6 | `text-sm` | Smallest headings |
| Body | `text-base` | Default body text |
| Small | `text-sm` | Supporting text |
| Caption | `text-xs` | Labels, captions |

### Font Weights

| Weight | Tailwind Class | Usage |
|--------|----------------|-------|
| Normal | `font-normal` | Body text |
| Medium | `font-medium` | Emphasis, labels |
| Semibold | `font-semibold` | Subheadings |
| Bold | `font-bold` | Headings, highlights |

---

## Spacing

### Spacing Scale

| Token | Value | Tailwind Class |
|-------|-------|----------------|
| xs | 0.25rem (4px) | `gap-1`, `p-1` |
| sm | 0.5rem (8px) | `gap-2`, `p-2` |
| md | 1rem (16px) | `gap-4`, `p-4` |
| lg | 1.5rem (24px) | `gap-6`, `p-6` |
| xl | 2rem (32px) | `gap-8`, `p-8` |
| 2xl | 3rem (48px) | `gap-12`, `p-12` |

### Border Radius

| Token | Value | Tailwind Class |
|-------|-------|----------------|
| `--radius-sm` | `calc(var(--radius) - 4px)` | `rounded-sm` |
| `--radius-md` | `calc(var(--radius) - 2px)` | `rounded-md` |
| `--radius-lg` | `var(--radius)` | `rounded-lg` |
| `--radius-xl` | `calc(var(--radius) + 4px)` | `rounded-xl` |
| `--radius-2xl` | `calc(var(--radius) + 8px)` | `rounded-2xl` |
| `--radius-3xl` | `calc(var(--radius) + 12px)` | `rounded-3xl` |
| `--radius-4xl` | `calc(var(--radius) + 16px)` | `rounded-4xl` |

**Base radius:** `--radius: 0.625rem` (10px)

---

## Components

### shadcn/ui Components

All shadcn/ui components are located in `frontend/components/ui/`:

| Component | File | Description |
|-----------|------|-------------|
| Skeleton | `skeleton.tsx` | Loading placeholders |
| Progress | `progress.tsx` | Linear progress bar |
| CircularProgress | `CircularProgress.tsx` | Circular progress indicator |
| LinearProgress | `LinearProgress.tsx` | Enhanced linear progress |
| Badge | `badge.tsx` | Status labels |
| Tooltip | `tooltip.tsx` | Hover information |
| Avatar | `avatar.tsx` | User profile images |
| Dropdown-Menu | `dropdown-menu.tsx` | Navigation menus |
| Sheet | `sheet.tsx` | Mobile drawer |
| Tabs | `tabs.tsx` | Content tabs |
| Separator | `separator.tsx` | Visual division |
| Button | `button.tsx` | Action buttons |
| Card | `card.tsx` | Content containers |
| Input | `input.tsx` | Text inputs |
| Dialog | `dialog.tsx` | Modal dialogs |
| Toast | `toast-banner.tsx` | Notifications |

### Indicator Components

Custom indicator components are in `frontend/components/indicators/`:

| Component | File | Description |
|-----------|------|-------------|
| IndicatorIcon | `IndicatorIcon.tsx` | Icon wrapper with variants |
| IndicatorCard | `IndicatorCard.tsx` | Dashboard indicator card |
| IndicatorGrid | `IndicatorGrid.tsx` | Responsive indicator grid |
| IndicatorCatalog | `IndicatorCatalog.tsx` | Homepage indicator catalog |

See `frontend/components/README.md` for detailed usage.

---

## Dark Mode

### Implementation

Dark mode is implemented using:

1. **CSS class toggle** — `.dark` class on `<html>` element
2. **CSS custom properties** — Different values for light/dark
3. **Tailwind dark variant** — `dark:` prefix for utilities

### CSS Structure

```css
:root {
  /* Light mode values */
  --background: oklch(1 0 0);
  --foreground: oklch(0.145 0 0);
  /* ... */
}

.dark {
  /* Dark mode overrides */
  --background: oklch(0.145 0 0);
  --foreground: oklch(0.985 0 0);
  /* ... */
}
```

### Tailwind Usage

```tsx
<div className="bg-background text-foreground dark:bg-surface-0">
  <span className="text-emerald-600 dark:text-emerald-400">
    Status
  </span>
</div>
```

### Color Adjustments in Dark Mode

| Color | Light Mode | Dark Mode | Change |
|-------|------------|-----------|--------|
| Brand Primary | L: 0.65 | L: 0.70 | +5% lightness |
| Brand Secondary | L: 0.55 | L: 0.60 | +5% lightness |
| Success | L: 0.65 | L: 0.70 | +5% lightness |
| Warning | L: 0.75 | L: 0.80 | +5% lightness |
| Error | L: 0.60 | L: 0.65 | +5% lightness |

**Principle:** Dark mode colors are slightly lighter to maintain visibility on dark backgrounds.

---

## Accessibility Guidelines

### Color Contrast

All text must meet WCAG 2.1 AA contrast requirements:

| Text Size | Minimum Contrast |
|-----------|------------------|
| Normal text | 4.5:1 |
| Large text (18px+ or 14px+ bold) | 3:1 |
| UI components | 3:1 |

### Focus Indicators

All interactive elements must have visible focus states:

```css
/* Focus ring */
.focus-visible:ring-2
.focus-visible:ring-primary
.focus-visible:ring-offset-2
.focus-visible:ring-offset-background

/* Focus outline */
focus-visible:outline-none
```

### Keyboard Navigation

| Key | Action |
|-----|--------|
| Tab | Move to next focusable element |
| Shift+Tab | Move to previous focusable element |
| Enter | Activate button/link |
| Escape | Close modal/dropdown |
| Space | Toggle checkbox/button |

### Screen Reader Support

- Use `aria-label` for icon-only buttons
- Use `aria-hidden="true"` for decorative elements
- Use `aria-live` regions for dynamic content
- Use semantic HTML (`<button>`, `<nav>`, `<main>`)

### Reduced Motion

Respect user preferences for reduced motion:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

Or in React:

```tsx
const prefersReducedMotion = window.matchMedia(
  '(prefers-reduced-motion: reduce)'
).matches;
```

---

## Tailwind Integration

### @theme Inline Mapping

Design tokens are mapped to Tailwind utilities via `@theme inline`:

```css
@theme inline {
  /* Brand Colors */
  --color-brand-primary: var(--brand-primary);
  --color-brand-secondary: var(--brand-secondary);
  --color-brand-accent: var(--brand-accent);
  
  /* Semantic Colors */
  --color-success: var(--success);
  --color-warning: var(--warning);
  --color-error: var(--error);
  --color-info: var(--info);
  
  /* Surface Layers */
  --color-surface-0: var(--surface-0);
  --color-surface-1: var(--surface-1);
  --color-surface-2: var(--surface-2);
  --color-surface-3: var(--surface-3);
}
```

### Usage in Components

```tsx
// Using mapped colors
<div className="bg-brand-primary text-brand-primary-foreground">
  Brand Button
</div>

// Using semantic colors
<span className="text-success">Success!</span>
<span className="text-warning">Warning!</span>
<span className="text-error">Error!</span>
<span className="text-info">Info</span>

// Using surface layers
<div className="bg-surface-0">
  <div className="bg-surface-1">Elevated</div>
  <div className="bg-surface-2">More elevated</div>
</div>
```

### Custom Variants

```css
@custom-variant dark (&:is(.dark *));
```

This enables the `dark:` variant in Tailwind.

---

## CSS Variables Reference

### Complete Variable List

```css
:root {
  /* Radius */
  --radius: 0.625rem;
  
  /* Theme Colors */
  --background: oklch(1 0 0);
  --foreground: oklch(0.145 0 0);
  --card: oklch(1 0 0);
  --card-foreground: oklch(0.145 0 0);
  --popover: oklch(1 0 0);
  --popover-foreground: oklch(0.145 0 0);
  --primary: oklch(0.205 0 0);
  --primary-foreground: oklch(0.985 0 0);
  --secondary: oklch(0.97 0 0);
  --secondary-foreground: oklch(0.205 0 0);
  --muted: oklch(0.97 0 0);
  --muted-foreground: oklch(0.5 0 0);
  --accent: oklch(0.97 0 0);
  --accent-foreground: oklch(0.205 0 0);
  --destructive: oklch(0.577 0.245 27.325);
  --border: oklch(0.922 0 0);
  --input: oklch(0.922 0 0);
  --ring: oklch(0.708 0 0);
  
  /* Brand Colors */
  --brand-primary: oklch(0.65 0.2 160);
  --brand-primary-foreground: oklch(0.145 0 0);
  --brand-secondary: oklch(0.55 0.15 200);
  --brand-accent: oklch(0.75 0.18 50);
  
  /* Semantic Colors */
  --success: oklch(0.65 0.2 145);
  --warning: oklch(0.75 0.18 85);
  --error: oklch(0.6 0.22 25);
  --info: oklch(0.6 0.15 230);
  
  /* Surface Layers */
  --surface-0: oklch(0.145 0 0);
  --surface-1: oklch(0.18 0 0);
  --surface-2: oklch(0.22 0 0);
  --surface-3: oklch(0.26 0 0);
}
```

---

## Related Documentation

- **DOCUMENTATION.md** — Feature documentation
- **frontend/components/README.md** — Component usage guide
- **frontend/app/globals.css** — CSS variable definitions

---

Last updated: 2026-03-07
