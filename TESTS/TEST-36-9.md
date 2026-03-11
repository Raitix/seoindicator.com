# TEST-36-9: Queue Jobs - Content Decay Detection

## Overview
Tests for queue jobs that handle background detection, recovery detection, digest generation, and AI brief generation.

---

## Test Files

| File | Location | Tests |
|------|----------|-------|
| DetectContentDecayForSiteJobTest.php | tests/Unit/Jobs/ContentDecay/ | 85+ tests |
| BackfillContentDecayRecoveryJobTest.php | tests/Unit/Jobs/ContentDecay/ | 70+ tests |
| SendContentDecayDigestJobTest.php | tests/Unit/Jobs/ContentDecay/ | 65+ tests |
| GenerateRefreshBriefJobTest.php | tests/Unit/Jobs/ContentDecay/ | 60+ tests |
| **Total** | | **280+ tests** |

---

## Job Specifications

### 1. DetectContentDecayForSiteJob
**Purpose:** Background decay detection for a site

| Property | Value |
|----------|-------|
| Queue | `content-decay` |
| Tries | 3 |
| Timeout | 600 seconds (10 min) |
| Unique | Yes (per site) |

**Parameters:**
- `site_id` (int) - The site to process

**Behavior:**
1. Load site and project settings
2. Call ContentDecayDetectionService::detectForSite()
3. Update project `last_scanned_at` timestamp
4. Handle failures gracefully with logging

---

### 2. BackfillContentDecayRecoveryJob
**Purpose:** Evaluate refreshed candidates for recovery status

| Property | Value |
|----------|-------|
| Queue | `content-decay` |
| Tries | 3 |
| Timeout | 300 seconds |
| Unique | Yes (per candidate) |

**Parameters:**
- `candidate_id` (int) - The candidate to evaluate

**Recovery Criteria (must meet 2 of 3):**
- Clicks improved: postRefresh > baseline * 1.1
- CTR improved: postRefresh > baseline * 1.05
- Position improved: postRefresh < baseline - 2

---

### 3. SendContentDecayDigestJob
**Purpose:** Generate and send weekly digest notifications

| Property | Value |
|----------|-------|
| Queue | `notifications` |
| Tries | 3 |
| Timeout | 120 seconds |
| Unique | Yes (per project) |

**Parameters:**
- `project_id` (int) - The project to generate digest for

**Behavior:**
1. Generate digest via ContentDecayDigestService
2. Send notification to project owner
3. Log delivery status

---

### 4. GenerateRefreshBriefJob
**Purpose:** Generate AI-powered refresh recommendations

| Property | Value |
|----------|-------|
| Queue | `ai-tasks` |
| Tries | 2 |
| Timeout | 120 seconds |
| Unique | Yes (per candidate) |

**Parameters:**
- `candidate_id` (int) - The candidate to generate brief for

**Behavior:**
1. Load candidate and project context
2. Use AI infrastructure (FEAT-031) to generate brief
3. Store brief in candidate's `evidence_json`

---

## Test Categories

### DetectContentDecayForSiteJob

#### DJ-INST: Job Instantiation (10 tests)
| Test ID | Description |
|---------|-------------|
| DJ-INST-01 | Job class exists in correct namespace |
| DJ-INST-02 | Job implements ShouldQueue |
| DJ-INST-03 | Job has $tries property set to 3 |
| DJ-INST-04 | Job has $timeout property set to 600 |
| DJ-INST-05 | Job has $queue property set to 'content-decay' |
| DJ-INST-06 | Job implements ShouldBeUnique |
| DJ-INST-07 | Job constructor accepts site_id parameter |
| DJ-INST-08 | Job stores site_id as property |
| DJ-INST-09 | Job has handle() method |
| DJ-INST-10 | Job has failed() method for error handling |

#### DJ-DISPATCH: Job Dispatch (12 tests)
| Test ID | Description |
|---------|-------------|
| DJ-DISPATCH-01 | Job can be dispatched with site_id |
| DJ-DISPATCH-02 | Job dispatched to correct queue |
| DJ-DISPATCH-03 | Job unique ID prevents duplicates |
| DJ-DISPATCH-04 | Job unique for 3600 seconds |
| DJ-DISPATCH-05 | Job dispatch after database transaction |
| DJ-DISPATCH-06 | Job dispatch with delay |
| DJ-DISPATCH-07 | Job chain dispatch |
| DJ-DISPATCH-08 | Job batch dispatch |
| DJ-DISPATCH-09 | Job release on lock contention |
| DJ-DISPATCH-10 | Job middleware applies rate limiting |
| DJ-DISPATCH-11 | Job without overlapping middleware |
| DJ-DISPATCH-12 | Job skip if site not found |

