# TASK-7-6: Local Pack Detection

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-6.md

## Description
Implement local pack detection logic. Local pack appears for queries with local intent and shows 3 businesses on a map. Tracking this helps users understand local SEO performance.

## Acceptance Criteria
- [ ] `detectLocalPack(serp: SERPResult, targetUrl: string)` method implemented
- [ ] Correctly identifies if local_pack exists in SERP results
- [ ] Extracts all 3 local pack entries
- [ ] Determines if target URL is in local pack
- [ ] Returns `SERPFeature` object with:
  - type: 'local_pack'
  - isPresent: boolean (true if target URL in local pack)
  - position: number (1, 2, or 3 within local pack, if present)
- [ ] Handles cases where no local pack exists
- [ ] Handles partial local pack data (fewer than 3 results)
- [ ] Unit tests cover edge cases

## Implementation Notes
```typescript
private detectLocalPack(serp: SERPResult, targetUrl: string): SERPFeature {
  const localPack = serp.localPack;
  if (!localPack || localPack.length === 0) {
    return { type: 'local_pack', isPresent: false };
  }
  
  const targetDomain = new URL(targetUrl).hostname;
  const position = localPack.findIndex(item => 
    item.url?.includes(targetDomain)
  );
  
  return {
    type: 'local_pack',
    isPresent: position >= 0,
    position: position >= 0 ? position + 1 : undefined
  };
}
```

## Dependencies
- TASK-7-4 (Detector service)

## Effort Estimate
Low (2-3 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for local pack detection. Post-MVP feature but important for local SEO tracking.
