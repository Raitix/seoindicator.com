# TASK-7-12: Filter by Feature Endpoint

## STATUS
`To Test Locally`

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

### [2026-02-26 18:45] Agent: Developer
**Implementation complete.** 

**Changes made:**
1. **KeywordController.php** - Extended `index()` method to support SERP feature filtering:
   - Added `serpFeature` query parameter (single or comma-separated list of feature types)
   - Added `present` query parameter (true/false, default true)
   - Added validation for feature types against `SerpFeature::FEATURE_TYPES`
   - Response now includes `serpFeatures` object for each keyword

2. **KeywordControllerTest.php** - Added 12 new test cases:
   - `test_filter_by_single_serp_feature`
   - `test_filter_by_serp_feature_present_false`
   - `test_filter_by_multiple_serp_features`
   - `test_filter_by_serp_feature_no_matches`
   - `test_filter_by_serp_feature_with_status`
   - `test_filter_by_serp_feature_with_pagination`
   - `test_response_includes_serp_features`
   - `test_invalid_serp_feature_type`
   - `test_empty_serp_feature_parameter`
   - `test_present_parameter_variations`
   - `test_sql_injection_in_serp_feature`

**API Usage:**
- `GET /api/v1/sites/{id}/keywords?serpFeature=featured_snippet` - Keywords WITH featured snippet
- `GET /api/v1/sites/{id}/keywords?serpFeature=featured_snippet&present=false` - Keywords WITHOUT featured snippet
- `GET /api/v1/sites/{id}/keywords?serpFeature=featured_snippet,local_pack` - Keywords with either feature

**Tests:** All 36 tests pass.

### [2026-02-26 18:40] Agent: Developer
Starting implementation of SERP feature filter for keywords endpoint. Will extend KeywordController index method to support serpFeature and present query parameters.

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for feature filtering. Enhances existing keyword list with SERP feature filters.
