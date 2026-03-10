# TEST-35-9: GBP Insights Sync Service

## Task Reference
- **Task:** TASK-35-9
- **Specification:** SPEC-035: Local SEO & Google Business Profile Tracking
- **Priority:** High
- **Risk:** Low
- **Depends On:** TASK-35-3 (GBP-Related Models), TASK-35-8 (GBP API Client Service)

## Overview

Red-phase coverage for the Google Business Profile insights domain service.

This task now defines the **domain-layer contract** in the `App\Services\GBP` namespace:
- sync raw/normalized GBP insights into `gbp_insights`
- expose date-range collections for dashboards/controllers
- calculate totals, trends, and top-performing day summaries
- support background execution through `SyncInsightsJob`

Important compatibility note: TASK-35-14 already introduced an int-based `App\Services\LocalSEO\GBPInsightsService` adapter for controller work. The Developer pass for TASK-35-9 should make that adapter delegate into the new domain service instead of duplicating logic.

## Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/app/Services/GBP/Contracts/GBPInsightsServiceInterface.php` | Contract | - | BusinessLocation-centric domain interface for sync + aggregation |
| `backend/app/Services/GBP/GBPInsightsService.php` | Skeleton | - | Compile-safe red-phase scaffold for the domain service |
| `backend/app/Jobs/SyncInsightsJob.php` | Skeleton | - | Background sync job surface for queue integration |
| `backend/tests/Feature/Services/GBP/GBPInsightsServiceTest.php` | Feature | 12 | Domain sync / storage / totals / trend / cache / logging contract |
| `backend/tests/Feature/Jobs/SyncInsightsJobTest.php` | Feature | 5 | Dispatch + handle + retry/timeout + failure logging |

## Test Scenarios

### GBP-INS-CLS: Class & Container

| ID | Scenario | Expected Outcome |
|----|----------|------------------|
| GBP-INS-CLS-001 | Service class exists | `GBPInsightsService` autoloads |
| GBP-INS-CLS-002 | Implements interface | Service implements `GBPInsightsServiceInterface` |
| GBP-INS-CLS-003 | Container binding resolves | `app(GBPInsightsServiceInterface::class)` returns service |
| GBP-INS-CLS-004 | Interface exposes required methods | sync, get, trend, totals, top-day methods exist |

### GBP-INS-SYNC: Sync & Upsert

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-SYNC-001 | Sync daily metrics from GBP API | location + date range | Calls `GBPApiServiceInterface::getInsights()`, stores snapshots, persists raw response |
| GBP-INS-SYNC-002 | Duplicate dates are upserted | existing row + same date from API | Row updated, no duplicate inserted |

### GBP-INS-GET: Read Models

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-GET-001 | Get insights collection | location + date range | Ordered `Collection`, includes derived `queries` and `actions` fields |

### GBP-INS-AGG: Aggregates

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-AGG-001 | Aggregate totals | location + date range | `queries`, `views`, `actions`, `photos_views` totals returned |

### GBP-INS-TRD: Trend Analysis

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-TRD-001 | Build trend summary | location + last 30 days | Returns `daily_breakdown`, `totals`, `wow`, and `mom` |

### GBP-INS-TOP: Best Day

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-TOP-001 | Get top-performing day | location + last 30 days | Returns highest-action day summary or `null` |

### GBP-INS-CACHE: Cache Behavior

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-CACHE-001 | One-hour insights cache | repeated location/date-range query | Identical second read served from cache within TTL |

### GBP-INS-ERR: Error Handling

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| GBP-INS-ERR-001 | API failure during sync | GBP API exception | Error logged and exception rethrown |

### JOB-INS: Queue Integration

