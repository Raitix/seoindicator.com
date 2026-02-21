# TASK-7-8: Feature Change Tracker

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Medium`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-8.md

## Description
Create the feature change tracker service that detects when SERP features are gained or lost. This service compares current feature state with previous state and records changes in the history table.

## Acceptance Criteria
- [ ] `SERPFeatureChangeTracker` class created
- [ ] `trackChanges(keywordId: number, currentFeatures: SERPFeature[], previousFeatures: SERPFeature[])` method implemented
- [ ] Correctly detects 'gained' feature transitions (false → true)
- [ ] Correctly detects 'lost' feature transitions (true → false)
- [ ] Records each change in serp_feature_history table
- [ ] Returns array of `SERPFeatureChange` objects
- [ ] Handles first-time detection (no previous features) - no 'gained' events
- [ ] Handles URL changes for features (previous_url vs current_url)
- [ ] Emits events for alert system integration
- [ ] Unit tests for all transition scenarios

## Implementation Notes
```typescript
interface SERPFeatureChange {
  type: SERPFeatureType;
  action: 'gained' | 'lost';
  keywordId: number;
  previousUrl?: string;
  currentUrl?: string;
  detectedAt: Date;
}

class SERPFeatureChangeTracker {
  async trackChanges(
    keywordId: number,
    currentFeatures: SERPFeature[],
    previousFeatures: SERPFeature[]
  ): Promise<SERPFeatureChange[]> {
    const changes: SERPFeatureChange[] = [];
    
    for (const featureType of ALL_FEATURE_TYPES) {
      const current = currentFeatures.find(f => f.type === featureType);
      const previous = previousFeatures.find(f => f.type === featureType);
      
      // Gained: wasn't present before, is present now
      if (current?.isPresent && !previous?.isPresent) {
        changes.push({ type: featureType, action: 'gained', ... });
      }
      
      // Lost: was present before, not present now
      if (!current?.isPresent && previous?.isPresent) {
        changes.push({ type: featureType, action: 'lost', ... });
      }
    }
    
    return changes;
  }
}
```

File location: `backend/src/services/serp-feature-service.ts`

## Dependencies
- TASK-7-3 (Repository)
- TASK-7-4 (Detector service)

## Effort Estimate
Medium (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for change tracking. Critical for alert generation. Risk Medium due to state management complexity.
