# TASK-7-9: Feature History Repository

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-9.md

## Description
Extend the repository layer with methods specific to feature history operations. This includes querying historical feature data, generating feature timelines, and aggregating feature statistics.

## Acceptance Criteria
- [ ] `getFeatureTimeline(keywordId: number, days: number)` method - returns daily feature presence
- [ ] `getFeatureGainsBySite(siteId: number, days: number)` method - all gains in period
- [ ] `getFeatureLossesBySite(siteId: number, days: number)` method - all losses in period
- [ ] `getFeatureStatistics(siteId: number)` method - aggregates (total features, by type)
- [ ] `getLongestHeldFeatures(siteId: number, limit: number)` method - features held longest
- [ ] `getMostVolatileKeywords(siteId: number, limit: number)` method - keywords with most feature changes
- [ ] All methods return properly typed results
- [ ] Query optimization for large datasets
- [ ] Pagination support for list methods

## Implementation Notes
Feature timeline query:
```sql
SELECT 
  date(checked_at) as date,
  json_group_object(feature_type, is_present) as features
FROM serp_features
WHERE keyword_id = ? AND checked_at >= datetime('now', ?)
GROUP BY date(checked_at)
ORDER BY date DESC;
```

File location: `backend/src/repositories/serp-feature-history-repo.ts`

## Dependencies
- TASK-7-3 (SERP Feature Repository)

## Effort Estimate
Medium (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for history repository. Provides data access for feature history and statistics.

### [2026-02-27 11:50] Agent: Developer
Starting implementation. The SerpFeatureHistoryRepository already exists with basic methods. Need to enhance:
- getFeatureTimeline: needs to return daily feature presence with all feature types
- getFeatureGainsBySite: needs pagination support
- getFeatureLossesBySite: needs pagination support
- getFeatureStatistics: needs full statistics with gains/losses counts
- getLongestHeldFeatures: needs proper calculation of held duration
- getMostVolatileKeywords: needs gains/losses breakdown
- All methods need pagination support