#### DJ-HANDLE: Job Handle Method (20 tests)
| Test ID | Description |
|---------|-------------|
| DJ-HANDLE-01 | Loads site by site_id |
| DJ-HANDLE-02 | Loads ContentDecayProject for site |
| DJ-HANDLE-03 | Checks if detection_enabled is true |
| DJ-HANDLE-04 | Calls DetectionService::detectForSite() |
| DJ-HANDLE-05 | Updates project last_scanned_at |
| DJ-HANDLE-06 | Handles site not found gracefully |
| DJ-HANDLE-07 | Handles project not found gracefully |
| DJ-HANDLE-08 | Handles detection disabled gracefully |
| DJ-HANDLE-09 | Logs start of detection |
| DJ-HANDLE-10 | Logs successful completion |
| DJ-HANDLE-11 | Logs warning on disabled detection |
| DJ-HANDLE-12 | Returns early if site missing |
| DJ-HANDLE-13 | Returns early if project missing |
| DJ-HANDLE-14 | Returns early if detection disabled |
| DJ-HANDLE-15 | Handles DetectionService exceptions |
| DJ-HANDLE-16 | Updates timing metrics |
| DJ-HANDLE-17 | Dispatches completion event |
| DJ-HANDLE-18 | Handles concurrent job detection |
| DJ-HANDLE-19 | Validates site_id is positive integer |
| DJ-HANDLE-20 | Handles invalid site_id |

#### DJ-RETRY: Job Retry Logic (12 tests)
| Test ID | Description |
|---------|-------------|
| DJ-RETRY-01 | Job retries on transient failure |
| DJ-RETRY-02 | Job respects max tries (3) |
| DJ-RETRY-03 | Job exponential backoff between retries |
| DJ-RETRY-04 | Job doesn't retry on validation failure |
| DJ-RETRY-05 | Job doesn't retry on site not found |
| DJ-RETRY-06 | Job logs retry attempt |
| DJ-RETRY-07 | Job retry count tracked |
| DJ-RETRY-08 | Job releases back to queue on retry |
| DJ-RETRY-09 | Job respects retry_until timestamp |
| DJ-RETRY-10 | Job backoff calculation correct |
| DJ-RETRY-11 | Job fails after max retries |
| DJ-RETRY-12 | Job cleanup on final failure |

#### DJ-FAIL: Job Failure Handling (10 tests)
| Test ID | Description |
|---------|-------------|
| DJ-FAIL-01 | Job failed() method called on exception |
| DJ-FAIL-02 | Job logs failure with context |
| DJ-FAIL-03 | Job sends failure notification |
| DJ-FAIL-04 | Job updates failure metrics |
| DJ-FAIL-05 | Job marks project as failed |
| DJ-FAIL-06 | Job exception passed to failed() |
| DJ-FAIL-07 | Job handles missing site gracefully |
| DJ-FAIL-08 | Job handles database connection failure |
| DJ-FAIL-09 | Job cleanup partial state |
| DJ-FAIL-10 | Job preserves exception chain |

#### DJ-UNIQUE: Job Uniqueness (10 tests)
| Test ID | Description |
|---------|-------------|
| DJ-UNIQUE-01 | Job unique per site_id |
| DJ-UNIQUE-02 | Job unique for default duration |
| DJ-UNIQUE-03 | Job unique ID format correct |
| DJ-UNIQUE-04 | Duplicate job skipped silently |
| DJ-UNIQUE-05 | Job released after completion |
| DJ-UNIQUE-06 | Job released after failure |
| DJ-UNIQUE-07 | Job unique across queue workers |
| DJ-UNIQUE-08 | Job unique with custom duration |
| DJ-UNIQUE-09 | Job unique until processing starts |
| DJ-UNIQUE-10 | Job unique prevents race conditions |

