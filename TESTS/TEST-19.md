# TEST-19: Progressive Analysis & Job System

## Overview
Comprehensive test suite for the Progressive Analysis & Job System (SPEC-019). Tests cover database schema, models, services, API endpoints, SSE streaming, queue processing, and security.

---

## Test Files Index

| Task | Test File | Type | Tests | Status |
|------|-----------|------|-------|--------|
| TASK-19-1 | `CreateAnalysisJobsTablesTest.php` | Unit (DB) | 35+ | Pending |
| TASK-19-2 | `AnalysisJobModelTest.php` | Unit | 40+ | Pending |
| TASK-19-3 | `AnalysisJobServiceTest.php` | Unit | 50+ | Pending |
| TASK-19-4 | `AnalysisJobControllerTest.php` | Feature | 45+ | Pending |
| TASK-19-5 | `IndicatorProcessorTest.php` | Unit | 30+ | Pending |
| TASK-19-6 | `IndicatorProcessorsTest.php` | Unit | 60+ | Pending |
| TASK-19-7 | `QueueWorkerConcurrencyTest.php` | Unit | 35+ | Pending |
| TASK-19-8 | `ProgressServiceTest.php` | Unit | 30+ | Pending |
| TASK-19-9 | `SSEStreamEndpointTest.php` | Feature | 25+ | Pending |
| TASK-19-10 | `PollingEndpointTest.php` | Feature | 20+ | Pending |
| TASK-19-11 | `AnalysisProgress.test.tsx` | Frontend | 25+ | Pending |
| TASK-19-12 | `ResultsTable.test.tsx` | Frontend | 20+ | Pending |
| TASK-19-13 | `ErrorHandlingTest.php` | Unit | 35+ | Pending |
| TASK-19-14 | `AnalysisRateLimitTest.php` | Feature | 30+ | Pending |
| TASK-19-15 | `SSRFProtectionTest.php` | Security | 40+ | Pending |
| TASK-19-16 | `AnalysisIntegrationTest.php` | Integration | 25+ | Pending |

**Total: 545+ test scenarios**

---

## TASK-19-1: Database Migration - Analysis Jobs Tables

### Test File: `backend/tests/Unit/Migrations/CreateAnalysisJobsTablesTest.php`

### Test Scenarios

#### Schema Structure (SS)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SS-01 | `analysis_jobs` table exists | `Schema::hasTable('analysis_jobs')` returns true |
| SS-02 | `analysis_job_results` table exists | `Schema::hasTable('analysis_job_results')` returns true |
| SS-03 | `analysis_jobs.id` is BIGINT UNSIGNED AUTO_INCREMENT | Column type correct |
| SS-04 | `analysis_jobs.user_id` is BIGINT UNSIGNED NOT NULL | Column type correct |
| SS-05 | `analysis_jobs.site_id` is BIGINT UNSIGNED NOT NULL | Column type correct |
| SS-06 | `analysis_jobs.status` is ENUM with correct values | 'pending', 'running', 'completed', 'cancelled', 'failed' |
| SS-07 | `analysis_jobs.total_indicators` is INT UNSIGNED DEFAULT 0 | Column type correct |
| SS-08 | `analysis_jobs.completed_indicators` is INT UNSIGNED DEFAULT 0 | Column type correct |
| SS-09 | `analysis_jobs.failed_indicators` is INT UNSIGNED DEFAULT 0 | Column type correct |
| SS-10 | `analysis_jobs.progress_percent` is TINYINT UNSIGNED DEFAULT 0 | Column type correct |
| SS-11 | `analysis_jobs.started_at` is TIMESTAMP NULL | Column type correct |
| SS-12 | `analysis_jobs.completed_at` is TIMESTAMP NULL | Column type correct |
| SS-13 | `analysis_jobs.cancelled_at` is TIMESTAMP NULL | Column type correct |
| SS-14 | `analysis_jobs.error_message` is TEXT NULL | Column type correct |
| SS-15 | `analysis_jobs.created_at` has default CURRENT_TIMESTAMP | Column type correct |
| SS-16 | `analysis_jobs.updated_at` has ON UPDATE CURRENT_TIMESTAMP | Column type correct |

