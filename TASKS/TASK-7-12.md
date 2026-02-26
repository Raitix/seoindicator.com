# TASK-7-12: Filter by Feature Endpoint

## STATUS
`To Develop`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-12.md

## Description
Extend the keywords listing endpoint to support filtering by SERP feature presence. This allows users to quickly find all keywords that have (or don't have) specific SERP features.

## Acceptance Criteria
- [ ] Extend `GET /api/v1/sites/:siteId/keywords` with serpFeature parameter
- [ ] `?serpFeature=featured_snippet` returns only keywords with that feature
- [ ] `?serpFeature=featured_snippet&present=false` returns keywords without feature
- [ ] Support multiple feature filter: `?serpFeature=featured_snippet,local_pack`
- [ ] Combine with existing filters (position, search engine, etc.)
- [ ] Response includes serpFeatures for each keyword
- [ ] Performance: filter should use indexes, not full scan
- [ ] Documentation updated

## Implementation Notes
```typescript
// Extend existing keywords endpoint
router.get('/sites/:siteId/keywords', async (req, res) => {
  const { serpFeature, serpFeaturePresent, ...otherFilters } = req.query;
  
  let query = keywordRepo.createQueryBuilder('keyword');
  
  if (serpFeature) {
    const features = serpFeature.split(',');
    query = query.innerJoin('keyword.serpFeatures', 'sf')
      .where('sf.feature_type IN (:features)', { features })
      .andWhere('sf.is_present = :present', { 
        present: serpFeaturePresent !== 'false' 
      });
  }
  
  // ... rest of query
});
```

## Dependencies
- TASK-7-3 (Repository)
- Existing keywords endpoint

## Effort Estimate
Low (2-3 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for feature filtering. Enhances existing keyword list with SERP feature filters.
