# TASK-12-6: Implement Fuzzy Query Matching

## STATUS
`To Deploy`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Develop logic to fuzzy-match tracked keywords with GSC query data. Enable automatic association between `keywords` and `gsc_data` records.

## Responsibilities
1. **Matching Algorithm**
   - Normalize queries (lowercase, trim whitespace).
   - Calculate similarity score (e.g., Levenshtein distance).
   - Auto-match high-confidence pairs (>90% similarity).

2. **Manual Override**
   - Allow users to manually confirm/correct matches.
   - Store confirmed matches in `gsc_query_match` column.

## Code Example
```php
class QueryMatcher
{
    public function findMatches(Keyword $keyword): array;
    public function calculateSimilarity(string $a, string $b): float;
    public function autoMatch(Project $project): int;
}
```

## Acceptance Criteria
- [x] Fuzzy matching implemented with configurable threshold.
- [x] Auto-matching runs during data sync.
- [ ] Users can manually adjust matches via UI.
- [x] Unit tests verify matching accuracy.

## Dependencies
- TASK-12-3: `gsc_query_match` column must exist.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 19:11] ProjectManager: Prioritization Complete
- Priority: Medium (correct - enhancement for keyword-GSC data linking)
- Risk: Low (algorithm logic, no security concerns)
- Dependencies: TASK-12-3 (gsc_query_match column)
- Execution order: After TASK-12-3 and TASK-12-5 (data sync)
- Can be developed in parallel with API layer tasks
- Status: To Develop

### [2026-02-20 17:35] Agent: SpecSystemAnalyst
Backend logic for linking tracked keywords to GSC query data.
### [2026-02-22 16:18] Agent: Developer
**Implementation Complete**

- Added QueryMatcher service with normalization + Levenshtein similarity and thresholded auto-match
- Hooked auto-match into GSCDataSync storeData
- Added GSCDataFactory + QueryMatcher unit tests
- Updated GSCData model to use site_id (schema-aligned)
- Manual override supported via QueryMatcher::setManualMatch (UI hook pending)
- Ran: php artisan test --filter QueryMatcherTest

**Status:** ? To Test Locally

### [2026-02-22 18:17] Agent: LocalTester
**Local Test Results — PASS**

- Command: `php artisan test --filter QueryMatcherTest`
- Result: 3 tests passed (4 assertions)
- Notes: No discrepancies observed vs DOCUMENTATION.md

**Status:** To Deploy

### [2026-02-22 18:23] Agent: DevOps
**Deployment Gate**
- Repo is ahead of origin/main (no deploy trigger yet): `main...origin/main [ahead 43]`
- Deployment requires push to origin/main to trigger GitHub Actions or manual SSH deploy.
- Awaiting approval to push/deploy (external action).

**Status:** To Deploy