#### DJ-EDGE: Edge Cases (11 tests)
| Test ID | Description |
|---------|-------------|
| DJ-EDGE-01 | Job handles site with no pages |
| DJ-EDGE-02 | Job handles site with no GSC data |
| DJ-EDGE-03 | Job handles project with no settings |
| DJ-EDGE-04 | Job handles detection with zero candidates |
| DJ-EDGE-05 | Job handles large site (1000+ pages) |
| DJ-EDGE-06 | Job handles concurrent sites |
| DJ-EDGE-07 | Job handles database transaction |
| DJ-EDGE-08 | Job handles memory constraints |
| DJ-EDGE-09 | Job handles timeout gracefully |
| DJ-EDGE-10 | Job handles unicode in site name |
| DJ-EDGE-11 | Job handles missing dependencies |

---

### BackfillContentDecayRecoveryJob

#### RJ-INST: Job Instantiation (10 tests)
| Test ID | Description |
|---------|-------------|
| RJ-INST-01 | Job class exists in correct namespace |
| RJ-INST-02 | Job implements ShouldQueue |
| RJ-INST-03 | Job has $tries property set to 3 |
| RJ-INST-04 | Job has $queue property set to 'content-decay' |
| RJ-INST-05 | Job implements ShouldBeUnique |
| RJ-INST-06 | Job constructor accepts candidate_id |
| RJ-INST-07 | Job stores candidate_id as property |
| RJ-INST-08 | Job has handle() method |
| RJ-INST-09 | Job has failed() method |
| RJ-INST-10 | Job recovery criteria method exists |

#### RJ-DISPATCH: Job Dispatch (10 tests)
| Test ID | Description |
|---------|-------------|
| RJ-DISPATCH-01 | Job can be dispatched with candidate_id |
| RJ-DISPATCH-02 | Job dispatched to correct queue |
| RJ-DISPATCH-03 | Job unique per candidate_id |
| RJ-DISPATCH-04 | Job unique for 3600 seconds |
| RJ-DISPATCH-05 | Job dispatch after database transaction |
| RJ-DISPATCH-06 | Job dispatch with delay |
| RJ-DISPATCH-07 | Job skip if candidate not found |
| RJ-DISPATCH-08 | Job skip if candidate not refreshed |
| RJ-DISPATCH-09 | Job batch for multiple candidates |
| RJ-DISPATCH-10 | Job chain with detection job |

#### RJ-HANDLE: Job Handle Method (20 tests)
| Test ID | Description |
|---------|-------------|
| RJ-HANDLE-01 | Loads candidate by candidate_id |
| RJ-HANDLE-02 | Checks candidate status is 'refreshed' |
| RJ-HANDLE-03 | Gathers post-refresh signals |
| RJ-HANDLE-04 | Gathers baseline signals |
| RJ-HANDLE-05 | Evaluates clicks improvement |
| RJ-HANDLE-06 | Evaluates CTR improvement |
| RJ-HANDLE-07 | Evaluates position improvement |
| RJ-HANDLE-08 | Requires 2 of 3 criteria met |
| RJ-HANDLE-09 | Marks candidate as recovered |
| RJ-HANDLE-10 | Sets recovered_at timestamp |
| RJ-HANDLE-11 | Updates evidence_json |
| RJ-HANDLE-12 | Handles candidate not found |
| RJ-HANDLE-13 | Handles candidate not refreshed |
| RJ-HANDLE-14 | Handles missing post-refresh data |
| RJ-HANDLE-15 | Logs evaluation details |
| RJ-HANDLE-16 | Dispatches recovery event |
| RJ-HANDLE-17 | Handles partial data |
| RJ-HANDLE-18 | Validates signal data |
| RJ-HANDLE-19 | Handles concurrent evaluation |
| RJ-HANDLE-20 | Returns early if criteria not met |

