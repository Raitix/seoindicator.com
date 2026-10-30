# TEST-28-1: CSS Variables Enhancement Tests

## Task Reference
- **Task:** TASK-28-1 - CSS Variables Enhancement (Design System)
- **Specification:** SPEC-028 - Modern UI/UX + Indicator Catalog
- **Phase:** 1 - Design System Foundation

## Test File
`frontend/__tests__/styles/css-variables.test.ts`

## Test Summary
| Category | Tests | Status |
|----------|-------|--------|
| Brand Color Variables | 9 | ❌ Awaiting Implementation |
| Semantic Color Variables | 12 | ❌ Awaiting Implementation |
| Surface Layer Variables | 9 | ❌ Awaiting Implementation |
| oklch Format Validation | 6 | ❌ Awaiting Implementation |
| Dark Mode Support | 12 | ❌ Awaiting Implementation |
| Tailwind Integration | 4 | ❌ Awaiting Implementation |
| Edge Cases | 5 | ✅ PASS |
| Acceptance Criteria | 6 | ❌ Awaiting Implementation |
| **Total** | **63** | **6 pass / 57 fail** |

## Test Categories

### BC: Brand Color Variables (9 tests)
| ID | Test | Description |
|----|------|-------------|
| BC-001 | --brand-primary exists | Defines brand primary in :root with oklch format |
| BC-002 | --brand-secondary exists | Defines brand secondary in :root with oklch format |
| BC-003 | --brand-accent exists | Defines brand accent in :root with oklch format |
| BC-004 | Brand primary hue | Emerald green hue (~140-180) |
| BC-005 | Brand secondary hue | Teal hue (~180-220) |
| BC-006 | Brand accent hue | Orange hue (~30-70) |

### SC: Semantic Color Variables (12 tests)
| ID | Test | Description |
|----|------|-------------|
| SC-001 | --success exists | Defines success color in :root with oklch format |
| SC-002 | --warning exists | Defines warning color in :root with oklch format |
| SC-003 | --error exists | Defines error color in :root with oklch format |
| SC-004 | --info exists | Defines info color in :root with oklch format |
| SC-005 | Success hue | Green hue (~120-160) |
| SC-006 | Warning hue | Yellow/orange hue (~60-100) |
| SC-007 | Error hue | Red hue (~0-50) |
| SC-008 | Info hue | Blue hue (~200-260) |

### SL: Surface Layer Variables (9 tests)
| ID | Test | Description |
|----|------|-------------|
| SL-001 | --surface-0 exists | Base surface layer |
| SL-002 | --surface-1 exists | Elevated surface layer |
| SL-003 | --surface-2 exists | Card surface layer |
| SL-004 | --surface-3 exists | Modal surface layer |
| SL-005 | Lightness progression | surface-0 < surface-1 < surface-2 < surface-3 |
| SL-006 | Neutral chroma | All surfaces have chroma = 0 (grayscale) |

### OK: oklch Format Validation (6 tests)
| ID | Test | Description |
|----|------|-------------|
| OK-001 | Brand colors format | All brand colors use oklch format |
| OK-002 | Semantic colors format | All semantic colors use oklch format |
| OK-003 | Surface layers format | All surface layers use oklch format |
| OK-004 | Lightness range | L values in valid range (0-1) |
| OK-005 | Chroma non-negative | C values are >= 0 |
| OK-006 | Hue range | H values in valid range (0-360) |

### DM: Dark Mode Support (12 tests)
| ID | Test | Description |
|----|------|-------------|
| DM-001 | Brand colors in .dark | All brand colors defined in dark mode |
| DM-002 | Semantic colors in .dark | All semantic colors defined in dark mode |
| DM-003 | Surface layers in .dark | All surface layers defined in dark mode |
| DM-004 | Dark mode surfaces progression | Lightness order maintained in dark mode |

### TW: Tailwind Integration (4 tests)
| ID | Test | Description |
|----|------|-------------|
| TW-001 | @theme block exists | Tailwind v4 @theme inline block present |
| TW-002 | brand-primary mapped | --color-brand-primary in @theme |
| TW-003 | success mapped | --color-success in @theme |
| TW-004 | surface-0 mapped | --color-surface-0 in @theme |

### EC: Edge Cases (5 tests) ✅ PASS
| ID | Test | Status |
|----|------|--------|
| EC-001 | No duplicate variable names | ✅ PASS |
| EC-002 | No deprecated formats (rgb, hsl, hex) | ✅ PASS |
| EC-003 | CSS syntax valid | ✅ PASS |

### AC: Acceptance Criteria (6 tests)
| ID | Criteria | Status |
|----|----------|--------|
| AC-1 | Brand color variables (primary, secondary, accent) | ❌ |
| AC-2 | Semantic color variables (success, warning, error, info) | ❌ |
| AC-3 | Surface layer variables (surface-0 through surface-3) | ❌ |
| AC-4 | Variables use oklch color space | ❌ |
| AC-5 | Variables integrate with Tailwind CSS theme | ❌ |
| AC-6 | Dark mode values properly defined | ❌ |

## Implementation Requirements

### CSS Variables to Add in `globals.css`

**In `:root` block:**
```css
/* Brand Colors */
--brand-primary: oklch(0.65 0.2 160);
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
```

**In `.dark` block:**
```css
/* Brand Colors - Dark Mode */
--brand-primary: oklch(0.65 0.2 160);
--brand-secondary: oklch(0.55 0.15 200);
--brand-accent: oklch(0.75 0.18 50);

/* Semantic Colors - Dark Mode */
--success: oklch(0.65 0.2 145);
--warning: oklch(0.75 0.18 85);
--error: oklch(0.6 0.22 25);
--info: oklch(0.6 0.15 230);

/* Surface Layers - Dark Mode */
--surface-0: oklch(0.145 0 0);
--surface-1: oklch(0.18 0 0);
--surface-2: oklch(0.22 0 0);
--surface-3: oklch(0.26 0 0);
```

**In `@theme inline` block:**
```css
--color-brand-primary: var(--brand-primary);
--color-brand-secondary: var(--brand-secondary);
--color-brand-accent: var(--brand-accent);
--color-success: var(--success);
--color-warning: var(--warning);
--color-error: var(--error);
--color-info: var(--info);
--color-surface-0: var(--surface-0);
--color-surface-1: var(--surface-1);
--color-surface-2: var(--surface-2);
--color-surface-3: var(--surface-3);
```

## Test Patterns Used
- CSS file parsing with regex
- oklch color space validation
- Lightness/chroma/hue range validation
- Dark mode variable verification
- Tailwind v4 @theme integration check
- Edge case handling (duplicates, deprecated formats)

## Running Tests
```bash
cd frontend
npm test -- __tests__/styles/css-variables.test.ts
```

## Notes for Developer
1. All new variables MUST use `oklch()` format (not rgb, hsl, or hex)
2. Surface layers must be neutral (chroma = 0) grayscale values
3. Surface layers must progress in lightness (0 < 1 < 2 < 3)
4. Both `:root` and `.dark` blocks need all variables
5. Tailwind v4 uses `@theme inline` block for color mapping
6. After adding variables, use in Tailwind classes: `bg-brand-primary`, `text-success`, `bg-surface-2`

## Status
- **Tests Complete:** ✅ 63 tests written
- **Implementation Required:** ❌ CSS variables not yet added
- **Ready for:** Developer (TASK-28-1 → To Develop)

---

**Created by:** PreTester
**Date:** 2026-03-03
