# TEST-28-4: IndicatorIcon Wrapper Component Tests

## Overview
Tests for the IndicatorIcon component - a reusable wrapper for Lucide icons and custom SVG icons with consistent styling.

## Test File Location
`frontend/components/indicators/__tests__/IndicatorIcon.test.tsx`

## Test Count: 100 tests

---

## Test Categories

### TypeScript Type Definitions (1 test)
| ID | Test | Description |
|----|------|-------------|
| TD-001 | Component props interface exists | Verifies typed props import correctly |

### Component Rendering (5 tests)
| ID | Test | Description |
|----|------|-------------|
| CR-001 | Renders without crashing | Basic mount test |
| CR-002 | Renders with data-slot attribute | Identifies wrapper element |
| CR-003 | Renders default size (md) | 24px default |
| CR-004 | Applies custom className | Class merging works |
| CR-005 | No render without icon/customIcon | Graceful null handling |

### Lucide Icon Rendering (13 tests)
| ID | Test | Description |
|----|------|-------------|
| LI-001 | Activity icon | Standard icon |
| LI-002 | AlertCircle icon | Alert variant |
| LI-003 | CheckCircle icon | Success indicator |
| LI-004 | ChevronDown icon | Navigation |
| LI-005 | HelpCircle icon | Help indicator |
| LI-006 | Info icon | Information |
| LI-007 | Search icon | Search action |
| LI-008 | Star icon | Rating/favorite |
| LI-009 | TrendingUp icon | Metrics |
| LI-010 | Warning icon | Warning state |
| LI-011 | XCircle icon | Error/cancel |
| LI-012 | Zap icon | Energy/action |
| LI-013 | Invalid icon name | Graceful failure |

### Size Variants (8 tests)
| ID | Test | Description |
|----|------|-------------|
| SV-001 | sm size = 16px (size-4) | Small variant |
| SV-002 | md size = 24px (size-6) | Medium variant |
| SV-003 | lg size = 32px (size-8) | Large variant |
| SV-004 | Default is md | No size prop |
| SV-005 | sm stroke width 1.5px | Thinner stroke |
| SV-006 | md stroke width 2px | Default stroke |
| SV-007 | lg stroke width 2.5px | Thicker stroke |
| SV-008 | data-size attribute | DOM attribute |

### Color Variants (11 tests)
| ID | Test | Description |
|----|------|-------------|
| CV-001 | default = text-current | Inherits color |
| CV-002 | primary = text-primary | Brand primary |
| CV-003 | success = text-emerald-600 | Green for success |
| CV-004 | warning = text-amber-500 | Amber for warnings |
| CV-005 | error = text-destructive | Red for errors |
| CV-006 | Default is default | No color prop |
| CV-007 | data-color attribute | DOM attribute |
| CV-008 | primary dark mode | dark:text-primary |
| CV-009 | success dark mode | dark:text-emerald-400 |
| CV-010 | warning dark mode | dark:text-amber-400 |
| CV-011 | error dark mode | dark:text-destructive |

### Custom SVG Rendering (5 tests)
| ID | Test | Description |
|----|------|-------------|
| CS-001 | Renders custom SVG | customIcon prop works |
| CS-002 | Custom overrides Lucide | Both props provided |
| CS-003 | Custom inherits size | Size applies to wrapper |
| CS-004 | Custom inherits color | Color applies to wrapper |
| CS-005 | Complex nested SVG | defs, gradients, paths |

### ViewBox and Styling (7 tests)
| ID | Test | Description |
|----|------|-------------|
| VS-001 | 24x24 viewBox | Standard Lucide viewBox |
| VS-002 | Default stroke width 2px | AC-002 requirement |
| VS-003 | Rounded corners (rounded-sm) | AC-005 requirement |
| VS-004 | SVG fills wrapper | size-full on SVG |
| VS-005 | Flex centering | items-center, justify-center |
| VS-006 | stroke-linecap round | Smooth line ends |
| VS-007 | stroke-linejoin round | Smooth line joins |

### Accessibility (6 tests)
| ID | Test | Description |
|----|------|-------------|
| AC-001 | aria-hidden by default | Decorative icon |
| AC-002 | role=img with aria-label | Meaningful icon |
| AC-003 | aria-label set when provided | Accessible name |
| AC-004 | aria-hidden removed with label | No conflict |
| AC-005 | focusable=false | Not keyboard focusable |
| AC-006 | Custom icon inherits a11y | Props propagate |