#### RJ-CRITERIA: Recovery Criteria Tests (15 tests)
| Test ID | Description |
|---------|-------------|
| RJ-CRITERIA-01 | Clicks 10% improvement detected |
| RJ-CRITERIA-02 | Clicks below 10% not counted |
| RJ-CRITERIA-03 | CTR 5% improvement detected |
| RJ-CRITERIA-04 | CTR below 5% not counted |
| RJ-CRITERIA-05 | Position 2+ improvement detected |
| RJ-CRITERIA-06 | Position below 2 not counted |
| RJ-CRITERIA-07 | All 3 criteria met → recovered |
| RJ-CRITERIA-08 | 2 of 3 criteria met → recovered |
| RJ-CRITERIA-09 | 1 of 3 criteria met → not recovered |
| RJ-CRITERIA-10 | 0 of 3 criteria met → not recovered |
| RJ-CRITERIA-11 | Handles zero baseline |
| RJ-CRITERIA-12 | Handles negative improvement |
| RJ-CRITERIA-13 | Handles exact threshold values |
| RJ-CRITERIA-14 | Weighted criteria evaluation |
| RJ-CRITERIA-15 | Confidence score calculation |

#### RJ-RETRY: Job Retry Logic (8 tests)
| Test ID | Description |
|---------|-------------|
| RJ-RETRY-01 | Job retries on transient failure |
| RJ-RETRY-02 | Job respects max tries (3) |
| RJ-RETRY-03 | Job exponential backoff |
| RJ-RETRY-04 | Job doesn't retry on not found |
| RJ-RETRY-05 | Job logs retry attempt |
| RJ-RETRY-06 | Job retry count tracked |
| RJ-RETRY-07 | Job fails after max retries |
| RJ-RETRY-08 | Job cleanup on final failure |

#### RJ-FAIL: Job Failure Handling (7 tests)
| Test ID | Description |
|---------|-------------|
| RJ-FAIL-01 | Job failed() method called |
| RJ-FAIL-02 | Job logs failure with context |
| RJ-FAIL-03 | Job updates candidate status |
| RJ-FAIL-04 | Job handles missing candidate |
| RJ-FAIL-05 | Job handles database failure |
| RJ-FAIL-06 | Job preserves exception chain |
| RJ-FAIL-07 | Job sends failure notification |

---

### SendContentDecayDigestJob

#### SJ-INST: Job Instantiation (10 tests)
| Test ID | Description |
|---------|-------------|
| SJ-INST-01 | Job class exists in correct namespace |
| SJ-INST-02 | Job implements ShouldQueue |
| SJ-INST-03 | Job has $tries property set to 3 |
| SJ-INST-04 | Job has $queue property set to 'notifications' |
| SJ-INST-05 | Job has $timeout property set to 120 |
| SJ-INST-06 | Job implements ShouldBeUnique |
| SJ-INST-07 | Job constructor accepts project_id |
| SJ-INST-08 | Job stores project_id as property |
| SJ-INST-09 | Job has handle() method |
| SJ-INST-10 | Job has failed() method |

#### SJ-DISPATCH: Job Dispatch (10 tests)
| Test ID | Description |
|---------|-------------|
| SJ-DISPATCH-01 | Job can be dispatched with project_id |
| SJ-DISPATCH-02 | Job dispatched to correct queue |
| SJ-DISPATCH-03 | Job unique per project_id |
| SJ-DISPATCH-04 | Job unique for 3600 seconds |
| SJ-DISPATCH-05 | Job dispatch after database transaction |
| SJ-DISPATCH-06 | Job dispatch with delay |
| SJ-DISPATCH-07 | Job skip if project not found |
| SJ-DISPATCH-08 | Job skip if digest disabled |
| SJ-DISPATCH-09 | Job batch for multiple projects |
| SJ-DISPATCH-10 | Job scheduled weekly |

#### SJ-HANDLE: Job Handle Method (20 tests)
| Test ID | Description |
|---------|-------------|
| SJ-HANDLE-01 | Loads project by project_id |
| SJ-HANDLE-02 | Loads project owner |
| SJ-HANDLE-03 | Checks if digest_enabled is true |
| SJ-HANDLE-04 | Calls DigestService::generateDigest() |
| SJ-HANDLE-05 | Calls DigestService::sendDigest() |
| SJ-HANDLE-06 | Handles project not found |
| SJ-HANDLE-07 | Handles owner not found |
| SJ-HANDLE-08 | Handles digest disabled |
| SJ-HANDLE-09 | Logs digest generation |
| SJ-HANDLE-10 | Logs digest sent |
| SJ-HANDLE-11 | Updates last_digest_sent_at |
| SJ-HANDLE-12 | Handles empty digest |
| SJ-HANDLE-13 | Handles DigestService exceptions |
| SJ-HANDLE-14 | Validates digest content |
| SJ-HANDLE-15 | Sends email notification |
| SJ-HANDLE-16 | Sends in-app notification |
| SJ-HANDLE-17 | Respects user preferences |
| SJ-HANDLE-18 | Handles notification failure |
| SJ-HANDLE-19 | Records delivery status |
| SJ-HANDLE-20 | Dispatches digest sent event |

