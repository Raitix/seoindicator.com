# TASK-12-6: Implement Fuzzy Query Matching

## STATUS
`To Prioritize`

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
- [ ] Fuzzy matching implemented with configurable threshold.
- [ ] Auto-matching runs during data sync.
- [ ] Users can manually adjust matches via UI.
- [ ] Unit tests verify matching accuracy.

## Dependencies
- TASK-12-3: `gsc_query_match` column must exist.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 17:35] Agent: SpecSystemAnalyst
Backend logic for linking tracked keywords to GSC query data.