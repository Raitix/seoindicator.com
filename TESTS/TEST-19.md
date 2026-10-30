# TEST-19: Progressive Analysis System Test Documentation

**Created:** 2026-03-01
**Author:** Developer Agent
**Status:** Completed
**Task:** TASK-19-16

---

## Overview

This document describes the test suite for the Progressive Analysis system (SPEC-19), which provides real-time SEO analysis with progress tracking via SSE streams and polling endpoints.

---

## Test Structure

### Unit Tests

| File | Purpose | Coverage |
|------|---------|----------|
| `tests/Unit/Services/AnalysisJobServiceTest.php` | Core job operations | Job creation, status transitions, cancellation, progress |
| `tests/Unit/Services/AnalysisRateLimiterServiceTest.php` | Rate limiting logic | Concurrent, hourly, API, and polling limits |
| `tests/Unit/Services/ProgressServiceTest.php` | Progress tracking | Mark complete/failed/running/skipped |
| `tests/Unit/Services/Analysis/Indicators/IndicatorProcessorTest.php` | Base processor | Abstract processor methods |
| `tests/Unit/Services/Analysis/Indicators/MetaTagsProcessorTest.php` | Meta tags analysis | Title, description, robots meta |
| `tests/Unit/Services/Analysis/Indicators/HeadingsProcessorTest.php` | Headings analysis | H1-H6 structure |
| `tests/Unit/Services/Analysis/Indicators/ImagesProcessorTest.php` | Images analysis | Alt text, size, format |
| `tests/Unit/Services/Analysis/Indicators/LinksProcessorTest.php` | Links analysis | Internal/external, broken links |
| `tests/Unit/Services/Analysis/ErrorHandlingServiceTest.php` | Error handling | Error recovery, logging |
| `tests/Unit/Services/Analysis/IndicatorResultTest.php` | Result DTO | Score calculation, data structure |

### Feature Tests

| File | Purpose | Coverage |
|------|---------|----------|
| `tests/Feature/AnalysisJobApiTest.php` | API endpoints | CRUD, authorization, rate limiting |
| `tests/Feature/AnalysisJobStreamTest.php` | SSE streaming | Real-time progress, heartbeats |
| `tests/Feature/AnalysisJobPerformanceTest.php` | Performance | Response times, memory usage |

### Integration Tests

| File | Purpose | Coverage |
|------|---------|----------|
| `tests/Integration/AnalysisJobLifecycleTest.php` | Full lifecycle | Create → Process → Complete |
| `tests/Integration/AnalysisJobErrorRecoveryTest.php` | Error scenarios | Failures, cancellation, data integrity |

---

## Test Coverage Summary

### AnalysisJobService

| Method | Tests | Coverage |
|--------|-------|----------|
| `createJob()` | 10 | User validation, indicator filtering, result creation |
| `getJob()` | 4 | Retrieval, ownership check |
| `getJobProgress()` | 5 | Progress data, ownership, results |
| `startJob()` | 3 | Status transition, validation |
| `completeJob()` | 3 | Status transition, completion |
| `failJob()` | 3 | Status transition, error message |
| `cancelJob()` | 5 | Cancellation, result skipping, ownership |
| `updateProgress()` | 3 | Counter updates, auto-completion |
| `canUserAccessJob()` | 3 | Ownership verification |
| `getAvailableIndicators()` | 2 | Indicator list, structure |

### AnalysisRateLimiterService

| Method | Tests | Coverage |
|--------|-------|----------|
| `canCreateJob()` | 7 | Concurrent, hourly, site limits, admin bypass |
| `checkApiRateLimit()` | 3 | API rate limiting, admin bypass |
| `hitApiRateLimit()` | 2 | Counter increment, blocking |
| `checkPollingRateLimit()` | 3 | Per-job rate limiting |
| `hitPollingRateLimit()` | 2 | Counter increment, blocking |
| `getJobCreationHeaders()` | 3 | Header format, remaining count |
| `getApiHeaders()` | 1 | Header format |
| `getPollingHeaders()` | 1 | Header format |
| `buildRateLimitResponse()` | 3 | Response structure, headers |

### API Endpoints