#### SJ-DIGEST: Digest Content Tests (15 tests)
| Test ID | Description |
|---------|-------------|
| SJ-DIGEST-01 | Digest contains new_decay_count |
| SJ-DIGEST-02 | Digest contains critical_count |
| SJ-DIGEST-03 | Digest contains recovered_count |
| SJ-DIGEST-04 | Digest contains total_lost_clicks |
| SJ-DIGEST-05 | Digest contains total_upside |
| SJ-DIGEST-06 | Digest contains top_candidates |
| SJ-DIGEST-07 | Digest contains recovered_pages |
| SJ-DIGEST-08 | Digest excludes ignored candidates |
| SJ-DIGEST-09 | Digest excludes stale-noise candidates |
| SJ-DIGEST-10 | Digest limits top_candidates to 5 |
| SJ-DIGEST-11 | Digest respects weekly frequency |
| SJ-DIGEST-12 | Digest respects monthly frequency |
| SJ-DIGEST-13 | Digest handles empty data |
| SJ-DIGEST-14 | Digest formats numbers correctly |
| SJ-DIGEST-15 | Digest subject line formatted |

#### SJ-RETRY: Job Retry Logic (8 tests)
| Test ID | Description |
|---------|-------------|
| SJ-RETRY-01 | Job retries on transient failure |
| SJ-RETRY-02 | Job respects max tries (3) |
| SJ-RETRY-03 | Job exponential backoff |
| SJ-RETRY-04 | Job doesn't retry on not found |
| SJ-RETRY-05 | Job logs retry attempt |
| SJ-RETRY-06 | Job retry count tracked |
| SJ-RETRY-07 | Job fails after max retries |
| SJ-RETRY-08 | Job cleanup on final failure |

#### SJ-FAIL: Job Failure Handling (7 tests)
| Test ID | Description |
|---------|-------------|
| SJ-FAIL-01 | Job failed() method called |
| SJ-FAIL-02 | Job logs failure with context |
| SJ-FAIL-03 | Job updates project status |
| SJ-FAIL-04 | Job handles missing project |
| SJ-FAIL-05 | Job handles email failure |
| SJ-FAIL-06 | Job preserves exception chain |
| SJ-FAIL-07 | Job sends failure notification |

---

### GenerateRefreshBriefJob

#### BJ-INST: Job Instantiation (10 tests)
| Test ID | Description |
|---------|-------------|
| BJ-INST-01 | Job class exists in correct namespace |
| BJ-INST-02 | Job implements ShouldQueue |
| BJ-INST-03 | Job has $tries property set to 2 |
| BJ-INST-04 | Job has $queue property set to 'ai-tasks' |
| BJ-INST-05 | Job has $timeout property set to 120 |
| BJ-INST-06 | Job implements ShouldBeUnique |
| BJ-INST-07 | Job constructor accepts candidate_id |
| BJ-INST-08 | Job stores candidate_id as property |
| BJ-INST-09 | Job has handle() method |
| BJ-INST-10 | Job has failed() method |

#### BJ-DISPATCH: Job Dispatch (10 tests)
| Test ID | Description |
|---------|-------------|
| BJ-DISPATCH-01 | Job can be dispatched with candidate_id |
| BJ-DISPATCH-02 | Job dispatched to correct queue |
| BJ-DISPATCH-03 | Job unique per candidate_id |
| BJ-DISPATCH-04 | Job unique for 3600 seconds |
| BJ-DISPATCH-05 | Job dispatch after database transaction |
| BJ-DISPATCH-06 | Job dispatch with delay |
| BJ-DISPATCH-07 | Job skip if candidate not found |
| BJ-DISPATCH-08 | Job skip if brief already exists |
| BJ-DISPATCH-09 | Job batch for multiple candidates |
| BJ-DISPATCH-10 | Job chain with detection job |

