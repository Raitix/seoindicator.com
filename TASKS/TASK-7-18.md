# TASK-7-18: Feature Filter UI

## STATUS
`To Test Locally`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-18.md

## Description
Expand frontend UI to allow users to filter keywords by SERP feature presence. This improves discoverability and allows for better analysis in the dashboard.

## Acceptance Criteria
- [ ] Add filter dropdown: "SERP Feature"
  - Options: All, Featured Snippet, Local Pack, etc.
  - Fetch options dynamically from `/api/v1/metadata` (for extensibility)
- [ ] Add checkbox: "Show Only Keywords With Features"
- [ ] Modifies existing keyword dashboard table filter state
- [ ] Combines seamlessly with existing filters
- [ ] Fetches updated results via `GET /api/v1/sites/:siteId/keywords?serpFeature=`

## Implementation Notes
Adjust filter bar component:
```tsx
function KeywordFilterBar() {
  return (
    <Dropdown label="SERP Features">
      {FEATURE_TYPES.map(ft => (
        <DropdownItem key={ft.value}>
          {ft.label}
        </DropdownItem>
      ))}
    </Dropdown>
  );
}
```

## Dependencies
- TASK-7-12 (Backend: Filter-by-feature parameter)

## Effort Estimate
Medium (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for filter enhancementsâ€”tight integration with dashboard table.

### [2026-03-06 22:55] Agent: Developer
Implementation complete. All tests pass.

**Completed work:**
- `KeywordFilterBar` component with SERP feature dropdown (dynamically loaded from `/api/v1/metadata/serp-features`)
- "Show Only Keywords With SERP Features" checkbox
- Integration with `KeywordList` component
- URL state management for filter persistence
- Active filter tags with removal capability
- Clear filters button

**Tests:**
- 23/23 unit tests pass (`KeywordFilterBar.test.tsx`)
- 16/16 integration tests pass (`keyword-filter-serp.test.tsx`)

**Files:**
- `components/keywords/KeywordFilterBar.tsx` - Filter bar component
- `components/keywords/KeywordList.tsx` - Integration with existing table
- `lib/api/metadata.ts` - `useSERPFeatureOptions` hook
- `lib/api/keywords.ts` - Updated query params to include `serpFeature` and `present`
- `__tests__/components/KeywordFilterBar.test.tsx` - Unit tests
- `__tests__/integration/keyword-filter-serp.test.tsx` - Integration tests
