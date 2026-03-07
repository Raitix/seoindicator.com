# Design System — SEOIndicator

This document defines the design tokens, color system, typography, spacing, and component guidelines for SEOIndicator's UI. All values are defined as CSS variables in `frontend/app/globals.css` and integrated with Tailwind CSS via the `@theme` directive.

---

## Table of Contents

1. [Color System](#color-system)
2. [Typography](#typography)
3. [Spacing System](#spacing-system)
4. [Border Radius](#border-radius)
5. [Shadows](#shadows)
6. [Component Patterns](#component-patterns)
7. [Tailwind Integration](#tailwind-integration)
8. [Dark Mode](#dark-mode)
9. [Accessibility Guidelines](#accessibility-guidelines)

---

## Color System

All colors use the **oklch** color space for better perceptual uniformity and wide-gamut support.

### Brand Colors

| Variable | Light Mode | Dark Mode | Usage |
|----------|------------|-----------|-------|
| `--brand-primary` | `oklch(0.65 0.2 160)` | `oklch(0.7 0.22 160)` | Primary brand color (emerald green) |
| `--brand-primary-foreground` | `oklch(0.145 0 0)` | `oklch(0.145 0 0)` | Text on primary color |
| `--brand-secondary` | `oklch(0.55 0.15 200)` | `oklch(0.6 0.17 200)` | Secondary accent (teal) |
| `--brand-accent` | `oklch(0.75 0.18 50)` | `oklch(0.8 0.2 50)` | Accent color (orange) |

**Usage Guidelines:**
- Use `--brand-primary` for primary CTAs, active states, and key interactive elements
- Use `--brand-secondary` for secondary actions and supporting elements
- Use `--brand-accent` sparingly for highlights and call-to-action accents

```css
/* Example usage */
.button-primary {
  background-color: var(--brand-primary);
  color: var(--brand-primary-foreground);
}
```

### Semantic Colors

| Variable | Light Mode | Dark Mode | Usage |
|----------|------------|-----------|-------|
| `--success` | `oklch(0.65 0.2 145)` | `oklch(0.7 0.22 145)` | Success states, positive indicators |
| `--warning` | `oklch(0.75 0.18 85)` | `oklch(0.8 0.2 85)` | Warning states, caution messages |
| `--error` | `oklch(0.6 0.22 25)` | `oklch(0.65 0.24 25)` | Error states, destructive actions |
| `--info` | `oklch(0.6 0.15 230)` | `oklch(0.65 0.17 230)` | Informational messages |

**Color Hue Ranges:**

| Color | Hue Range | Description |
|-------|-----------|-------------|
| Success (Green) | 130-170 | Fresh, positive, completed |
| Warning (Yellow/Orange) | 60-100 | Attention, caution |
| Error (Red) | 0-50 | Urgent, destructive |
| Info (Blue) | 200-260 | Informational, neutral |

**Usage Guidelines:**
- `success`: Use for positive status indicators, completed actions, and good scores (≥80%)
- `warning`: Use for cautionary states, moderate scores (50-79%), and pending actions
- `error`: Use for error messages, destructive confirmations, and critical scores (<50%)
- `info`: Use for neutral information, tooltips, and helpful hints

### Surface Layers

Surface layers provide depth hierarchy in the UI. All surface layers have **zero chroma** (neutral grays).

| Variable | Light Mode | Dark Mode | Usage |
|----------|------------|-----------|-------|
| `--surface-0` | `oklch(0.145 0 0)` | `oklch(0.145 0 0)` | Base background |
| `--surface-1` | `oklch(0.18 0 0)` | `oklch(0.18 0 0)` | Elevated surfaces |
| `--surface-2` | `oklch(0.22 0 0)` | `oklch(0.22 0 0)` | Cards, panels |
| `--surface-3` | `oklch(0.26 0 0)` | `oklch(0.26 0 0)` | Modals, overlays |

**Lightness Progression:** Each surface layer increases in lightness (surface-0 < surface-1 < surface-2 < surface-3).

**Usage Guidelines:**
- `surface-0`: Page backgrounds, app shell
- `surface-1`: Navigation bars, sidebars
- `surface-2`: Cards, content containers
- `surface-3`: Modals, dropdowns, tooltips

### Base Colors

| Variable | Light Mode | Dark Mode | Usage |
|----------|------------|-----------|-------|
| `--background` | `oklch(1 0 0)` | `oklch(0.145 0 0)` | Page background |
| `--foreground` | `oklch(0.145 0 0)` | `oklch(0.985 0 0)` | Primary text |
| `--card` | `oklch(1 0 0)` | `oklch(0.205 0 0)` | Card backgrounds |
| `--card-foreground` | `oklch(0.145 0 0)` | `oklch(0.985 0 0)` | Card text |
| `--primary` | `oklch(0.205 0 0)` | `oklch(0.922 0 0)` | Primary button |
| `--primary-foreground` | `oklch(0.985 0 0)` | `oklch(0.205 0 0)` | Primary button text |
| `--secondary` | `oklch(0.97 0 0)` | `oklch(0.269 0 0)` | Secondary button |
| `--secondary-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Secondary button text |
| `--muted` | `oklch(0.97 0 0)` | `oklch(0.269 0 0)` | Muted backgrounds |
| `--muted-foreground` | `oklch(0.5 0 0)` | `oklch(0.708 0 0)` | Muted text |
| `--accent` | `oklch(0.97 0 0)` | `oklch(0.269 0 0)` | Accent backgrounds |
| `--accent-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Accent text |
| `--destructive` | `oklch(0.577 0.245 27.325)` | `oklch(0.704 0.191 22.216)` | Destructive actions |
| `--border` | `oklch(0.922 0 0)` | `oklch(1 0 0 / 10%)` | Borders |
| `--input` | `oklch(0.922 0 0)` | `oklch(1 0 0 / 15%)` | Input backgrounds |
| `--ring` | `oklch(0.708 0 0)` | `oklch(0.556 0 0)` | Focus rings |

---

## Typography

### Font Families

| Font | Variable | Usage |
|------|----------|-------|
| Sans | `--font-geist-sans` | Body text, UI elements |
| Mono | `--font-geist-mono` | Code, numbers, data |

### Type Scale

| Token | Tailwind Class | Size | Line Height | Usage |
|-------|---------------|------|-------------|-------|
| Display | `text-4xl`, `text-5xl` | 36-48px | 1.1 | Hero headings |
| H1 | `text-3xl` | 30px | 1.25 | Page titles |
| H2 | `text-2xl` | 24px | 1.3 | Section titles |
| H3 | `text-xl` | 20px | 1.4 | Card titles |
| H4 | `text-lg` | 18px | 1.5 | Subsection titles |
| Body | `text-base` | 16px | 1.5 | Paragraphs, default |
| Small | `text-sm` | 14px | 1.5 | Captions, metadata |
| XSmall | `text-xs` | 12px | 1.5 | Fine print, labels |

### Font Weights

| Weight | Tailwind Class | Usage |
|--------|---------------|-------|
| Normal | `font-normal` | Body text |
| Medium | `font-medium` | Emphasis, labels |
| Semibold | `font-semibold` | Headings, buttons |
| Bold | `font-bold` | Strong emphasis |

---

## Spacing System

The spacing system uses a consistent 4px base unit.

| Token | Tailwind Class | Value | Usage |
|-------|---------------|-------|-------|
| xs | `p-1`, `gap-1` | 4px | Tight spacing |
| sm | `p-2`, `gap-2` | 8px | Compact spacing |
| md | `p-4`, `gap-4` | 16px | Default spacing |
| lg | `p-6`, `gap-6` | 24px | Comfortable spacing |
| xl | `p-8`, `gap-8` | 32px | Generous spacing |
| 2xl | `p-12`, `gap-12` | 48px | Section spacing |
| 3xl | `p-16`, `gap-16` | 64px | Major section spacing |

### Component Spacing Patterns

| Component | Internal Padding | Gap |
|-----------|-----------------|-----|
| Button | `px-4 py-2` | - |
| Card | `p-6` | `gap-4` |
| Modal | `p-6` | `gap-4` |
| Form Group | - | `gap-2` |
| Section | `py-12` | `gap-6` |

---

## Border Radius

| Token | Tailwind Class | Value | Usage |
|-------|---------------|-------|-------|
| sm | `rounded-sm` | 2px | Subtle rounding |
| md | `rounded-md` | 4px | Default rounding |
| lg | `rounded-lg` | 10px | Cards, buttons |
| xl | `rounded-xl` | 14px | Large cards |
| 2xl | `rounded-2xl` | 18px | Modals |
| 3xl | `rounded-3xl` | 24px | Feature cards |
| full | `rounded-full` | 9999px | Pills, avatars |

**Base Radius:** `--radius: 0.625rem` (10px)

---

## Shadows

| Token | Tailwind Class | Usage |
|-------|---------------|-------|
| sm | `shadow-sm` | Subtle elevation |
| md | `shadow-md` | Cards, dropdowns |
| lg | `shadow-lg` | Modals, popovers |
| xl | `shadow-xl` | Floating elements |

---

## Component Patterns

### Button Variants

| Variant | Classes | Usage |
|---------|---------|-------|
| Primary | `bg-primary text-primary-foreground` | Main CTAs |
| Secondary | `bg-secondary text-secondary-foreground` | Secondary actions |
| Destructive | `bg-destructive text-destructive-foreground` | Destructive actions |
| Outline | `border border-input bg-background` | Tertiary actions |
| Ghost | `hover:bg-accent` | Minimal actions |
| Link | `text-primary underline-offset-4` | Text links |

### Badge Variants

| Variant | Usage | Color |
|---------|-------|-------|
| default | Primary status | Brand primary |
| secondary | Secondary status | Muted |
| destructive | Error status | Error |
| outline | Neutral status | Border |

### Status Indicators

| Status | Color | Badge Variant |
|--------|-------|---------------|
| good | `--success` | default |
| warning | `--warning` | secondary |
| critical | `--error` | destructive |
| neutral | `--muted-foreground` | outline |

---

## Tailwind Integration

Colors and design tokens are integrated with Tailwind CSS via the `@theme` directive in `globals.css`:

```css
@theme inline {
  --color-brand-primary: var(--brand-primary);
  --color-success: var(--success);
  --color-surface-0: var(--surface-0);
  /* ... etc */
}
```

### Usage in Components

```tsx
// Using Tailwind classes with design tokens
<button className="bg-brand-primary text-white">
  Primary Button
</button>

<div className="bg-surface-2 border-border rounded-lg">
  Card content
</div>

<span className="text-success font-medium">
  Status: Good
</span>
```

### Custom Utility Classes

```css
/* From globals.css */
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
.animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
.animate-scale-in { animation: scale-in 0.5s ease-out forwards; }
```

---

## Dark Mode

Dark mode is implemented via the `.dark` class on the root element. All color variables have corresponding dark mode values.

### Implementation

```tsx
// ThemeProvider handles dark mode
<html className={theme === 'dark' ? 'dark' : ''}>
  {/* ... */}
</html>
```

### Color Inversion

| Element | Light Mode | Dark Mode |
|---------|------------|-----------|
| Background | Near white | Near black |
| Text | Dark | Light |
| Primary button | Dark | Light |
| Borders | Light gray | Semi-transparent white |

### Dark Mode Best Practices

1. **Maintain contrast ratios:** Ensure text remains readable in both modes
2. **Reduce chroma slightly:** Dark mode colors can appear more saturated
3. **Test shadows:** Dark mode shadows may need adjustment
4. **Consider reduced motion:** Respect `prefers-reduced-motion`

---

## Accessibility Guidelines

### Color Contrast

All text must meet **WCAG 2.1 AA** requirements:
- **Normal text:** Minimum 4.5:1 contrast ratio
- **Large text (18px+):** Minimum 3:1 contrast ratio
- **Interactive elements:** Minimum 3:1 for UI components

### Focus Indicators

All interactive elements must have visible focus states:

```css
.focus-visible {
  outline: none;
  ring: 2px;
  ring-color: var(--ring);
  ring-offset: 2px;
}
```

### ARIA Labels

| Element | Requirement |
|---------|-------------|
| Buttons | `aria-label` for icon-only buttons |
| Icons | `aria-hidden="true"` for decorative, `role="img"` + `aria-label` for meaningful |
| Links | Descriptive text or `aria-label` |
| Form inputs | Associated `<label>` or `aria-labelledby` |

### Keyboard Navigation

| Key | Action |
|-----|--------|
| Tab | Move focus forward |
| Shift + Tab | Move focus backward |
| Enter | Activate buttons/links |
| Space | Activate buttons, toggle checkboxes |
| Escape | Close modals, dropdowns |
| Arrow keys | Navigate menus, tabs |

### Screen Reader Support

- Use semantic HTML elements (`<nav>`, `<main>`, `<article>`)
- Provide skip links for main content
- Use `aria-live` regions for dynamic content updates
- Announce status changes with `role="status"` or `role="alert"`

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## Related Documentation

- **DOCUMENTATION.md** — Feature documentation and component locations
- **frontend/components/README.md** — Component usage guide
- **frontend/app/globals.css** — CSS variable definitions

---

Last updated: 2026-03-07