#### BJ-HANDLE: Job Handle Method (20 tests)
| Test ID | Description |
|---------|-------------|
| BJ-HANDLE-01 | Loads candidate by candidate_id |
| BJ-HANDLE-02 | Loads project for context |
| BJ-HANDLE-03 | Loads page content for analysis |
| BJ-HANDLE-04 | Builds AI prompt from signals |
| BJ-HANDLE-05 | Calls AI service (FEAT-031) |
| BJ-HANDLE-06 | Parses AI response |
| BJ-HANDLE-07 | Stores brief in evidence_json |
| BJ-HANDLE-08 | Sets brief_generated_at timestamp |
| BJ-HANDLE-09 | Handles candidate not found |
| BJ-HANDLE-10 | Handles AI service timeout |
| BJ-HANDLE-11 | Handles AI service error |
| BJ-HANDLE-12 | Handles malformed AI response |
| BJ-HANDLE-13 | Logs brief generation |
| BJ-HANDLE-14 | Logs AI call details |
| BJ-HANDLE-15 | Updates candidate status |
| BJ-HANDLE-16 | Dispatches brief generated event |
| BJ-HANDLE-17 | Handles empty AI response |
| BJ-HANDLE-18 | Validates brief structure |
| BJ-HANDLE-19 | Handles concurrent generation |
| BJ-HANDLE-20 | Skips if brief exists |

#### BJ-AI: AI Integration Tests (15 tests)
| Test ID | Description |
|---------|-------------|
| BJ-AI-01 | Uses existing AI infrastructure |
| BJ-AI-02 | Constructs proper prompt |
| BJ-AI-03 | Includes decay signals in prompt |
| BJ-AI-04 | Includes reason codes in prompt |
| BJ-AI-05 | Includes recovery suggestions |
| BJ-AI-06 | Handles token limits |
| BJ-AI-07 | Handles rate limiting |
| BJ-AI-08 | Handles API errors |
| BJ-AI-09 | Parses JSON response |
| BJ-AI-10 | Validates brief structure |
| BJ-AI-11 | Stores brief outline |
| BJ-AI-12 | Stores refresh suggestions |
| BJ-AI-13 | Stores priority actions |
| BJ-AI-14 | Handles truncated response |
| BJ-AI-15 | Falls back on AI failure |

#### BJ-RETRY: Job Retry Logic (6 tests)
| Test ID | Description |
|---------|-------------|
| BJ-RETRY-01 | Job retries on transient failure |
| BJ-RETRY-02 | Job respects max tries (2) |
| BJ-RETRY-03 | Job exponential backoff |
| BJ-RETRY-04 | Job doesn't retry on not found |
| BJ-RETRY-05 | Job fails after max retries |
| BJ-RETRY-06 | Job cleanup on final failure |

#### BJ-FAIL: Job Failure Handling (6 tests)
| Test ID | Description |
|---------|-------------|
| BJ-FAIL-01 | Job failed() method called |
| BJ-FAIL-02 | Job logs failure with context |
| BJ-FAIL-03 | Job handles AI service error |
| BJ-FAIL-04 | Job preserves exception chain |
| BJ-FAIL-05 | Job updates candidate status |
| BJ-FAIL-06 | Job sends failure notification |

---

## Service Interfaces Required

### ContentDecayDetectionServiceInterface
```php
interface ContentDecayDetectionServiceInterface {
    public function detectForSite(Site $site): array;
    public function detectForProject(ContentDecayProject $project): array;
}
```

### ContentDecayDigestServiceInterface (to be created)
```php
interface ContentDecayDigestServiceInterface {
    public function generateDigest(ContentDecayProject $project): array;
    public function sendDigest(User $user, array $digest): void;
    public function shouldSendDigest(ContentDecayProject $project): bool;
}
```

### AIAnalysisServiceInterface
```php
interface AIAnalysisServiceInterface {
    public function analyze(string $prompt, array $options = []): array;
}
```

---

## Test Patterns

### Mock Helper Methods