#### Results Table Schema (RS)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RS-01 | `analysis_job_results.id` is BIGINT UNSIGNED AUTO_INCREMENT | Column type correct |
| RS-02 | `analysis_job_results.job_id` is BIGINT UNSIGNED NOT NULL | Column type correct |
| RS-03 | `analysis_job_results.indicator_slug` is VARCHAR(100) NOT NULL | Column type correct |
| RS-04 | `analysis_job_results.indicator_name` is VARCHAR(255) NOT NULL | Column type correct |
| RS-05 | `analysis_job_results.status` is ENUM with correct values | 'pending', 'running', 'completed', 'failed', 'skipped' |
| RS-06 | `analysis_job_results.score` is DECIMAL(5,2) NULL | Column type correct |
| RS-07 | `analysis_job_results.max_score` is DECIMAL(5,2) NULL | Column type correct |
| RS-08 | `analysis_job_results.percentage` is TINYINT UNSIGNED NULL | Column type correct |
| RS-09 | `analysis_job_results.data` is JSON NULL | Column type correct |
| RS-10 | `analysis_job_results.recommendations` is JSON NULL | Column type correct |
| RS-11 | `analysis_job_results.error_message` is TEXT NULL | Column type correct |
| RS-12 | `analysis_job_results.processing_time_ms` is INT UNSIGNED NULL | Column type correct |

#### Foreign Keys (FK)
| ID | Scenario | Assertion |
|----|----------|-----------|
| FK-01 | `analysis_jobs.user_id` references `users.id` | Foreign key exists |
| FK-02 | `analysis_jobs.site_id` references `sites.id` | Foreign key exists |
| FK-03 | `analysis_job_results.job_id` references `analysis_jobs.id` | Foreign key exists |
| FK-04 | CASCADE DELETE on `analysis_jobs.user_id` | Delete user cascades |
| FK-05 | CASCADE DELETE on `analysis_jobs.site_id` | Delete site cascades |
| FK-06 | CASCADE DELETE on `analysis_job_results.job_id` | Delete job cascades results |

#### Indexes (IX)
| ID | Scenario | Assertion |
|----|----------|-----------|
| IX-01 | Index on `analysis_jobs.user_id` exists | Index found |
| IX-02 | Index on `analysis_jobs.site_id` exists | Index found |
| IX-03 | Index on `analysis_jobs.status` exists | Index found |
| IX-04 | Index on `analysis_jobs.created_at` exists | Index found |
| IX-05 | Index on `analysis_job_results.job_id` exists | Index found |
| IX-06 | Index on `analysis_job_results.status` exists | Index found |
| IX-07 | Unique constraint on `(job_id, indicator_slug)` exists | Unique constraint found |

#### Migration Rollback (MR)
| ID | Scenario | Assertion |
|----|----------|-----------|
| MR-01 | Rollback drops `analysis_job_results` table | Table not exists after rollback |
| MR-02 | Rollback drops `analysis_jobs` table | Table not exists after rollback |
| MR-03 | Re-run migration creates tables again | Tables exist after re-run |

#### Security (SC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SC-01 | SQL injection in status field fails gracefully | Invalid status rejected |
| SC-02 | SQL injection in indicator_slug fails gracefully | Injection blocked |
| SC-03 | Negative values for total_indicators rejected | Validation error |
| SC-04 | Negative values for progress_percent rejected | Validation error |
| SC-05 | progress_percent > 100 rejected | Validation error |

---

## TASK-19-2: AnalysisJob and AnalysisJobResult Models

### Test File: `backend/tests/Unit/Models/AnalysisJobTest.php`

### Test Scenarios

#### Model Creation (MC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| MC-01 | Create AnalysisJob with minimum required fields | Model created successfully |
| MC-02 | Create AnalysisJob with all fields | All fields persisted correctly |
| MC-03 | Create AnalysisJobResult with minimum required fields | Model created successfully |
| MC-04 | Create AnalysisJobResult with all fields | All fields persisted correctly |
| MC-05 | Default status is 'pending' | Status defaults correctly |
| MC-06 | Default progress_percent is 0 | Progress defaults correctly |
| MC-07 | Default indicator counts are 0 | Counts default correctly |

#### Relationships (RL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RL-01 | AnalysisJob belongsTo User | Relationship defined |
| RL-02 | AnalysisJob belongsTo Site | Relationship defined |
| RL-03 | AnalysisJob hasMany AnalysisJobResults | Relationship defined |
| RL-04 | AnalysisJobResult belongsTo AnalysisJob | Relationship defined |
| RL-05 | Eager loading results works | No N+1 queries |
| RL-06 | Cascade delete removes results | Results deleted with job |

#### Status Scopes (SS)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SS-01 | `pending()` scope returns only pending jobs | Only pending jobs returned |
| SS-02 | `running()` scope returns only running jobs | Only running jobs returned |
| SS-03 | `completed()` scope returns only completed jobs | Only completed jobs returned |
| SS-04 | `cancelled()` scope returns only cancelled jobs | Only cancelled jobs returned |
| SS-05 | `failed()` scope returns only failed jobs | Only failed jobs returned |
| SS-06 | Chained scopes work correctly | Combined filters work |

