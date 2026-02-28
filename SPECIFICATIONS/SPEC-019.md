# SPEC-019: Progressive Analysis & Job System

## STATUS
`To Develop`

## PRIORITY
`High`

## RISK
`Medium`

---

## Overview

Implement a progressive analysis system that creates analysis jobs, processes indicators concurrently with throttling, and provides real-time UI updates with progress tracking and cancel support.

---

## Architecture

### High-Level Flow

```
┌─────────────┐    ┌──────────────┐    ┌─────────────────┐
│   Frontend  │───▶│  API Layer   │───▶│  Job Queue      │
│  (Progress) │◀───│  (REST/SSE)  │◀───│  (Processing)   │
└─────────────┘    └──────────────┘    └─────────────────┘
       │                  │                    │
       │                  ▼                    ▼
       │           ┌──────────────┐    ┌─────────────────┐
       │           │   Database   │    │  Indicator      │
       └──────────▶│   (Jobs)     │◀───│  Processors     │
                   └──────────────┘    └─────────────────┘
```

### Components

1. **Job Management Service** - Create, track, cancel analysis jobs
2. **Indicator Processing Queue** - Concurrent processing with throttling
3. **Progress Tracking** - Real-time progress updates
4. **Real-time Updates** - Server-Sent Events (SSE) for live updates
5. **Frontend Components** - Progress bar, live row updates

---

## Database Schema

### Jobs Table

```sql
CREATE TABLE analysis_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'running', 'completed', 'cancelled', 'failed') DEFAULT 'pending',
    total_indicators INT UNSIGNED NOT NULL DEFAULT 0,
    completed_indicators INT UNSIGNED NOT NULL DEFAULT 0,
    failed_indicators INT UNSIGNED NOT NULL DEFAULT 0,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    
    INDEX idx_jobs_user_id (user_id),
    INDEX idx_jobs_site_id (site_id),
    INDEX idx_jobs_status (status),
    INDEX idx_jobs_created_at (created_at)
);
```

### Job Results Table

```sql
CREATE TABLE analysis_job_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    indicator_slug VARCHAR(100) NOT NULL,
    indicator_name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    score DECIMAL(5,2) NULL,
    max_score DECIMAL(5,2) NULL,
    percentage TINYINT UNSIGNED NULL,
    data JSON NULL,
    recommendations JSON NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    processing_time_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES analysis_jobs(id) ON DELETE CASCADE,
    
    INDEX idx_job_results_job_id (job_id),
    INDEX idx_job_results_status (status),
    UNIQUE KEY idx_job_indicator (job_id, indicator_slug)
);
```

---

## API Endpoints

### Create Job
`POST /api/v1/sites/{siteId}/analysis/jobs`

**Request:**
```json
{
  "indicators": ["meta_tags", "headings", "images", "links", "performance"]
}
```

**Response (201):**
```json
{
  "id": 1,
  "status": "pending",
  "totalIndicators": 5,
  "progressPercent": 0,
  "createdAt": "2026-02-28T10:00:00Z"
}
```

### Get Job Progress
`GET /api/v1/analysis/jobs/{jobId}`

**Response:**
```json
{
  "id": 1,
  "status": "running",
  "totalIndicators": 17,
  "completedIndicators": 5,
  "failedIndicators": 0,
  "progressPercent": 29,
  "startedAt": "2026-02-28T10:00:00Z",
  "results": [
    {
      "indicatorSlug": "meta_tags",
      "indicatorName": "Meta Tags",
      "status": "completed",
      "score": 85.00,
      "maxScore": 100.00,
      "percentage": 85
    }
  ]
}
```

### Get Job Results (SSE Stream)
`GET /api/v1/analysis/jobs/{jobId}/stream`

**Server-Sent Events Format:**
```
event: progress
data: {"completedIndicators":5,"progressPercent":29}

event: result
data: {"indicatorSlug":"meta_tags","status":"completed","score":85}

event: complete
data: {"status":"completed","totalIndicators":17}
```

### Cancel Job
`DELETE /api/v1/analysis/jobs/{jobId}`

**Response (200):**
```json
{
  "id": 1,
  "status": "cancelled",
  "cancelledAt": "2026-02-28T10:05:00Z"
}
```

### List Jobs
`GET /api/v1/sites/{siteId}/analysis/jobs`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `status` | string | null | Filter by status |
| `page` | int | 1 | Page number |
| `perPage` | int | 15 | Results per page |

---

## Backend Services

### AnalysisJobService

```php
class AnalysisJobService
{
    public function createJob(int $userId, int $siteId, array $indicators): AnalysisJob;
    public function getJob(int $jobId): ?AnalysisJob;
    public function getJobProgress(int $jobId): array;
    public function cancelJob(int $jobId): bool;
    public function processIndicator(AnalysisJob $job, string $indicator): void;
}
```

### IndicatorProcessor (Base Class)