| ID | Scenario | Input | Expected Outcome |
|----|----------|-------|------------------|
| JOB-INS-001 | Dispatch job with optional date range | location id + range | Job queued with preserved context |
| JOB-INS-002 | Handle resolves location and calls service | valid location | Domain service receives `BusinessLocation` + date range |
| JOB-INS-003 | Missing location handled gracefully | invalid location id | Warning logged, no service call |
| JOB-INS-004 | Retry and timeout defaults | new job instance | `tries=3`, `timeout=300` |
| JOB-INS-005 | Failure hook logs context | thrown exception | Error log includes location/date-range context |

## Contract Decisions

### 1. Namespace Split
- **Domain service:** `App\Services\GBP\GBPInsightsService`
- **Controller adapter:** existing `App\Services\LocalSEO\GBPInsightsService`

This mirrors the reviews architecture already present in the repo and avoids breaking TASK-35-14 while TASK-35-9 is being completed.

### 2. Storage Shape
The sync service should persist canonical model-task columns from TASK-35-3:
- `views`
- `searches`
- `calls`
- `directions`
- `website_clicks`
- `photos_views`
- `insights_date`
- `raw_response`

### 3. Derived Output Shape
Downstream dashboards/controllers still expect convenience fields:
- `queries = searches`
- `actions = calls + directions + website_clicks`

The tests therefore require the read/aggregate methods to expose both the stored metrics and these derived aliases.

### 4. Queue Integration
`syncInsights()` is the **actual sync implementation** in the domain service.
Background processing is handled by `SyncInsightsJob`, which resolves the location model and invokes the domain service.

## Mock Data

### Mock Daily Metrics Payload
```php
[
    'daily_metrics' => [
        [
            'date' => '2026-03-01',
            'views' => 120,
            'searches' => 40,
            'calls' => 3,
            'directions' => 2,
            'website_clicks' => 4,
            'photos_views' => 10,
        ],
        [
            'date' => '2026-03-02',
            'views' => 160,
            'searches' => 55,
            'calls' => 5,
            'directions' => 4,
            'website_clicks' => 6,
            'photos_views' => 18,
        ],
    ],
]
```

### Derived Totals Expectation
```php
[
    'queries' => 95,
    'views' => 280,
    'actions' => 24,
    'photos_views' => 28,
]
```

## Test Execution

```bash
cd backend
php -l app/Services/GBP/Contracts/GBPInsightsServiceInterface.php
php -l app/Services/GBP/GBPInsightsService.php
php -l app/Jobs/SyncInsightsJob.php
php -l tests/Feature/Services/GBP/GBPInsightsServiceTest.php
php -l tests/Feature/Jobs/SyncInsightsJobTest.php
php artisan test tests/Feature/Services/GBP/GBPInsightsServiceTest.php tests/Feature/Jobs/SyncInsightsJobTest.php
```

## Developer Notes

1. **Keep the namespace split.** Implement the heavy lifting in `App\Services\GBP\GBPInsightsService`, then adapt the existing LocalSEO service to delegate into it for TASK-35-14 compatibility.
2. **Persist canonical metrics; expose derived aliases.** Store `searches/calls/directions/website_clicks`, but return `queries/actions` for consumers.
3. **Use upsert semantics** on `(location_id, insights_date)` to avoid duplicate rows.
4. **Cache only read models** (`getInsights`, totals/trends/top day) for 1 hour; sync operations should clear relevant cache keys.
5. **Store raw payloads** in `raw_response` so later schema refinements do not require a re-fetch.
6. **Job should stay thin.** Resolve the location, call the domain service, log start/fail/success.

## Comments

### PreTester Session (2026-03-10 16:22 GMT+02:00)
- Added the red-phase domain contract for GBP insights under `App\Services\GBP`, plus a compile-safe background job surface.
- Intentionally kept the existing `App\Services\LocalSEO\GBPInsightsService` stub untouched so TASK-35-14 controller work does not regress during this phase.
- The new suite defines the implementation target for storage, aggregation, cache behavior, and queue-driven sync while documenting the adapter/delegation path Developer should take.