### Edge Cases (8 tests)
| ID | Test | Description |
|----|------|-------------|
| EC-001 | Empty className string | Handled gracefully |
| EC-002 | Null customIcon | Renders nothing |
| EC-003 | Undefined icon | Renders nothing |
| EC-004 | Both props null | Renders nothing |
| EC-005 | Multiple className values | Merges correctly |
| EC-006 | Invalid size fallback | Runtime safety |
| EC-007 | Invalid color fallback | Runtime safety |
| EC-008 | Rapid re-renders | No memory leaks |

### Module Exports (3 tests)
| ID | Test | Description |
|----|------|-------------|
| ME-001 | Exported from indicators/index | Barrel export |
| ME-002 | Named export available | Direct import |
| ME-003 | IndicatorIconProps type exported | TypeScript types |

### Integration (5 tests)
| ID | Test | Description |
|----|------|-------------|
| IN-001 | All size/color combinations | Matrix test |
| IN-002 | Parent color inheritance | text-current works |
| IN-003 | Dark mode container | Dark variants apply |
| IN-004 | Multiple icons alignment | Flex row test |
| IN-005 | Screen reader friendly | Accessible button |

### Snapshots (4 tests)
| ID | Test | Description |
|----|------|-------------|
| SN-001 | Default icon snapshot | Baseline |
| SN-002 | All sizes snapshot | sm, md, lg |
| SN-003 | All colors snapshot | 5 variants |
| SN-004 | Custom icon snapshot | SVG wrapper |

---

## Component Interface

```typescript
interface IndicatorIconProps {
  icon?: LucideIconName;           // Lucide icon name string
  customIcon?: React.ReactNode;     // Custom SVG element
  size?: 'sm' | 'md' | 'lg';        // Size variant (default: 'md')
  color?: 'default' | 'primary' | 'success' | 'warning' | 'error';  // Color variant
  className?: string;               // Additional CSS classes
  'aria-label'?: string;            // Accessibility label
}
```

## Size Mappings

| Size | Tailwind Class | Dimensions | Stroke Width |
|------|----------------|------------|--------------|
| `sm` | `size-4` | 16x16px | 1.5px |
| `md` | `size-6` | 24x24px | 2px |
| `lg` | `size-8` | 32x32px | 2.5px |

## Color Mappings

| Color | Light Mode | Dark Mode |
|-------|------------|-----------|
| `default` | `text-current` | `text-current` |
| `primary` | `text-primary` | `dark:text-primary` |
| `success` | `text-emerald-600` | `dark:text-emerald-400` |
| `warning` | `text-amber-500` | `dark:text-amber-400` |
| `error` | `text-destructive` | `dark:text-destructive` |

---

## Acceptance Criteria Coverage

| Criteria | Tests |
|----------|-------|
| AC-001: Accepts Lucide icon name | LI-001 to LI-012 |
| AC-001: Accepts custom SVG | CS-001 to CS-005 |
| AC-002: 24x24 viewBox with 2px stroke | VS-001, VS-002 |
| AC-003: Size variants (sm, md, lg) | SV-001 to SV-008 |
| AC-004: Color variants | CV-001 to CV-011 |
| AC-005: Rounded corners (1px) | VS-003 |
| AC-006: Inherits text color by default | CV-001, CV-006 |
| AC-007: TypeScript types defined | TD-001, ME-003 |
| AC-008: Exported from indicators index | ME-001 to ME-003 |

---

## Test Patterns Used

1. **Dynamic Imports**: Components imported after mocks are set up
2. **Mocked Lucide Icons**: Simplified SVGs for predictable testing
3. **Matrix Testing**: All size/color combinations tested
4. **Accessibility First**: ARIA attributes tested comprehensively
5. **Snapshot Regression**: Visual baseline for future changes

---

## Dependencies

- `lucide-react` - Icon library
- `@/lib/utils` - cn() utility for class merging
- `class-variance-authority` - For variant styling (implementation)

---

## Notes for Developer

1. Create `frontend/components/indicators/` directory
2. Create `IndicatorIcon.tsx` with the tested interface
3. Create `index.ts` barrel export
4. Use `cva` for size and color variants
5. Apply rounded corners via `rounded-sm` (1px radius in Tailwind)
6. Use `stroke-2` as default stroke width for md size
7. Ensure dark mode variants for all colors

---

## Test Execution

```bash
# Run all IndicatorIcon tests
npm test -- IndicatorIcon.test.tsx

# Run with coverage
npm test -- --coverage IndicatorIcon.test.tsx

# Update snapshots
npm test -- -u IndicatorIcon.test.tsx
```

---

Created by: PreTester
Date: 2026-03-03
