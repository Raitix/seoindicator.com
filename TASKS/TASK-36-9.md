# TASK-36-9: Queue Jobs - Content Decay Detection

## STATUS
`To Write Tests`

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

## Comments

### ProjectManager (2026-03-10 22:03)
**Prioritization Complete**

**Priority: HIGH** (confirmed - already set)
- Queue jobs are **production-enabling infrastructure** for SPEC-036
- Without these jobs:
  - No background detection runs
  - No weekly digest delivery
  - No recovery backfill processing
  - No optional AI brief generation
- Jobs integrate multiple services: DetectionService, DigestService, AI infrastructure

**Risk: MEDIUM** (confirmed - already set)
- 4 queue jobs with error handling and logging
- No direct external API calls - delegates to existing services
- Proper queue configuration (tries: 2-3, timeouts: 120-600s)
- ShouldBeUnique implementation prevents duplicate processing
- Graceful error handling with logging

**Dependencies Analysis:**
| Dependency | Status | TDD Impact |
|------------|--------|------------|
| TASK-36-6 (DetectionService) | To Write Tests | Jobs CAN be tested with mocked service |
| TASK-36-7 (DigestService) | To Prioritize | Jobs CAN be tested with mocked service |
| FEAT-019 (Job Queue) | Done ✅ | Queue infrastructure available |
| FEAT-031 (AI Infrastructure) | Done ✅ | AI brief job infrastructure exists |

**TDD Pattern Applied:**
- PreTester CAN design tests immediately with mocked service interfaces
- Jobs are thin wrappers around services - mock all service calls
- Test job execution: parameter acceptance, service calls, error handling, logging
- Queue integration tests verify job dispatch and handling

**Job Architecture:**
| Job | Purpose | Queue | Tries | Timeout |
|-----|---------|-------|-------|---------|
| `DetectContentDecayForSiteJob` | Background decay detection | content-decay | 3 | 600s |
| `BackfillContentDecayRecoveryJob` | Recovery status evaluation | content-decay | 3 | - |
| `SendContentDecayDigestJob` | Weekly digest delivery | notifications | 3 | - |
| `GenerateRefreshBriefJob` | AI refresh outline | ai-tasks | 2 | 120s |

**Execution Order for SPEC-036:**
1. TASK-36-1 (Migrations) → Done
2. TASK-36-2 (Models) → To Write Tests
3. TASK-36-3 to 36-5 (Services) → To Write Tests (parallel)
4. TASK-36-6 (Detection Orchestrator) → To Write Tests
5. TASK-36-7 (Digest Service) → To Prioritize
6. **TASK-36-9 (Queue Jobs) → This task**
7. TASK-36-10 (API Controllers) → Frontend tasks

**Scheduling Integration:**
```php
// Daily detection run
$schedule->job(new DetectContentDecayForSiteJob($siteId))
    ->daily()
    ->when(fn() => $project->detection_enabled);

// Weekly digest
$schedule->job(new SendContentDecayDigestJob($projectId))
    ->weekly()
    ->when(fn() => $project->digest_enabled);
```

**Downstream Impact:**
| Task | Dependency Type | Impact |
|------|-----------------|--------|
| TASK-36-10 (API Controllers) | Optional | API can trigger manual detection runs |
| Frontend Tasks | Optional | UI can dispatch jobs on user action |

**PreTester Queue Status:**
- Multiple tasks at "To Write Tests" - queue has capacity
- Dependencies can be mocked for test design

**Status:** `To Write Tests` → Ready for PreTester

---

*Created by SpecSystemAnalyst agent on 2026-03-10*