#### Accessors (AC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AC-01 | Progress accessor calculates percentage | Returns calculated value |
| AC-02 | Duration accessor calculates elapsed time | Returns correct duration |
| AC-03 | Duration returns null if not started | Null for pending job |
| AC-04 | Duration returns elapsed since start if not completed | Duration in progress |
| AC-05 | Duration returns final duration if completed | Final duration correct |

#### JSON Casts (JC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| JC-01 | `data` cast to array | JSON decoded correctly |
| JC-02 | `recommendations` cast to array | JSON decoded correctly |
| JC-03 | Empty JSON returns empty array | Not null |
| JC-04 | JSON stored correctly | Encoding works |

#### Result Status Scopes (RS)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RS-01 | Result `pending()` scope works | Only pending results |
| RS-02 | Result `running()` scope works | Only running results |
| RS-03 | Result `completed()` scope works | Only completed results |
| RS-04 | Result `failed()` scope works | Only failed results |
| RS-05 | Result `skipped()` scope works | Only skipped results |

#### Model Methods (MM)
| ID | Scenario | Assertion |
|----|----------|-----------|
| MM-01 | `markAsRunning()` sets status and started_at | Status changed, timestamp set |
| MM-02 | `markAsCompleted()` sets status and completed_at | Status changed, timestamp set |
| MM-03 | `markAsCancelled()` sets status and cancelled_at | Status changed, timestamp set |
| MM-04 | `markAsFailed()` sets status and error_message | Status changed, error set |
| MM-05 | `isComplete()` returns true when completed | Boolean correct |
| MM-06 | `isRunning()` returns true when running | Boolean correct |
| MM-07 | `canCancel()` returns true for pending/running | Boolean correct |
| MM-08 | `isExpired()` checks created_at threshold | Boolean correct |

---

## TASK-19-3: AnalysisJobService - Core Operations

### Test File: `backend/tests/Unit/Services/AnalysisJobServiceTest.php`

### Test Scenarios

#### Create Job (CJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CJ-01 | Create job with valid data | Job created with pending status |
| CJ-02 | Create job creates pending results for each indicator | N results created |
| CJ-03 | Create job validates user owns site | Returns null/false if not |
| CJ-04 | Create job sets total_indicators correctly | Count matches input |
| CJ-05 | Create job returns AnalysisJob instance | Correct type returned |
| CJ-06 | Create job with empty indicators array fails | Validation error |
| CJ-07 | Create job with invalid indicator slug logs warning | Invalid indicator skipped |

#### Get Job (GJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| GJ-01 | Get existing job returns model | Job found |
| GJ-02 | Get non-existent job returns null | Null returned |
| GJ-03 | Get job loads results relationship | Results eager loaded |
| GJ-04 | Get job with results includes all data | Complete data returned |

#### Get Job Progress (GP)
| ID | Scenario | Assertion |
|----|----------|-----------|
| GP-01 | Get progress returns correct structure | Keys exist |
| GP-02 | Get progress calculates percentage | Correct calculation |
| GP-03 | Get progress includes results array | Results included |
| GP-04 | Get progress for non-existent job returns null | Null returned |

#### Cancel Job (CA)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CA-01 | Cancel pending job succeeds | Status changed to cancelled |
| CA-02 | Cancel running job succeeds | Status changed, cancelled_at set |
| CA-03 | Cancel completed job fails | Returns false |
| CA-04 | Cancel already cancelled job fails | Returns false |
| CA-05 | Cancel failed job fails | Returns false |
| CA-06 | Cancel non-existent job returns false | False returned |

#### Start Job (SJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SJ-01 | Start pending job succeeds | Status running, started_at set |
| SJ-02 | Start already running job fails | Returns false |
| SJ-03 | Start completed job fails | Returns false |
| SJ-04 | Start cancelled job fails | Returns false |

#### Complete Job (CO)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CO-01 | Complete running job succeeds | Status completed, completed_at set |
| CO-02 | Complete pending job fails | Returns false |
| CO-03 | Complete already completed job fails | Returns false |
| CO-04 | Complete updates progress_percent to 100 | Full progress |

#### Fail Job (FJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| FJ-01 | Fail running job succeeds | Status failed, error set |
| FJ-02 | Fail pending job fails | Returns false |
| FJ-03 | Fail with error message stores message | Error persisted |
| FJ-04 | Fail with long error message truncates | Truncated correctly |

#### Authorization (AU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AU-01 | User can access their own job | Access granted |
| AU-02 | User cannot access another user's job | Access denied |
| AU-03 | Site ownership verified on create | Ownership checked |
| AU-04 | Authorization returns false for invalid user | False returned |

---

## TASK-19-4: Job CRUD API Endpoints