```php
abstract class IndicatorProcessor
{
    abstract public function slug(): string;
    abstract public function name(): string;
    abstract public function process(string $url): IndicatorResult;
    
    protected function fetchUrl(string $url): string;
    protected function parseHtml(string $html): Crawler;
}
```

### Indicator Implementations

| Indicator | Slug | Description |
|-----------|------|-------------|
| Meta Tags | `meta_tags` | Title, description, Open Graph |
| Headings | `headings` | H1-H6 structure |
| Images | `images` | Alt text, sizes, lazy loading |
| Links | `links` | Internal/external, broken links |
| Performance | `performance` | Load time, page size |
| Mobile | `mobile` | Responsive design checks |
| Security | `security` | HTTPS, security headers |
| Structured Data | `structured_data` | Schema.org validation |

---

## Concurrency & Throttling

### Queue Configuration

```php
// config/queue.php
'analysis' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'analysis',
    'retry_after' => 90,
    'after_commit' => true,
],
```

### Concurrency Limits

| Setting | Value | Description |
|---------|-------|-------------|
| Max concurrent indicators | 5 | Process up to 5 indicators simultaneously |
| Timeout per indicator | 30s | Maximum processing time per indicator |
| Memory limit | 256MB | Per worker memory limit |
| Retry attempts | 1 | Single retry on failure |

### Throttling Implementation

```php
class AnalysisJobService
{
    public function processWithThrottle(AnalysisJob $job): void
    {
        $pending = $job->results()->where('status', 'pending')->get();
        $batch = $pending->take(config('analysis.concurrent_limit', 5));
        
        foreach ($batch as $result) {
            dispatch(function () use ($job, $result) {
                $this->processIndicator($job, $result->indicator_slug);
            })->onQueue('analysis');
        }
    }
}
```

---

## Real-time Updates

### Server-Sent Events (SSE)

**Why SSE over WebSockets:**
- Simpler implementation (no external service)
- Automatic reconnection
- Native browser support
- Works with standard HTTP/HTTPS

**Implementation:**

```php
// AnalysisJobStreamController.php
public function stream(int $jobId)
{
    return response()->stream(function () use ($jobId) {
        while (true) {
            $job = AnalysisJob::find($jobId);
            
            if ($job->status !== 'running') {
                echo "event: complete\n";
                echo "data: " . json_encode($job->toArray()) . "\n\n";
                break;
            }
            
            echo "event: progress\n";
            echo "data: " . json_encode([
                'completedIndicators' => $job->completed_indicators,
                'progressPercent' => $job->progress_percent,
            ]) . "\n\n";
            
            ob_flush();
            flush();
            sleep(1);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

### Fallback: Polling Endpoint

For browsers/environments without SSE support:

```
GET /api/v1/analysis/jobs/{jobId}/progress
```

Returns simple JSON progress data every 2 seconds.

---

## Frontend Components

### Progress Bar Component

```tsx
interface AnalysisProgressProps {
  jobId: number;
  onComplete: (results: AnalysisResult[]) => void;
  onCancel: () => void;
}

function AnalysisProgress({ jobId, onComplete, onCancel }: AnalysisProgressProps) {
  // SSE connection
  // Progress bar visualization
  // Cancel button
  // Live result list
}
```

### Live Results Table

```tsx
interface ResultRowProps {
  result: AnalysisJobResult;
}

