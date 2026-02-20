# TASK-12-3: Add GSC Query Match Column to Keywords

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Add a `gsc_query_match` column to the `keywords` table to support fuzzy matching between tracked keywords and GSC query data.

## Schema Requirements
```sql
ALTER TABLE keywords ADD COLUMN gsc_query_match VARCHAR(255) NULL COMMENT 'Fuzzy match to GSC queries';
```

## Acceptance Criteria
- [ ] Migration adds the `gsc_query_match` column.
- [ ] Column allows NULL values for keywords without GSC matches.
- [ ] Index created for efficient matching queries.

## Dependencies
- Existing `keywords` table.

## Effort Estimate
1 hour.

---

## Comments
### [2026-02-20 18:56] ProjectManager: Prioritization Complete
- Priority: Medium (correct - enhancement for keyword-GSC linking)
- Risk: Low (simple ALTER TABLE, non-breaking change)
- Dependencies: keywords table (from FEAT-001)
- Execution order: After TASK-12-2, before query matching service
- Quick win: 1 hour effort, can be done early in sprint
- Status: To Develop

### [2026-02-20 17:34] Agent: SpecSystemAnalyst
Database modification to link tracked keywords with GSC query data.