### Test File: `backend/tests/Feature/Api/V1/AnalysisJobControllerTest.php`

### Test Scenarios

#### Create Job Endpoint (CR)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CR-01 | POST /api/v1/sites/{siteId}/analysis/jobs returns 201 | Job created |
| CR-02 | Create with valid indicators returns job data | Response structure correct |
| CR-03 | Create with empty indicators returns 422 | Validation error |
| CR-04 | Create with missing indicators returns 422 | Validation error |
| CR-05 | Create for non-owned site returns 403 | Forbidden |
| CR-06 | Create unauthenticated returns 401 | Unauthorized |
| CR-07 | Create with invalid site ID returns 404 | Not found |
| CR-08 | Response includes id, status, totalIndicators | Fields present |

#### List Jobs Endpoint (LJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| LJ-01 | GET /api/v1/sites/{siteId}/analysis/jobs returns 200 | Jobs listed |
| LJ-02 | List returns paginated results | Pagination present |
| LJ-03 | List filters by status parameter | Only matching status |
| LJ-04 | List for non-owned site returns 403 | Forbidden |
| LJ-05 | List unauthenticated returns 401 | Unauthorized |
| LJ-06 | List with perPage parameter respects limit | Correct count |
| LJ-07 | List with page parameter paginates correctly | Correct page |

#### Get Job Endpoint (GJ)
| ID | Scenario | Assertion |
|----|----------|-----------|
| GJ-01 | GET /api/v1/analysis/jobs/{jobId} returns 200 | Job found |
| GJ-02 | Get includes results array | Results present |
| GJ-03 | Get includes progress data | Progress present |
| GJ-04 | Get non-owned job returns 403 | Forbidden |
| GJ-05 | Get non-existent job returns 404 | Not found |
| GJ-06 | Get unauthenticated returns 401 | Unauthorized |

#### Cancel Job Endpoint (CA)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CA-01 | DELETE /api/v1/analysis/jobs/{jobId} returns 200 | Job cancelled |
| CA-02 | Cancel pending job succeeds | Status cancelled |
| CA-03 | Cancel running job succeeds | Status cancelled |
| CA-04 | Cancel completed job returns 400 | Bad request |
| CA-05 | Cancel non-owned job returns 403 | Forbidden |
| CA-06 | Cancel non-existent job returns 404 | Not found |
| CA-07 | Cancel unauthenticated returns 401 | Unauthorized |
| CA-08 | Response includes cancelled_at timestamp | Timestamp present |

#### Response Format (RF)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RF-01 | Job response includes all required fields | Structure correct |
| RF-02 | Result response includes all required fields | Structure correct |
| RF-03 | Error response follows SPEC-006 format | Format matches |
| RF-04 | Pagination meta included | Meta present |

---

## TASK-19-5: IndicatorProcessor Base Class

### Test File: `backend/tests/Unit/Services/IndicatorProcessorTest.php`

### Test Scenarios

#### Abstract Methods (AM)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AM-01 | Concrete class must implement slug() | Abstract enforced |
| AM-02 | Concrete class must implement name() | Abstract enforced |
| AM-03 | Concrete class must implement maxScore() | Abstract enforced |
| AM-04 | Concrete class must implement process() | Abstract enforced |

#### Protected Helpers (PH)
| ID | Scenario | Assertion |
|----|----------|-----------|
| PH-01 | fetchUrl() returns HTML content | HTML returned |
| PH-02 | fetchUrl() throws on invalid URL | Exception thrown |
| PH-03 | fetchUrl() respects timeout | Timeout enforced |
| PH-04 | fetchUrl() respects max response size | Size limit enforced |
| PH-05 | parseHtml() returns Crawler instance | Crawler returned |
| PH-06 | parseHtml() handles malformed HTML | Graceful handling |

#### IndicatorResult DTO (IR)
| ID | Scenario | Assertion |
|----|----------|-----------|
| IR-01 | Result contains score, maxScore, percentage | Fields present |
| IR-02 | Result contains data array | Data array present |
| IR-03 | Result contains recommendations array | Recommendations present |
| IR-04 | Result contains processingTimeMs | Time tracked |
| IR-05 | Result contains optional error | Error field present |
| IR-06 | Percentage calculated from score/maxScore | Correct calculation |

#### Configuration (CF)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CF-01 | Default timeout is 30s | Default correct |
| CF-02 | Default max response size is 10MB | Default correct |
| CF-03 | Configurable timeout works | Custom timeout used |
| CF-04 | Configurable max size works | Custom size used |

#### SSRF Integration (SI)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SI-01 | Localhost URLs blocked | Blocked correctly |
| SI-02 | Private IP ranges blocked | Blocked correctly |
| SI-03 | Cloud metadata IP blocked | 169.254.169.254 blocked |
| SI-04 | Valid public URLs allowed | Allowed correctly |

