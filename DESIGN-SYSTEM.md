# Design System

> SEOIndicator Design System Documentation

This document defines the typography scale and spacing system used throughout the SEOIndicator application. All values align with Tailwind CSS utilities for consistent implementation.

---

## Table of Contents

- [Typography Scale](#typography-scale)
- [Spacing System](#spacing-system)
- [Responsive Considerations](#responsive-considerations)

---

## Typography Scale

The typography scale provides a consistent hierarchy for all text elements in the application. Use these scales to maintain visual consistency across pages and components.

### Typography Scale Table

| Name | Tailwind Class | Font Size | Use Case |
|------|---------------|-----------|----------|
| **Display** | `text-5xl` / `text-4xl` | 48px / 36px | Hero headings, major page headers |
| **H1** | `text-3xl` | 30px | Page titles |
| **H2** | `text-2xl` | 24px | Section titles |
| **H3** | `text-xl` | 20px | Card titles, subsection headers |
| **Body** | `text-base` | 16px | Paragraphs, body text |
| **Small** | `text-sm` | 14px | Captions, metadata, helper text |

### Typography Tailwind Class Mappings

| Scale | Tailwind Classes | CSS Equivalent |
|-------|-----------------|----------------|
| Display | `text-5xl` | `font-size: 3rem; line-height: 1;` |
| Display (sm) | `text-4xl` | `font-size: 2.25rem; line-height: 2.5rem;` |
| H1 | `text-3xl` | `font-size: 1.875rem; line-height: 2.25rem;` |
| H2 | `text-2xl` | `font-size: 1.5rem; line-height: 2rem;` |
| H3 | `text-xl` | `font-size: 1.25rem; line-height: 1.75rem;` |
| Body | `text-base` | `font-size: 1rem; line-height: 1.5rem;` |
| Small | `text-sm` | `font-size: 0.875rem; line-height: 1.25rem;` |

### Typography Examples

#### Display (Hero Headings)

```tsx
// Large display for hero sections
<h1 className="text-5xl font-bold tracking-tight">
  SEO Dashboard
</h1>

// Medium display for secondary hero
<h1 className="text-4xl font-semibold">
  Analyze Your Rankings
</h1>
```

#### H1 (Page Titles)

```tsx
// Main page title
<h1 className="text-3xl font-semibold">
  Keyword Rankings
</h1>
```

#### H2 (Section Titles)

```tsx
// Section heading
<h2 className="text-2xl font-medium">
  Top Performing Keywords
</h2>
```

#### H3 (Card Titles)

```tsx
// Card or subsection title
<h3 className="text-xl font-medium">
  Visibility Score
</h3>
```

#### Body (Paragraphs)

```tsx
// Body text for descriptions
<p className="text-base text-muted-foreground">
  Track your keyword positions across search engines
  and identify opportunities for improvement.
</p>
```

#### Small (Captions & Metadata)

```tsx
// Caption or helper text
<span className="text-sm text-muted-foreground">
  Last updated: 2 hours ago
</span>

// Metadata label
<span className="text-sm font-medium">
  150 keywords tracked
</span>
```

---

## Spacing System

The spacing system provides consistent margins, padding, and gaps throughout the application. These values follow Tailwind's default spacing scale.

### Spacing Scale Table

| Name | Tailwind Class | Rem Value | Pixel Value | Use Case |
|------|---------------|-----------|-------------|----------|
| **xs** | `p-1`, `m-1`, `gap-1` | 0.25rem | 4px | Tight spacing, icon gaps |
| **sm** | `p-2`, `m-2`, `gap-2` | 0.5rem | 8px | Compact elements, badge padding |
| **md** | `p-4`, `m-4`, `gap-4` | 1rem | 16px | Default spacing, card padding |
| **lg** | `p-6`, `m-6`, `gap-6` | 1.5rem | 24px | Section spacing, container padding |
| **xl** | `p-8`, `m-8`, `gap-8` | 2rem | 32px | Large gaps, major sections |
| **2xl** | `p-12`, `m-12`, `gap-12` | 3rem | 48px | Page-level spacing, hero margins |

### Spacing Tailwind Class Mappings

| Scale | Tailwind Classes | CSS Equivalent |
|-------|-----------------|----------------|
| xs | `p-1`, `m-1`, `gap-1` | `0.25rem` (4px) |
| sm | `p-2`, `m-2`, `gap-2` | `0.5rem` (8px) |
| md | `p-4`, `m-4`, `gap-4` | `1rem` (16px) |
| lg | `p-6`, `m-6`, `gap-6` | `1.5rem` (24px) |
| xl | `p-8`, `m-8`, `gap-8` | `2rem` (32px) |
| 2xl | `p-12`, `m-12`, `gap-12` | `3rem` (48px) |

### Spacing Usage Examples

#### Padding Examples

```tsx
// Card with default padding
<div className="p-4 bg-card rounded-lg">
  <h3 className="text-xl">Card Title</h3>
  <p className="text-sm mt-2">Card content...</p>
</div>

// Compact badge
<span className="px-2 py-1 text-sm bg-primary/10 rounded">
  New
</span>

// Section with larger padding
<section className="p-6 md:p-8">
  <h2 className="text-2xl">Section Title</h2>
</section>
```

#### Margin Examples

```tsx
// Margin between elements
<h2 className="text-2xl mb-4">Section Title</h2>
<p className="text-base mb-2">Description text...</p>

// Centered container with top margin
<div className="mt-8 mx-auto max-w-7xl">
  Content here...
</div>

// Card grid with gap
<div className="grid grid-cols-3 gap-6">
  {/* Cards */}
</div>
```

#### Gap Examples

```tsx
// Flex container with gap
<div className="flex items-center gap-2">
  <Icon name="check" />
  <span>Completed</span>
</div>

// Grid layout with responsive gap
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
  {/* Grid items */}
</div>

// Stack with consistent vertical spacing
<div className="flex flex-col gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div>
```

### Spacing Best Practices

1. **Use gap over margins** for flex/grid children to avoid margin collapse issues
2. **Responsive spacing**: Scale padding/margins at breakpoints (e.g., `p-4 md:p-6 lg:p-8`)
3. **Consistent sections**: Use `lg` (24px) for section separators
4. **Tight groups**: Use `xs` (4px) or `sm` (8px) for related inline elements
5. **Page-level spacing**: Use `xl` (32px) or `2xl` (48px) for hero sections

---

## Responsive Considerations

Typography and spacing should adapt to different screen sizes for optimal readability and layout.

### Responsive Typography

Use Tailwind's responsive modifiers to scale typography:

```tsx
// Display scales down on mobile
<h1 className="text-4xl md:text-5xl font-bold">
  Hero Title
</h1>

// Section titles adjust for mobile
<h2 className="text-xl md:text-2xl font-medium">
  Section Title
</h2>
```

### Responsive Spacing Patterns

```tsx
// Container padding scales with viewport
<div className="p-4 md:p-6 lg:p-8">
  Content...
</div>

// Section margins responsive
<section className="mb-8 md:mb-12 lg:mb-16">
  Section content...
</section>

// Grid gap responsive
<div className="grid gap-4 md:gap-6 lg:gap-8">
  Grid items...
</div>
```

### Breakpoint Reference

| Breakpoint | Min Width | Use Case |
|------------|-----------|----------|
| `sm` | 640px | Mobile landscape |
| `md` | 768px | Tablet portrait |
| `lg` | 1024px | Tablet landscape / Small desktop |
| `xl` | 1280px | Desktop |
| `2xl` | 1536px | Large desktop |

---

## Summary

| Category | Values | Tailwind Prefix |
|----------|--------|-----------------|
| Typography | Display, H1, H2, H3, Body, Small | `text-*` |
| Spacing | xs, sm, md, lg, xl, 2xl | `p-*`, `m-*`, `gap-*` |

Always reference this document when implementing new components to ensure design consistency across the application.
