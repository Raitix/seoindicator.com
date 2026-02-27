# TASK-7-13: Integrate with Ranking Check Workflow

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-13.md

## Description
Integrate SERP feature detection into the existing ranking check workflow. When a ranking check runs, it should also detect and record SERP features. This is the core integration that makes feature tracking work automatically.

## Acceptance Criteria
- [ ] Modify ranking check service to call SERP feature detector after fetching SERP data
- [ ] Store detected features in serp_features table
- [ ] Update rankings.serp_features JSON column
- [ ] Trigger change tracker to detect gains/losses
- [ ] Handle errors gracefully (feature detection failure shouldn't break ranking check)
- [ ] Add logging for debugging
- [ ] Performance: feature detection adds <1 second to ranking check
- [ ] Configuration flag to enable/disable feature tracking per site

## Implementation Notes
```typescript
class RankingService {
  async checkRanking(keyword: Keyword): Promise<Ranking> {
    // 1. Fetch SERP data (existing)
    const serpResult = await this.serpFetcher.fetch(keyword.query);
    
    // 2. Parse ranking (existing)
    const ranking = this.parseRanking(serpResult, keyword);
    
    // 3. NEW: Detect SERP features
    if (keyword.site.enableSerpFeatures) {
      try {
        const features = await this.featureDetector.detectFeatures(
          keyword.query,
          keyword.site.url,
          serpResult
        );
        
        // 4. Store features
        await this.featureRepo.insertBatch(ranking.id, keyword.id, features);
        
        // 5. Update ranking JSON
        ranking.serpFeatures = this.featuresToJSON(features);
        
        // 6. Track changes
        const previousFeatures = await this.featureRepo.findByKeywordId(keyword.id, { limit: 1, offset: 1 });
        await this.changeTracker.trackChanges(keyword.id, features, previousFeatures);
      } catch (error) {
        logger.error('SERP feature detection failed', { keyword: keyword.id, error });
        // Don't throw - ranking check should succeed
      }
    }
    
    return ranking;
  }
}
```

## Dependencies
- TASK-7-4 (Detector service)
- TASK-7-8 (Change tracker)
- FEAT-001 (Ranking check service)

## Effort Estimate
Medium (4-5 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for workflow integration. This is the critical integration point. Risk Medium due to modification of core ranking check flow.