---

## TASK-19-6: Core Indicator Implementations

### Test File: `backend/tests/Unit/Services/IndicatorProcessorsTest.php`

### MetaTagsProcessor Tests (MT)

| ID | Scenario | Assertion |
|----|----------|-----------|
| MT-01 | Process page with valid title | Score positive |
| MT-02 | Process page missing title | Score reduced |
| MT-03 | Process page with short title (<50 chars) | Warning in recommendations |
| MT-04 | Process page with long title (>60 chars) | Warning in recommendations |
| MT-05 | Process page with optimal title length | Full score for title |
| MT-06 | Process page with valid meta description | Score positive |
| MT-07 | Process page missing meta description | Score reduced |
| MT-08 | Process page with short description | Warning in recommendations |
| MT-09 | Process page with long description | Warning in recommendations |
| MT-10 | Process page with Open Graph tags | Score bonus |
| MT-11 | Process page missing og:title | Recommendation added |
| MT-12 | Process page missing og:description | Recommendation added |
| MT-13 | Process page missing og:image | Recommendation added |
| MT-14 | Data includes title text | Title captured |
| MT-15 | Data includes description text | Description captured |

### HeadingsProcessor Tests (HD)

| ID | Scenario | Assertion |
|----|----------|-----------|
| HD-01 | Process page with single H1 | Score positive |
| HD-02 | Process page with multiple H1s | Score reduced |
| HD-03 | Process page with no H1 | Score reduced |
| HD-04 | Process page with valid hierarchy (H1→H2→H3) | Full score |
| HD-05 | Process page with skipped levels (H1→H3) | Warning added |
| HD-06 | Process page with H2 before H1 | Warning added |
| HD-07 | Data includes heading outline | Outline captured |
| HD-08 | Data includes H1 count | Count correct |
| HD-09 | Data includes heading counts by level | Counts correct |
| HD-10 | Recommendations include hierarchy issues | Issues listed |

### ImagesProcessor Tests (IM)

| ID | Scenario | Assertion |
|----|----------|-----------|
| IM-01 | Process page with all images having alt text | Full score |
| IM-02 | Process page with missing alt text | Score reduced |
| IM-03 | Process page with large images (>500KB) | Warning added |
| IM-04 | Process page with lazy loading on large images | No warning |
| IM-05 | Process page with no images | Neutral score |
| IM-06 | Data includes total image count | Count correct |
| IM-07 | Data includes images missing alt | List provided |
| IM-08 | Data includes large image URLs | URLs listed |
| IM-09 | Recommendations for missing alt text | Recommendations added |
| IM-10 | Recommendations for large images | Recommendations added |

### LinksProcessor Tests (LK)

| ID | Scenario | Assertion |
|----|----------|-----------|
| LK-01 | Process page with working internal links | Score positive |
| LK-02 | Process page with working external links | Score positive |
| LK-03 | Process page with broken internal links | Score reduced |
| LK-04 | Process page with broken external links | Score reduced |
| LK-05 | Process page with nofollow external links | Tracked correctly |
| LK-06 | Process page with sponsored links | Tracked correctly |
| LK-07 | Data includes internal link count | Count correct |
| LK-08 | Data includes external link count | Count correct |
| LK-09 | Data includes broken link URLs | URLs listed |
| LK-10 | Data includes nofollow links | Links tracked |
| LK-11 | Recommendations for broken links | Recommendations added |
| LK-12 | Recommendations for nofollow on internal | Warning added |

---

## TASK-19-7: Queue Worker with Concurrency Throttling

### Test File: `backend/tests/Unit/Services/QueueWorkerConcurrencyTest.php`

### Test Scenarios

#### Queue Configuration (QC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| QC-01 | Queue uses database driver | Driver correct |
| QC-02 | Queue retry_after is 90 seconds | Config correct |
| QC-03 | Queue after_commit is true | Config correct |
| QC-04 | Queue named 'analysis' | Name correct |

#### Concurrency Limits (CL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CL-01 | Max 5 concurrent indicators processed | Limit enforced |
| CL-02 | Per-indicator timeout is 30 seconds | Timeout correct |
| CL-03 | Worker memory limit is 256MB | Limit correct |
| CL-04 | Retry attempts is 1 | Retry count correct |

#### Job Dispatching (JD)
| ID | Scenario | Assertion |
|----|----------|-----------|
| JD-01 | Jobs dispatched to analysis queue | Queue correct |
| JD-02 | Jobs processed in priority order | Priority respected |
| JD-03 | Batch processing respects concurrency | Batch size correct |
| JD-04 | Failed job moves to next indicator | Continues processing |

