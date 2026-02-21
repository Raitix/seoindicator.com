# TASK-7-4: SERP Feature Detector Service

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-4.md

## Description
Create the main SERP feature detector service that orchestrates the detection of all SERP feature types. This service receives SERP results and determines which features are present for a given target URL.

## Acceptance Criteria
- [ ] `SERPFeatureDetector` class created
- [ ] `detectFeatures(keyword: string, targetUrl: string, serpResult: SERPResult)` method implemented
- [ ] Method returns array of `SERPFeature` objects for all feature types
- [ ] Each feature type has dedicated detection method (pluggable architecture)
- [ ] URL matching logic for determining if feature belongs to target site
- [ ] Support for mock SERP data (for development/testing)
- [ ] Error handling for malformed SERP data
- [ ] Logging for debugging feature detection

## Implementation Notes
```typescript
class SERPFeatureDetector {
  async detectFeatures(
    keyword: string,
    targetUrl: string,
    serpResult: SERPResult
  ): Promise<SERPFeature[]> {
    const features: SERPFeature[] = [];
    for (const detector of this.detectors) {
      features.push(await detector.detect(serpResult, targetUrl));
    }
    return features;
  }
}
```

File location: `backend/src/services/serp-feature-detector.ts`

## Dependencies
- TASK-7-2 (Types and models)

## Effort Estimate
Medium (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for detector service. Core service that coordinates all feature detection. Risk Medium due to complexity of parsing SERP data.