```php
/**
 * Create mock site for job testing
 */
protected function createMockSite(int $id, array $attributes = []): Site
{
    return Mockery::mock(Site::class, array_merge([
        'id' => $id,
        'exists' => true,
    ], $attributes));
}

/**
 * Create mock project for job testing
 */
protected function createMockProject(int $id, array $attributes = []): ContentDecayProject
{
    return Mockery::mock(ContentDecayProject::class, array_merge([
        'id' => $id,
        'detection_enabled' => true,
        'digest_enabled' => true,
        'exists' => true,
    ], $attributes));
}

/**
 * Create mock candidate for job testing
 */
protected function createMockCandidate(int $id, array $attributes = []): ContentDecayCandidate
{
    return Mockery::mock(ContentDecayCandidate::class, array_merge([
        'id' => $id,
        'status' => 'new',
        'exists' => true,
    ], $attributes));
}

/**
 * Create mock user for job testing
 */
protected function createMockUser(int $id, array $attributes = []): User
{
    return Mockery::mock(User::class, array_merge([
        'id' => $id,
        'email' => 'test@example.com',
        'exists' => true,
    ], $attributes));
}
```

### Queue Fake Patterns

```php
Queue::fake();

// Dispatch job
DetectContentDecayForSiteJob::dispatch($siteId);

// Assert pushed
Queue::assertPushed(DetectContentDecayForSiteJob::class, function ($job) use ($siteId) {
    return $job->siteId === $siteId;
});

// Assert pushed on queue
Queue::assertPushedOn('content-decay', DetectContentDecayForSiteJob::class);
```

### Event Fake Patterns

```php
Event::fake();

// Dispatch job
$job->handle($mockService);

// Assert event dispatched
Event::assertDispatched(ContentDecayDetectionCompleted::class);
```

---

## Dependencies

| Dependency | Status | TDD Impact |
|------------|--------|------------|
| TASK-36-6 (DetectionService) | To Develop | Mock via interface |
| TASK-36-7 (DigestService) | To Write Tests | Mock via interface |
| FEAT-019 (Job Queue) | Done ✅ | Real infrastructure |
| FEAT-031 (AI Infrastructure) | Done ✅ | Real infrastructure |

---

## Expected Job Properties

| Job | Queue | Tries | Timeout | Unique |
|-----|-------|-------|---------|--------|
| DetectContentDecayForSiteJob | content-decay | 3 | 600s | Yes |
| BackfillContentDecayRecoveryJob | content-decay | 3 | 300s | Yes |
| SendContentDecayDigestJob | notifications | 3 | 120s | Yes |
| GenerateRefreshBriefJob | ai-tasks | 2 | 120s | Yes |

---

## Key Test Patterns

1. **Job dispatch verification:** Use `Queue::fake()` and assert pushed
2. **Service call verification:** Use Mockery with expectations
3. **Error handling:** Test exception handling and logging
4. **Retry logic:** Test retry behavior with multiple attempts
5. **Uniqueness:** Test that duplicate jobs are prevented
6. **Middleware:** Test rate limiting and without overlapping

---

## Implementation Notes for Developer

### DetectContentDecayForSiteJob

```php
<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\ContentDecayProject;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetectContentDecayForSiteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public string $queue = 'content-decay';

    public function __construct(
        public int $siteId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->siteId;
    }

    public function handle(ContentDecayDetectionServiceInterface $detectionService): void
    {
        Log::info('Content decay detection started', ['site_id' => $this->siteId]);

        $site = Site::find($this->siteId);
        
        if (!$site) {
            Log::warning('Site not found for decay detection', ['site_id' => $this->siteId]);
            return;
        }

        $project = ContentDecayProject::where('site_id', $this->siteId)->first();

        if (!$project) {
            Log::warning('Project not found for decay detection', ['site_id' => $this->siteId]);
            return;
        }

        if (!$project->detection_enabled) {
            Log::info('Detection disabled for project', ['project_id' => $project->id]);
            return;
        }

        $result = $detectionService->detectForSite($site);

        $project->update(['last_scanned_at' => now()]);

        Log::info('Content decay detection completed', [
            'site_id' => $this->siteId,
            'candidates_created' => $result['candidates_created'] ?? 0,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Content decay detection job failed', [
            'site_id' => $this->siteId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Recovery Criteria Logic

```php
$criteria = [
    'clicks_improved' => $postRefreshClicks > $baselineClicks * 1.1,
    'ctr_improved' => $postRefreshCtr > $baselineCtr * 1.05,
    'position_improved' => $postRefreshPosition < $baselinePosition - 2,
];

if (count(array_filter($criteria)) >= 2) {
    $candidate->update([
        'status' => 'recovered',
        'recovered_at' => now(),
    ]);
}
```

---

*Created by PreTester agent on 2026-03-11*
