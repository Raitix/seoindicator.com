# TASK-36-6: ContentDecayDetectionService

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## SPECIFICATION
SPEC-036: Content Decay Detection & Refresh Planner

---

## Description
Create the main detection orchestration service that gathers signals from multiple sources (GSC, rankings, audits, annotations, AI visibility), compares windows, and creates/updates decay candidates.

## Dependencies
- TASK-36-2 (Eloquent Models) - To Prioritize
- TASK-36-3 (ContentDecayScoringService) - To Prioritize
- TASK-36-4 (ContentDecayReasonService) - To Prioritize
- TASK-36-5 (ContentRefreshPlannerService) - To Prioritize
- FEAT-012 (GSC Integration) - Done ✅
- FEAT-001 (Rank Tracking) - Done ✅
- FEAT-014 (Site Audit) - Partial ✅

## Acceptance Criteria
- [ ] `ContentDecayDetectionService` class created with:
  - `detectForSite(Site $site): array` method returning detection results
  - `detectForProject(ContentDecayProject $project): array` method for specific project
  - `gatherSignals(string $pageUrl, ContentDecayProject $project): array` method
  - `compareWindows(array $signals): array` method
  - `upsertCandidate(array $detectionData): ContentDecayCandidate` method
- [ ] Eligibility filtering implemented:
  - Page has GSC data for both windows
  - Previous window impressions >= threshold (default: 100) OR clicks >= threshold (default: 10)
  - Page has existed long enough for 2 full windows
  - Page is not in ignore state
- [ ] Cooldown/suppression rules:
  - Suppress new decay alerts for pages marked `refreshed` in last 14 days
  - Suppress high-confidence flags when annotation indicates recent content update
  - Suppress candidates where total movement is below low-signal threshold
- [ ] Signal gathering from sources:
  - GSC (required): clicks, impressions, CTR, avg position
  - Rank tracking (optional): tracked keyword visibility
  - Site audit (optional): unresolved issues for page
  - Annotations (optional): recent update annotations
  - AI visibility (optional): mention rate delta
- [ ] Window comparison:
  - Default: last 28 complete days vs prior 28 complete days
  - Configurable window sizes per project
- [ ] Graceful degradation when optional sources unavailable
- [ ] Unit tests verify eligibility filtering
- [ ] Unit tests verify signal gathering
- [ ] Unit tests verify window comparison
- [ ] Feature tests verify full detection flow

## Effort Estimate
**8 hours** - Core detection orchestration with multiple data sources

## Technical Notes
### Detection Flow
```
1. Load project/site settings
2. Get list of eligible pages from GSC
3. For each page:
   a. Gather signals from all available sources
   b. Compare windows
   c. Calculate decay score
   d. Assign reason codes
   e. Upsert candidate record
4. Archive/downgrade candidates no longer meeting thresholds
5. Return detection summary
```

### Signal Collection Example
```php
[
    'gsc' => [
        'previous' => ['clicks' => 1250, 'impressions' => 20000, ...],
        'current' => ['clicks' => 850, 'impressions' => 18000, ...],
    ],
    'rankings' => [ // optional
        'visibility' => ['previous' => 45.2, 'current' => 38.1],
    ],
    'audit' => [ // optional
        'unresolved_issues' => 3,
    ],
    'ai_visibility' => [ // optional
        'mention_rate' => ['previous' => 0.12, 'current' => 0.08],
    ]
]
```

## Files to Create/Modify
- `backend/app/Services/ContentDecay/ContentDecayDetectionService.php`
- `backend/tests/Unit/Services/ContentDecay/ContentDecayDetectionServiceTest.php`
- `backend/tests/Feature/Services/ContentDecay/ContentDecayDetectionServiceTest.php`

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
