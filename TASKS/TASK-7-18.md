# TASK-7-18: Feature Filter UI

## STATUS
`To Prioritize`

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
Created task for filter enhancements—tight integration with dashboard table.
