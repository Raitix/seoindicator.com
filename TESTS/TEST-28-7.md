# Test Results: TASK-28-7 - IndicatorCatalog Component

**Agent:** LocalTester
**Date:** 2026-03-04
**Status:** FAILED

## Summary

| Metric | Value |
|--------|-------|
| Test Suite | 1 failed |
| Tests Passed | 87 |
| Tests Failed | 33 |
| Total Tests | 120 |
| Snapshots Failed | 8 |

## Bug Fix Applied

### Fixed: Missing `Warning` icon import in IndicatorIcon.tsx

The `Warning` icon was referenced in `iconRegistry` but not imported from `lucide-react`.

**File:** `frontend/components/indicators/IndicatorIcon.tsx`
**Fix:** Added `Warning` to the import statement.

## Test Failure Categories

### 1. Missing `data-testid` Attributes (Critical)

The tests expect elements to have `data-testid` attributes, but the implementation uses `data-slot` instead:

| Expected testID | Actual Attribute | Affected Tests |
|-----------------|------------------|----------------|
| `tabs` | `data-slot="tabs"` | CT-008, CT-009, CT-012, SC-001, AC-008, EC-007 |
| `tabs-list` | `data-slot="tabs-list"` | CT-011, RM-007, AC-003 |
| `skeleton` | `data-slot="skeleton"` | LS-003, LS-008, LS-009, SC-003 |
| `separator` | Not found | CT-010, SC-002 |
| `tabs-content` | `data-slot="tabs-content"` | AC-005 |

**Recommendation:** Update `IndicatorCatalog.tsx` to add `data-testid` attributes alongside `data-slot` for testing purposes.

### 2. Category Change Callback Not Firing (Critical)

Tests expecting `onCategoryChange` to be called when clicking category tabs fail:

- IG-007: Category filter works correctly
- EC-010: Rapid category changes

**Root Cause:** The Tabs component's `onValueChange` may not be wired to call `onCategoryChange` callback.

### 3. Missing `data-indicator-count` Attribute

Tests IG-002, IG-003, IG-008, IG-009 expect `data-indicator-count` attribute on the indicator grid:

```tsx
// Expected:
<div data-indicator-count="8" ...>

// Actual:
<div ...>  // attribute missing
```

**Recommendation:** Add `data-indicator-count={indicators.length}` to the IndicatorGrid component.

### 4. Module Export Type Mismatch (ME-002)

Test expects:
```tsx
expect(typeof IndicatorCatalog).toBe('function');
```

But receives `'object'` because the component is exported with `forwardRef` which returns an object.

**Recommendation:** Update test to check for `'object'` or use a different assertion.

### 5. Snapshot Updates Required

8 snapshot tests fail due to implementation changes:
- SN-001 through SN-008

Changes include:
- Updated padding classes (`p-4 sm:p-6` → `px-4 py-4 sm:px-6 sm:py-5 md:px-8 md:py-6`)
- Added `transition-all duration-200 ease-out`
- Updated aria-label text
- Changed error/empty state styling
- Skeleton structure changes

**Recommendation:** Run `npm test -- -u` to update snapshots after fixing other issues.

## Files Requiring Changes

1. **`frontend/components/indicators/IndicatorCatalog.tsx`**
   - Add `data-testid` attributes to Tabs, TabsList, TabsContent, Separator elements
   - Ensure `onCategoryChange` is wired correctly to Tabs `onValueChange`

2. **`frontend/components/indicators/IndicatorGrid.tsx`**
   - Add `data-indicator-count` attribute

3. **`frontend/components/indicators/__tests__/IndicatorCatalog.test.tsx`**
   - Update ME-002 test for `typeof` check
   - Run snapshot update after fixes

## Recommended Actions for Developer

1. **Priority 1:** Add missing `data-testid` attributes to match test expectations
2. **Priority 2:** Fix category change callback wiring
3. **Priority 3:** Add `data-indicator-count` to grid
4. **Priority 4:** Update snapshots with `npm test -- -u`

## Lock File

Lock file created at: `TASKS/.locks/TASK-28-7.lock`