#### Graceful Shutdown (GS)
| ID | Scenario | Assertion |
|----|----------|-----------|
| GS-01 | SIGTERM handled gracefully | Shutdown clean |
| GS-02 | Current indicator completes before shutdown | Completion logged |
| GS-03 | Pending indicators requeued | Jobs preserved |

#### Retry Logic (RL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RL-01 | Failed job retries once | Retry occurs |
| RL-02 | Exponential backoff on retry | Delay correct |
| RL-03 | After max attempts, job fails | Final failure |
| RL-04 | Retry does not block other jobs | Parallel continues |

#### Progress Updates (PU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| PU-01 | Progress updated after each indicator | Job updated |
| PU-02 | Progress percent calculated correctly | Calculation correct |
| PU-03 | Progress reaches 100 on completion | Full progress |

---

## TASK-19-8: Progress Tracking and Updates

### Test File: `backend/tests/Unit/Services/ProgressServiceTest.php`

### Test Scenarios

#### Update Progress (UP)
| ID | Scenario | Assertion |
|----|----------|-----------|
| UP-01 | updateProgress() increments completed count | Count incremented |
| UP-02 | updateProgress() recalculates percentage | Percentage correct |
| UP-03 | updateProgress() is atomic | Transaction used |

#### Mark Complete (MC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| MC-01 | markComplete() sets result status to completed | Status updated |
| MC-02 | markComplete() stores score and data | Data persisted |
| MC-03 | markComplete() sets completed_at | Timestamp set |
| MC-04 | markComplete() calculates processing time | Time tracked |

#### Mark Failed (MF)
| ID | Scenario | Assertion |
|----|----------|-----------|
| MF-01 | markFailed() sets result status to failed | Status updated |
| MF-02 | markFailed() stores error message | Error persisted |
| MF-03 | markFailed() increments failed_indicators on job | Count incremented |
| MF-04 | markFailed() continues processing other indicators | Processing continues |

#### Events (EV)
| ID | Scenario | Assertion |
|----|----------|-----------|
| EV-01 | AnalysisProgressUpdated event dispatched | Event fired |
| EV-02 | Event contains jobId, completedIndicators, progressPercent | Data present |
| EV-03 | AnalysisIndicatorComplete event dispatched | Event fired |
| EV-04 | Event contains indicatorSlug, score, percentage | Data present |
| EV-05 | Events are broadcastable | Broadcast enabled |

---

## TASK-19-9: SSE Stream Endpoint

### Test File: `backend/tests/Feature/Api/V1/SSEStreamEndpointTest.php`

### Test Scenarios

#### Stream Connection (SC)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SC-01 | GET /api/v1/analysis/jobs/{jobId}/stream returns 200 | Stream started |
| SC-02 | Content-Type is text/event-stream | Header correct |
| SC-03 | Cache-Control is no-cache | Header correct |
| SC-04 | X-Accel-Buffering is no | Header correct |

#### Event Types (ET)
| ID | Scenario | Assertion |
|----|----------|-----------|
| ET-01 | 'progress' event sent with correct data | Event format correct |
| ET-02 | 'result' event sent when indicator completes | Event format correct |
| ET-03 | 'complete' event sent when job finishes | Event format correct |
| ET-04 | 'error' event sent on failure | Event format correct |

#### Connection Management (CM)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CM-01 | Heartbeat sent every 15 seconds | Keep-alive works |
| CM-02 | Connection closes on job completion | Stream ends |
| CM-03 | Client disconnect detected | Server handles disconnect |

#### Authorization (AU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AU-01 | Stream requires authentication | 401 without auth |
| AU-02 | User must own job | 403 for other user |
| AU-03 | Non-existent job returns 404 | Not found |

---

## TASK-19-10: Polling Fallback Endpoint

### Test File: `backend/tests/Feature/Api/V1/PollingEndpointTest.php`

### Test Scenarios

#### Poll Endpoint (PE)
| ID | Scenario | Assertion |
|----|----------|-----------|
| PE-01 | GET /api/v1/analysis/jobs/{jobId}/progress returns 200 | Success |
| PE-02 | Response includes status, totalIndicators, completedIndicators | Fields present |
| PE-03 | Response includes progressPercent | Field present |
| PE-04 | Response includes results array | Results included |
| PE-05 | Cache-Control: no-store header present | Fresh data |

#### Authorization (AU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AU-01 | Requires authentication | 401 without auth |
| AU-02 | User must own job | 403 for other user |
| AU-03 | Non-existent job returns 404 | Not found |