| Endpoint | Tests | Coverage |
|----------|-------|----------|
| `GET /sites/{siteId}/analysis/jobs` | 5 | Auth, filtering, pagination, authorization |
| `POST /sites/{siteId}/analysis/jobs` | 8 | Auth, validation, creation, rate limiting |
| `GET /analysis/jobs/{jobId}` | 4 | Auth, retrieval, authorization |
| `DELETE /analysis/jobs/{jobId}` | 5 | Auth, cancellation, state validation |
| `POST /analysis/jobs/{jobId}/start` | 4 | Auth, dispatch, state validation |
| `GET /analysis/jobs/{jobId}/progress` | 5 | Auth, data, rate limiting headers |
| `GET /analysis/jobs/{jobId}/stream` | 10 | Auth, SSE format, events |
| `GET /analysis/indicators` | 2 | Auth, indicator list |

---

## Rate Limits

| Limit Type | Value | Scope |
|------------|-------|-------|
| Concurrent jobs | 2 | Per user |
| User hourly | 10 | Per user per hour |
| Site hourly | 5 | Per site per hour |
| API requests | 60 | Per user per minute |
| Polling | 30 | Per job per minute |

---

## Running Tests

### All Tests

```bash
cd backend
php artisan test --filter=Analysis
```

### Unit Tests Only

```bash
php artisan test --filter="Unit.*Analysis"
```

### Feature Tests Only

```bash
php artisan test --filter="Feature.*Analysis"
```

### Integration Tests Only

```bash
php artisan test --filter="Integration.*Analysis"
```

### Specific Test File

```bash
php artisan test tests/Unit/Services/AnalysisJobServiceTest.php
```

### With Coverage Report

```bash
php artisan test --coverage --min=80
```

---

## Performance Benchmarks

| Operation | Target | Notes |
|-----------|--------|-------|
| Job creation | < 1s | With all 17 indicators |
| Progress update | < 0.1s | Single indicator |
| API response | < 0.5s | All endpoints |
| Full lifecycle | < 3s | 4 indicators |
| Memory per job | < 10MB | Creation + processing |

---

## Test Data

### Available Indicators

```php
$indicators = [
    'meta_tags', 'headings', 'images', 'links',
    'performance', 'mobile', 'security', 'structured_data',
    'content_quality', 'url_structure', 'canonicalization',
    'robots_txt', 'sitemap', 'internal_linking',
    'external_links', 'social_tags', 'crawlability',
];
```

### Job Status Values

- `pending` - Job created, waiting to start
- `running` - Job is processing
- `completed` - All indicators processed successfully
- `failed` - Job failed with error
- `cancelled` - User cancelled the job

### Result Status Values

- `pending` - Waiting to process
- `running` - Currently processing
- `completed` - Processed successfully
- `failed` - Failed with error
- `skipped` - Skipped due to cancellation

---

## Common Test Patterns

### Creating a Test Job

```php
$job = $this->service->createJob(
    $userId,
    $siteId,
    ['meta_tags', 'headings']
);
```

### Simulating Progress

```php
$this->service->startJob($job->id);
$this->progressService->markRunning($job->id, 'meta_tags');
$this->progressService->markComplete(
    $job->id,
    'meta_tags',
    IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
);
```

### Testing Rate Limits

```php
// Create jobs up to limit
for ($i = 0; $i < $limit; $i++) {
    AnalysisJob::create([...]);
}

// Should fail
$response = $this->postJson(...);
$response->assertStatus(429);
```

---

## Notes

1. **Admin Bypass:** Users with `admin` or `super_admin` role bypass all rate limits
2. **SSE Reconnection:** Client should handle reconnection with `Last-Event-ID` header
3. **Polling Fallback:** Use polling endpoint if SSE is not supported
4. **Concurrent Processing:** Multiple indicators can be processed in parallel
5. **Error Recovery:** Failed indicators don't stop other indicators from processing

---

## Related Documentation

- `SPECIFICATIONS/SPEC-19.md` - Progressive Analysis specification
- `TASKS/TASK-19-16.md` - Test implementation task
- `backend/app/Services/AnalysisJobService.php` - Core service
- `backend/app/Services/AnalysisRateLimiterService.php` - Rate limiting
- `backend/app/Http/Controllers/Api/V1/AnalysisJobController.php` - API controller