function ResultRow({ result }: ResultRowProps) {
  // Status indicator (pending/running/completed/failed)
  // Indicator name
  // Score/percentage
  // Expandable details
}
```

---

## Error Handling

### Indicator Failure Handling

| Scenario | Behavior |
|----------|----------|
| Network timeout | Mark as failed, continue with others |
| Invalid URL | Mark as failed with error, continue |
| Rate limited | Exponential backoff, retry once |
| Memory exceeded | Kill worker, mark as failed, continue |

### Job Failure Recovery

| Status | Recovery Action |
|--------|-----------------|
| `pending` | Can be started |
| `running` | Can be cancelled |
| `cancelled` | Cannot be resumed (create new job) |
| `failed` | Can retry (creates new job) |
| `completed` | View results |

---

## Security Considerations

### SSRF Protection

Reuse existing URL validation from TASK-16-1:

```php
class UrlValidationService
{
    public function isValidAnalysisUrl(string $url): bool
    {
        // Block localhost, private IPs, cloud metadata
        // Max response size: 10MB
        // Timeout: 30s
    }
}
```

### Rate Limiting

| Limit | Value | Scope |
|-------|-------|-------|
| Jobs per user per hour | 10 | Per user |
| Jobs per site per hour | 5 | Per site |
| Concurrent jobs per user | 2 | Per user |

### Authorization

- Users can only create/view/cancel their own jobs
- Site ownership verified before job creation
- Job results scoped to user

---

## Performance Considerations

### Caching

| Data | TTL | Purpose |
|------|-----|---------|
| Job results | 24h | Allow re-viewing completed analyses |
| Indicator definitions | 1h | Avoid repeated lookups |

### Database Optimization

- Index on `(user_id, status)` for job queries
- Index on `(job_id, status)` for result queries
- Batch inserts for initial job results

---

## Tasks Breakdown

### Phase 1: Core Job System
1. **TASK-19-1**: Database migration (analysis_jobs, analysis_job_results tables)
2. **TASK-19-2**: AnalysisJob and AnalysisJobResult models
3. **TASK-19-3**: AnalysisJobService (create, get, cancel)
4. **TASK-19-4**: Job CRUD API endpoints

### Phase 2: Processing Queue
5. **TASK-19-5**: IndicatorProcessor base class
6. **TASK-19-6**: Core indicator implementations (meta_tags, headings, images, links)
7. **TASK-19-7**: Queue worker with concurrency throttling
8. **TASK-19-8**: Progress tracking and updates

### Phase 3: Real-time Updates
9. **TASK-19-9**: SSE stream endpoint
10. **TASK-19-10**: Polling fallback endpoint
11. **TASK-19-11**: Frontend progress bar component
12. **TASK-19-12**: Frontend live results table

### Phase 4: Polish & Security
13. **TASK-19-13**: Error handling and recovery
14. **TASK-19-14**: Rate limiting implementation
15. **TASK-19-15**: SSRF protection integration
16. **TASK-19-16**: Unit and integration tests

---

## Dependencies

### Existing
- TASK-16-1: Tools infrastructure (URL validation, SSRF protection)
- Laravel queue system
- Authentication middleware

### External
- None (SSE uses native PHP)

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| SSE connection issues | Medium | Medium | Polling fallback |
| Queue backlog | Low | Medium | Concurrency limits |
| Memory exhaustion | Medium | High | Worker memory limits, timeouts |
| Long-running jobs | Medium | Low | Progress tracking, cancel support |

---

## Testing Strategy

### Unit Tests
- AnalysisJobService methods
- IndicatorProcessor implementations
- Progress calculation
- Cancel logic

### Feature Tests
- Job CRUD API endpoints
- SSE stream behavior
- Authorization checks
- Rate limiting

### Integration Tests
- Full job lifecycle (create → process → complete)
- Concurrent processing
- Error recovery
- Cancel mid-processing

---

## Documentation Updates

After implementation, update:
- `DOCUMENTATION.md`: Add Analysis Job System section
- API documentation: All new endpoints
- Frontend docs: Component usage

---

## Status History

| Date | Status | Agent | Notes |
|------|--------|-------|-------|
| 2026-02-28 | To Develop | PreTester | Tests written for all 16 tasks |
| 2026-02-28 | To Write Tests | SpecSystemAnalyst | Task breakdown completed (16 tasks) |
| 2026-02-28 | Specification In Progress | Architect | Initial specification created |

---

## Comments
_Agent communication log_

### [2026-02-28 13:13 GMT+2] Agent: PreTester
**Test Documentation Complete**

Created comprehensive test documentation for all 16 tasks:
- **TEST-19.md** - Master test overview
- **TEST-19-1.md** - Database migration tests
- **TEST-19-2.md** - Model tests
- **TEST-19-3.md** - AnalysisJobService tests
- **TEST-19-4.md** - Job CRUD API tests
- **TEST-19-5.md** - IndicatorProcessor base tests
- **TEST-19-6.md** - Core indicator tests
- **TEST-19-7.md** - Queue worker tests (HIGH RISK)
- **TEST-19-8.md** - Progress tracking tests
- **TEST-19-9.md** - SSE stream tests
- **TEST-19-10.md** - Polling fallback tests
- **TEST-19-11.md** - Frontend progress bar tests
- **TEST-19-12.md** - Frontend results table tests
- **TEST-19-13.md** - Error handling tests
- **TEST-19-14.md** - Rate limiting tests
- **TEST-19-15.md** - SSRF protection tests (HIGH RISK)
- **TEST-19-16.md** - Integration tests

**Test Files Created:**
- `tests/Unit/Migrations/CreateAnalysisJobsTableTest.php` (31 tests)
- `tests/Unit/Models/AnalysisJobTest.php` (35+ tests)
- `tests/Unit/Models/AnalysisJobResultTest.php` (35+ tests)
- `tests/Unit/Services/AnalysisJobServiceTest.php` (50+ tests)
- `tests/Feature/Api/AnalysisJobApiTest.php` (45+ tests)

**High-Risk Tasks Identified:**
- TASK-19-7: Queue Worker (concurrency, memory management)
- TASK-19-15: SSRF Protection (security-critical)

**Total Test Coverage Target:** 200+ test methods, 80%+ coverage

All tasks ready for Developer implementation.

---

*Created by Architect from FEAT-019*