#### Rate Limiting (RL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RL-01 | 30 requests per minute per job allowed | Within limit OK |
| RL-02 | Exceeding limit returns 429 | Rate limited |
| RL-03 | Retry-After header present on 429 | Header present |

---

## TASK-19-11: Frontend Progress Bar Component

### Test File: `frontend/components/analysis/__tests__/AnalysisProgress.test.tsx`

### Test Scenarios

#### Rendering (RN)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RN-01 | Component renders with jobId | Component visible |
| RN-02 | Progress bar shows percentage | Percentage displayed |
| RN-03 | Status indicator shows current status | Status visible |
| RN-04 | Cancel button renders when running | Button visible |
| RN-05 | Cancel button disabled when not running | Button disabled |
| RN-06 | Elapsed time displays | Time shown |

#### SSE Connection (SE)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SE-01 | Connects to stream endpoint on mount | Connection made |
| SE-02 | Updates progress on progress event | State updated |
| SE-03 | Updates results on result event | State updated |
| SE-04 | Calls onComplete on complete event | Callback called |
| SE-05 | Reconnects on disconnect with backoff | Reconnection works |
| SE-06 | Falls back to polling after 3 failures | Fallback triggered |

#### Interactions (IN)
| ID | Scenario | Assertion |
|----|----------|-----------|
| IN-01 | Cancel button triggers onCancel | Callback called |
| IN-02 | Cancel button shows confirmation | Dialog shown |
| IN-03 | Component unmounts cleanly | Connection closed |

---

## TASK-19-12: Frontend Live Results Table

### Test File: `frontend/components/analysis/__tests__/ResultsTable.test.tsx`

### Test Scenarios

#### Rendering (RN)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RN-01 | Table renders with results | Table visible |
| RN-02 | Each result shows status indicator | Status shown |
| RN-03 | Each result shows indicator name | Name shown |
| RN-04 | Each result shows score/percentage | Score shown |
| RN-05 | Expandable details section exists | Details expandable |

#### Real-time Updates (RU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RU-01 | New results appear as they complete | Row added |
| RU-02 | Existing rows update in place | Row updated |
| RU-03 | Smooth animations on state change | Animation plays |

#### Sorting (SO)
| ID | Scenario | Assertion |
|----|----------|-----------|
| SO-01 | Sort by status (completed first) works | Order correct |
| SO-02 | Sort by score works | Order correct |
| SO-03 | Sort by name works | Order correct |

---

## TASK-19-13: Error Handling and Recovery

### Test File: `backend/tests/Unit/Services/AnalysisErrorHandlingTest.php`

### Test Scenarios

#### Network Timeout (NT)
| ID | Scenario | Assertion |
|----|----------|-----------|
| NT-01 | Timeout marks indicator as failed | Status failed |
| NT-02 | Timeout logs URL and duration | Error logged |
| NT-03 | Processing continues with other indicators | Continues |

#### Invalid URL (IU)
| ID | Scenario | Assertion |
|----|----------|-----------|
| IU-01 | Invalid URL format rejected early | Validation error |
| IU-02 | Invalid URL marks indicator as failed | Status failed |
| IU-03 | Clear error message provided | Message helpful |
| IU-04 | Processing continues | Continues |

#### Rate Limiting Detection (RL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RL-01 | 429 response detected | Detected correctly |
| RL-02 | Exponential backoff applied | Backoff correct |
| RL-03 | Single retry after backoff | Retry occurs |
| RL-04 | Final failure after retry | Final status set |

#### Memory Exhaustion (ME)
| ID | Scenario | Assertion |
|----|----------|-----------|
| ME-01 | Memory usage monitored | Monitoring active |
| ME-02 | Worker killed approaching limit | Kill triggered |
| ME-03 | Indicator marked as failed | Status failed |
| ME-04 | Job continues processing | Continues |

#### Error Logging (EL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| EL-01 | Errors logged with context | Context included |
| EL-02 | Stack traces included in debug mode | Trace present |
| EL-03 | Sensitive data redacted in logs | Data safe |

---

## TASK-19-14: Rate Limiting Implementation

### Test File: `backend/tests/Feature/Api/V1/AnalysisRateLimitTest.php`

### Test Scenarios

#### Job Creation Limits (JL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| JL-01 | 10 jobs per user per hour allowed | Within limit OK |
| JL-02 | Exceeding user limit returns 429 | Rate limited |
| JL-03 | 5 jobs per site per hour allowed | Within limit OK |
| JL-04 | Exceeding site limit returns 429 | Rate limited |
| JL-05 | 2 concurrent jobs per user allowed | Within limit OK |
| JL-06 | Exceeding concurrent limit returns 429 | Rate limited |

