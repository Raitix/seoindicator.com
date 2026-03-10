# TASK-36-9: Queue Jobs - Content Decay Detection

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
Create queue jobs for background detection, recovery detection, digest generation, and optional AI brief generation.

## Dependencies
- TASK-36-6 (ContentDecayDetectionService) - To Prioritize
- TASK-36-7 (ContentDecayDigestService) - To Prioritize
- FEAT-019 (Job Queue) - Done ✅

## Acceptance Criteria
- [ ] `DetectContentDecayForSiteJob` created with:
  - Accepts `site_id` parameter
  - Loads site and project settings
  - Calls ContentDecayDetectionService
  - Updates project `last_scanned_at` timestamp
  - Handles failures gracefully with logging
  - Queue: `content-decay`
  - Tries: 3
  - Timeout: 600 seconds (10 minutes)
- [ ] `BackfillContentDecayRecoveryJob` created with:
  - Accepts `candidate_id` parameter
  - Evaluates refreshed candidates for recovery status
  - Updates candidate status to `recovered` if criteria met
  - Queue: `content-decay`
  - Tries: 3
- [ ] `SendContentDecayDigestJob` created with:
  - Accepts `project_id` parameter
  - Generates digest via ContentDecayDigestService
  - Sends notification to project owner
  - Queue: `notifications`
  - Tries: 3
- [ ] `GenerateRefreshBriefJob` created with:
  - Accepts `candidate_id` parameter
  - Uses existing AI infrastructure (FEAT-031)
  - Generates refresh outline with suggestions
  - Stores brief in candidate's evidence_json
  - Queue: `ai-tasks`
  - Tries: 2
  - Timeout: 120 seconds
- [ ] All jobs implement `ShouldBeUnique` where appropriate
- [ ] All jobs have proper error handling and logging
- [ ] Unit tests verify job execution
- [ ] Feature tests verify job queue integration

## Effort Estimate
**4 hours** - Four queue jobs with error handling

## Technical Notes
### Job Scheduling
```php
// Schedule in app/Console/Kernel.php
$schedule->job(new DetectContentDecayForSiteJob($siteId))
    ->daily()
    ->when(fn() => $project->detection_enabled);

$schedule->job(new SendContentDecayDigestJob($projectId))
    ->weekly()
    ->when(fn() => $project->digest_enabled);
```

### Recovery Detection Logic
```php
// In BackfillContentDecayRecoveryJob
$criteria = [
    'clicks_improved' => $postRefreshClicks > $baselineClicks * 1.1,
    'ctr_improved' => $postRefreshCtr > $baselineCtr * 1.05,
    'position_improved' => $postRefreshPosition < $baselinePosition - 2,
];
if (count(array_filter($criteria)) >= 2) {
    $candidate->markAsRecovered();
}
```

## Files to Create/Modify
- `backend/app/Jobs/DetectContentDecayForSiteJob.php`
- `backend/app/Jobs/BackfillContentDecayRecoveryJob.php`
- `backend/app/Jobs/SendContentDecayDigestJob.php`
- `backend/app/Jobs/GenerateRefreshBriefJob.php`
- `backend/tests/Unit/Jobs/DetectContentDecayForSiteJobTest.php`
- `backend/tests/Unit/Jobs/BackfillContentDecayRecoveryJobTest.php`
- `backend/tests/Unit/Jobs/SendContentDecayDigestJobTest.php`
- `backend/tests/Unit/Jobs/GenerateRefreshBriefJobTest.php`

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