#### API Rate Limits (AL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| AL-01 | 60 requests per minute per user allowed | Within limit OK |
| AL-02 | Exceeding API limit returns 429 | Rate limited |
| AL-03 | Polling endpoint: 30 per minute per job | Within limit OK |

#### Headers (HD)
| ID | Scenario | Assertion |
|----|----------|-----------|
| HD-01 | X-RateLimit-Limit header present | Header present |
| HD-02 | X-RateLimit-Remaining header present | Header present |
| HD-03 | X-RateLimit-Reset header present | Header present |

#### 429 Response (FR)
| ID | Scenario | Assertion |
|----|----------|-----------|
| FR-01 | 429 status code returned | Status correct |
| FR-02 | JSON body with error message | Body correct |
| FR-03 | retry_after field present | Field present |
| FR-04 | Follows SPEC-006 format | Format matches |

---

## TASK-19-15: SSRF Protection Integration

### Test File: `backend/tests/Security/AnalysisSSRFProtectionTest.php`

### Test Scenarios

#### Localhost Blocking (LB)
| ID | Scenario | Assertion |
|----|----------|-----------|
| LB-01 | 127.0.0.1 blocked | Blocked |
| LB-02 | ::1 blocked | Blocked |
| LB-03 | localhost blocked | Blocked |
| LB-04 | 0.0.0.0 blocked | Blocked |
| LB-05 | Localhost with port blocked | Blocked |

#### Private IP Blocking (PB)
| ID | Scenario | Assertion |
|----|----------|-----------|
| PB-01 | 10.0.0.1 blocked | Blocked |
| PB-02 | 172.16.0.1 blocked | Blocked |
| PB-03 | 172.31.255.255 blocked | Blocked |
| PB-04 | 192.168.0.1 blocked | Blocked |
| PB-05 | 169.254.0.1 blocked | Link-local blocked |

#### Cloud Metadata Blocking (CB)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CB-01 | 169.254.169.254 blocked | Metadata blocked |
| CB-02 | metadata.google.internal blocked | DNS blocked |
| CB-03 | AWS metadata endpoint blocked | Blocked |

#### DNS Rebinding Protection (DB)
| ID | Scenario | Assertion |
|----|----------|-----------|
| DB-01 | DNS resolved before connection | Resolution occurs |
| DB-02 | Resolved IP checked against blocklist | Check occurs |
| DB-03 | Re-check after redirects | Follow-up check |
| DB-04 | Redirect to blocked IP fails | Blocked |

#### Request Limits (RL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| RL-01 | Max response size 10MB enforced | Limit enforced |
| RL-02 | Timeout 30 seconds enforced | Limit enforced |
| RL-03 | Max redirects 5 enforced | Limit enforced |

#### Blocked Patterns (BP)
| ID | Scenario | Assertion |
|----|----------|-----------|
| BP-01 | Internal services blocked via config | Pattern blocked |
| BP-02 | file:// protocol blocked | Protocol blocked |
| BP-03 | gopher:// protocol blocked | Protocol blocked |
| BP-04 | dict:// protocol blocked | Protocol blocked |

---

## TASK-19-16: Unit and Integration Tests

### Test File: `backend/tests/Integration/AnalysisIntegrationTest.php`

### Test Scenarios

#### Full Job Lifecycle (FL)
| ID | Scenario | Assertion |
|----|----------|-----------|
| FL-01 | Create → Start → Process → Complete flow works | All transitions OK |
| FL-02 | All indicators processed | All complete |
| FL-03 | Progress reaches 100% | Full progress |
| FL-04 | Final results stored correctly | Data persisted |

#### Concurrent Processing (CP)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CP-01 | Multiple indicators process concurrently | Parallel execution |
| CP-02 | Concurrency limit respected | Max 5 at once |
| CP-03 | Progress updates accurate under load | Updates correct |

#### Error Recovery (ER)
| ID | Scenario | Assertion |
|----|----------|-----------|
| ER-01 | Failed indicator doesn't stop others | Processing continues |
| ER-02 | Partial results stored on failure | Results saved |
| ER-03 | Job marked as failed if all indicators fail | Status correct |
| ER-04 | Job marked as completed with partial success | Status correct |

#### Cancel Mid-Processing (CM)
| ID | Scenario | Assertion |
|----|----------|-----------|
| CM-01 | Cancel stops processing | Processing stops |
| CM-02 | Completed indicators preserved | Results kept |
| CM-03 | Job status set to cancelled | Status correct |

---

## Summary

| Category | Tests |
|----------|-------|
| Database Schema | 35+ |
| Models | 40+ |
| Services | 120+ |
| API Endpoints | 90+ |
| Frontend | 45+ |
| Security | 40+ |
| Integration | 25+ |
| **Total** | **545+** |

---

*Created by PreTester for SPEC-019*
